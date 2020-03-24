<?php
namespace php_active_record;
/* DATA-1793 
coraltraits	Tuesday 2019-02-05 11:37:23 AM	{"measurement_or_fact_specific.tab":56713,"occurrence_specific.tab":33518,"reference.tab":555,"taxon.tab":1547} -- first run
coraltraits	Tuesday 2019-02-19 10:57:19 PM	{"measurement_or_fact_specific.tab":56713,"occurrence_specific.tab":33518,"reference.tab":555,"taxon.tab":1547}
cotr	    Wednesday 2019-02-20 12:23:55 AM{"measurement_or_fact_specific.tab":56713,"occurrence_specific.tab":33518,"reference.tab":555,"taxon.tab":1547}
cotr	Tuesday 2019-07-09 12:43:12 AM	    {"measurement_or_fact_specific.tab":56713,"occurrence_specific.tab":33518,"reference.tab":555,"taxon.tab":1547}
cotr	Friday 2019-11-22 11:04:16 AM	    {"measurement_or_fact_specific.tab":56713,"occurrence_specific.tab":33518,"reference.tab":555,"taxon.tab":1547,"time_elapsed":{"sec":56.13,"min":0.9399999999999999,"hr":0.02}}
cotr	Monday 2020-02-10 06:42:54 AM	    {"measurement_or_fact_specific.tab":56713, "occurrence_specific.tab":33518, "reference.tab":555, "taxon.tab":1547, "time_elapsed":{"sec":52.81,"min":0.88,"hr":0.01}}
cotr	Tuesday 2020-03-03 04:30:02 AM	    {"measurement_or_fact_specific.tab":56670, "occurrence_specific.tab":33475, "reference.tab":555, "taxon.tab":1547, "time_elapsed":{"sec":64.98999999999999, "min":1.08, "hr":0.02}}
cotr	Tuesday 2020-03-24 11:01:13 AM	    {"measurement_or_fact_specific.tab":56648, "occurrence_specific.tab":33475, "reference.tab":555, "taxon.tab":1547, "time_elapsed":{"sec":78.41, "min":1.31, "hr":0.02}}
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/CoralTraitsAPI');
$timestart = time_elapsed();
$resource_id = "cotr"; //coraltraits
$func = new CoralTraitsAPI($resource_id);
$func->start(); //main operation
Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
?>
