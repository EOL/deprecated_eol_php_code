<?php
$GLOBALS['ENV_DEBUG'] = false;
$GLOBALS['ENV_NAME'] = "integration";
//$GLOBALS['ENV_NAME'] = "staging";

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = microtime(1);
$temp_time = time_elapsed();            

ini_set('memory_limit', '1500M');
set_time_limit(0);

$mysqli =& $GLOBALS['mysqli_connection'];
require_library('SiteStatistics_v4');
$stats = new SiteStatistics_v4();

/* can only be run when the latest migrations are run in production */

//$stats->generate_taxon_concept_with_bhl_links_textfile(); //1.89 mins      
$stats->generate_taxon_concept_with_bhl_publications_textfile(); // singe-query approach --- should be run ahead of time
//$stats->generate_taxon_concept_with_bhl_publications_textfile2(); // multi-query approach --- proves to be slower than single-query approach
//$stats->create_page_metrics_table(); //1.5 hrs
       
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