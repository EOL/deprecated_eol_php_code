<?php
namespace php_active_record;
/* connector for STRI maps (Shorefishes of the tropical eastern Pacific online information system)
estimated execution time: 16 minutes
Connector processes the original XML resource and checks if the map image exists
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/STRImapsAPI');
$timestart = time_elapsed();
$resource_id = 358;
$avibase = new STRImapsAPI();
$avibase->get_all_taxa($resource_id);
Functions::set_resource_status_to_force_harvest($resource_id);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = $elapsed_time_sec seconds             \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes  \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
exit("\n\n Done processing.");
?>