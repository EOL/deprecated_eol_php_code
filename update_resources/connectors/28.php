<?php
namespace php_active_record;
/* connector for: Alaska Fisheries Science Center
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();

$resource_id = 28;

//start converting to DwCA
require_library('connectors/ConvertEOLtoDWCaAPI');
$params["eol_xml_file"] = "http://services.eol.org/resources/28.xml";
$params["filename"]     = "no need to mention here.xml"; //no need to mention if eol_xml_file is already .xml and not .xml.gz
$params["dataset"]      = "EOL XML files";
$params["resource_id"]  = $resource_id;

$func = new ConvertEOLtoDWCaAPI($resource_id);
$func->export_xml_to_archive($params, true, 60*60*24*25); // true => means it is an XML file, not an archive file nor a zip file. Expires in 25 days.
Functions::finalize_dwca_resource($resource_id, false, true); //3rd param true means to delete the dwca folder.
//end conversion

$elapsed_time_sec = time_elapsed()-$timestart;
echo "\n";
echo "elapsed time = $elapsed_time_sec seconds             \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes  \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\n\n Done processing.";
?>