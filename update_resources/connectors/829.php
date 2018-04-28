<?php
namespace php_active_record;
/* http://eol.org/content_partners/22/resources/829 - DATA-1622
This is a generic script that will convert EOL XML to EOL DWC-A
DATA-1702
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/ConvertEOLtoDWCaAPI');
$timestart = time_elapsed();

$resource_id = 829;
$params["eol_xml_file"] = "";
$params["eol_xml_file"] = Functions::get_accesspoint_url_if_available($resource_id, "https://zookeys.pensoft.net/lib/eol_exports/ZK.xml");
$params["filename"]     = "no need to mention here.xml";
$params["dataset"]      = "Pensoft XML files";
$params["resource_id"]  = $resource_id;

$func = new ConvertEOLtoDWCaAPI($resource_id);
$func->export_xml_to_archive($params, true, 60*60*24*5); // true => means it is an XML file, not an archive file nor a zip file. Expires in 5 days.
Functions::finalize_dwca_resource($resource_id, false, true);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>