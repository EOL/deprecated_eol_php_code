<?php
namespace php_active_record;
/*
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");

/* e.g. php 26_new.php jenkins */
$cmdline_params['jenkins_or_cron']  = @$argv[1]; //irrelevant here

require_library('connectors/AntWebAPI');
$timestart = time_elapsed();
// ini_set('memory_limit','7096M'); //required

// 25 November 2020
// exit("\n".date("d F Y")."\n");

// /* //main operation
$resource_id = 24;
$func = new AntWebAPI($resource_id);
$func->start();
Functions::finalize_dwca_resource($resource_id, false, false, $timestart); //3rd param if false it will not remove the /24/ folder
// */

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>