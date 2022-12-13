<?php
/* connector for Biolib.cz
This connector only gets taxa with dataobjects from the external resource XML.
estimated execution time:  3-4 mins.
*/
$timestart = microtime(1);
include_once(dirname(__FILE__) . "/../../config/environment.php");

//$file = "http://www.biolib.cz/DWN/eoldata.xml";
//$file = "http://128.128.175.77/eol_php_code/applications/content_server/resources/eoldata.xml";
$file = "http://pandanus.eol.org/public/EOL_resource/eoldata.xml";
$xml = simplexml_load_file($file);

$i = 0;
$wrap = "\n";
print "taxa count = " . count($xml) . "\n";

$resource = new Resource(11);
$old_resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource->id .".xml";
if(!($OUT = Functions::file_open($old_resource_path, "w+"))) return;

$str = "<?xml version='1.0' encoding='utf-8' ?>\n";
$str .= "<response\n";
$str .= "  xmlns='http://www.eol.org/transfer/content/0.2'\n";
$str .= "  xmlns:xsd='http://www.w3.org/2001/XMLSchema'\n";
$str .= "  xmlns:dc='http://purl.org/dc/elements/1.1/'\n";
$str .= "  xmlns:dcterms='http://purl.org/dc/terms/'\n";
$str .= "  xmlns:geo='http://www.w3.org/2003/01/geo/wgs84_pos#'\n";
$str .= "  xmlns:dwc='http://rs.tdwg.org/dwc/dwcore/'\n";
$str .= "  xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance'\n";
$str .= "  xsi:schemaLocation='http://www.eol.org/transfer/content/0.2 http://services.eol.org/schema/content_0_2.xsd'>\n";
fwrite($OUT, $str);

foreach($xml->taxon as $t)
{
    $i++;
    print "$i $wrap";
    if(true)
    {
        $do_count = sizeof($t->dataObject);
        if($do_count > 0)//true operation # -test eli-
        {
            $t_dwc = $t->children("http://rs.tdwg.org/dwc/dwcore/");
            $t_dc = $t->children("http://purl.org/dc/elements/1.1/");

            $identifier = Functions::import_decode($t_dc->identifier);
            $source     = Functions::import_decode($t_dc->source);
            $kingdom    = Functions::import_decode($t_dwc->Kingdom);
            $phylum     = Functions::import_decode($t_dwc->Phylum);
            $class      = Functions::import_decode($t_dwc->Class);
            $order      = Functions::import_decode($t_dwc->Order);
            $family     = Functions::import_decode($t_dwc->Family);
            $sciname    = Functions::import_decode($t_dwc->ScientificName);

            $taxonParameters = array();
            $taxonParameters["identifier"]      = utf8_encode($identifier);
            $taxonParameters["source"]          = utf8_encode($source);
            $taxonParameters["kingdom"]         = Functions::import_decode($kingdom);
            $taxonParameters["phylum"]          = Functions::import_decode($phylum);
            $taxonParameters["class"]           = Functions::import_decode($class);
            $taxonParameters["order"]           = Functions::import_decode($order);
            $taxonParameters["family"]          = Functions::import_decode($family);
            $taxonParameters["scientificName"]  = Functions::import_decode($sciname);
            
            $taxonParameters["synonyms"] = array();
            foreach($t->synonym as $syn)
            {
                $taxonParameters["synonyms"][] = new \SchemaSynonym(array("synonym" => $syn, "relationship" => $url = $syn["relationship"]));
            }
            
            //start process dataObjects =====================================================================
            $taxonParameters["dataObjects"] = array();
            $dataObjects = array();
            $arr = $t->dataObject;
            foreach($arr as $do)
            {
                $t_dc2      = $do->children("http://purl.org/dc/elements/1.1/");
                $t_dcterms  = $do->children("http://purl.org/dc/terms/");
                //print $do->dataType $t_dc2->identifier $do->agent $do->agent["role"] $do->agent["homepage"] . " ";
                $dataObjects[] = get_data_object($do,$t_dc2,$t_dcterms);
            }    
            foreach($dataObjects as $k => $v)
            {
                $taxonParameters["dataObjects"][] = new \SchemaDataObject($v);
                unset($v);
            }
            //end process dataObjects ===================================================================== 

            ///////////////////////////////////////////////////////////////////////////////////
            $taxa = array();
            $taxa[] = new \SchemaTaxon($taxonParameters);
        
            //$new_resource_xml = SchemaDocument::get_taxon_xml($taxa);
            $str='';
            foreach($taxa as $tax)
            {
                $str .= $tax->__toXML();
            }        
            fwrite($OUT, $str);
            
            //print utf8_encode("$sciname - ") . $do . "\n";
            echo $sciname . "\n";
            ///////////////////////////////////////////////////////////////////////////////////
        }
    }
    else break;
}

$str = "</response>";
fwrite($OUT, $str);
fclose($OUT);

$elapsed_time_sec = microtime(1)-$timestart;
echo "$wrap";
echo "elapsed time = $elapsed_time_sec sec              \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " min   \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hr \n";
echo "\n\n Done processing.";

//==========================================================================================
function get_data_object($do, $t_dc2, $t_dcterms)
{
    $dataObjectParameters = array();
    $dataObjectParameters["identifier"] = $t_dc2->identifier;
    $dataObjectParameters["dataType"]   = $do->dataType;
    $dataObjectParameters["mimeType"]   = $do->mimeType;

    $dataObjectParameters["description"] = $t_dc2->description;
    
    if($do->subject != "")
    {
        $dataObjectParameters["subjects"] = array();
        $subjectParameters = array();
        $subjectParameters["label"] = $do->subject;
        $dataObjectParameters["subjects"][] = new \SchemaSubject($subjectParameters);
    }
    
    $agents = array();
    foreach($do->agent as $agent)
    {  
        $agentParameters = array();
        $agentParameters["role"]     = $agent["role"];
        $agentParameters["homepage"] = $agent["homepage"];
        $agentParameters["logoURL"]  = $agent["logoURL"];
        $agentParameters["fullName"] = Functions::import_decode($agent);
        
        $agents[] = new \SchemaAgent($agentParameters);
    }
    $dataObjectParameters["agents"]        = $agents;
    $dataObjectParameters["created"]       = $do->created;
    $dataObjectParameters["modified"]      = $do->modified;
    $dataObjectParameters["license"]       = $do->license;
    $dataObjectParameters["rightsHolder"]  = Functions::import_decode($t_dcterms->rightsHolder);
    $dataObjectParameters["source"]        = $t_dc2->source;
    $dataObjectParameters["mediaURL"]      = $do->mediaURL;
    $dataObjectParameters["thumbnailURL"]  = $do->thumbnailURL;
    $dataObjectParameters["location"]      = Functions::import_decode($do->location);

    ///////////////////////////////////
    $dataObjectParameters["audiences"] = array();
    $audienceParameters = array();
  
    $audienceParameters["label"] = "Expert users";
    $dataObjectParameters["audiences"][] = new \SchemaAudience($audienceParameters);
  
    $audienceParameters["label"] = "General public";
    $dataObjectParameters["audiences"][] = new \SchemaAudience($audienceParameters);
    ///////////////////////////////////
    return $dataObjectParameters;
}
?>