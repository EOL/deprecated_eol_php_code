<?php
/* connector for BOLD Systems
estimated execution time: 17 mins
Partner provides XML service
*/

$timestart = microtime(1);
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/BOLDSysAPI');
$GLOBALS['ENV_DEBUG'] = true;

$resource_id = 212;
$taxa = BOLDSysAPI::get_all_taxa($resource_id);

$elapsed_time_sec = microtime(1)-$timestart;
echo "\n";
echo "elapsed time = $elapsed_time_sec sec              \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " min   \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hr \n";
exit("\n\n Done processing.");
?>