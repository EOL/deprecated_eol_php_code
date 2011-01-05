<?php
$GLOBALS['ENV_DEBUG'] = false;
//$GLOBALS['ENV_NAME'] = "integration";
//$GLOBALS['ENV_NAME'] = "staging";

include_once(dirname(__FILE__) . "/../../config/environment.php");
$time_start = time_elapsed();            

ini_set('memory_limit', '1500M');
set_time_limit(0);

$mysqli =& $GLOBALS['mysqli_connection'];
require_library('SiteStatistics');
$stats = new SiteStatistics();

/* can only be run when the latest migrations were already run in production */

$stats->create_page_metrics_table(); //1.5 hrs
       
$time_elapsed_sec = time_elapsed() - $time_start;
echo "\n elapsed time = $time_elapsed_sec sec               ";
echo "\n elapsed time = " . $time_elapsed_sec/60 . " mins   ";
echo "\n elapsed time = " . $time_elapsed_sec/60/60 . " hrs ";
?>