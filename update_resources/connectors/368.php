<?php
namespace php_active_record;
/* PaleoDB connector - we use their data services to assemble their data and ingest it as structured data
estimated execution time: 40 minutes | 1.9 hours
This resource is formerly id = 719

            4-Sep-2015  11-Jul-2016
taxon       [234423]    [263815]
occurrence  [972262]    [1103650]
measurement [972262]    [1103650]
vernacular  [3753]      [3911]
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
$resource_id = 368;

// /*
require_library('connectors/PaleoDBAPI');
$func = new PaleoDBAPI($resource_id);
$func->get_all_taxa();
Functions::finalize_dwca_resource($resource_id);
// */

// /* utility
require_library('connectors/DWCADiagnoseAPI');
$func = new DWCADiagnoseAPI();
$func->check_if_all_parents_have_entries($resource_id);
// */

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";

?>