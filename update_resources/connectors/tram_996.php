<?php
namespace php_active_record;
/* TRAM-996: Fetch synonyms from DH source data sets */
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/DH_v21_TRAM_996');
ini_set('memory_limit','8096M');
// $GLOBALS['ENV_DEBUG'] = true;
$timestart = time_elapsed();

//############################################################ start main
$resource_id = "DH_v21";
$func = new DH_v21_TRAM_996($resource_id);

// /* main operation
$func->start($resource_id);
// unset($func);
// */

/* copied template
$func->generate_dwca(); //works OK - final step
Functions::finalize_dwca_resource($resource_id, true, false);
run_diagnostics($resource_id);
*/

/* stats:
counting: [/opt/homebrew/var/www/eol_php_code/applications/content_server/resources/DH_v1_1_postproc/taxon.tab] total: [2338864]
*/
//############################################################ end main

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
Function run_diagnostics($resource_id) // utility - takes time for this resource but very helpful to catch if all parents have entries.
{
    require_library('connectors/DWCADiagnoseAPI');
    $func = new DWCADiagnoseAPI();
    // $func->check_unique_ids($resource_id); //takes time

    $undefined = $func->check_if_all_parents_have_entries($resource_id, true); //true means output will write to text file
    if($undefined) echo "\nERROR: There is undefined parent(s): ".count($undefined)."\n";
    else           echo "\nOK: All parents in taxon.tab have entries.\n";

    $undefined = $func->check_if_all_parents_have_entries($resource_id, true, false, array(), "acceptedNameUsageID"); //true means output will write to text file
    if($undefined) echo "\nERROR: There is undefined acceptedNameUsageID(s): ".count($undefined)."\n";
    else           echo "\nOK: All acceptedNameUsageID have entries.\n";
}
?>