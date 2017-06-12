<?php
namespace php_active_record;
/* e-mammal for FreshData: https://eol-jira.bibalex.org/browse/DATA-1683 

1. Resource is then added to: https://github.com/gimmefreshdata
2. then added to queue in: http://archive.effechecka.org/

                                2017-Jun-06
observations.txt rows total:    463616

Todo: get remote raw file using script
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/FreeDataAPI');
$timestart = time_elapsed();

// /*
//local - during development
$local_path = "";
// */

/*
//remote - actual
*/

$func = new FreeDataAPI();
$func->generate_usgs_archive($local_path);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>
