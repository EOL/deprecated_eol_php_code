<?php
namespace php_active_record;
/* connector for Maryland Dept. of Natural Resources: https://eol-jira.bibalex.org/browse/DATA-1693
1. Resource is then added to: https://github.com/gimmefreshdata
2. then added to queue in: http://archive.effechecka.org/
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/FreeDataAPI');
$timestart = time_elapsed();

$xls_zip_url = "";                                                                                      //NO actual remote source yet
$xls_zip_url = "http://localhost/cp/FreshData/Maryland Dept. of Natural Resources/ForNISC2017.xls.zip"; //local copy

$func = new FreeDataAPI("MarylandDNR");
$func->generate_MarylandDNR_archive($xls_zip_url);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>
