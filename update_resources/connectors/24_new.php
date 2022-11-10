<?php
namespace php_active_record;
/* AntWeb
24	Tue 2020-12-08 06:51:52 AM	{"agent.tab":173, "MoF.tab":112164, "media.tab":140143, "occurrence.tab":71314, "taxon.tab":16470, "time_elapsed":{"sec":1668.92, "min":27.82, "hr":0.46}}
'clouds' removed
24	Tue 2020-12-08 09:10:17 AM	{"agent.tab":173, "MoF.tab":110680, "media.tab":140143, "occurrence.tab":70884, "taxon.tab":16470, "time_elapsed":{"sec":1584.2, "min":26.4, "hr":0.44}}
removed descendants of 'aquatic'
24	Tue 2020-12-08 08:37:56 PM	{"agent.tab":173, "MoF.tab":107786, "media.tab":140143, "occurrence.tab":68850, "taxon.tab":16470, "time_elapsed":{"sec":2012.86, "min":33.55, "hr":0.5600000000000001}}
24	Wed 2020-12-09 08:54:57 PM	{"agent.tab":173, "MoF.tab":107786, "media.tab":140143, "occurrence.tab":68850, "taxon.tab":16470, "time_elapsed":{"sec":1881.16, "min":31.35, "hr":0.52}}
removed 'cut'
24	Fri 2020-12-11 01:06:04 AM	{"agent.tab":173, "MoF.tab":107730, "media.tab":140143, "occurrence.tab":68814, "taxon.tab":16470, "time_elapsed":{"sec":1399.82, "min":23.33, "hr":0.39}}
24	Tue 2020-12-15 01:52:21 AM	{"agent.tab":173, "MoF.tab":107730, "media.tab":140143, "occurrence.tab":68814, "taxon.tab":16470, "time_elapsed":{"sec":1520.83, "min":25.35, "hr":0.42}}
apply all general filters and remaps and adjustments:
24	Tue 2020-12-15 07:38:06 AM	{"agent.tab":173, "MoF.tab":98829, "media.tab":140143, "occurrence.tab":62766, "taxon.tab":16470, "time_elapsed":{"sec":1539.16, "min":25.65, "hr":0.43}}
added new list of excluded URIs
24	Tue 2020-12-15 10:22:28 AM	{"agent.tab":173, "MoF.tab":94696, "media.tab":140143, "occurrence.tab":59875, "taxon.tab":16470, "time_elapsed":{"sec":1781.58, "min":29.69, "hr":0.49}}
revert back by Jen, no more new list of excluded URIs
24	Tue 2020-12-15 05:50:51 PM	{"agent.tab":173, "MoF.tab":98829, "media.tab":140143, "occurrence.tab":62766, "taxon.tab":16470, "time_elapsed":{"sec":1571.16, "min":26.19, "hr":0.44}}
with Biology
24	Wed 2020-12-16 05:48:39 AM	{"agent.tab":173, "MoF.tab":100516, "media.tab":140143, "occurrence.tab":64025, "taxon.tab":16470, "time_elapsed":{"sec":2675.91, "min":44.6, "hr":0.74}}
with the new "terms_to_remove" list
24	Wed 2020-12-16 09:45:03 PM	{"agent.tab":173, "MoF.tab":96172, "media.tab":140143, "occurrence.tab":60821, "taxon.tab":16470, "time_elapsed":{"sec":1933.25, "min":32.22, "hr":0.54}}
24	Wed 2021-10-13 11:30:48 AM	{"agent.tab":173, "MoF.tab":96447, "media.tab":140143, "occurrence.tab":60908, "taxon.tab":16470, "time_elapsed":{"sec":3690.66, "min":61.51, "hr":1.03}}
24	Tue 2022-03-15 02:19:23 AM	{"agent.tab":173, "MoF.tab":96447, "media.tab":140143, "occurrence.tab":60908, "taxon.tab":16470, "time_elapsed":{"sec":3142.83, "min":52.38, "hr":0.87}}
24_cleaned_MoF_habitat	Wed 2022-04-13 08:18:11 AM	
                                {"agent.tab":173, "MoF.tab":96287, "media.tab":140143, "occurrence.tab":60823, "taxon.tab":16470, "time_elapsed":{"sec":243.34, "min":4.06, "hr":0.07}} Mac Mini
24_cleaned_MoF_habitat	Wed 2022-04-13 08:30:12 AM	
                                {"agent.tab":173, "MoF.tab":96287, "media.tab":140143, "occurrence.tab":60823, "taxon.tab":16470, "time_elapsed":{"sec":156.28, "min":2.6, "hr":0.04}} eol-archive
24	Thu 2022-06-09 11:13:21 PM	{"agent.tab":173, "MoF.tab":96412, "media.tab":140143, "occurrence.tab":60873, "taxon.tab":16470, "time_elapsed":{"sec":3525.04, "min":58.75, "hr":0.98}}
24	Wed 2022-10-26 01:03:46 PM	{"agent.tab":173, "MoF.tab":95234, "media.tab":140143, "occurrence.tab":60013, "taxon.tab":16470, "time_elapsed":{"sec":12819.9, "min":213.67, "hr":3.56}}
24	Tue 2022-11-01 02:28:48 PM	{"agent.tab":173, "MoF.tab":95234, "media.tab":140143, "occurrence.tab":60013, "taxon.tab":16470, "time_elapsed":{"sec":19342.15, "min":322.37, "hr":5.37}}
Below start of removing all MoF term URIs that are not in EOL Terms File:
24	Tue 2022-11-08 09:45:01 AM	{"agent.tab":173, "MoF.tab":89324, "media.tab":140143, "occurrence.tab":58339, "taxon.tab":16470, "time_elapsed":{"sec":3440.87, "min":57.35, "hr":0.96}}
24	Tue 2022-11-08 12:51:05 PM	{"agent.tab":173, "MoF.tab":89324, "media.tab":140143, "occurrence.tab":58339, "taxon.tab":16470, "time_elapsed":{"sec":1854.12, "min":30.9, "hr":0.52}}
24	Wed 2022-11-09 10:54:41 AM	{"agent.tab":173, "MoF.tab":89324, "media.tab":140143, "occurrence.tab":58339, "taxon.tab":16470, "time_elapsed":{"sec":1815.34, "min":30.26, "hr":0.5}}

# From Jen: it looks like we can discard this filter from both AntWeb and AmphibiaWeb. DATA-1870
php5.6 rem_marine_terr_desc.php jenkins '{"resource_id":"24"}'
# generates 24_cleaned_MoF_habitat.tar.gz
# it worked OK, but no longer being used
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