<?php
/* connector for AquaMaps
estimated execution time: 

This connector reads an XML (list of species with AquaMaps) then loops on each species and run the service to get the
interactive distribution maps.
*/

define("SPECIES_URL", "http://www.aquamaps.org/premap2.php?cache=1&SpecID=");

define("SERVICE_URL", "http://www.aquamaps.org/webservice/getAMap.php?");
//define("SERVICE_URL2", "http://www.aquamaps.org/webservice/aquamap.xml.php?");

define("FISHBASE_URL", "http://www.fishbase.org/summary/speciessummary.php?id=");
define("SEALIFEBASE_URL", "http://www.sealifebase.org/summary/speciessummary.php?id=");

include_once(dirname(__FILE__) . "/../../config/environment.php");
$GLOBALS['ENV_DEBUG'] = false;

$taxa = AquamapsAPI::get_all_eol_photos();
$xml = SchemaDocument::get_taxon_xml($taxa);

$resource_path = CONTENT_RESOURCE_LOCAL_PATH . "123.xml";

$OUT = fopen($resource_path, "w+");
fwrite($OUT, $xml);
fclose($OUT);

echo "time: ". Functions::time_elapsed()."\n";
?>


<?php

class AquamapsAPI
{
    public static function get_all_eol_photos()
    {
        $all_taxa = array();
        $used_collection_ids = array();
        
        $path=dirname(__FILE__) . "/files/Aquamaps/";
        $urls = array( 0  => array( "path" => $path . "aquamaps_species_list2.XML" , "active" => 1),  //
                       1  => array( "path" => $path . ""                          , "active" => 0)   //                       
                     );
        foreach($urls as $url)
        {
            if($url["active"])
            {
                $page_taxa = self::get_larvae_taxa($url["path"]);                                
                print"<hr>website: " . $url["path"] . "<br>";
                print"page_taxa count: " . $url["path"] . " -- " . count($page_taxa) . "<hr>";
                
                //print"<pre>page_taxa: ";print_r($page_taxa);print"</pre>";                        

                if($page_taxa)
                {                    
                    $all_taxa = array_merge($all_taxa,$page_taxa);                                    
                    //or use this => foreach($page_taxa as $t) $all_taxa[] = $t;
                }
            }
        }
        //print"<hr><pre>all_taxa: ";print_r($all_taxa);print"</pre>"; //debug see all records
        print"total: " . count($all_taxa);        
        return $all_taxa;
    }
    
    public static function get_larvae_taxa($url)
    {
        global $used_collection_ids;
        
        $response = self::search_collections($url);//this will output the raw (but structured) output from the external service
        $page_taxa = array();
        foreach($response as $rec)
        {
            if(@$used_collection_ids[$rec["sciname"]]) continue;
            
            $taxon = self::get_taxa_for_photo($rec);
            if($taxon) $page_taxa[] = $taxon;
            
            $used_collection_ids[$rec["sciname"]] = true;
        }        
        return $page_taxa;
    }    
    
    public static function search_collections($url)//this will output the raw (but structured) output from the external service
    {
        /*
        $html = Functions::get_remote_file_fake_browser($url);
        $arr_url_list = self::get_url_list($html);        
        $response = self::scrape_species_page($url);
        */
        
        $response = self::parse_xml($url);
        return $response;//structured array
    }        
    
    public static function parse_xml($url)
    {
        $arr_scraped=array();
        
        $xml = simplexml_load_file($url);        
        
        $ctr=0;
        foreach($xml->RECORD as $rec)
        {
            //if($ctr >= 5)break;//debug
            
            echo "\n <hr> $ctr";
            
            //$sourceURL=$rec["url"]; //not used
            
            //print $rec->SpecCode . $rec->SPECIESID; exit;
            
            if(substr($rec->SPECIESID,0,3)=="Fis")$source_dbase_link = "<a target='$rec->SpecCode' href='" . FISHBASE_URL . $rec->SpecCode . "'>FishBase</a>";
            else                                  $source_dbase_link = "<a target='$rec->SpecCode' href='" . SEALIFEBASE_URL . $rec->SpecCode . "'>SeaLifeBase</a>";
            
            //=============================================================================================================            
            $agent=array();
            $agent[]=array("role" => "" , "homepage" => "http://www.aquamaps.org" , "name" => "Rainer Froese");
            //=============================================================================================================
            
            //start distribution
            
            $genus = $rec->Genus;
            $species = $rec->Species;
            
            $arr_result = self::get_aquamaps($genus, $species, $source_dbase_link);
            $distribution = $arr_result[0];
            $sourceURL = $arr_result[1];
            $arr_photos = $arr_result[2];
            
            /*
            $distribution = "<form>" . $distribution . "</form>";            
            $distribution = str_ireplace("'",'"',$distribution);                        
            $distribution = str_ireplace("onmouseover","onclick",$distribution);            
            //$distribution = str_ireplace("<a href='#'","<input value='' id='' type='radio'",$distribution);            
            $distribution = str_ireplace("<a href='#'","<a href='javascript: void(0)'",$distribution);            
            //$distribution = str_ireplace("Native range</a>","Native range",$distribution);
            //$distribution = str_ireplace("All suitable habitat</a>","All suitable habitat",$distribution);
            //$distribution = str_ireplace("PointMap</a>","PointMap",$distribution);
            //$distribution = str_ireplace("Year 2050</a>","Year 2050",$distribution);
            */            
            //print"[[$distribution]]<hr>";
            
            //end distribution
            //=============================================================================================================

            //=============================================================================================================
            //start photos
            //print"<pre>";print_r($arr_photos);print"</pre>"; //debug            
            //end photos
            //=============================================================================================================
                        
            $ctr++;
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
                                                     "subject"=>"http://rs.tdwg.org/ontology/voc/SPMInfoItems#Distribution",
                                                     "title"=>"AquaMaps for <i>$rec->Genus $rec->Species</i>",
                                                     "dataType"=>"http://purl.org/dc/dcmitype/Text",
                                                     "dc_source"=>$sourceURL)
                                );   
            
            //,

        }
        //print"<pre>";print_r($arr_scraped);print"</pre>"; //debug
        return $arr_scraped;
        
    }

    public static function get_aquamaps($genus,$species,$source_dbase_link)
    {	
    	//$fn = "http://www.aquamaps.org/webservice/getAMap.php?genus=$genus&species=$species";

        $param = "genus=" . $genus . "&species=" . $species;
        $param2 = "genusname=" . $genus . "&speciesname=" . $species;
        //==============================================================================
        $fn = SERVICE_URL . $param;        
        $xml = simplexml_load_file($fn);        
        $html = $xml->section_body;        
        if(is_numeric(stripos($html,"has not yet been reviewed")))$review="un-reviewed";
        else                                                      $review="reviewed";
        
        if(preg_match("/href=\'http:\/\/(.*?)\'>/ims", $html, $matches)){$sourceURL = "http://" . trim($matches[1]);}
        else                                                             $sourceURL = "";        

        $attribution = "$source_dbase_link <a target='am $genus $species' href='$sourceURL'>AquaMaps</a> ";
        if(preg_match("/Data sources:(.*?)<\/font><\/td>/ims", $html, $matches)){$attribution .= trim($matches[1]) . "";}

        $attribution = str_ireplace(array("\n", "\r", "\t", "\o", "\xOB","\xA0"), '', $attribution);			

        //$attribution = "Data sources: " . str_ireplace(" "," | ",$attribution);        
        $attribution = "Data sources: " . $attribution;        
        
        //print"[$attribution]"; exit;        
        //print "[$sourceURL]"; exit;


        //print $html;    
        
        
        //http://www.aquamaps.org/imagethumb/file_destination/exp_8_pic_ITS-180469.jpg</a>
        $arr_photos=array();        

        $str="<table border='0' cellspacing='0' cellpadding='5'><tr><td><b>Computer Generated Maps of <i>$genus $species</i> ($review)</b></td></tr>
        <tr><td>$attribution</td></tr>
        ";        
        
        $native_range="";
        if(preg_match("/=\&quot\;\s*(.*?)\&quot\;\'\>\s*Native range\s*/ims", $html, $matches))
        {   $native_range = trim($matches[1]) . "";
            $src = "http://www.aquamaps.org/imagethumb/workimagethumb.php?s=" . $native_range . "&w=430";
            $str.="
            <tr><td>&nbsp;</td></tr>
            <tr><td>Native range</td></tr>                
            <tr><td><a target='am $genus $species' href='$sourceURL'><img alt='native_range' src='$src'></a></td></tr>";
            $arr_photos[] = self::build_image_array($native_range,$genus,$species,"Native range",$sourceURL);
        }
        print"<hr>native_range = $native_range";        
        
        $pointmap="";
        if(preg_match("/\/pointmap\/(.*?)&quot;/ims", $html, $matches))
        {   $pointmap = trim($matches[1]) . "";            
            $pointmap = "http://www.aquamaps.org/imagethumb/cached_maps/pointmap/" . $pointmap;
            $src = "http://www.aquamaps.org/imagethumb/workimagethumb.php?s=" . $pointmap . "&w=430";
            $str.="
            <tr><td>&nbsp;</td></tr>
            <tr><td>PointMap</td></tr>                
            <tr><td><a target='am $genus $species' href='$sourceURL'><img alt='pointmap' src='$src'></a></td></tr>";
            $arr_photos[] = self::build_image_array($pointmap,$genus,$species,"PointMap",$sourceURL);
        }
        print"<hr> pointmap = $pointmap";

        $suitable="";
        if(preg_match("/\/suitable\/(.*?)&quot;/ims", $html, $matches))
        {   $suitable = trim($matches[1]) . "";
            $suitable = "http://www.aquamaps.org/imagethumb/cached_maps/suitable/" . $suitable;
            $src = "http://www.aquamaps.org/imagethumb/workimagethumb.php?s=" . $suitable . "&w=430";
            $str.="
            <tr><td>&nbsp;</td></tr>
            <tr><td>All suitable habitat</td></tr>                
            <tr><td><a target='am $genus $species' href='$sourceURL'><img alt='suitable' src='$src'></a></td></tr>";
            $arr_photos[] = self::build_image_array($suitable,$genus,$species,"All suitable habitat",$sourceURL);
        }
        print"<hr> suitable = $suitable";

        $m2050="";
        if(preg_match("/\/2050\/(.*?)&quot;/ims", $html, $matches))
        {   $m2050 = trim($matches[1]) . "";
            $m2050 = "http://www.aquamaps.org/imagethumb/cached_maps/2050/" . $m2050;            
            $src = "http://www.aquamaps.org/imagethumb/workimagethumb.php?s=" . $m2050 . "&w=430";
            $str.="
            <tr><td>&nbsp;</td></tr>
            <tr><td>Year 2050 range</td></tr>                
            <tr><td><a target='am $genus $species' href='$sourceURL'><img alt='2050' src='$src'></a></td></tr>";
            $arr_photos[] = self::build_image_array($m2050,$genus,$species,"Year 2050 range",$sourceURL);
        }
        print"<hr> 2050 = [$m2050]";
        
        //==============================================================================
        ///*

        

        /* RD's service, not correct
        $fn = SERVICE_URL2 . $param;        
        $xml = simplexml_load_file($fn);        
        
        $arr_photos=array();        
        foreach($xml->images->image as $img)
        {
            if($img->url == "")continue;
            switch($img->type)
            {
                case 'native range':            $type = 'Native range';break;
                case 'all suitable habitat':    $type = 'All suitable habitat';break;
                case 'year 2050 range':         $type = 'Year 2050 range';break;
                case 'pointmap':                $type = 'PointMap';break;
                default:                        return false;
            }
            
            $img_url = "http://www.aquamaps.org/imagethumb/workimagethumb.php?s=" . $img->url . "&w=430";                    
            
            if($img->type != "pointmap")
            {                
                $html.="
                <tr><td>&nbsp;</td></tr>
                <tr><td>$type</td></tr>                
                <tr><td><a target='am $genus $species' href='$sourceURL'><img alt='$type' src='$img_url'></a></td></tr>";
            }
            else $pointmap_url = "http://www.aquamaps.org/imagethumb/workimagethumb.php?s=" . $img->url . "&w=430";
            
            $arr_photos[] = array(  "mediaURL"=>$img->url,
                                    "mimeType"=>"image/jpeg",
                                    "dataType"=>"http://purl.org/dc/dcmitype/StillImage",
                                    "description"=>"<i>$genus $species</i>: $type",
                                    "dc_source"=>$sourceURL,
                                    "agent"=>array());                        
        }//end loop
        //print"<pre>";print_r($arr_photos);print"</pre>"; exit;

        if(isset($pointmap_url))
        {
            $html.="
            <tr><td>&nbsp;</td></tr>
            <tr><td>PointMap</td></tr>
            <tr><td><a target='am $genus $species' href='$sourceURL'><img alt='PointMap' src='$pointmap_url'></a></td></tr>
            ";
        }

        */
        
                
        $str.="</table>";

        //print $str; exit;
        //<div style='font-size : x-small;overflow : scroll;'>
        $str = str_ireplace(array("\n", "\r", "\t", "\o", "\xOB", "\xA0"), '', $str);			
        return array(trim($str),$sourceURL,$arr_photos);
        
        //*/
               
    }

    
    public static function build_image_array($img,$genus,$species,$type,$sourceURL)
    {
        return array(  "mediaURL"=>$img,
                       "mimeType"=>"image/jpeg",
                       "dataType"=>"http://purl.org/dc/dcmitype/StillImage",
                       "description"=>"<i>$genus $species</i>: $type",
                       "dc_source"=>$sourceURL,
                       "agent"=>array());                        
    }
    
    
    public static function get_taxa_for_photo($rec)
    {
        $taxon = array();
        $taxon["commonNames"] = array();
        $license = null;
        
        $taxon["source"] = $rec["dc_source"];
        $taxon["scientificName"] = ucfirst(trim($rec["sciname"]));
        $taxon["family"] = ucfirst(trim(@$rec["family"]));
        $taxon["order"] = ucfirst(trim(@$rec["order"]));
        $taxon["class"] = ucfirst(trim(@$rec["class"]));        
        $taxon["phylum"] = ucfirst(trim(@$rec["phylum"]));
        $taxon["kingdom"] = ucfirst(trim(@$rec["kingdom"]));        
        
        //$taxon["commonNames"][] = new SchemaCommonName(array("name" => trim($arr[1])));
        if(@!$taxon["genus"] && @preg_match("/^([^ ]+) /", $taxon["scientificName"], $arr)) $taxon["genus"] = $arr[1];
        
        $arr = $rec["distribution"];
        if($arr["description"])
        {
            $data_object = self::get_data_object($arr);
            if(!$data_object) return false;
            $taxon["dataObjects"][] = new SchemaDataObject($data_object);                     
        }        
                

        $photos = $rec["photos"];
        if($photos)
        {
            foreach($photos as $photos)
            {
                $data_object = self::get_data_object($photos);
                if(!$data_object) return false;
                $taxon["dataObjects"][] = new SchemaDataObject($data_object);                     
            }
        }        

        
        
        $taxon_object = new SchemaTaxon($taxon);
        return $taxon_object;
    }
    
    public static function get_data_object($rec)
    {
        $data_object_parameters = array();
        //$data_object_parameters["identifier"] = $synth->Id;        
        
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
            $data_object_parameters["subjects"][] = new SchemaSubject($subjectParameters);
        }
        
        //print_r($rec);
        //print_r(@$rec["agent"]);exit;

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
                 $agents[] = new SchemaAgent($agentParameters);
             }
             $data_object_parameters["agents"] = $agents;
         }
        
        //return new SchemaDataObject($data_object_parameters);
        return $data_object_parameters;
    }
    
}
?>