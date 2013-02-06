<?php
namespace php_active_record;
/* connector for Avibase - IOC World Bird Names
estimated execution time: 3 minutes (9 hours with synonyms)
Connector scrapes the Avibase site.
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/AvibaseAPI');

$timestart = time_elapsed();
$resource_id = 355;
$av = new AvibaseAPI($resource_id, 'ioc');
$av->get_all_taxa();

Functions::set_resource_status_to_force_harvest($resource_id);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = $elapsed_time_sec seconds             \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes  \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\n\n Done processing.";

?>