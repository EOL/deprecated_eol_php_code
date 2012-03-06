<?php
namespace php_active_record;
/* connector for BOLD Systems
estimated execution time 8 days:
Partner provides XML service
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
require_library('connectors/BOLDSysAPI');
$resource_id = 212;

$bolds = new BOLDSysAPI();
$bolds->initialize_text_files();
//Functions::kill_running_connectors($resource_id);
$bolds->start_process($resource_id, false);

Functions::set_resource_status_to_force_harvest($resource_id);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes   \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
exit("\n\n Done processing.");
?>