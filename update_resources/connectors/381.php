<?php
namespace php_active_record;
/* 

http://www.eol.org/content_partners/455/resources/381

This is a generic script that will convert EOL XML to EOL DWC-A

*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/ConvertEOLtoDWCaAPI');
$timestart = time_elapsed();

$resource_id = 381;
$params["eol_xml_file"] = "http://services.eol.org/resources/381.xml";
$params["filename"]     = "381.xml"; //need not to mention 381.xml here because eol_xml_file is already 381.xml
$params["dataset"]      = "EOL XML files";
$params["resource_id"]  = $resource_id;

$func = new ConvertEOLtoDWCaAPI($resource_id);
$func->export_xml_to_archive($params, true, false); // true => means it is an XML file, not an archive file nor a zip file. 3rd param false means won't expire
Functions::finalize_dwca_resource($resource_id, false, true);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>