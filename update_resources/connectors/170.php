<?php
namespace php_active_record;

/* connector for SERPENT
estimated execution time: 23 mins. | 1.5 hours (total taxa has increased and I increased download wait time to 1 sec.)
Connector screen scrapes the partner website.
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();

require_library('connectors/SerpentAPI');

$func = new SerpentAPI();
$taxa = $func->get_all_taxa();
/* $taxa = SerpentAPI::get_all_taxa();  --- old code */
$xml = \SchemaDocument::get_taxon_xml($taxa);

$resource_id = 170;
$resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml";
if(!($OUT = fopen($resource_path, "w+")))
{
  debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .$resource_path);
  return;
}
fwrite($OUT, $xml);
fclose($OUT);

Functions::set_resource_status_to_harvest_requested($resource_id);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\n\n Done processing.";
?>