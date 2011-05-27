<?php
/* connector for DiscoverLife ID keys
estimated execution time: 4.27 hours
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();

require_library('connectors/DiscoverLife_KeysAPI');

$resource_id = 252;
DiscoverLife_KeysAPI::get_all_taxa_keys($resource_id);

// set to force harvest
if(filesize(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml"))
{
    $GLOBALS['db_connection']->update("UPDATE resources SET resource_status_id=" . ResourceStatus::insert('Force Harvest') . " WHERE id=" . $resource_id);
}

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes   \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
exit("\n\n Done processing.");
?>