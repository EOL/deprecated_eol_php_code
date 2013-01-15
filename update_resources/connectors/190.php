<?php
namespace php_active_record;
/* connector for FishWise
estimated execution time: 25 mins

Partner gave us temporary remote access to their MS SQL server. We connected and downloaded tables to an Access DB, 
and created XLS files for connector.

                taxa    dataObject  references  records processed by connector
as of 18Oct     19714   90655       27753
as of 07Nov                                     65534
*/


$timestart = microtime(1);
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/FishWiseAPI');
$GLOBALS['ENV_DEBUG'] = false;

$taxa = FishWiseAPI::get_all_taxa(190);

$elapsed_time_sec = microtime(1)-$timestart;
echo "\n";
echo "elapsed time = $elapsed_time_sec sec              \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " min   \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hr \n";
echo "\n\n Done processing.";
?>