<?php
namespace php_active_record;
/* execution time: 9 seconds 
                            Mar2014     11May2015
measurement_or_fact.tab]    [10618]     10312
occurrence.tab]             [3537]      3435
taxon.tab]                  [1176]      1141

750	Thursday 2018-03-08 10:24:33 AM	{"measurement_or_fact.tab":10308,"occurrence.tab":3434,"taxon.tab":1140} eol-archive - all-fileds hash measurementID
750	Friday 2018-03-09 12:01:12 AM	{"measurement_or_fact.tab":10308,"occurrence.tab":3434,"taxon.tab":1140}
750	Tuesday 2018-07-03 10:28:48 AM	{"measurement_or_fact.tab":10308,"occurrence.tab":3434,"taxon.tab":1140} - hard-coded source data, thus no change for now.
After DATA-1841: terms remapping:
750	Monday 2019-11-25 05:14:27 AM	{"measurement_or_fact.tab":10308,"occurrence.tab":3434,"taxon.tab":1140,"time_elapsed":{"sec":11.25,"min":0.19,"hr":0}}
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/FEISDataConnector');
$timestart = time_elapsed();
$resource_id = 750;
$func = new FEISDataConnector($resource_id);
$func->generate_FEIS_data();
Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
?>