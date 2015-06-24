<?php
namespace php_active_record;
/* Scorpion Files - DATA-1620
estimated execution time:

For some reason the generated [scorpion_families.xls] has some 'false' values under Articles column.
I just used the [scorpion_families.txt] and saved it as spreadsheet (.xls) and I used that.
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/ScorpionFilesAPI');
$timestart = time_elapsed();

$func = new ScorpionFilesAPI();
$func->get_all_taxa();

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>
