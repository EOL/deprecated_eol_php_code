<?php
namespace php_active_record;
/* TRAM-992: Create a list of taxonIDs used in branch painting data sets
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/TRAM_992_API');
$timestart = time_elapsed();
$func = new TRAM_992_API();
$func->start();
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>