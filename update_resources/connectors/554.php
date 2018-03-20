<?php
namespace php_active_record;
/* http://eol.org/content_partners/587/resources/554 - DATA-1622
This is a generic script that will convert EOL XML to EOL DWC-A
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");

//used collections_generic.php instead.
return;

require_library('connectors/ConvertEOLtoDWCaAPI');
$timestart = time_elapsed();

$resource_id = 554;
$params["eol_xml_file"] = "";
$params["eol_xml_file"] = Functions::get_accesspoint_url_if_available($resource_id, "http://www.pensoft.net/J_FILES/EoLData/CompCytoGen.xml");
$params["filename"]     = "no need to mention here.xml";
$params["dataset"]      = "Pensoft XML files";
$params["resource_id"]  = $resource_id;

$func = new ConvertEOLtoDWCaAPI($resource_id);
$func->export_xml_to_archive($params, true); // true => means it is an XML file, not an archive file nor a zip file
Functions::finalize_dwca_resource($resource_id);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>