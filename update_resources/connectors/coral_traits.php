<?php
namespace php_active_record;
/* DATA-1793 
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/CoralTraitsAPI');
$timestart = time_elapsed();
$resource_id = "coraltraits";
$func = new CoralTraitsAPI($resource_id);
$func->start(); //main operation
Functions::finalize_dwca_resource($resource_id, false);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>
