<?php
namespace php_active_record;
/* PaleoDB connector - from https://eol-jira.bibalex.org/browse/TRAM-746
we use their data service to assemble their data and ingest it as structured data
estimated execution time: 

368	Wednesday 2018-04-11 09:41:49 AM	{"measurement_or_fact.tab":2450420,"occurrence.tab":528288,"taxon.tab":359968,"vernacular_name.tab":4273} - local
368	Thursday 2018-04-12 06:34:45 AM	    {"measurement_or_fact.tab":2696122,"occurrence.tab":552850,"taxon.tab":359968,"vernacular_name.tab":4273}

368	Wednesday 2018-04-11 11:17:47 AM	{"measurement_or_fact.tab":2451203,"occurrence.tab":528502,"taxon.tab":360156,"vernacular_name.tab":4366} - eol-archive (without 'ignore' and multiple values yet)
368	Wednesday 2018-04-11 01:05:31 PM	{"measurement_or_fact.tab":2698963,"occurrence.tab":553064,"taxon.tab":360156,"vernacular_name.tab":4366} - eol-archive (with ignore and multiple values)
368	Thursday 2018-04-12 06:24:10 AM	    {"measurement_or_fact.tab":2697018,"occurrence.tab":553064,"taxon.tab":360156,"vernacular_name.tab":4366}
368	Monday 2018-04-16 08:26:19 AM	    {"measurement_or_fact.tab":2687714,"occurrence.tab":550882,"taxon.tab":358530,"vernacular_name.tab":4361} - excluding descendants of parents without entries
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
ini_set('memory_limit','8096M');
$timestart = time_elapsed();
$resource_id = 368;

// /*
require_library('connectors/PaleoDBAPI_v2');
$func = new PaleoDBAPI_v2($resource_id);
$func->get_all_taxa();
unset($func);
Functions::finalize_dwca_resource($resource_id);
// */

// /* utility - but also now became part of the entire process since we're going to remove taxa that are descendants of 'parents without entries'.
require_library('connectors/DWCADiagnoseAPI');
$func = new DWCADiagnoseAPI();
if($parents_without_entries = $func->check_if_all_parents_have_entries($resource_id, true)) { //2nd param True means write to text file
    print_r($parents_without_entries);
}
else echo "\nAll parents have entries OK (1st try)\n";
// */

if($parents_without_entries)
{
    /* Given a list of parent_ids, get all descendants of these taxa. Get all the taxon_ids of descendants. */
    $func = new PaleoDBAPI_v2($resource_id);
    $dwca_file = CONTENT_RESOURCE_LOCAL_PATH . "$resource_id".".tar.gz";
    $descendant_taxon_ids = $func->get_descendants_given_parent_ids($dwca_file, $parents_without_entries);
    // print_r($descendant_taxon_ids);
}
else $descendant_taxon_ids = array();

// /* 2nd round
$func = new PaleoDBAPI_v2($resource_id);
$func->get_all_taxa($descendant_taxon_ids);
unset($func);
Functions::finalize_dwca_resource($resource_id);

$func = new DWCADiagnoseAPI();
if($undefined = $func->check_if_all_parents_have_entries($resource_id, true)) { //2nd param True means write to text file
    $arr['parents without entries'] = $undefined;
    print_r($arr);
}
else echo "\nAll parents have entries OK (2nd try)\n";
// */

recursive_rmdir(CONTENT_RESOURCE_LOCAL_PATH . $resource_id); //to remove temp folder /368/

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>