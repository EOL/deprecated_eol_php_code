<?php
namespace php_active_record;
/* https://eol-jira.bibalex.org/browse/TRAM-811
estimated execution time:
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/AmphibiansOfTheWorldAPI');
$timestart = time_elapsed();

// /* normal operation
$resource_id = "aotw"; //amphibians of the world
$func = new AmphibiansOfTheWorldAPI($resource_id);
$func->start();
unset($func);
// Functions::finalize_dwca_resource($resource_id);
// */

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>
