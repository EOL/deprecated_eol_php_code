<?php
namespace php_active_record;

/* This connector assembles BOLDS' higher-level taxa list (hl_master_list.txt) {37 hours}
estimated execution time: 37 hours
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
require_library('connectors/BoldsAPIpre');

if(isset($argv[1])) $call_multiple_instance = false;
else $call_multiple_instance = true;

$resource_id = '81pre';
BoldsAPIpre::start_process($resource_id, $call_multiple_instance);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes  \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
exit("\n\n Done processing.");
?>