<?php
namespace php_active_record;
/* 
This will use the Google Client Library, and will access a Google Sheet
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/GoogleClientAPI');

$timestart = time_elapsed();
$resource_id = 1;

$func = new GoogleClientAPI();
$func->access_google_sheet();

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>
