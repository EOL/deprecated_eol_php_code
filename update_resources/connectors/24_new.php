<?php
namespace php_active_record;
/* AntWeb
24	Tue 2020-12-08 06:51:52 AM	{"agent.tab":173, "measurement_or_fact_specific.tab":112164, "media_resource.tab":140143, "occurrence_specific.tab":71314, "taxon.tab":16470, "time_elapsed":{"sec":1668.92, "min":27.82, "hr":0.46}}
'clouds' removed
24	Tue 2020-12-08 09:10:17 AM	{"agent.tab":173, "measurement_or_fact_specific.tab":110680, "media_resource.tab":140143, "occurrence_specific.tab":70884, "taxon.tab":16470, "time_elapsed":{"sec":1584.2, "min":26.4, "hr":0.44}}
removed descendants of 'aquatic'
24	Tue 2020-12-08 08:37:56 PM	{"agent.tab":173, "measurement_or_fact_specific.tab":107786, "media_resource.tab":140143, "occurrence_specific.tab":68850, "taxon.tab":16470, "time_elapsed":{"sec":2012.86, "min":33.55, "hr":0.5600000000000001}}
24	Wed 2020-12-09 08:54:57 PM	{"agent.tab":173, "measurement_or_fact_specific.tab":107786, "media_resource.tab":140143, "occurrence_specific.tab":68850, "taxon.tab":16470, "time_elapsed":{"sec":1881.16, "min":31.35, "hr":0.52}}
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");

/* e.g. php 24_new.php jenkins */
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