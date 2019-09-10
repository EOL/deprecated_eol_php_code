<?php
namespace php_active_record;
/* GBIF classification update - https://eol-jira.bibalex.org/browse/DATA-1826 */

include_once(dirname(__FILE__) . "/../../config/environment.php");
ini_set('memory_limit','8096M');
$timestart = time_elapsed();
$resource_id = 'gbif_classification';
require_library('connectors/GBIF_backboneAPI');

$func = new GBIF_backboneAPI($resource_id);
$func->start();
unset($func);
Functions::finalize_dwca_resource($resource_id);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";

?>