<?php
namespace php_active_record;
/* connector for Silvics of North America
estimated execution time: 20 minutes
Connector scrapes the site: http://www.na.fs.fed.us/spfo/pubs/silvics_manual/table_of_contents.htm
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/SilvicsNorthAmericaAPI');
$timestart = time_elapsed();

$resource_id = 419;
$func = new SilvicsNorthAmericaAPI();
$func->get_all_taxa($resource_id);
Functions::set_resource_status_to_harvest_requested($resource_id);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = $elapsed_time_sec seconds             \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes  \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\n\n Done processing.";
?>