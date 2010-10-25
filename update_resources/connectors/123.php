<?php
//exit;
/* connector for AquaMaps
estimated execution time: 1.7 hrs.

This connector reads an XML (list of species with AquaMaps) then loops on each species 
and accesses the external service (provide by AquaMaps) to get the distribution maps.

Maps are shown in the Distribution section.
*/

$timestart = microtime(1);
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/AquaMapsAPI');
$GLOBALS['ENV_DEBUG'] = false;
$taxa = AquamapsAPI::get_all_taxa();
$xml = SchemaDocument::get_taxon_xml($taxa);
$resource_path = CONTENT_RESOURCE_LOCAL_PATH . "123.xml";
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