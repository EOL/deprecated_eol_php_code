<?php
namespace php_active_record;
/* 
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
$resource_id = "";

require_library('connectors/TraitDataImportAPI');
$func = new TraitDataImportAPI($resource_id);
$func->clean_up_unused_resources(); //remove files in /resources/, those that are not related to any OpenData resource.
/* found in:  https://opendata.eol.org/dataset/trait-spreadsheet-repository */

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n elapsed time = " . $elapsed_time_sec . " seconds";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n Done processing\n";
?>