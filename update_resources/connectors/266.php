<?php
namespace php_active_record;
/* connector for US Fish and Wildlife Services - Endangered Species Program
estimated execution time: 4.17 hours (taxa = 2233)
Taxa list comes from 3 spreadsheets and content is scraped.
Note: Due to scraping, some characters are needed to be manually removed from the generated resource XML.
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
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
exit("\n\n Done processing.");
?>