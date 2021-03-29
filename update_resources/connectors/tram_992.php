<?php
namespace php_active_record;
/* TRAM-992: Create a list of taxonIDs used in branch painting data sets

e.g. land-and-fernald-1992, marine-ecology-literature/resource/8fd26874-96b9-42ff-abc8-534c931497ba
To get the real URL:
https://opendata.eol.org/dataset/land-and-fernald-1992
https://opendata.eol.org/dataset/marine-ecology-literature/resource/8fd26874-96b9-42ff-abc8-534c931497ba
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/TRAM_992_API');
$timestart = time_elapsed();

/*
$url = "https://opendata.eol.org/dataset/86081133-3db1-4ffc-8b1f-2bbba1d1f948/resource/b9951366-90e8-475e-927e-774b95faf7ed/download/hardtomatch.tar.gz";
print_r(pathinfo($url));
exit("\n-end test-\n");
*/

$func = new TRAM_992_API();
$func->start();
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>