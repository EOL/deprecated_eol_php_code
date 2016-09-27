<?php
namespace php_active_record;
/* connector for Plant-feeding insects of Illinois Wildflowers
estimated execution time: 47 minutes
Connector scrapes the site: http://www.illinoiswildflowers.info/plant_insects/database.html
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/PlantFeedingInsectsAPI');
$timestart = time_elapsed();

$resource_id = 417;
$func = new PlantFeedingInsectsAPI();
$func->get_all_taxa($resource_id);
Functions::set_resource_status_to_harvest_requested($resource_id);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = $elapsed_time_sec seconds             \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes  \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\n\n Done processing.";
?>