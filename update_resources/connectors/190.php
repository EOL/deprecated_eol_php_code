<?php
namespace php_active_record;
/* connector for FishWise
estimated execution time: 25 mins
Partner gave us temporary remote access to their MS SQL server. 
We connected and downloaded their tables to an MS Access DB, and created XLS files for connector.
*/

$timestart = microtime(1);
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/FishWiseAPI');

$fw = new FishWiseAPI();
$taxa = $fw->get_all_taxa(190);

$elapsed_time_sec = microtime(1)-$timestart;
echo "\n\n elapsed time = $elapsed_time_sec seconds";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n\n Done processing.";
?>