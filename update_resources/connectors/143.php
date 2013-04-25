<?php
namespace php_active_record;
/* connector for Insect Visitors of Illinois Wildflowers
estimated execution time: 1.8 hours
Connector scrapes the site: http://www.illinoiswildflowers.info/flower_insects/index.htm
This resource is regularly being harvested. Connector is scheduled as a cron task.
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/InsectVisitorsAPI');
$timestart = time_elapsed();

$resource_id = 143;
$func = new InsectVisitorsAPI();
$func->get_all_taxa($resource_id);
Functions::set_resource_status_to_force_harvest($resource_id);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = $elapsed_time_sec seconds             \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes  \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\n\n Done processing.";
?>