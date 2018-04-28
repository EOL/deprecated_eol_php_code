<?php
namespace php_active_record;
/* execution time: 9 seconds 

                            Mar2014     11May2015
measurement_or_fact.tab]    [10618]     10312
occurrence.tab]             [3537]      3435
taxon.tab]                  [1176]      1141

750	Thursday 2018-03-08 10:24:33 AM	{"measurement_or_fact.tab":10308,"occurrence.tab":3434,"taxon.tab":1140} eol-archive - all-fileds hash measurementID
750	Friday 2018-03-09 12:01:12 AM	{"measurement_or_fact.tab":10308,"occurrence.tab":3434,"taxon.tab":1140}
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/FEISDataConnector');
$timestart = time_elapsed();

$resource_id = 750;
$func = new FEISDataConnector($resource_id);
$func->generate_FEIS_data();

Functions::finalize_dwca_resource($resource_id, false, true);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec . " seconds";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>