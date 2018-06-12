<?php
namespace php_active_record;
/* Turbellarian Taxonomic Database
estimated execution time:

This is now the new connector: https://eol-jira.bibalex.org/browse/TRAM-709 (June 2018)
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");

/*
$a["900x"] = "";
$a["200x"] = "";
$b[(string)"1000"] = "";
$b["173"] = "";
$c = array_merge($a, $b);
$d["123"] = "";
$c = array_merge($c, $d, array());

print_r($c); exit;
*/

require_library('connectors/TurbellarianAPI_v2');
$timestart = time_elapsed();
$resource_id = 185;
$func = new TurbellarianAPI_v2($resource_id);
$func->start();
Functions::finalize_dwca_resource($resource_id);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>