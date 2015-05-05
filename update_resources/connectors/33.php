<?php
namespace php_active_record;
/* connector for AskNature - Biomimicry
This connector re-maps the SPM and title of the external resource XML.
estimated execution time: 5 secs.
*/
$timestart = microtime(1);

include_once(dirname(__FILE__) . "/../../config/environment.php");


$file = "http://www.asknature.org/organism/export/eol.xml";
//$file = "http://127.0.0.1/eol_php_code/applications/content_server/resources/33_small.xml";
$xml = simplexml_load_file($file);

$i=0;
$wrap="\n";
print "taxa count = " . count($xml) . "\n";

$resource_id = 33;//AskNature - Biomimicry

$old_resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource_id .".xml";
if(!($OUT = fopen($old_resource_path, "w+")))
{
  debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .$old_resource_path);
  return;
}

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

foreach($xml->taxon as $t)
{
    $i++;    
    print "$wrap $i ";    
    
    //if($i >= 1 and $i <= 10)//debug
    if(true)//true operation    
    {                   
        $do_count = sizeof($t->dataObject);        
        
        //if($do_count > 0)//true operation
        if(true)//debug
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
            $genus      = Functions::import_decode($t_dwc->Genus);    
            $sciname    = Functions::import_decode($t_dwc->ScientificName);               
            $created    = Functions::import_decode($t_dwc->created);               
            $modified   = Functions::import_decode($t_dwc->modified);               
           
            $taxonParameters = array();
            $taxonParameters["identifier"]      = $identifier;
            $taxonParameters["source"]          = $source;
            $taxonParameters["kingdom"]         = $kingdom;
            $taxonParameters["phylum"]          = $phylum;
            $taxonParameters["class"]           = $class;
            $taxonParameters["order"]           = $order;
            $taxonParameters["family"]          = $family;
            $taxonParameters["genus"]           = $genus;
            $taxonParameters["scientificName"]  = $sciname;                
            $taxonParameters["created"]         = $created;
            $taxonParameters["modified"]        = $modified;            
            
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
        }//if($do > 0)
    }    
    else{break;}
}

$str = "</response>";
fwrite($OUT, $str);
fclose($OUT);


$elapsed_time_sec = microtime(1)-$timestart;
echo "$wrap";
echo "elapsed time = $elapsed_time_sec sec              \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " min   \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hr \n";

echo "$wrap$wrap Done processing.";

//==========================================================================================
function get_data_object($do,$t_dc2,$t_dcterms)
{
    $dataObjectParameters = array();
    $dataObjectParameters["identifier"] = $t_dc2->identifier;
    $dataObjectParameters["dataType"]   = $do->dataType;
    $dataObjectParameters["mimeType"]   = $do->mimeType;    

    $description = str_ireplace('<strong>','',$t_dc2->description);
    $description = str_ireplace('</strong>','',$description);    
    
    $dataObjectParameters["description"] = "<strong>$t_dc2->title</strong>  <br>  $description";
    $dataObjectParameters["title"] = "Functional adaptation";
    $dataObjectParameters["language"] = $t_dc2->language;

    //this overwrites whatever is given by AskNature, currently it is GenDesc    
    $dataObjectParameters["subjects"] = array();
    $subjectParameters = array();
    $subjectParameters["label"] = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Evolution";
    $dataObjectParameters["subjects"][] = new \SchemaSubject($subjectParameters);

    $dataObjectParameters["additionalInformation"] = "<subject>http://www.eol.org/voc/table_of_contents#FunctionalAdaptations</subject>";
    ///////////////////////////////////////////////////////////////////////
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
    $dataObjectParameters["agents"] = $agents;    
    ///////////////////////////////////////////////////////////////////////
    
    $dataObjectParameters["created"]       = $do->created;
    $dataObjectParameters["modified"]      = $do->modified;
    $dataObjectParameters["license"]       = $do->license;
    $dataObjectParameters["rights"]       = $do->rights;
    $dataObjectParameters["rightsHolder"]  = Functions::import_decode($t_dcterms->rightsHolder);    
    $dataObjectParameters["source"]        = $t_dc2->source;
    $dataObjectParameters["mediaURL"]      = $do->mediaURL;
    $dataObjectParameters["thumbnailURL"]  = $do->thumbnailURL;
    $dataObjectParameters["location"]      = Functions::import_decode($do->location);              

    ///////////////////////////////////////////////////////////////////////
    $dataObjectParameters["audiences"] = array();    
    $audienceParameters = array();  
    $audienceParameters["label"] = $do->audience;
    $dataObjectParameters["audiences"][] = new \SchemaAudience($audienceParameters);  
    ///////////////////////////////////////////////////////////////////////
    
    
    ///////////////////////////////////////////////////////////////////////
    $dataObjectParameters["references"] = array();        
    $refs=array();
    foreach($do->reference as $ref)
    {
        $referenceParameters = array();
        $referenceParameters["fullReference"] = $ref;
        if(@$agent["URL"] || @$agent["ISBN"])
        {
            $referenceParameters["referenceIdentifiers"][] = new \SchemaReferenceIdentifier(array("label" => "url" , "value" => self::format(@$agent["URL"])));                                
            $referenceParameters["referenceIdentifiers"][] = new \SchemaReferenceIdentifier(array("label" => "isbn" , "value" => self::format(@$agent["ISBN"])));                                
        }            
        $refs[] = new \SchemaReference($referenceParameters);
    }
    $dataObjectParameters["references"] = $refs;
    ///////////////////////////////////////////////////////////////////////
    return $dataObjectParameters;
}
?>