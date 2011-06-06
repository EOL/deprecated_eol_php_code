<?php
/* connector for SERPENT
estimated execution time: 23 mins.
Connector screen scrapes the partner website.
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();

require_library('connectors/SerpentAPI');
$GLOBALS['ENV_DEBUG'] = false;

$taxa = SerpentAPI::get_all_taxa();
$xml = SchemaDocument::get_taxon_xml($taxa);

$resource_id = 170;
$resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml";
$OUT = fopen($resource_path, "w+");
fwrite($OUT, $xml);
fclose($OUT);

// set SERPENT to force harvest
if(filesize(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml"))
{
    $GLOBALS['db_connection']->update("UPDATE resources SET resource_status_id=" . ResourceStatus::find_or_create_by_label('Force Harvest')->id . " WHERE id=" . $resource_id);
}

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
exit("\n\n Done processing.");
?>