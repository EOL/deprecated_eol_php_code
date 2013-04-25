<?php
namespace php_active_record;

/* connector for SERPENT
estimated execution time: 23 mins. | 1.5 hours (total taxa has increased and I increased download wait time to 1 sec.)
Connector screen scrapes the partner website.
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();

require_library('connectors/SerpentAPI');
$taxa = SerpentAPI::get_all_taxa();
$xml = \SchemaDocument::get_taxon_xml($taxa);

$resource_id = 170;
$resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml";
$OUT = fopen($resource_path, "w+");
fwrite($OUT, $xml);
fclose($OUT);

Functions::set_resource_status_to_force_harvest($resource_id);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\n\n Done processing.";
?>