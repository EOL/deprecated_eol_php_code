<?php
namespace php_active_record;
/* http://eol.org/content_partners/32/resources/85 - https://eol-jira.bibalex.org/browse/DATA-1705
This is a generic script that will convert EOL XML to EOL DWC-A
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/ConvertEOLtoDWCaAPI');
$timestart = time_elapsed();

$resource_id = 85;
$params["eol_xml_file"] = "";
$params["eol_xml_file"] = "http://opendata.eol.org/dataset/fff84c38-bd30-434a-93bb-cab84a3d2db8/resource/ad9bad24-5452-46b3-84c6-5ea2fed6c073/download/northamericanmammals.xml";
$params["filename"]     = "no need to mention here.xml"; //no need to mention if eol_xml_file is already .xml and not .xml.gz
$params["dataset"]      = "EOL XML files";
$params["resource_id"]  = $resource_id;

$func = new ConvertEOLtoDWCaAPI($resource_id);
$func->export_xml_to_archive($params, true, 60*60*24*25); // true => means it is an XML file, not an archive file nor a zip file. Expires in 25 days.
Functions::finalize_dwca_resource($resource_id);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>