<?php
namespace php_active_record;

define("SERVICE_URL", "http://www.aquamaps.org/webservice/getAMap.php?");
define("FISHBASE_URL", "http://www.fishbase.us/summary/speciessummary.php?id=");
define("SEALIFEBASE_URL", "http://www.sealifebase.org/summary/speciessummary.php?id=");
define("MAP_RESIZER_URL", "http://www.aquamaps.org/imagethumb/workimagethumb.php?s=");
define("CACHED_MAPS_URL", "http://www.aquamaps.org/imagethumb/cached_maps");

class AquamapsAPI
{
    public static function get_all_taxa()
    {
        $all_taxa = array();
        $used_collection_ids = array();        
        $path= DOC_ROOT . "/update_resources/connectors/files/AquaMaps/";
        $urls = array( 0  => array( "path" => $path . "aquamaps_species_list.XML"  , "active" => 1),  // all 8k species
                       1  => array( "path" => $path . "aquamaps_species_list2.XML" , "active" => 0)   // test just 3 species                       
                     );                
        foreach($urls as $url)
        {
            if($url["active"])
            {
                $arr = self::get_aquamaps_taxa($url["path"],$used_collection_ids);    
                $page_taxa              = $arr[0];
                $used_collection_ids    = $arr[1];            
                print"page_taxa count: " . count($page_taxa) . "\n\n";                
                if($page_taxa)
                {                    
                    $all_taxa = array_merge($all_taxa,$page_taxa);                                    
                }
            }
        }
        return $all_taxa;
    }
    
    public static function get_aquamaps_taxa($url,$used_collection_ids)
    {
        $response = self::search_collections($url);//this will output the raw (but structured) output from the external service
        $page_taxa = array();        
        foreach($response as $rec)
        {            
            if(@$used_collection_ids[$rec["sciname"]]) continue;            
            $taxon = self::get_taxa_for_photo($rec);
            if($taxon) $page_taxa[] = $taxon;            
            $used_collection_ids[$rec["sciname"]] = true;
        }        
        return array($page_taxa,$used_collection_ids);        
    }    
    
    function search_collections($url)//this will output the raw (but structured) output from the external service
    {
        $response = self::parse_xml($url);
        return $response;//structured array
    }        
    
    function parse_xml($url)
    {
        $arr_scraped=array();        
        $xml = Functions::get_hashed_response($url);                
        $ctr=0; $total=sizeof($xml->RECORD);
        foreach($xml->RECORD as $rec)
        {
            $ctr++; 
            //print"$ctr of $total \n";
            if(substr($rec->SPECIESID,0,3)=="Fis")$source_dbase_link = "<a target='$rec->SpecCode' href='" . FISHBASE_URL . $rec->SpecCode . "'>FishBase</a>";
            else                                  $source_dbase_link = "<a target='$rec->SpecCode' href='" . SEALIFEBASE_URL . $rec->SpecCode . "'>SeaLifeBase</a>";
            
            $agent=array();

            //start distribution            
            $genus = $rec->Genus;
            $species = $rec->Species;            
            $arr_result = self::get_aquamaps($genus, $species, $source_dbase_link);
            $distribution = $arr_result[0];
            $sourceURL    = $arr_result[1];
            $arr_photos   = $arr_result[2];
            //end distribution
            
            $arr_scraped[]=array("id"=>$ctr,
                                 "sciname"=>$rec->Genus . ' ' . $rec->Species,
                                 "genus"=>$rec->Genus,
                                 "family"=>$rec->Family,
                                 "order"=>$rec->Order,
                                 "class"=>$rec->Class,
                                 "phylum"=>$rec->Phylum,
                                 "kingdom"=>$rec->Kingdom,
                                 "photos"=>$arr_photos,
                                 "dc_source"=>$sourceURL,
                                 "distribution"=>array("description"=>$distribution,
                                                       "subject"    =>"http://rs.tdwg.org/ontology/voc/SPMInfoItems#Distribution",
                                                       "title"      =>"AquaMaps for <i>$rec->Genus $rec->Species</i>",
                                                       "dataType"   =>"http://purl.org/dc/dcmitype/Text",
                                                       "dc_source"  =>$sourceURL)
                                );               
        }
        return $arr_scraped;        
    }

    function get_aquamaps($genus,$species,$source_dbase_link)
    {	
        $param = "genus=" . $genus . "&species=" . $species;
        $param2 = "genusname=" . $genus . "&speciesname=" . $species;
        //==============================================================================
        $fn = SERVICE_URL . $param;        
        $xml = Functions::get_hashed_response($fn);        
        $html = $xml->section_body;        
        if(is_numeric(stripos($html,"has not yet been reviewed")))$review="un-reviewed";
        else                                                      $review="reviewed";        
        if(preg_match("/href=\'http:\/\/(.*?)\'>/ims", $html, $matches)){$sourceURL = "http://" . trim($matches[1]);}
        else                                                             $sourceURL = "";        
        $attribution = "$source_dbase_link <a target='aquamaps' href='http://www.aquamaps.org'>AquaMaps</a> ";        
        if(preg_match("/Data sources:(.*?)<\/font><\/td>/ims", $html, $matches)){$attribution .= trim($matches[1]) . "";}
        $attribution = str_ireplace(array("\n", "\r", "\t", "\o", "\xOB","\xA0"), '', $attribution);			
        $attribution = "Data sources: " . $attribution;                        
        /* http://www.aquamaps.org/imagethumb/file_destination/exp_8_pic_ITS-180469.jpg */
        $arr_photos=array();        
        $str="<table border='0' cellspacing='0' cellpadding='5'><tr><td><b>Computer Generated Maps of <i>$genus $species</i> ($review)</b></td></tr>
        <tr><td>
            <table>
                <tr valign='top'>                    
                    <td><img src='http://www.aquamaps.org/pic/probability1.gif'></td>                    
                    <td>&nbsp;</td>
                    <td valign='top'>$attribution</td>
                </tr>
            </table>            
        </td></tr>";                
        //============================================================================================        
        $native_range="";
        if(preg_match("/=\&quot\;\s*(.*?)\&quot\;\'\>\s*Native range\s*/ims", $html, $matches))
        {   $native_range = trim($matches[1]) . "";
            $src = MAP_RESIZER_URL . $native_range . "&w=430";
            $str.="
            <tr><td>&nbsp;</td></tr>
            <tr><td>Native range</td></tr>                
            <tr><td><a target='am $genus $species' href='$sourceURL'><img alt='native_range' src='$src'></a></td></tr>";
        }
        //============================================================================================        
        $suitable="";
        if(preg_match("/\/suitable\/(.*?)&quot;/ims", $html, $matches))
        {   $suitable = trim($matches[1]) . "";
            $suitable = CACHED_MAPS_URL . "/suitable/" . $suitable;
            $src = MAP_RESIZER_URL . $suitable . "&w=430";
            $str.="
            <tr><td>&nbsp;</td></tr>
            <tr><td>All suitable habitat</td></tr>                
            <tr><td><a target='am $genus $species' href='$sourceURL'><img alt='suitable' src='$src'></a></td></tr>";
        }
        //============================================================================================        
        $pointmap="";
        if(preg_match("/\/pointmap\/(.*?)&quot;/ims", $html, $matches))
        {   $pointmap = trim($matches[1]) . "";            
            $pointmap = CACHED_MAPS_URL . "/pointmap/" . $pointmap;
            $src = MAP_RESIZER_URL . $pointmap . "&w=430";
            $str.="
            <tr><td>&nbsp;</td></tr>
            <tr><td>PointMap</td></tr>                
            <tr><td><a target='am $genus $species' href='$sourceURL'><img alt='pointmap' src='$src'></a></td></tr>";
        }
        //============================================================================================        
        $m2050="";
        if(preg_match("/\/2050\/(.*?)&quot;/ims", $html, $matches))
        {   $m2050 = trim($matches[1]) . "";
            $m2050 = CACHED_MAPS_URL . "/2050/" . $m2050;            
            $src = MAP_RESIZER_URL . $m2050 . "&w=430";
            $str.="
            <tr><td>&nbsp;</td></tr>
            <tr><td>Year 2050 range</td></tr>                
            <tr><td><a target='am $genus $species' href='$sourceURL'><img alt='2050' src='$src'></a></td></tr>";
        }
        //============================================================================================                        
        $str.="</table>";
        $str = str_ireplace(array("\n", "\r", "\t", "\o", "\xOB", "\xA0"), '', $str);			
        return array(trim($str),$sourceURL,$arr_photos);        
    }

    function build_image_array($img,$genus,$species,$type,$sourceURL)
    {
        return array(  "mediaURL"=>$img,
                       "mimeType"=>"image/jpeg",
                       "dataType"=>"http://purl.org/dc/dcmitype/StillImage",
                       "description"=>"<i>$genus $species</i>: $type",
                       "dc_source"=>$sourceURL,
                       "agent"=>array());                        
    }    
    
    function get_taxa_for_photo($rec)
    {
        $taxon = array();
        $taxon["commonNames"] = array();
        $license = null;        
        $taxon["source"] = $rec["dc_source"];
        if(preg_match("/&SpecID=(.*?)(&?$|&)/ims", $rec["dc_source"], $matches)){$species_id = trim($matches[1]);}//ends with & or end of string
        else $species_id="";
        $taxon["identifier"] = $species_id;
        $taxon["scientificName"] = ucfirst(trim($rec["sciname"]));
        $taxon["family"] = ucfirst(trim(@$rec["family"]));
        $taxon["order"] = ucfirst(trim(@$rec["order"]));
        $taxon["class"] = ucfirst(trim(@$rec["class"]));        
        $taxon["phylum"] = ucfirst(trim(@$rec["phylum"]));
        $taxon["kingdom"] = ucfirst(trim(@$rec["kingdom"]));                
        if(@!$taxon["genus"] && @preg_match("/^([^ ]+) /", $taxon["scientificName"], $arr)) $taxon["genus"] = $arr[1];        
        $arr = $rec["distribution"];
        if($arr["description"])
        {
            $data_object = self::get_data_object($arr);
            if(!$data_object) return false;
            $taxon["dataObjects"][] = new \SchemaDataObject($data_object);                     
        }                        
        $photos = $rec["photos"];
        if($photos)
        {
            foreach($photos as $photos)
            {
                $data_object = self::get_data_object($photos);
                if(!$data_object) return false;
                $taxon["dataObjects"][] = new \SchemaDataObject($data_object);                     
            }
        }        
        $taxon_object = new \SchemaTaxon($taxon);
        return $taxon_object;
    }
    
    function get_data_object($rec)
    {
        $data_object_parameters = array();        
        $data_object_parameters["source"] = $rec["dc_source"];        
        $data_object_parameters["dataType"] = $rec["dataType"];
        $data_object_parameters["mimeType"] = @$rec["mimeType"];
        $data_object_parameters["mediaURL"] = @$rec["mediaURL"];        
        $data_object_parameters["title"] = @$rec["title"];
        $data_object_parameters["description"] = utf8_encode($rec["description"]);
        $data_object_parameters["source"] = @$rec["sourceURL"];
        $data_object_parameters["license"] = 'http://creativecommons.org/licenses/by-nc-sa/3.0/';        
        if(@$rec["subject"])
        {
            $data_object_parameters["subjects"] = array();
            $subjectParameters = array();
            $subjectParameters["label"] = @$rec["subject"];
            $data_object_parameters["subjects"][] = new \SchemaSubject($subjectParameters);
        }
         if(@$rec["agent"])
         {               
             $agents = array();
             foreach($rec["agent"] as $a)
             {  
                 $agentParameters = array();
                 $agentParameters["role"]     = $a["role"];
                 $agentParameters["homepage"] = $a["homepage"];
                 $agentParameters["logoURL"]  = "";        
                 $agentParameters["fullName"] = $a["name"];
                 $agents[] = new \SchemaAgent($agentParameters);
             }
             $data_object_parameters["agents"] = $agents;
         }
        return $data_object_parameters;
    }    
}
?>