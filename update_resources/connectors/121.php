<?php
/* connector for Hydrothermal Vent Larvae
estimated execution time: 16-20 seconds
Connector screen scrapes the partner website.
*/
$timestart = microtime(1);
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/HydrothermalVentLarvaeAPI');
$GLOBALS['ENV_DEBUG'] = false;

$taxa = HydrothermalVentLarvaeAPI::get_all_taxa();
$xml = SchemaDocument::get_taxon_xml($taxa);

$resource_path = CONTENT_RESOURCE_LOCAL_PATH . "121.xml";
$OUT = fopen($resource_path, "w+");
fwrite($OUT, $xml);
fclose($OUT);

$elapsed_time_sec = microtime(1)-$timestart;
echo "\n";
echo "elapsed time = $elapsed_time_sec sec              \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " min   \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hr \n";
exit("\n\n Done processing.");
?>