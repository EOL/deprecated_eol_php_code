<?php
namespace php_active_record;
/* connector for Hydrothermal Vent Larvae
estimated execution time: 16-20 seconds
Connector screen scrapes the partner website.
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
require_library('connectors/HydrothermalVentLarvaeAPI');
$taxa = HydrothermalVentLarvaeAPI::get_all_taxa();
$xml = \SchemaDocument::get_taxon_xml($taxa);
$resource_id = 121;
$resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml";
if(!($OUT = Functions::file_open($resource_path, "w+"))) return;
fwrite($OUT, $xml);
fclose($OUT);
// Functions::set_resource_status_to_harvest_requested($resource_id);
Functions::gzip_resource_xml($resource_id);
unlink($resource_path);


//start converting to DwCA
require_library('connectors/ConvertEOLtoDWCaAPI');
$params["eol_xml_file"] = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml";
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