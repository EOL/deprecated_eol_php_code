<?php
namespace php_active_record;
/*
this is auxilliary script that calls function(s) in diff. libraries for testing.
*/


include_once(dirname(__FILE__) . "/../../config/environment.php");
$GLOBALS['ENV_DEBUG'] = true;
ini_set('memory_limit','4096M'); //314,5728,000
$timestart = time_elapsed();

/* works OK
require_library('connectors/AntWebDataAPI');
$func = new AntWebDataAPI(null, null);
$func->initialize_habitat_mapping();
*/

// /*
$file = "/Volumes/AKiTiO4/FreshData resource/monitors v2/occurrences.tsv";
$count = Functions::count_rows_from_text_file($file);
exit("\n[$count]\n");
// */


$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>