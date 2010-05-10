<?php
/* connector for AquaMaps
estimated execution time: 

This connector reads an XML (list of species with AquaMaps) then loops on each species and run the service to get the
interactive distribution maps.
*/

define("SPECIES_URL", "http://www.aquamaps.org/premap2.php?cache=1&SpecID=");
define("SERVICE_URL", "http://www.aquamaps.org/webservice/getAMap.php?");




include_once(dirname(__FILE__) . "/../../config/environment.php");
$GLOBALS['ENV_DEBUG'] = false;

$taxa = AquamapsAPI::get_all_eol_photos();
$xml = SchemaDocument::get_taxon_xml($taxa);

$resource_path = CONTENT_RESOURCE_LOCAL_PATH . "1.xml";

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
        $urls = array( 0  => array( "path" => $path . "aquamaps_species_list.XML" , "active" => 1),  //
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
        print"<hr><pre>all_taxa: ";print_r($all_taxa);print"</pre>";        
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
            if($ctr >= 3)break;//debug
            
            $sourceURL=$rec["url"];

            //=============================================================================================================            
            $agent=array();
            $agent[]=array("role" => "" , "homepage" => "http://www.aquamaps.org" , "name" => "Rainer Froese");
            //=============================================================================================================
            
            //start distribution
            $distribution = "found here and there";                  
            
            $genus = $rec->Genus;
            $species = $rec->Species;
            $distribution = self::get_aquamaps($genus, $species);
            //$distribution = str_ireplace('&quot;','"',$distribution);
            
            $distribution = str_ireplace("onmouseover","onclick",$distribution);
            
            //$distribution = str_ireplace("href='#'","href=''",$distribution);
            
            
            
            if($distribution)$distribution="<div style='font-size : x-small;overflow : scroll;'>$distribution</div>";
            

            //print"[[$distribution]]<hr>";
            
            //end distribution
            //=============================================================================================================

            $sourceURL = SPECIES_URL . $rec->SPECIESID;
                        
            $ctr++;
            $arr_scraped[]=array("id"=>$ctr,
                                 "sciname"=>$rec->Genus . ' ' . $rec->Species,

                                 "genus"=>$rec->Genus,
                                 "family"=>$rec->Family,
                                 "order"=>$rec->Order,
                                 "class"=>$rec->Class,
                                 "phylum"=>$rec->Phylum,
                                 "kingdom"=>$rec->Kingdom,
                                 
                                 "dc_source"=>$sourceURL,
                                 "distribution"=>array("description"=>$distribution   ,
                                                     "subject"=>"http://rs.tdwg.org/ontology/voc/SPMInfoItems#Distribution",
                                                     "title"=>"AquaMaps for ",
                                                     "dataType"=>"http://purl.org/dc/dcmitype/Text",
                                                     "dc_source"=>$sourceURL)
                                );   
            
            //"photos"=>$arr_photos,

        }
        //print"<pre>";print_r($arr_scraped);print"</pre>"; //debug
        return $arr_scraped;
        
    }

    public static function get_aquamaps($genus,$species)
    {	
    	//$fn = "http://www.aquamaps.org/webservice/getAMap.php?genus=$genus&species=$species";

        $param = "genus=" . $genus . "&species=" . $species;
        $fn = SERVICE_URL . $param;

    	$doc = new DOMDocument();
    	$doc->load( $fn );  
    	$temp = $doc->getElementsByTagName( "section" );  
    	foreach( $temp as $section )
    	{
    		$e = $section->getElementsByTagName( "section_body" );  
    		$section_body = $e->item(0)->nodeValue;				
    	}	
    	return $section_body;
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
                
        /*
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
        */
        
        
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