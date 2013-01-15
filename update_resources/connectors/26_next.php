<?php
namespace php_active_record;
/* connector for WORMS
This script is called if the main script calls for another instance of the connector.
*/

define('DOWNLOAD_WAIT_TIME', '200000');
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
require_library('connectors/WormsAPI');
$resource_id = 26;

if(isset($argv[1])) $call_multiple_instance = false;
else $call_multiple_instance = true;

$worms = new WormsAPI();
$worms->start_process($resource_id, $call_multiple_instance);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = " . $elapsed_time_sec . " seconds \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "elapsed time = " . $elapsed_time_sec/60/60/24 . " days \n";
echo date('Y-m-d h:i:s a', time())."\n";
echo "\n\n Done processing.";
?>
