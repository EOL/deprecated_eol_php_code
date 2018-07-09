<?php
namespace php_active_record;
/* http://www.eol.org/content_partners/285/resources/200 - DATA-
This is a generic script that will convert EOL XML to EOL DWC-A

200	Monday 2018-07-09 05:49:39 AM	{"agent.tab":35,"media_resource.tab":13774,"reference.tab":1,"taxon.tab":1158} - eol-archive

*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/ConvertEOLtoDWCaAPI');
$timestart = time_elapsed();

$resource_id = 200;
// $params["eol_xml_file"] = "http://localhost/cp/Bioimages_Vanderbilt/eol-harvest.xml";
$params["eol_xml_file"] = "http://bioimages.vanderbilt.edu/eol-harvest.xml";
$params["filename"]     = "no need to mention here.xml";
$params["dataset"]      = "";
$params["resource_id"]  = $resource_id;

$func = new ConvertEOLtoDWCaAPI($resource_id);
$func->export_xml_to_archive($params, true, 60*60*24*25); // true => means it is an XML file, not an archive file nor a zip file. Expires in 25 days.
Functions::finalize_dwca_resource($resource_id, false, true);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>