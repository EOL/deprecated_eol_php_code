<?php
namespace php_active_record;
/* DATA-1793 
coraltraits	Tuesday 2019-02-05 11:37:23 AM	{"MoF.tab":56713,"occurrence_specific.tab":33518,"reference.tab":555,"taxon.tab":1547} -- first run
coraltraits	Tuesday 2019-02-19 10:57:19 PM	{"MoF.tab":56713,"occurrence_specific.tab":33518,"reference.tab":555,"taxon.tab":1547}
cotr	    Wednesday 2019-02-20 12:23:55 AM{"MoF.tab":56713,"occurrence_specific.tab":33518,"reference.tab":555,"taxon.tab":1547}
cotr	Tuesday 2019-07-09 12:43:12 AM	    {"MoF.tab":56713,"occurrence_specific.tab":33518,"reference.tab":555,"taxon.tab":1547}
cotr	Friday 2019-11-22 11:04:16 AM	    {"MoF.tab":56713,"occurrence_specific.tab":33518,"reference.tab":555,"taxon.tab":1547,"time_elapsed":{"sec":56.13,"min":0.9399999999999999,"hr":0.02}}
cotr	Monday 2020-02-10 06:42:54 AM	    {"MoF.tab":56713, "occurrence_specific.tab":33518, "reference.tab":555, "taxon.tab":1547, "time_elapsed":{"sec":52.81,"min":0.88,"hr":0.01}}
cotr	Tuesday 2020-03-03 04:30:02 AM	    {"MoF.tab":56670, "occurrence_specific.tab":33475, "reference.tab":555, "taxon.tab":1547, "time_elapsed":{"sec":64.98999999999999, "min":1.08, "hr":0.02}}
cotr	Tuesday 2020-03-24 11:01:13 AM	    {"MoF.tab":56648, "occurrence_specific.tab":33475, "reference.tab":555, "taxon.tab":1547, "time_elapsed":{"sec":78.41, "min":1.31, "hr":0.02}}
cotr	Thursday 2020-05-14 02:44:27 AM	    {"MoF.tab":56648, "occurrence_specific.tab":33475, "reference.tab":555, "taxon.tab":1547, "time_elapsed":{"sec":69.16, "min":1.15, "hr":0.02}}
cotr	Fri 2020-12-11 01:17:35 AM	        {"MoF.tab":56648, "occurrence_specific.tab":33475, "reference.tab":555, "taxon.tab":1547, "time_elapsed":{"sec":70.03, "min":1.17, "hr":0.02}}
cotr	Wed 2021-02-10 06:43:08 AM	        {"MoF.tab":56481, "occurrence_specific.tab":33335, "reference.tab":555, "taxon.tab":1547, "time_elapsed":{"sec":67.62, "min":1.13, "hr":0.02}}
cotr	Sun 2021-10-10 06:43:18 AM	        {"MoF.tab":56481, "occurrence_specific.tab":33335, "reference.tab":555, "taxon.tab":1547, "time_elapsed":{"sec":77.11, "min":1.29, "hr":0.02}}
cotr	Wed 2021-11-10 06:43:11 AM	        {"MoF.tab":56481, "occurrence_specific.tab":33335, "reference.tab":555, "taxon.tab":1547, "time_elapsed":{"sec":69.73999999999999, "min":1.16, "hr":0.02}}
cotr	Mon 2021-11-22 10:27:51 AM	        {"MoF.tab":55004, "occurrence_specific.tab":31858, "reference.tab":555, "taxon.tab":1547, "time_elapsed":{"sec":68.48999999999999, "min":1.14, "hr":0.02}}
cotr	Tue 2021-11-23 11:32:12 PM	        {"MoF.tab":55004, "occurrence_specific.tab":31858, "reference.tab":555, "taxon.tab":1547, "time_elapsed":{"sec":88.56999999999999, "min":1.48, "hr":0.02}}

cotr_meta_recoded_final	Thu 2022-09-29 12:00:02 PM	{"measurement_or_fact_specific.tab":53008, "occurrence.tab":31858, "reference.tab":555, "taxon.tab":1547, "time_elapsed":{"sec":36, "min":0.6, "hr":0.01}}

*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/CoralTraitsAPI');
$timestart = time_elapsed();
$resource_id = "cotr"; //coraltraits
$func = new CoralTraitsAPI($resource_id);
$func->start(); //main operation
Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
?>
