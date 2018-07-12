<?php
namespace php_active_record;
/* Diptera of Central America
estimated execution time: 9 minutes 
Connector scrapes the partner's website
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/DipteraCentralAmericaAPI');
$timestart = time_elapsed();
$resource_id = "683"; // formerly 661
$func = new DipteraCentralAmericaAPI($resource_id);
$func->start();
Functions::finalize_dwca_resource($resource_id, false, true);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n elapsed time = " . $elapsed_time_sec . " seconds";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing\n";
?>