<?php
exit; 
/* connector for Turbellarian
Estimated execution time: 10.57 hrs.
This connector gets data from website. Ancestry information is still pending, to be provided by partner.

as of       records
2010 10 01  17492
2010 11 18  9780
2011 01 05  9491
*/

$timestart = microtime(1);
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/TurbellarianAPI');
$GLOBALS['ENV_DEBUG'] = false;

$taxa = TurbellarianAPI::get_all_taxa();
$xml = SchemaDocument::get_taxon_xml($taxa);

$resource_path = CONTENT_RESOURCE_LOCAL_PATH . "185.xml";

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