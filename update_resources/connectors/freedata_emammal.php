<?php
namespace php_active_record;
/* e-mammal for FreshData: https://eol-jira.bibalex.org/browse/DATA-1659 */
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/FreeDataAPI');
$timestart = time_elapsed();

// /*
//local - during development
$local_path = "/Library/WebServer/Documents/cp/eMammal";
// */

/*
//remote - actual
*/

$func = new FreeDataAPI();
$func->generate_eMammal_archive($local_path);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>
