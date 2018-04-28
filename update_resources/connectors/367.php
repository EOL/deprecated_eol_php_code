<?php
namespace php_active_record;
/* https://eol-jira.bibalex.org/browse/DATA-1702 - xml files to transform
This is a generic script that will convert EOL XML to EOL DWC-A
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$GLOBALS['ENV_DEBUG'] = true;
require_library('connectors/ConvertEOLtoDWCaAPI');
$timestart = time_elapsed();
// exit("\nStopped. Just one-time import.\n");
/* Sample way to access the generic script of converting EOL XML to EOL DWCA
$params["eol_xml_file"] = "http://localhost/cp/EOL_China/FaunaSinica_Aves.zip";
$params["eol_xml_file"] = "https://dl.dropboxusercontent.com/u/7597512/EOL_China/FaunaSinica_Aves.zip";
$params["filename"]     = "FaunaSinica_Aves.xml";
$params["dataset"]      = "EOL China";
$params["resource_id"]  = 412;
*/

// $params["eol_xml_file"] = "http://localhost/cp/OpenData/EOLxml_2_DWCA/DC Birds Video/dcbirds-video-xml-resource.xml";      //works OK. This is local copy of XML. OpenData copy was already removed.
// $params["eol_xml_file"] = "http://localhost/cp/OpenData/EOLxml_2_DWCA/DC Birds Video/dcbirds-video-xml-resource.xml.zip";  //works OK also

// this one is removed from OpenData already but I have a copy localy above.
$resource_id = 367;
// $params["eol_xml_file"] = "http://opendata.eol.org/dataset/9676aab5-bef0-4b55-b626-911f49553337/resource/e439db03-c92a-49c2-bcb0-7e1aec4ebda2/download/dcbirds-video-xml-resource.xml";
// $params["eol_xml_file"] = "http://services.eol.org/resources/367.xml"; //salvaged by Jen
$params["eol_xml_file"] = "https://raw.githubusercontent.com/eliagbayani/EOL-connector-data-files/master/OpenData/EOLxml_2_DWCA/DC Birds Video/dcbirds-video-xml-resource.xml"; //edited by Jen: dropbox.com to media.eol.org

$params["filename"]     = "dcbirds-video-xml-resource.xml";
$params["dataset"]      = "EOL XML";
$params["resource_id"]  = $resource_id;

$func = new ConvertEOLtoDWCaAPI($resource_id);
$func->export_xml_to_archive($params, true, 0); //true here means it is an XML file and not .zip nor .gzip | 3rd param 0 means cache expires now.
Functions::finalize_dwca_resource($resource_id, false, true);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>