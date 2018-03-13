<?php
namespace php_active_record;
/* connector for Efloras
estimated execution time: 5 minutes
This script will modify the original Efloras resource (17_orig.xml).
    - change subject GeneralDescript to Morphology
    - remove all references
    - splits the "habitat & distribution" into "habitat" and "distribution", each a <dataObject> of its own
    - then split habitat further into #cyclicity (flowering-time) and #habitat
    - re-map text objects with title 'Comments' to http://www.eol.org/voc/table_of_contents#Notes
    - change schema ver. from 0.1 to 0.3
17	Tuesday 2018-03-13 02:08:41 AM	{"agent.tab":19,"media_resource.tab":124726,"taxon.tab":32792}
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('ResourceDataObjectElementsSetting');

$timestart = time_elapsed();
$resource_id = 17;
// $resource_path = "http://localhost/~eolit/eol_php_code/applications/content_server/resources/17_test.xml"; //test data
// $resource_path = "http://dl.dropbox.com/u/7597512/resources/17_orig.xml.gz";
// $resource_path = "http://services.eol.org/resources/17.xml";
// $resource_path = "http://localhost/cp_new/services.eol.org_xml/17.xml";
$resource_path = "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/services.eol.org_resources/17.xml.gz";

$func = new ResourceDataObjectElementsSetting($resource_id, $resource_path);
$xml = $func->load_xml_string(false); //false here means expire_seconds set to false, meaning it won't expire.

//removes the <reference> entries, faster this way than to loop each entry.
$xml = preg_replace("/<reference (.*?)>/ims", "<reference>", $xml);
$xml = preg_replace("/<reference>(.*?)<\/reference>/ims", "", $xml);

//replace schema 0.1 to 0.3 because the new resource uses <additionalInformation> element
$xml = str_ireplace("/transfer/content/0.1", "/transfer/content/0.3", $xml);
$xml = str_ireplace("/content_0_1.xsd", "/content_0_3.xsd", $xml);

//re-maps the #GeneralDescription to #Morphology
$xml = str_ireplace("<subject>http://rs.tdwg.org/ontology/voc/SPMInfoItems#GeneralDescription</subject>", "<subject>http://rs.tdwg.org/ontology/voc/SPMInfoItems#Morphology</subject>", $xml);

//splits the "habitat & distribution" into "habitat" and "distribution", each a <dataObject> of its own
$xml = split_habitat_and_distribution($xml);

$resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml";
if(!($OUT = Functions::file_open($resource_path, "w"))) return;
fwrite($OUT, $xml);
fclose($OUT);

// Functions::set_resource_status_to_harvest_requested($resource_id);

//start converting to DwCA
require_library('connectors/ConvertEOLtoDWCaAPI');
$params["eol_xml_file"] = $resource_path;
$params["filename"]     = "no need to mention here.xml"; //no need to mention if eol_xml_file is already .xml and not .xml.gz
$params["dataset"]      = "EOL XML files";
$params["resource_id"]  = $resource_id;

$func = new ConvertEOLtoDWCaAPI($resource_id);
$func->export_xml_to_archive($params, true, 60*60*24*25); // true => means it is an XML file, not an archive file nor a zip file. Expires in 25 days.
Functions::finalize_dwca_resource($resource_id, false, true); //3rd param true means to delete the dwca folder.
//end conversion

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = $elapsed_time_sec seconds             \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes  \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\n\n Done processing.";

function split_habitat_and_distribution($xml_string)
{
    $dc_namespace = "http://purl.org/dc/elements/1.1/";
    $dcterms_namespace = "http://purl.org/dc/terms/";

    $xml = simplexml_load_string($xml_string);
    foreach($xml->taxon as $taxon) {
        foreach($taxon->dataObject as $dataObject) {
            $dataObject_dc = $dataObject->children($dc_namespace);
            $dataObject_dcterms = $dataObject->children($dcterms_namespace);
            // print "\n" . $dataObject_dc->identifier . "\n";
            if($dataObject_dc->title == "Habitat & Distribution") {
                $agents = array();
                foreach($dataObject->agent as $agent) $agents[] = $agent;
                $license = $dataObject->license;
                $dc_rights = $dataObject_dc->rights;
                $dcterms_rightsHolder = $dataObject_dcterms->rightsHolder;
                $dcterms_bibliographicCitation = $dataObject_dcterms->bibliographicCitation;
                $dc_source = $dataObject_dc->source;

                $texts = split_description($dataObject_dc->description); //splits the description into 2: habitat and distribution texts
                if($texts) {
                    $dataObject_dc->title = "Habitat";
                    $dataObject_dc->description = trim($texts[0]); //habitat
                    $text_distribution = trim($texts[1]); //distribution

                    //create a new #Distribution <dataObject>
                    add_dataObject($taxon, $dataObject_dc->identifier . "_distribution", $dc_namespace, $agents,
                    $license, $dc_rights, $dcterms_rightsHolder, $dcterms_namespace, $dcterms_bibliographicCitation,
                    $dc_source, 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Distribution', $text_distribution, 'Distribution');

                    //see if #habitat can further be divided into #cyclicity (flowering/fruiting time) and #habitat.
                    if($texts = get_flowering_time($dataObject_dc->description)) {
                        $dataObject_dc->description = trim($texts[1]); //habitat
                        $text_cyclicity = trim($texts[0]); //cyclicity - flowering/fruiting time
                        //create a new #Cyclicity <dataObject>
                        add_dataObject($taxon, $dataObject_dc->identifier . "_cyclicity", $dc_namespace, $agents,
                        $license, $dc_rights, $dcterms_rightsHolder, $dcterms_namespace, $dcterms_bibliographicCitation,
                        $dc_source, 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Cyclicity', $text_cyclicity, 'Flowering/Fruiting');
                    }
                }
            }

            if($dataObject_dc->title == "Comments") {
                $dataObject->addChild("additionalInformation", "");
                $dataObject->additionalInformation->addChild("subject", "http://www.eol.org/voc/table_of_contents#Notes");
            }

        }
    }
    return $xml->asXML();
}

function get_flowering_time($text)
{
    $texts = array();
    if(substr($text, 0, 9) == 'Flowering' || substr($text, 0, 8) == 'Fruiting') {
        $pos_of_first_period = stripos($text, '.');
        if ($pos_of_first_period !== false) {
            $texts[0] = substr($text, 0, $pos_of_first_period + 1);
            $texts[1] = trim(substr($text, $pos_of_first_period + 2, strlen($text)));
        }
    }
    return $texts;
}

function split_description($text)
{
    $separators = array(' m; ', 'm. '); //possible separators between 'habitat' and 'distribution' string in dc:description
    foreach($separators as $separator) {
        $texts = explode($separator, $text);
        if(count($texts) > 1) {
            $texts[0] .= "m. ";
            return $texts;
        }
    }
    return array();
}

function add_dataObject($taxon, $identifier, $dc_namespace, $agents, $license, $dc_rights, $dcterms_rightsHolder, $dcterms_namespace, 
    $dcterms_bibliographicCitation, $dc_source, $subject, $text_distribution, $dc_title)
{
    $obj = $taxon->addChild('dataObject');
    $obj->addChild('identifier', $identifier, $dc_namespace);
    $obj->addChild('dataType', 'http://purl.org/dc/dcmitype/Text');
    $obj->addChild('mimeType', 'text/html');
    foreach($agents as $agent) {
        $a = $obj->addChild('agent', htmlentities($agent));
        $a->addAttribute('role', $agent['role']);
        $a->addAttribute('logoURL', $agent['logoURL']);
        $a->addAttribute('homepage', $agent['homepage']);
    }
    $obj->addChild('title', $dc_title, $dc_namespace);
    $obj->addChild('license', $license);
    $obj->addChild('rights', $dc_rights, $dc_namespace);
    $obj->addChild('rightsHolder', $dcterms_rightsHolder, $dcterms_namespace);
    $obj->addChild('bibliographicCitation', '', $dcterms_namespace);
    $obj->bibliographicCitation = $dcterms_bibliographicCitation;
    $obj->addChild('source', '', $dc_namespace);
    $obj->source = $dc_source;
    $obj->addChild('subject', $subject);
    $obj->addChild('description', '', $dc_namespace);
    $obj->description = $text_distribution;
}
?>