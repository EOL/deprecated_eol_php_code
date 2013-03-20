<?php
namespace php_active_record;
/* connector for Soundcloud 
estimated execution time:  10 minutes
*/

$timestart = microtime(1);
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/SoundcloudAPI');

$resource_id = 511;
$func = new SoundcloudAPI;
$taxa = $func->get_all_taxa();
$xml = \SchemaDocument::get_taxon_xml($taxa);
$resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml";
$OUT = fopen($resource_path, "w");
fwrite($OUT, $xml);
fclose($OUT);

Functions::set_resource_status_to_force_harvest($resource_id);

$elapsed_time_sec = microtime(1) - $timestart;
echo "\n";
echo "elapsed time = $elapsed_time_sec seconds              \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes   \n";
echo "\n\n Done processing.";
?>