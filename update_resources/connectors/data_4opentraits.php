<?php
namespace php_active_record;
/* DATA-1909: resource metadata and summary-from-resource-data export from CKAN
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/Data_OpenTraits');
$timestart = time_elapsed();

/*
$url = "https://opendata.eol.org/dataset/86081133-3db1-4ffc-8b1f-2bbba1d1f948/resource/b9951366-90e8-475e-927e-774b95faf7ed/download/hardtomatch.tar.gz";
print_r(pathinfo($url));
exit("\n-end test-\n");
*/

$func = new Data_OpenTraits();
$func->start();
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>