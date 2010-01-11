<?php
//#!/usr/local/bin/php
//connector for Biolib.cz

//exit;

set_time_limit(0);
ini_set('memory_limit','3000M');
//define("ENVIRONMENT", "slave_32");
//define("ENVIRONMENT", "development");
define("MYSQL_DEBUG", true);
define("DEBUG", true);
include_once(dirname(__FILE__) . "/../../config/start.php");

//$file = "http://127.0.0.1/mtce/biolib_cz/txt/eoldata.xml";
$file = "http://www.biolib.cz/DWN/eoldata.xml";
$xml = simplexml_load_file($file);

$i=0;
$wrap="\n";
print "taxa count = " . count($xml) . "\n";

$resource = new Resource(11);//Biolib.cz
$old_resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource->id .".xml";
$OUT = fopen($old_resource_path, "w+");

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
    //if($i >= 100 and $i <= 105)
    if(true)    
    {                   
        $do_count = sizeof($t->dataObject);
        if($do_count > 0)
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
            $taxonParameters["kingdom"]         = utf8_encode($kingdom);
            $taxonParameters["phylum"]          = utf8_encode($phylum);
            $taxonParameters["class"]           = utf8_encode($class);
            $taxonParameters["order"]           = utf8_encode($order);
            $taxonParameters["family"]          = utf8_encode($family);
            $taxonParameters["scientificName"]  = utf8_encode($sciname);    

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
            
            //print utf8_encode("$sciname - ") . $do . "\n";            
            echo $sciname . "\n";      
            ///////////////////////////////////////////////////////////////////////////////////            
        }//if($do > 0)
    }    
}

$str = "</response>";
fwrite($OUT, $str);
fclose($OUT);

function get_data_object($do,$t_dc2,$t_dcterms)
{
    /*
    print $do->agent . " ";  
    print $do->agent["role"] . " \n";  
    print $do->agent["homepage"] . " ";  
    */

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
        $dataObjectParameters["subjects"][] = new SchemaSubject($subjectParameters);
    }
    
    $agents = array();
    foreach($do->agent as $agent)
    {  
        $agentParameters = array();
        $agentParameters["role"]     = $agent["role"];
        $agentParameters["homepage"] = $agent["homepage"];
        $agentParameters["logoURL"]  = $agent["logoURL"];        
        $agentParameters["fullName"] = $agent;
        $agents[] = new SchemaAgent($agentParameters);
    }
    $dataObjectParameters["agents"] = $agents;    
    
    $dataObjectParameters["created"]       = $do->created;
    $dataObjectParameters["modified"]      = $do->modified;
    $dataObjectParameters["license"]       = $do->license;
    $dataObjectParameters["rightsHolder"]  = $t_dcterms->rightsHolder;    
    $dataObjectParameters["source"]        = $t_dc2->source;
    $dataObjectParameters["mediaURL"]      = $do->mediaURL;
    $dataObjectParameters["thumbnailURL"]  = $do->thumbnailURL;
    $dataObjectParameters["location"]      = $do->location;              

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