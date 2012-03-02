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
$OUT = fopen($resource_path, "w+");
fwrite($OUT, $xml);
fclose($OUT);
Functions::set_resource_status_to_force_harvest($resource_id);
$elapsed_time_sec = time_elapsed()-$timestart;
echo "\n";
echo "elapsed time = $elapsed_time_sec seconds             \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes  \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
exit("\n\n Done processing.");
?>