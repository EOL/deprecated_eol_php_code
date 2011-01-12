<?php
$GLOBALS['ENV_DEBUG'] = true;
//$GLOBALS['ENV_NAME'] = "staging";

include_once(dirname(__FILE__) . "/../../config/environment.php");
$time_start = time_elapsed();            

ini_set('memory_limit', '1500M');
set_time_limit(0);

$mysqli =& $GLOBALS['mysqli_connection'];
require_library('SiteStatistics');
$stats = new SiteStatistics();

$stats->create_page_metrics_table(); //2.23 hrs
       
$time_elapsed_sec = time_elapsed() - $time_start;
echo "\n elapsed time = $time_elapsed_sec sec               ";
echo "\n elapsed time = " . $time_elapsed_sec/60 . " mins   ";
echo "\n elapsed time = " . $time_elapsed_sec/60/60 . " hrs ";
?>