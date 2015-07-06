<?php
namespace php_active_record;
// DATA-1544 Structured data from wikipedia for fungi

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/WikipediaMycologicalAPI');
$timestart = time_elapsed();
$resource_id = 879;
$func = new WikipediaMycologicalAPI($resource_id);
$func->get_all_taxa();

Functions::finalize_dwca_resource($resource_id);


$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec . " seconds";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>