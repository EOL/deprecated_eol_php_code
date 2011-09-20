<?php
namespace php_active_record;
/* connector for BOLD Systems
estimated execution time:
Partner provides XML service
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
require_library('connectors/BOLDSysAPI');
$resource_id = 212;

if(isset($argv[1])) $call_multiple_instance = false;
else $call_multiple_instance = true;

BOLDSysAPI::start_process($resource_id, $call_multiple_instance);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes   \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
exit("\n\n Done processing.");

/*
$timestart = microtime(1);
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/BOLDSysAPI');
$GLOBALS['ENV_DEBUG'] = true;
$resource_id = 6; //orig 212;
$taxa = BOLDSysAPI::get_all_taxa($resource_id);
$elapsed_time_sec = microtime(1)-$timestart;
echo "\n";
echo "elapsed time = $elapsed_time_sec sec              \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " min   \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hr \n";
exit("\n\n Done processing.");
*/

?>