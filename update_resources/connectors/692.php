<?php
namespace php_active_record;
/* OBIS Environmental Information
execution time: 1.11 hours
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/ObisDataConnector');

$timestart = time_elapsed();
$resource_id = 692;
$connector = new ObisDataConnector($resource_id);
$connector->build_archive();

Functions::finalize_dwca_resource($resource_id);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>