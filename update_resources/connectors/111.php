<?php
/* connector for Lichens database - Field Museum Grainger EMU
This connector reads an XML dump from provider.
estimated execution time: 4 secs -> for the text XML dump of 10 species.
*/
$timestart = microtime(1);

$species_page_url = "http://emuweb.fieldmuseum.org/arthropod/InsDisplay.php?irn=";
$image_url        = "http://emuweb.fieldmuseum.org/web/objects/common/webmedia.php?irn=";

include_once(dirname(__FILE__) . "/../../config/environment.php");


//$file = "http://localhost/eol_php_code/applications/content_server/resources/FMNH_2010_03_23.xml";
$file = "files/FieldMuseumLichen/FMNH_2010_03_23.xml";
$xml = simplexml_load_file($file);

$i=0;
$wrap="\n";
$wrap="<br>";
print "taxa count = " . count($xml) . "$wrap";

$resource = new Resource(111);//Lichens database - Field Museum Grainger EMU
//exit($resource->id);

$old_resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource->id .".xml";
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

$do_count=0;
foreach($xml->taxon as $t)
{
    $i++;    
    print "$i ";    
    
    //if($i >= 1 and $i <= 100)//debug
    if(true)//true operation    
    {                   
        if(true)//true operation
        {
            /* no namespaces used
            $t_dwc = $t->children("http://rs.tdwg.org/dwc/dwcore/");                         
            $t_dc = $t->children("http://purl.org/dc/elements/1.1/");        
            */
        
            $identifier = Functions::import_decode($t->EOL_Identifier);    
            $source     = "";    
            $kingdom    = Functions::import_decode($t->ClaKingdom);    
            $phylum     = Functions::import_decode($t->ClaPhylum);    
            $class      = Functions::import_decode($t->ClaClass);    
            $order      = Functions::import_decode($t->ClaOrder);    
            $family     = Functions::import_decode($t->ClaFamily);    
            $genus     = Functions::import_decode($t->ClaGenus);                
            $sciname    = Functions::import_decode($t->ClaScientificName);               
           
            $taxonParameters = array();
            $taxonParameters["identifier"]      = utf8_encode($identifier);
            $taxonParameters["source"]          = utf8_encode($source);
            $taxonParameters["kingdom"]         = Functions::import_decode($kingdom);
            $taxonParameters["phylum"]          = Functions::import_decode($phylum);
            $taxonParameters["class"]           = Functions::import_decode($class);
            $taxonParameters["order"]           = Functions::import_decode($order);
            $taxonParameters["family"]          = Functions::import_decode($family);
            $taxonParameters["genus"]           = Functions::import_decode($genus);
            $taxonParameters["scientificName"]  = Functions::import_decode($sciname);    
            
            /* no synonyms
            $taxonParameters["synonyms"] = array();
            foreach($t->synonym as $syn)
            {
                $taxonParameters["synonyms"][] = new SchemaSynonym(array("synonym" => $syn, "relationship" => $url = $syn["relationship"]));                
            }
            */
            
            //start process dataObjects =====================================================================
            $taxonParameters["dataObjects"] = array();    
            $dataObjects = array();

            $subject="http://rs.tdwg.org/ontology/voc/SPMInfoItems#GeneralDescription";
            $dataType = "http://purl.org/dc/dcmitype/Text";
            $mimeType = "text/html";
    
            //print"<hr>$t->PhysDescription<hr>";            
                        
            $title = "";
            if($desc = $t->EOL_GeneralDescription)
            {   $do_count++;
                $do_identifier = $identifier . "_GenDesc;
                $dataObjects[] = get_data_object($desc,$do_identifier,$subject,$dataType,$mimeType,$title);            
            }                

            $title = "Physical Description";
            if($desc = $t->EOL_PhysDescription)
            {   $do_count++;
                $do_identifier = $identifier . "_PhysDesc";
                $dataObjects[] = get_data_object($desc,$do_identifier,$subject,$dataType,$mimeType,$title);            
            }                
            
            $title = "Type specimen information";
            if($desc = $t->PhysDescription)
            {   $do_count++;
                $do_identifier = $identifier . "_TypeSpecimen";
                $dataObjects[] = get_data_object($desc,$do_identifier,$subject,$dataType,$mimeType,$title);            
            }    
            
            //stat images =================================================================================================
            $subject="";
            $dataType = "http://purl.org/dc/dcmitype/StillImage";
            $mimeType = "";
            $title = "";

            //print"<hr>";
            //<Img_1> <Caption_1> <mimeType_1>

            for ($j = 1; $j <= 10; $j++) 
            {
                $img_str="Img_$j"; $cap_str="Caption_$j"; $mim_str="mimeType_$j";   
                //print $t->$img_str . "<br>";   
                if($t->$img_str != "")
                {
                    $mediaURL = str_ireplace("(", "", $t->$img_str);                    
                    $id = parse_url($mediaURL, PHP_URL_QUERY);
                    $id = trim(substr($id,stripos($id,"=")+1,strlen($id)));
                    $mediaURL = $image_url . $id;                                        

                    $desc  = Functions::import_decode($t->$cap_str);
                    $mimeType = Functions::import_decode($t->$mim_str);
                    
                    $do_count++;
                    $do_identifier = $identifier . "_" . $do_count;
                    $dataObjects[] = get_data_object($desc,$do_identifier,$subject,$dataType,$mimeType,$title,$mediaURL);            
                }
            }
            
            //print"<hr>";
            //print"<pre>";print_r($img);print"</pre>";
            //exit;            
            //end images =================================================================================================
            
            foreach($dataObjects as $k => $v)
            {
                $taxonParameters["dataObjects"][] = new SchemaDataObject($v);
                unset($v);
            }
            //end process dataObjects =====================================================================        

            ///////////////////////////////////////////////////////////////////////////////////    
            $taxa = array();
            $taxa[] = new SchemaTaxon($taxonParameters);    
        
            //$new_resource_xml = SchemaDocument::get_taxon_xml($taxa);
            $str='';
            foreach($taxa as $tax)
            {
                $str .= $tax->__toXML();            
            }        
            fwrite($OUT, $str);
            
            print utf8_decode("$sciname") . $wrap;
            
            ///////////////////////////////////////////////////////////////////////////////////            
        }//if($do > 0)
    }    
    else{break;}
}

$str = "</response>";
fwrite($OUT, $str);
fclose($OUT);


$elapsed_time_sec = microtime(1)-$timestart;
echo "$wrap";
echo "elapsed time = $elapsed_time_sec sec              $wrap";
echo "elapsed time = " . $elapsed_time_sec/60 . " min   $wrap";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hr $wrap";

exit("$wrap$wrap Done processing.");
//######################################################################################################################
//######################################################################################################################
//######################################################################################################################

//==========================================================================================
function get_data_object($desc,$do_identifier,$subject,$dataType,$mimeType,$title,$mediaURL=NULL)
{

    $dataObjectParameters = array();
    $dataObjectParameters["identifier"] = $do_identifier;
    $dataObjectParameters["dataType"]   = $dataType;
    $dataObjectParameters["mimeType"]   = $mimeType;    
    $dataObjectParameters["title"]      = $title;        
    $dataObjectParameters["language"]      = "en";    

    $dataObjectParameters["description"] = $desc;
    
    if($subject != "")
    {
        $dataObjectParameters["subjects"] = array();
        $subjectParameters = array();
        $subjectParameters["label"] = $subject;
        $dataObjectParameters["subjects"][] = new SchemaSubject($subjectParameters);
    }
    
    /*
    $agents = array();
    foreach($do->agent as $agent)
    {  
        $agentParameters = array();
        $agentParameters["role"]     = $agent["role"];
        $agentParameters["homepage"] = $agent["homepage"];
        $agentParameters["logoURL"]  = $agent["logoURL"];        
        $agentParameters["fullName"] = Functions::import_decode($agent);            
        $agents[] = new SchemaAgent($agentParameters);
    }
    $dataObjectParameters["agents"] = $agents;    
    */
    
    $dataObjectParameters["license"]       = "http://creativecommons.org/licenses/by-nc-sa/3.0/";
    
    /*    
    $dataObjectParameters["created"]       = $do->created;
    $dataObjectParameters["modified"]      = $do->modified;    
    $dataObjectParameters["rightsHolder"]  = Functions::import_decode($t_dcterms->rightsHolder);    
    $dataObjectParameters["source"]        = $t_dc2->source;    
    $dataObjectParameters["thumbnailURL"]  = $do->thumbnailURL;
    $dataObjectParameters["location"]      = Functions::import_decode($do->location);              
    */
    
    if($mimeType != "text/html")$dataObjectParameters["mediaURL"] = $mediaURL;
    
    ///////////////////////////////////
    $dataObjectParameters["audiences"] = array();    
    $audienceParameters = array();
  
    $audienceParameters["label"] = "Expert users";
    $dataObjectParameters["audiences"][] = new SchemaAudience($audienceParameters);
  
    $audienceParameters["label"] = "General public";
    $dataObjectParameters["audiences"][] = new SchemaAudience($audienceParameters);
       ///////////////////////////////////

    return $dataObjectParameters;
}
?>