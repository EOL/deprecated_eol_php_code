<?php
namespace php_active_record;
/* connector for DiscoverLife ID keys
estimated execution time: 4.27 hours

Note: 
- before updating the broken connector for EOL V3, we used http://services.eol.org/resources/252.xml (converted to DwCA) as temporary resource.
- now connector is revived, available for V3.

252	Tuesday 2018-05-08 10:29:38 AM	{"agent.tab":1,"media_resource.tab":6231,"taxon.tab":6231} - MacMini

*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
require_library('connectors/DiscoverLife_KeysAPI');
$resource_id = 252;
// DiscoverLife_KeysAPI::get_all_taxa_keys($resource_id);
$func = new DiscoverLife_KeysAPI();
$func->get_all_taxa_keys($resource_id);

//start converting to DwCA
require_library('connectors/ConvertEOLtoDWCaAPI');
$params["eol_xml_file"] = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml";
$params["filename"]     = "no need to mention here.xml"; //no need to mention if eol_xml_file is already .xml and not .xml.gz
$params["dataset"]      = "EOL XML files";
$params["resource_id"]  = $resource_id;
$func = new ConvertEOLtoDWCaAPI($resource_id);
$func->export_xml_to_archive($params, true, 60*60*24*25); // true => means it is an XML file, not an archive file nor a zip file. Expires in 25 days.
Functions::finalize_dwca_resource($resource_id, false, true); //3rd param true means to delete the dwca folder.
unlink($params["eol_xml_file"]);
//end conversion

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes   \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\n\n Done processing.";
?>