<?php
namespace php_active_record;
/* connector for US Fish and Wildlife Services - Endangered Species Program
estimated execution time: 5 hours (taxa = 2233)
Taxa list comes from 3 spreadsheets and content is scraped.
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
require_library('connectors/USFishWildlifeAPI');
$resource_id = 266;
USFishWildlifeAPI::get_all_taxa_keys($resource_id);
Functions::set_resource_status_to_harvest_requested($resource_id);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\n\n Done processing.";
?>