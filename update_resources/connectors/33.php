<?php
namespace php_active_record;
/* 
http://www.eol.org/content_partners/41/resources/33
This is a generic script that will convert EOL XML to EOL DWC-A

33	Wednesday 2018-02-14 09:28:24 AM	{"agent.tab":22,"media_resource.tab":1438,"reference.tab":1008,"taxon.tab":970}
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/ConvertEOLtoDWCaAPI');
$timestart = time_elapsed();

$resource_id = 33;
$params["eol_xml_file"] = "https://opendata.eol.org/dataset/f57501e3-b65e-41bc-b4b8-ccd93cb82bea/resource/6dd97eb0-d386-4f29-acc2-1c36f6323713/download/asknature.xml.gz";
$params["filename"]     = "asknature.xml"; //need to mention 40.xml here because eol_xml_file is .xml.gz
$params["dataset"]      = "EOL XML files";
$params["resource_id"]  = $resource_id;

$func = new ConvertEOLtoDWCaAPI($resource_id);
$func->export_xml_to_archive($params, false, 60*60*24*25); // true => means it is an XML file, not an archive file nor a zip file. Expires in 25 days.
Functions::finalize_dwca_resource($resource_id, false, true);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>