<?php
/* connector for WORMS
Provider provides a service to get their list of IDs and another service to use the id to get each taxon information.
estimated execution time:
sample api: http://www.marinespecies.org/aphia.php?p=eol&action=taxdetails&id=571925
*/
$timestart = microtime(1);
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/WormsAPI');
$resource_id = 26;

if(isset($argv[1])) $call_multiple_instance = false;
else $call_multiple_instance = true;

WormsAPI::start_process($resource_id, $call_multiple_instance);
$elapsed_time_sec = microtime(1)-$timestart;
echo "\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo date('Y-m-d h:i:s a', time())."\n";
exit("\n\n Done processing.");
?>