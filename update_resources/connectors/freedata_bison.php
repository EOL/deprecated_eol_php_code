<?php
namespace php_active_record;
/* Connector for BISON resource: https://eol-jira.bibalex.org/browse/DATA-1699

1. Resource is then added to: https://github.com/gimmefreshdata
2. then added to queue in: http://archive.effechecka.org/

*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/FreshDataBisonAPI');
$timestart = time_elapsed();

// $a1 = array(1,2,3,4);
// if (($key = array_search('3', $a1)) !== false) {
//     unset($a1[$key]);
// }
// print_r($a1);
// exit("\n");

$func = new FreshDataBisonAPI("bison"); //'bison' will be a folder name
$func->start();

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>
