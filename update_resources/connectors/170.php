<?php
/* connector for Serpent
estimated execution time: 23 mins.
Connector screen scrapes the partner website.
*/
$timestart = microtime(1);

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/SerpentAPI');
$GLOBALS['ENV_DEBUG'] = false;

$taxa = SerpentAPI::get_all_taxa();
$xml = SchemaDocument::get_taxon_xml($taxa);

$resource_path = CONTENT_RESOURCE_LOCAL_PATH . "170.xml";
$OUT = fopen($resource_path, "w+");
fwrite($OUT, $xml);
fclose($OUT);

//echo "time: ". Functions::time_elapsed() ."\n";

$elapsed_time_sec = microtime(1)-$timestart;
echo "\n";
echo "elapsed time = $elapsed_time_sec sec              \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " min   \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hr \n";
exit("\n\n Done processing.");
?>