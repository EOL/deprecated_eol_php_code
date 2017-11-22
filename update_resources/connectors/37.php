<?php
namespace php_active_record;
/*connector for [USDA PLANTS images] http://www.eol.org/content_partners/36/resources/37
https://opendata.eol.org/dataset/usda_plants/resource/fcbdd9c9-f33f-4ec6-8c9f-218510e74d04
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
$resource_id = 37;
$GLOBALS['ENV_DEBUG'] = true;

require_library('connectors/ConvertEOLtoDWCaAPI');
$params["eol_xml_file"] = "https://opendata.eol.org/dataset/42fd51a0-e31a-4b2a-9f18-6e4f08242d42/resource/fcbdd9c9-f33f-4ec6-8c9f-218510e74d04/download/usda-plants-images.xml.zip";
$params["filename"]     = "USDA PLANTS images 2.xml"; //extract usda-plants-images.xml.zip to get this filename
$params["dataset"]      = "USDA resource";
$params["resource_id"]  = $resource_id;
$func = new ConvertEOLtoDWCaAPI($resource_id);
$func->export_xml_to_archive($params, false, 0); // false => means it is NOT an XML file, BUT an archive file OR a zip file. IMPORTANT: Expires now = 0.

$deleteYN = true; //true means delete the DwCA folder in /resources/
Functions::finalize_dwca_resource($resource_id, false, $deleteYN);
Functions::set_resource_status_to_harvest_requested($resource_id);

// Functions::delete_if_exists($params["eol_xml_file"]);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
?>