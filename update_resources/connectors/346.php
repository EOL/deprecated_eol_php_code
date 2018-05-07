<?php
namespace php_active_record;
/* connector for National Museum of Natural History Image Collection - part of 120 176 341 342 343 344 346
estimated execution time: 6.9 hours
Connector reads the XML provided by partner and 
- sets the image rating.
- If needed ingests TypeInformation text dataObjects

346	Monday 2017-12-04 12:38:00 AM	{"agent.tab":106,"media_resource.tab":510761,"reference.tab":71767,"taxon.tab":109583} - Mac Mini
346	Monday 2017-12-04 01:01:00 AM	{"agent.tab":96, "media_resource.tab":494622,"reference.tab":70199,"taxon.tab":107355} - eol-archive
346	Sunday 2018-04-08 11:17:19 AM	{"agent.tab":40,"media_resource.tab":36359,"reference.tab":1930,"taxon.tab":11966} eol-archive, no more text objects
346	Monday 2018-05-07 03:10:22 AM	{"agent.tab":33,"media_resource.tab":35867,                     "taxon.tab":11869} eol-archive, no more references for text objects


IMPORTANT: since 346 has a big resource (nmnh-botany-response.xml.gz) it can't be processed similarly like 120, 176, 341, 342, 343, 344.
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
$GLOBALS['ENV_DEBUG'] = true;
// require_library('ResourceDataObjectElementsSetting'); --- not used anymore here, see IMPORTANT
$timestart = time_elapsed();
$resource_id = 346;
require_library('connectors/ConvertEOLtoDWCaAPI');

/* ================================================ test resource ================================================
// $params["eol_xml_file"] = 'http://localhost/eol_php_code/applications/content_server/resources/eli.xml.zip';
// $params['xmlYN']        = false;
// $params["filename"]     = "eli.xml";

$params["eol_xml_file"] = 'http://localhost/eol_php_code/applications/content_server/resources/eli.xml';
$params['xmlYN'] = true;
$params["filename"]     = "eli.xml";
================================================================================================ */

// /* ================================================ actual resource ================================================
// $resource_path = "http://localhost/cp/OpenData/EOLxml_2_DWCA/nmnh-botany-response.xml.gz";
// $resource_path = Functions::get_accesspoint_url_if_available($resource_id, "http://collections.mnh.si.edu/services/eol/nmnh-botany-response.xml.gz"); //Botany Resource
$resource_path = "http://collections.mnh.si.edu/services/eol/nmnh-botany-response.xml.gz";

$params["eol_xml_file"] = $resource_path;
$params['xmlYN']        = false;
$params["filename"]     = "nmnh-botany-response.xml";
// ================================================================================================ */

$params["dataset"]      = 'NMNH Botany';
$params["resource_id"]  = $resource_id;
$func = new ConvertEOLtoDWCaAPI($resource_id);
$func->export_xml_to_archive($params, $params['xmlYN'], 60*60*24*25); // true => means it is an XML file, not an archive file nor a zip file. IMPORTANT: Expires now = 0. Currently expires after 25 days

$deleteYN = true; //before was false, coz we can't afford to delete NMNH departmental folders bec. we need it in processing media extension from type speciemen resource 891.
Functions::finalize_dwca_resource($resource_id, false, $deleteYN);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = $elapsed_time_sec seconds             \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes  \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\n\n Done processing.";
?>
