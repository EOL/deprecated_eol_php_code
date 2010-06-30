<?php


define("PDF2TEXT_PROGRAM", LOCAL_ROOT . "/vendor/PDFtoText/pdftotext");
define("SOURCE_URL", "http://projects.bebif.be/fruitfly/taxoninfo.html?id=");


class AfrotropicalAPI
{
    public static function get_all_taxa()
    {
        global $used_collection_ids;
    
        $all_taxa = array();
        $used_collection_ids = array();
        
        $urls = array( 0  => array( "path" => "http://128.128.175.77/eol_php_code/applications/content_server/resources/EOLexportFruitfly_fixed.xml"  , "active" => 1),
                       1  => array( "path" => "http://128.128.175.77/eol_php_code/applications/content_server/resources/EOLexportFruitfly.xml"        , "active" => 0)
                     );
                
        foreach($urls as $url)
        {
            if($url["active"])
            {
                $page_taxa = self::get_afrotropical_taxa($url["path"]);                                
                /*debug
                print"<hr>website: " . $url["path"] . "<br>";
                print"page_taxa count: " . $url["path"] . " -- " . count($page_taxa) . "<hr>";                
                */                
                //print"<pre>page_taxa: ";print_r($page_taxa);print"</pre>";                        
                if($page_taxa)
                {                    
                    $all_taxa = array_merge($all_taxa,$page_taxa);                                    
                    //or use this => foreach($page_taxa as $t) $all_taxa[] = $t;
                }
            }
        }
        //print"<hr><pre>all_taxa: ";print_r($all_taxa);print"</pre>"; //debug see all records
        //print"total: " . count($all_taxa) . "<br>\n"; //debug       
        return $all_taxa;
    }
    
    public static function get_afrotropical_taxa($url)
    {
        global $used_collection_ids;
        
        $response = self::search_collections($url);//this will output the raw (but structured) output from the external service
        $page_taxa = array();
        foreach($response as $rec)
        {
            if(@$used_collection_ids[$rec["sciname"]]) continue;
            
            $taxon = self::get_taxa_for_photo($rec);
            if($taxon) $page_taxa[] = $taxon;
            
            @$used_collection_ids[$rec["sciname"]] = true;
        }        
        return $page_taxa;
    }    
    
    public static function search_collections($url)//this will output the raw (but structured) output from the external service
    {        
        $response = self::parse_xml($url);
        return $response;//structured array
    }        
    
    public static function parse_xml($url)
    {
        $arr_data=array();        
        
        $xml = simplexml_load_file($url);                
        
        $ctr=0;
        $loops = sizeof($xml->taxon);
        
        $id_arr=array();
        
        foreach($xml->taxon as $t)
        {
            $t_dwc = $t->children("http://rs.tdwg.org/dwc/dwcore/");                         
            $t_dc = $t->children("http://purl.org/dc/elements/1.1/");        

            $ctr++;
            if(in_array(trim($t_dc->identifier), $id_arr)) 
            {
                continue;
            }
            else
            {
                $id_arr[]=trim($t_dc->identifier);
            }
            
            //if($ctr == 5)break;//debug to limit the no. of records                        
            //if(trim($t_dc->identifier) != 136)continue;
            //if(trim($t_dc->identifier) != 1)continue;
            if(trim($t_dc->identifier) != 7)continue;
            //if(trim($t_dc->identifier) != 165)continue;
            /*
            if  (trim($t_dc->identifier) != 136 and trim($t_dc->identifier) != 1
                 and trim($t_dc->identifier) != 310 and trim($t_dc->identifier) != 7   
                )continue;
            */
            
            print"$ctr of " . $loops . "[" . trim($t_dc->identifier) . "]" . "\n";
            //=============================================================================================================            
            
            $arr_objects=array();

            foreach($t->dataObject as $do)
            {
                $t_dc2      = $do->children("http://purl.org/dc/elements/1.1/");            
                $t_dcterms  = $do->children("http://purl.org/dc/terms/");            
                $xml_attr = $t_dc2->title->attributes("http://www.w3.org/XML/1998/namespace");                                

                $agent=array();
                foreach($do->agent as $a)
                {
                    $agent[]=array( "fullName"  =>trim($a), 
                                    "role"      =>$a["role"],
                                    "homepage"  =>$a["homepage"],
                                    "logoURL"   =>$a["logoURL"]
                                  );
                }
                $audience=array();
                foreach($do->audience as $a)
                {
                    $audience[]=trim($a);
                }
                
            
                $description="";
                $mediaURL = trim($do->mediaURL);                               
                if(substr($mediaURL,strlen($mediaURL)-4,4) == ".pdf")
                {
                    $pdf = Functions::get_remote_file($mediaURL);                    
                    
                    $file = DOC_ROOT . "update_resources/connectors/files/temp.pdf";
                    $target = DOC_ROOT . "update_resources/connectors/files/temp.xml";
                    
                    //$file = "files/temp.pdf";
                    //$target = "files/temp.xml";
                    
                    $OUT = fopen($file, "w");
                    fwrite($OUT, $pdf);
                    fclose($OUT);            

                    $description = shell_exec(PDF2TEXT_PROGRAM . " -layout -nopgbrk -raw -enc UTF-8 " . $file . " -");      
                    //$description = shell_exec(PDF2TEXT_PROGRAM . " -layout -nopgbrk -raw -enc UTF-8 " . $file . " " . $target);      
                                        
                    //start test if all chars are good
                    $temp = Functions::import_decode($description);
                    $temp = str_ireplace("&nbsp;", " ", $temp);
                    $temp = str_ireplace("&", "&amp;", $temp);
                    $xml_temp = "<?xml version='1.0' encoding='utf-8' ?><text>" . $temp . "</text>";
                    $OUT = fopen($target, "w");
                    fwrite($OUT, $xml_temp);
                    fclose($OUT);    

                    /* same effect as below
                    $xmlContent = file_get_contents($target);
                    $errorMessage = '';
                    if (self::XmlIsWellFormed($xmlContent, $errorMessage)) print"\n GOOD XML";
                    else PRINT"\n BAD XML";
                    */
                    
                    if(self::check_xml_if_well_formed($target))
                    {
                        print"good xml \n";                                            
                        $pos = stripos($description,"mm.");
                        if(is_numeric($pos))
                        {
                            $str1 = substr($description,0,$pos+3);
                            $str1 = str_ireplace("\n", "<br>", $str1);        
                            $str2 = substr($description,$pos+3,strlen($description));                        
                            $str2 = str_replace("Male\n", "Male <br>", $str2);
                            $str2 = str_replace("Female\n", "Female <br>", $str2);                        

                            $str2 = str_replace("Male", "<br>Male", $str2);
                            $str2 = str_replace("Female", "<br>Female", $str2);                        

                            $str2 = str_ireplace("1\n", "\n", $str2);                        
                            $str2 = str_ireplace("\n", " ", $str2);        
                            $str2 = str_ireplace(":", ":<br>", $str2);        
                            $str2 = str_ireplace("(Description", "<br>(Description", $str2);        
                            
                            $description = $str1 . "<br>" . $str2;                           
                        }
                        else
                        {
                            $description = str_ireplace("1\n", "\n", $description);
                            $description = str_ireplace("\n", "<br>", $description);
                        }           
                        $description .= "<br><a target='afrotropical' href='" . trim($do->mediaURL) . "'>See " . trim($t_dc2->description) . " in source PDF.</a>";
                    }
                    else
                    {
                        print"bad xml \n";
                        $description = "<a target='afrotropical' href='" . trim($do->mediaURL) . "'>See " . trim($t_dc2->description) . ".</a>";
                    }
                    
                    
                }//if(substr($mediaURL,strlen($mediaURL)-4,4) == ".pdf")

                if(trim($do->dataType)=="http://purl.org/dc/dcmitype/StillImage")
                {
                    $to_be_replaced = "image.html?id=" . trim($t_dc2->identifier) . "&";
                    $mediaURL = str_ireplace("$to_be_replaced", "imageview.html?", trim($do->mediaURL));
                    $source_url = trim($do->mediaURL);
                    $subject="";   
                }
                else
                {
                    $source_url = SOURCE_URL . trim($t_dc->identifier);
                    $subject="http://rs.tdwg.org/ontology/voc/SPMInfoItems#GeneralDescription";
                }
                
                if($description == "")
                {
                    $description=trim($t_dc2->description);                    
                }
                
                

                $arr_objects[]=array( "identifier"=>$t_dc2->identifier,
                                      "dataType"=>$do->dataType,
                                      "mimeType"=>$do->mimeType,
                                      "agent"=>$agent,
                                      "audience"=>$audience,
                                      "created"=>$t_dcterms->created,                                                                            
                                      
                                      "title"=>array("title"=>trim($t_dc2->title), "lang"=>$xml_attr["lang"]),
                                      "subject"=>$subject,
                                      
                                      "license"=>$do->license,
                                      "rights"=>$t_dc2->rights,
                                      "rightsHolder"=>$t_dcterms->rightsHolder,
                                      "audience"=>$do->audience,
                                      "source"=>$source_url,
                                      "description"=>$description,
                                      "mediaURL"=>$mediaURL
                                    );                        
            
            }//foreach($t->dataObject as $do)
                                                    
            
            $arr_data[]=array(  "id"           =>$ctr,
                                "identifier"   =>trim($t_dc->identifier),   
                                "source"       =>SOURCE_URL . trim($t_dc->identifier),
                                "kingdom"      =>$t_dwc->Kingdom,
                                "phylum"       =>$t_dwc->Phylum,
                                "class"        =>$t_dwc->Class,
                                "order"        =>$t_dwc->Order,
                                "family"       =>$t_dwc->Family,
                                "genus"        =>$t_dwc->Genus,
                                "sciname"      =>$t_dwc->ScientificName,
                                "arr_objects"  =>$arr_objects                                 
                             );               

                                
        }//foreach($xml->taxon as $t)
        
        print"\n" . sizeof($id_arr) . " \n";
        
        if(isset($file))unlink($file);
        
        return $arr_data;        
    }

    public static function XmlIsWellFormed($xmlString, $message) 
    {
        libxml_use_internal_errors(true);            
        $doc = new DOMDocument('1.0', 'utf-8');
        $doc->loadXML($xmlString);    
        $errors = libxml_get_errors();
        if (empty($errors))return true;
        $error = $errors[ 0 ];
        if ($error->level < 3)return true;
        $lines = explode("r", $xmlString);
        $line = $lines[($error->line)-1];
        $message = $error->message . ' at line ' . $error->line . ': ' . htmlentities($line);
        return false;
    }

    
    
    public static function check_xml_if_well_formed($url)
    {
        /*
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        if (simplexml_load_string($output))return true;     // well-formed XML
        else                               return false;    // not well-formed
        */
        
        if(simplexml_load_file($url))return true;     // well-formed XML
        else                         return false;    // not well-formed
        
    }
    
    public static function get_taxa_for_photo($rec)
    {
        $taxon = array();
        $taxon["commonNames"] = array();
        $license = null;
        
        $taxon["source"] = $rec["source"];
        $taxon["identifier"] = $rec["identifier"];
        $taxon["scientificName"] = ucfirst(trim($rec["sciname"]));
        $taxon["genus"] = ucfirst(trim(@$rec["genus"]));
        $taxon["family"] = ucfirst(trim(@$rec["family"]));
        $taxon["order"] = ucfirst(trim(@$rec["order"]));
        $taxon["class"] = ucfirst(trim(@$rec["class"]));        
        $taxon["phylum"] = ucfirst(trim(@$rec["phylum"]));
        $taxon["kingdom"] = ucfirst(trim(@$rec["kingdom"]));        
        
        //$taxon["commonNames"][] = new SchemaCommonName(array("name" => trim($arr[1])));

        /*        
        $arr = $rec["distribution"];
        if($arr["description"])
        {
            $data_object = self::get_data_object($arr);
            if(!$data_object) return false;
            $taxon["dataObjects"][] = new SchemaDataObject($data_object);                     
        }
        */                        

        if($rec["arr_objects"])
        {
            foreach($rec["arr_objects"] as $object)
            {
                $data_object = self::get_data_object($object);
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
        
        $data_object_parameters["identifier"] = trim($rec["identifier"]);
        
        $data_object_parameters["source"] = $rec["source"];
        
        $data_object_parameters["dataType"] = trim($rec["dataType"]);
        $data_object_parameters["mimeType"] = trim($rec["mimeType"]);
        $data_object_parameters["mediaURL"] = trim(@$rec["mediaURL"]);
        
        $data_object_parameters["created"] = trim(@$rec["created"]);
        
        $data_object_parameters["description"] = trim($rec["description"]);
        $data_object_parameters["source"] = @$rec["source"];
        $data_object_parameters["license"] = trim(@$rec["license"]);
        
        $data_object_parameters["rights"] = trim(@$rec["rights"]);
        $data_object_parameters["rightsHolder"] = trim(@$rec["rightsHolder"]);
        
        if(@$rec["subject"])
        {
            $data_object_parameters["subjects"] = array();
            $subjectParameters = array();
            $subjectParameters["label"] = @$rec["subject"];
            $data_object_parameters["subjects"][] = new SchemaSubject($subjectParameters);
        }

        $data_object_parameters["title"]    = @$rec["title"]["title"];
        $data_object_parameters["language"] = @$rec["title"]["lang"];
        
        if(@$rec["agent"])
        {               
            $agents = array();
            foreach($rec["agent"] as $a)
            {  
                $agentParameters = array();
                $agentParameters["role"]     = $a["role"];
                $agentParameters["homepage"] = $a["homepage"];
                $agentParameters["logoURL"]  = $a["logoURL"];
                $agentParameters["fullName"] = $a["fullName"];
                $agents[] = new SchemaAgent($agentParameters);
            }
            $data_object_parameters["agents"] = $agents;
        }

        if(@$rec["audience"])
        {
            $data_object_parameters["audiences"] = array();    
            $audienceParameters = array();
            foreach($rec["audience"] as $a)
            {
                $audienceParameters["label"] = $a;
                $data_object_parameters["audiences"][] = new SchemaAudience($audienceParameters);            
            }
        } 
        

        
        //return new SchemaDataObject($data_object_parameters);
        return $data_object_parameters;
    }    
}
?>