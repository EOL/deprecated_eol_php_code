<?php
namespace php_active_record;
/* execution time: 9 seconds 

                            Mar2014     11May2015
measurement_or_fact.tab]    [10618]     10312
occurrence.tab]             [3537]      3435
taxon.tab]                  [1176]      1141

*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/FEISDataConnector');
$timestart = time_elapsed();

$resource_id = 750;
$func = new FEISDataConnector($resource_id);
$func->generate_FEIS_data();

Functions::finalize_dwca_resource($resource_id);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec . " seconds";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>