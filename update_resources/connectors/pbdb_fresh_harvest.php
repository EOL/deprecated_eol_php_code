<?php
namespace php_active_record;
/* PaleoDB connector - from https://eol-jira.bibalex.org/browse/TRAM-746
we use their data service to assemble their data and ingest it as structured data
estimated execution time: 
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
// ini_set('memory_limit','8096M');
$timestart = time_elapsed();
$resource_id = 368;

// /*
require_library('connectors/PaleoDBAPI_v2');
$func = new PaleoDBAPI_v2($resource_id);
$func->get_all_taxa();
unset($func);
// exit;
Functions::finalize_dwca_resource($resource_id);
// */

/* utility
require_library('connectors/DWCADiagnoseAPI');
$func = new DWCADiagnoseAPI();
if($undefined = $func->check_if_all_parents_have_entries($resource_id)) print_r($undefined);
else echo "\nAll parents have entries OK\n";
*/

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>