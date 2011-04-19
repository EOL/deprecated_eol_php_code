<?php
/* connector for Bioimages
estimated execution time: 34 hours
Connector screen scrapes the partner website. 
Ancestry information comes from 2 spreadsheet files.

                taxa    dataObject  references
as of 18Oct     19714   90655       27753

*/
exit;

$timestart = microtime(1);
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/BioImagesAPI');
$GLOBALS['ENV_DEBUG'] = false;

$taxa = BioImagesAPI::get_all_taxa();
$xml = SchemaDocument::get_taxon_xml($taxa);

$resource_path = CONTENT_RESOURCE_LOCAL_PATH . "168.xml";
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