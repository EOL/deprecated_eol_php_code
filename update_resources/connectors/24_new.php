<?php
namespace php_active_record;
/* AntWeb
24	Tue 2020-12-08 06:51:52 AM	    {"agent.tab":173, "measurement_or_fact_specific.tab":112164, "media_resource.tab":140143, "occurrence_specific.tab":71314, "taxon.tab":16470, "time_elapsed":{"sec":1668.92, "min":27.82, "hr":0.46}}
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");

/* e.g. php 26_new.php jenkins */
$cmdline_params['jenkins_or_cron']  = @$argv[1]; //irrelevant here

require_library('connectors/AntWebAPI');
$timestart = time_elapsed();
// ini_set('memory_limit','7096M'); //required

/* tests
$a1 = array('a','b', 'c');
$a2 = array('b','a', 'c');
$a3 = array_diff($a1, $a2);
print_r($a3); 
if($a3) echo "\nmay array\n";
else     echo "\nwala array\n";
exit("\n");
*/

// 25 November 2020
// exit("\n".date("d F Y")."\n");

// print_r(pathinfo("https://www.antweb.org/images/psw7796-21/psw7796-21_d_1_high.jpg")); exit;

// /* //main operation
$resource_id = 24;
$func = new AntWebAPI($resource_id);
$func->start();
Functions::finalize_dwca_resource($resource_id, false, true, $timestart); //3rd param if false it will not remove the /24/ folder
// */

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>