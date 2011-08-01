<?php
namespace php_active_record;

define("PHYLUM_SERVICE_URL", "http://www.boldsystems.org/connect/REST/getSpeciesBarcodeStatus.php?phylum=");
define("SPECIES_URL", "http://www.boldsystems.org/views/taxbrowser.php?taxid=");

class BOLDSysAPI
{
    public static function get_all_taxa($resource_id)
    {                
        $used_collection_ids = array();        

        $arr_phylum = self::compile_taxon_list();
        $total_phylum = sizeof($arr_phylum); $p=0;
        $save_count=0;
        $all_taxa = array(); 
        foreach($arr_phylum as $phylum)
        {            
            $p++;
            $xml = simplexml_load_file(PHYLUM_SERVICE_URL . $phylum['name']);                        
            $num_rows = sizeof($xml->record); $i=0;
            foreach($xml->record as $rec)
            {
                $i++; print"\n [$p of $total_phylum] [$i of $num_rows] ";
                print $rec->taxonomy->species->taxon->name;
                
                $arr = self::get_boldsys_taxa($rec,$used_collection_ids);                                
                $page_taxa              = $arr[0];
                $used_collection_ids    = $arr[1];                            

                if($page_taxa) $all_taxa = array_merge($all_taxa,$page_taxa);                
                unset($page_taxa);                
                
                if(sizeof($all_taxa)==5000)
                {
                    $save_count++;
                    $xml = SchemaDocument::get_taxon_xml($all_taxa);
                    $resource_path = CONTENT_RESOURCE_LOCAL_PATH . "temp_BOLD_" . $save_count . ".xml";
                    $OUT = fopen($resource_path, "w"); fwrite($OUT, $xml); fclose($OUT);                    
                    $all_taxa = array();
                }                
            }                                    
            //if($p==2)break; //debug - get just 1 phylum
        }
        
        if(sizeof($all_taxa)>0)
        {
            //last write, remaining
            $save_count++;
            $xml = SchemaDocument::get_taxon_xml($all_taxa);
            $resource_path = CONTENT_RESOURCE_LOCAL_PATH . "temp_BOLD_" . $save_count . ".xml";
            $OUT = fopen($resource_path, "w"); fwrite($OUT, $xml); fclose($OUT);                                        
        }            
        
        /* return $all_taxa; */
        self::combine_all_xmls($resource_id,$save_count);        
    }
    
    function combine_all_xmls($resource_id,$save_count)
    {
        $old_resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource_id .".xml";
        $OUT = fopen($old_resource_path, "w+");
        $str = "<?xml version='1.0' encoding='utf-8' ?>\n";
        $str .= "<response\n";
        $str .= "  xmlns='http://www.eol.org/transfer/content/0.3'\n";           
        $str .= "  xmlns:xsd='http://www.w3.org/2001/XMLSchema'\n";
        $str .= "  xmlns:dc='http://purl.org/dc/elements/1.1/'\n";           
        $str .= "  xmlns:dcterms='http://purl.org/dc/terms/'\n";           
        $str .= "  xmlns:geo='http://www.w3.org/2003/01/geo/wgs84_pos#'\n";           
        $str .= "  xmlns:dwc='http://rs.tdwg.org/dwc/dwcore/'\n";           
        $str .= "  xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance'\n";                      
        $str .= "  xsi:schemaLocation='http://www.eol.org/transfer/content/0.3 http://services.eol.org/schema/content_0_3.xsd'>\n";
        fwrite($OUT, $str);
        $i=0;    
        
        while($i <= $save_count)
        {
            print " $i "; 
            $filename = CONTENT_RESOURCE_LOCAL_PATH . "temp_BOLD_" . $i . ".xml";
            if(!is_file($filename))
            {
                print" - not yet ready";
                break;
            }
            $READ = fopen($filename, "r");
            $contents = fread($READ,filesize($filename));    
            fclose($READ);                        
    
            if($contents)
            {                
                $pos1 = stripos($contents,"<taxon>");
                $pos2 = stripos($contents,"</response>");                    
                $str  = substr($contents,$pos1,$pos2-$pos1);                
                fwrite($OUT, $str);
                unlink($filename);
            }            
            $i++; 
        }
        fwrite($OUT, "</response>");fclose($OUT);                    
    }    
    
    /*
    function initialize_xmls()
    {        
        while(true)
        {
            $i++; print "\n delete old files $i ";
            $filename = CONTENT_RESOURCE_LOCAL_PATH . "BOLD/" . $i . ".xml";
            if(is_file($filename)) unlink($filename);            
            else break; //nothing to delete anymore
        }        
    }    
    */
    
        
    public static function get_boldsys_taxa($rec,$used_collection_ids)
    {
        $response = self::parse_xml($rec);//this will output the raw (but structured) array
        $page_taxa = array();
        foreach($response as $rec)
        {
            if(@$used_collection_ids[$rec["sciname"]]) continue;            
            $taxon = self::get_taxa_for_photo($rec);
            if($taxon) $page_taxa[] = $taxon;            
            @$used_collection_ids[$rec["sciname"]] = true;
        }        
        return array($page_taxa,$used_collection_ids);        
    }            
    
    function get_taxon_id($rec)
    {
        if(isset($rec->taxonomy->species->taxon->taxid))return array($rec->taxonomy->species->taxon->taxid  ,$rec->taxonomy->species->taxon->name);
        if(isset($rec->taxonomy->genus->taxon->taxid))  return array($rec->taxonomy->genus->taxon->taxid    ,$rec->taxonomy->genus->taxon->name);
        if(isset($rec->taxonomy->family->taxon->taxid)) return array($rec->taxonomy->family->taxon->taxid   ,$rec->taxonomy->family->taxon->name);
        if(isset($rec->taxonomy->order->taxon->taxid))  return array($rec->taxonomy->order->taxon->taxid    ,$rec->taxonomy->order->taxon->name);
        if(isset($rec->taxonomy->class->taxon->taxid))  return array($rec->taxonomy->class->taxon->taxid    ,$rec->taxonomy->class->taxon->name);
        if(isset($rec->taxonomy->phylum->taxon->taxid)) return array($rec->taxonomy->phylum->taxon->taxid   ,$rec->taxonomy->phylum->taxon->name);
        if(isset($rec->taxonomy->kingdom->taxon->taxid))return array($rec->taxonomy->kingdom->taxon->taxid  ,$rec->taxonomy->kingdom->taxon->name);
    }
    function parse_xml($rec)
    {
        $arr_data=array();                                
        $arr = self::get_taxon_id($rec);
        $taxon_id   = $arr[0];
        $sciname    = $arr[1];
            
        //start data objects //----------------------------------------------------------------------------------------
        $arr_objects=array();
        
        //barcode stats
        $bold_stats="<table>";
        if(isset($rec->stats->public_barcodes)) $bold_stats.="<tr><td>Public Records:</td>  <td align='right'>".$rec->stats->public_barcodes."</td></tr>";
        else                                    $bold_stats.="<tr><td>Public Records:</td>  <td align='right'>0</td></tr>";
        if(isset($rec->stats->barcodes))        $bold_stats.="<tr><td>Species:</td>         <td align='right'>".$rec->stats->barcodes."</td></tr>";
        if(isset($rec->stats->barcoded_species))$bold_stats.="<tr><td>Species With Barcodes:</td><td align='right'>".$rec->stats->barcoded_species."</td></tr>";
        $bold_stats.="</table>";                
        $identifier  = $taxon_id . "_stats";
        $dataType    = "http://purl.org/dc/dcmitype/Text"; $mimeType    = "text/html";
        $title       = "Statistics of barcoding coverage";
        $source      = SPECIES_URL . trim($taxon_id);
        $mediaURL    = "";               
        $description = "Barcode of Life Data Systems (BOLDS) Stats <br> $bold_stats";        
        if($bold_stats!="<table><tr></tr></table>")$arr_objects = self::add_objects($identifier,$dataType,$mimeType,$title,$source,$description,$mediaURL,$arr_objects);
        
        //barcode image
        if(isset($rec->barcode_image_url))                
        {
            $identifier  = $taxon_id . "_barcode_data";            
            $dataType    = "http://purl.org/dc/dcmitype/Text"; $mimeType    = "text/html";            
            $title       = "Barcode data";                
            $source      = SPECIES_URL . trim($taxon_id);
            $mediaURL    = "";               
            $description = "The following is a representative barcode sequence, the centroid of all available sequences for this species.<br><a target='barcode' href='".$rec->barcode_image_url."'><img src='http://".$rec->barcode_image_url."' height=''></a>";
            $arr_objects = self::add_objects($identifier,$dataType,$mimeType,$title,$source,$description,$mediaURL,$arr_objects);
        }
        
        //map 
        if(isset($rec->map_url))                
        {
            $identifier  = $taxon_id . "_map";            
            $dataType    = "http://purl.org/dc/dcmitype/Text"; $mimeType    = "text/html";            
            $title="Locations of barcode samples";            
            $source      = SPECIES_URL . trim($taxon_id);
            $mediaURL    = "";               
            $description = "Collection Sites: world map showing specimen collection locations for <i>" . $sciname . "</i><br><img border='0' src='".$rec->map_url."'>";                
            $arr_objects = self::add_objects($identifier,$dataType,$mimeType,$title,$source,$description,$mediaURL,$arr_objects);
        }            
        //end data objects //----------------------------------------------------------------------------------------
        
        $phylum="";$class="";$order="";$family="";$genus="";$species="";
        if(isset($rec->taxonomy->phylum->taxon->name))   $phylum=$rec->taxonomy->phylum->taxon->name;
        if(isset($rec->taxonomy->class->taxon->name))    $class=$rec->taxonomy->class->taxon->name;        
        if(isset($rec->taxonomy->order->taxon->name))    $order=$rec->taxonomy->order->taxon->name;
        if(isset($rec->taxonomy->family->taxon->name))   $family=$rec->taxonomy->family->taxon->name;
        if(isset($rec->taxonomy->genus->taxon->name))    $genus=$rec->taxonomy->genus->taxon->name;
        if(isset($rec->taxonomy->species->taxon->name))  $species=$rec->taxonomy->species->taxon->name;
        
        $arr_data[]=array(  "identifier"   =>$taxon_id,
                            "source"       =>SPECIES_URL . trim($taxon_id),
                            "kingdom"      =>"",
                            "phylum"       =>$phylum,
                            "class"        =>$class,
                            "order"        =>$order,
                            "family"       =>$family,
                            "genus"        =>$genus,
                            "sciname"      =>$species,
                            "arr_objects"  =>$arr_objects                                 
                         );               
        return $arr_data;        
    }
    
    function add_objects($identifier,$dataType,$mimeType,$title,$source,$description,$mediaURL,$arr_objects)
    {
        $arr_objects[]=array( "identifier"=>$identifier,
                              "dataType"=>$dataType,
                              "mimeType"=>$mimeType,
                              "title"=>$title,
                              "source"=>$source,
                              "description"=>$description,
                              "mediaURL"=>$mediaURL
                            );                                    
        return $arr_objects;
    }

    function check_xml_if_well_formed($url)
    {
        if(simplexml_load_file($url))return true;     // well-formed XML
        else                         return false;    // not well-formed        
    }
    
    function get_taxa_for_photo($rec)
    {
        $taxon = array();
        $taxon["commonNames"] = array();
        $license = null;        
        $taxon["source"] = $rec["source"];
        $taxon["identifier"] = trim($rec["identifier"]);
        $taxon["scientificName"] = ucfirst(trim($rec["sciname"]));
        $taxon["genus"] = ucfirst(trim(@$rec["genus"]));
        $taxon["family"] = ucfirst(trim(@$rec["family"]));
        $taxon["order"] = ucfirst(trim(@$rec["order"]));
        $taxon["class"] = ucfirst(trim(@$rec["class"]));        
        $taxon["phylum"] = ucfirst(trim(@$rec["phylum"]));
        $taxon["kingdom"] = ucfirst(trim(@$rec["kingdom"]));                
        if($rec["arr_objects"])
        {
            foreach($rec["arr_objects"] as $object)
            {
                $data_object = self::get_data_object($object);
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
        $data_object_parameters["identifier"]   = trim(@$rec["identifier"]);        
        $data_object_parameters["source"]       = $rec["source"];        
        $data_object_parameters["dataType"]     = trim($rec["dataType"]);
        $data_object_parameters["mimeType"]     = trim($rec["mimeType"]);
        $data_object_parameters["mediaURL"]     = trim(@$rec["mediaURL"]);        
        $data_object_parameters["created"]      = trim(@$rec["created"]);        
        
        $data_object_parameters["description"]  = Functions::import_decode(@$rec["description"]);            
        
        
        $data_object_parameters["source"]       = @$rec["source"];
        $data_object_parameters["license"]      = "http://creativecommons.org/licenses/by/3.0/";        
        $data_object_parameters["rightsHolder"] = "Barcode of Life Data Systems";
        $data_object_parameters["title"]        = @trim($rec["title"]);
        $data_object_parameters["language"]     = "en";
        //==========================================================================================        
        if(trim($rec["mimeType"]) == "text/html")
        {
            $data_object_parameters["subjects"] = array();
            $subjectParameters = array();
            $subjectParameters["label"] = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#MolecularBiology";            
            $data_object_parameters["subjects"][] = new \SchemaSubject($subjectParameters);
        }                            
        //==========================================================================================
        $agent = array(0 => array("role" => "compiler" , "homepage" => "http://www.boldsystems.org/" , "Sujeevan Ratnasingham"),
                       1 => array("role" => "compiler" , "homepage" => "http://www.boldsystems.org/" , "Paul D.N. Hebert"));    
        $agents = array();
        foreach($agent as $agent)
        {  
            $agentParameters = array();
            $agentParameters["role"]     = $agent["role"];
            $agentParameters["homepage"] = $agent["homepage"];
            $agentParameters["logoURL"]  = "";        
            $agentParameters["fullName"] = $agent[0];
            $agents[] = new \SchemaAgent($agentParameters);
        }
        $data_object_parameters["agents"] = $agents;    
        return $data_object_parameters;
    }    

    function compile_taxon_list()
    {
        /*
        $arr_phylum = array(0 => array( "name" => "Acanthocephala"   , "id" => 11),
                            1 => array( "name" => "Annelida"         , "id" => 2),
                            2 => array( "name" => "Arthropoda"       , "id" => 20),
                            3 => array( "name" => "Brachiopoda"      , "id" => 9),        
        */
        
        //Animals
        $arr_phylum = array(0 => array( "name" => "Acanthocephala"   , "id" => 11),
                            1 => array( "name" => "Annelida"         , "id" => 2),
                            2 => array( "name" => "Arthropoda"       , "id" => 20),
                            3 => array( "name" => "Brachiopoda"      , "id" => 9),        
                            4 => array( "name" => "Bryozoa"          , "id" => 7),
                            5 => array( "name" => "Chaetognatha"     , "id" => 13),
                            6 => array( "name" => "Chordata"         , "id" => 18),
                            7 => array( "name" => "Cnidaria"         , "id" => 3),
                            8 => array( "name" => "Cycliophora"      , "id" => 79455),
                            9 => array( "name" => "Echinodermata"    , "id" => 4),
                            10 => array( "name" => "Echiura"         , "id" => 27333),
                            11 => array( "name" => "Gnathostomulida" , "id" => 78956),
                            12 => array( "name" => "Hemichordata"    , "id" => 21),
                            13 => array( "name" => "Mollusca"        , "id" => 23),
                            14 => array( "name" => "Nematoda"        , "id" => 19),
                            15 => array( "name" => "Onychophora"     , "id" => 10),
                            16 => array( "name" => "Platyhelminthes" , "id" => 5),                        
                            17 => array( "name" => "Porifera"        , "id" => 24818),
                            18 => array( "name" => "Rotifera"        , "id" => 16),
                            19 => array( "name" => "Sipuncula"       , "id" => 15),
                            20 => array( "name" => "Tardigrada"      , "id" => 26033),
                            21 => array( "name" => "Xenoturbellida"  , "id" => 88647)
                           );
                           
        //Fungi 
        $temp = array(0 => array( "name" => "Ascomycota"      , "id" => 34),
                      1 => array( "name" => "Basidiomycota"   , "id" => 23675),
                      2 => array( "name" => "Chytridiomycota" , "id" => 23691),
                      3 => array( "name" => "Myxomycota"      , "id" => 83947),
                      4 => array( "name" => "Zygomycota"      , "id" => 23738)
                     );                        
        $arr_phylum = array_merge($arr_phylum, $temp);                 
        
        //Plants 
        $temp = array(0 => array( "name" => "Bryophyta"          , "id" => 176192),
                      1 => array( "name" => "Chlorophyta"        , "id" => 112296),
                      2 => array( "name" => "Lycopodiophyta"     , "id" => 38696),
                      3 => array( "name" => "Magnoliophyta"      , "id" => 12),
                      4 => array( "name" => "Pinophyta"          , "id" => 251587),
                      5 => array( "name" => "Pteridophyta"       , "id" => 38074),
                      6 => array( "name" => "Rhodophyta"         , "id" => 48327),
                      7 => array( "name" => "Stramenopiles"      , "id" => 109924)
                     );        
        $arr_phylum = array_merge($arr_phylum, $temp);                 
                         
        //Protists                        
        $temp = array(0 => array( "name" => "Bacillariophyta"    , "id" => 74445),
                      1 => array( "name" => "Ciliophora"         , "id" => 72834),
                      2 => array( "name" => "Dinozoa"            , "id" => 70855),
                      3 => array( "name" => "Heterokontophyta"   , "id" => 53944),
                      4 => array( "name" => "Opalozoa"           , "id" => 72171),                        
                      5 => array( "name" => "Straminipila"       , "id" => 23715),
                      6 => array( "name" => "Chlorarachniophyta" , "id" => 316986),                        
                      7 => array( "name" => "Pyrrophycophyta"    , "id" => 317010)                        
                     );
        $arr_phylum = array_merge($arr_phylum, $temp);                                                  
        return $arr_phylum;
    }    
            
}
?>