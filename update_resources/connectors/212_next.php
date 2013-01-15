<?php
namespace php_active_record;
/* connector for BOLD Systems
This script is called if the main script calls for another instance of the connector.
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
require_library('connectors/BOLDSysAPI');
$resource_id = 212;

if(isset($argv[1])) $call_multiple_instance = false;
else $call_multiple_instance = true;

$bolds = new BOLDSysAPI();
$bolds->start_process($resource_id, $call_multiple_instance);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes   \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\n\n Done processing.";
?>