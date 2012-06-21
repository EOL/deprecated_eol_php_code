<?php
namespace php_active_record;
/* connector for Efloras
estimated execution time: 3 minutes
This script will modify the original Efloras resource (17_orig.xml).
    - change subject GeneralDescript to Morphology
    - remove all references
    - splits the "habitat & distribution" into "habitat" and "distribution", each a <dataObject> of its own
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('ResourceDataObjectElementsSetting');

$timestart = time_elapsed();
$resource_id = 17;
// $resource_path = "http://localhost/~eolit/eol_php_code/applications/content_server/resources/17_test.xml"; //test data
$resource_path = "http://dl.dropbox.com/u/7597512/resources/17_orig.xml.gz";

$func = new ResourceDataObjectElementsSetting($resource_id, $resource_path);
$xml = $func->load_xml_string();

//removes the <reference> entries, faster this way than to loop each entry.
$xml = preg_replace("/<reference (.*?)>/ims", "<reference>", $xml);
$xml = preg_replace("/<reference>(.*?)<\/reference>/ims", "", $xml);

//re-maps the #GeneralDescription to #Morphology
$xml = str_ireplace("<subject>http://rs.tdwg.org/ontology/voc/SPMInfoItems#GeneralDescription</subject>", "<subject>http://rs.tdwg.org/ontology/voc/SPMInfoItems#Morphology</subject>", $xml);

//splits the "habitat & distribution" into "habitat" and "distribution", each a <dataObject> of its own
$xml = split_habitat_and_distribution($xml);

$resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml";
$OUT = fopen($resource_path, "w");
fwrite($OUT, $xml);
fclose($OUT);

Functions::set_resource_status_to_force_harvest($resource_id);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = $elapsed_time_sec seconds             \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes  \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
exit("\n\n Done processing.");

function split_habitat_and_distribution($xml_string)
{
    $dc_namespace = "http://purl.org/dc/elements/1.1/";
    $dcterms_namespace = "http://purl.org/dc/terms/";
    
    $xml = simplexml_load_string($xml_string);
    foreach($xml->taxon as $taxon)
    {
        foreach($taxon->dataObject as $dataObject)
        {
            $dataObject_dc = $dataObject->children($dc_namespace);
            $dataObject_dcterms = $dataObject->children($dcterms_namespace);
            
            if($dataObject_dc->title == "Habitat & Distribution")
            {
                //start storing
                $identifier = $dataObject_dc->identifier . "_distribution";
                $agents = array();
                foreach($dataObject->agent as $agent) $agents[] = $agent;
                $license = $dataObject->license;
                $dc_rights = $dataObject_dc->rights;
                $dcterms_rightsHolder = $dataObject_dcterms->rightsHolder;
                $dcterms_bibliographicCitation = $dataObject_dcterms->bibliographicCitation;
                $dc_source = $dataObject_dc->source;
                
                $texts = split_description($dataObject_dc->description); //splits the description into 2: habitat and distribution texts
                if($texts)
                {
                    $dataObject_dc->title = "Habitat";
                    $dataObject_dc->description = $texts[0]; //habitat
                    $text_distribution = $texts[1]; //distribution

                    //create a new #Distribution <dataObject>
                    $obj = $taxon->addChild('dataObject');
                    $obj->addChild('identifier', $identifier, $dc_namespace);
                    $obj->addChild('dataType', 'http://purl.org/dc/dcmitype/Text');
                    $obj->addChild('mimeType', 'text/html');
                    foreach($agents as $agent)
                    {
                        $a = $obj->addChild('agent', htmlentities($agent));
                        $a->addAttribute('role', $agent['role']);
                        $a->addAttribute('logoURL', $agent['logoURL']);
                        $a->addAttribute('homepage', $agent['homepage']);
                    }
                    $obj->addChild('title', 'Distribution', $dc_namespace);
                    $obj->addChild('license', $license);
                    $obj->addChild('rights', $dc_rights, $dc_namespace);
                    $obj->addChild('rightsHolder', $dcterms_rightsHolder, $dcterms_namespace);
                    $obj->addChild('bibliographicCitation', '', $dcterms_namespace);
                    $obj->bibliographicCitation = $dcterms_bibliographicCitation;
                    $obj->addChild('source', '', $dc_namespace);
                    $obj->source = $dc_source;
                    $obj->addChild('subject', 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Distribution');
                    $obj->addChild('description', '', $dc_namespace);
                    $obj->description = $text_distribution;
                }
                //end storing
            }
        }
    }
    return $xml->asXML();
}

function split_description($text)
{
    $separators = array('m; ', 'm. '); //possible separators between 'habitat' and 'distribution' string in dc:description
    foreach($separators as $separator)
    {
        $texts = explode($separator, $text);
        if(count($texts) > 1) 
        {
            $texts[0] .= $separator;
            return $texts;
        }
    }
    return array();
}

?>