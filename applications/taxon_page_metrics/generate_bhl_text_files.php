<?php
$GLOBALS['ENV_DEBUG'] = false;
//$GLOBALS['ENV_NAME'] = "integration";
//$GLOBALS['ENV_NAME'] = "staging";

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = microtime(1);
$temp_time = time_elapsed();            

ini_set('memory_limit', '1500M');
set_time_limit(0);

$mysqli =& $GLOBALS['mysqli_connection'];
require_library('SiteStatistics');
$stats = new SiteStatistics();

//$stats->generate_taxon_concept_with_bhl_links_textfile();           //24 mins      
$stats->generate_taxon_concept_with_bhl_publications_textfile();    //10 hrs single-query approach --- should be run ahead of time
       
$elapsed_time_sec = microtime(1)-$timestart;
$time_elapsed_sec = time_elapsed() - $temp_time;

echo "\n elapsed time = $elapsed_time_sec sec               ";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " mins   ";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hrs ";
echo "\n";
echo "\n elapsed time = $time_elapsed_sec sec               ";
echo "\n elapsed time = " . $time_elapsed_sec/60 . " mins   ";
echo "\n elapsed time = " . $time_elapsed_sec/60/60 . " hrs ";
?>