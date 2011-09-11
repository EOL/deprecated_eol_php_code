<?php
namespace php_active_record;
/* connector for US Fish and Wildlife Services - Endangered Species Program
estimated execution time: 18 minutes
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();

require_library('connectors/USFishWildlifeAPI');

$resource_id = 266;
USFishWildlifeAPI::get_all_taxa_keys($resource_id);

// set to force harvest
if(filesize(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml"))
{
    $GLOBALS['db_connection']->update("UPDATE resources SET resource_status_id=" . ResourceStatus::force_harvest()->id . " WHERE id=" . $resource_id);
}

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = $elapsed_time_sec sec              \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " min   \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hr \n";
exit("\n\n Done processing.");
?>