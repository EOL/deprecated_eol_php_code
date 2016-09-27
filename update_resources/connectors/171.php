<?php
namespace php_active_record;
/* connector for OBIS depth range data
estimated execution time: 1.7 hours
This connector will use CSV files submitted by partner.
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
require_library('connectors/ObisAPI');
$resource_id = 171;

$func = new ObisAPI();
$func->get_all_taxa($resource_id);

Functions::set_resource_status_to_harvest_requested($resource_id);
Functions::gzip_resource_xml($resource_id);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = $elapsed_time_sec seconds             \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes  \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\n\n Done processing.";
?>