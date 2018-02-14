<?php
namespace php_active_record;
/* 
http://www.eol.org/content_partners/490/resources/380
https://eol-jira.bibalex.org/browse/TRAM-703
This is a generic script that will convert EOL XML to EOL DWC-A
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/ConvertEOLtoDWCaAPI');
$timestart = time_elapsed();

$resource_id = 380;
$params["eol_xml_file"] = "http://www.planetscott.com/eolexport.xml";
$params["filename"]     = "eolexport.xml";
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