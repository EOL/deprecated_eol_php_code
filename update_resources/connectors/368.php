<?php
namespace php_active_record;
/* PaleoDB connector - we use their data services to assemble their data and ingest it as structured data
estimated execution time: 40 minutes | 1.9 hours
This resource is formerly id = 719
                                    2017
            4-Sep-2015  11-Jul-2016 8-Oct
taxon       [234423]    [263815]    275509
occurrence  [972262]    [1103650]   1156975
measurement [972262]    [1103650]   1156975
vernacular  [3753]      [3911]      4023

eol-archive:
368 Sunday 2017-10-08 03:17:15 AM       {"measurement_or_fact.tab":1156975,"occurrence.tab":1156975,"taxon.tab":275509,"vernacular_name.tab":4023} eol-archive
368	Wednesday 2017-12-20 10:07:26 AM	{"measurement_or_fact.tab":1156975,"occurrence.tab":1156975,"taxon.tab":275391,"vernacular_name.tab":4023} eol-archive
368	Thursday 2018-03-29 07:33:16 PM	    {"measurement_or_fact.tab":1156975,"occurrence.tab":1156975,"taxon.tab":275391,"vernacular_name.tab":4023}

MacMini:
368 Wednesday 2017-12-20 05:33:15 AM    {"measurement_or_fact.tab":1103650,"occurrence.tab":1103650,"taxon.tab":263656,"vernacular_name.tab":3911}
368	Thursday 2018-03-29 12:56:30 PM	    {"measurement_or_fact.tab":1156975,"occurrence.tab":1156975,"taxon.tab":275389,"vernacular_name.tab":4023}
*/

return; //this is now replaced by connector: pbdb_fresh_harvest.php | PaleoDBAPI_v2.php
include_once(dirname(__FILE__) . "/../../config/environment.php");
ini_set('memory_limit','8096M');
$timestart = time_elapsed();
$resource_id = 368;

// /*
require_library('connectors/PaleoDBAPI');
$func = new PaleoDBAPI($resource_id);
$func->get_all_taxa();
unset($func);
Functions::finalize_dwca_resource($resource_id);
// */

// /* utility
require_library('connectors/DWCADiagnoseAPI');
$func = new DWCADiagnoseAPI();
if($undefined = $func->check_if_all_parents_have_entries($resource_id)) print_r($undefined);
else echo "\nAll parents have entries OK\n";
// */

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>