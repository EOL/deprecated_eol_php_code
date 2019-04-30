<?php
namespace php_active_record;
/* TRAM-807: Dynamic Hierarchy Version 1.1. Postprocessing
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/DH_v1_1_postProcessing');
// ini_set('memory_limit','15096M');
// ini_set('memory_limit','6096M');
// $GLOBALS['ENV_DEBUG'] = true;
$timestart = time_elapsed();

/*
$a[0] = 'eli';
$a[1] = 'cha';
$a[2] = 'isaiah';
unset($a[1]);
print_r($a);
exit("\n-end tests-\n");
*/

//############################################################ start main
// /*
$resource_id = "DH_v1_1_postproc";
$func = new DH_v1_1_postProcessing($resource_id);
// $func->start_tram_807(); //this creates taxonomy1.txt
$func->step_4pt2_of_9(); //this uses and starts with taxonomy1.txt from prev. step.
// Functions::finalize_dwca_resource($resource_id, true);
// run_diagnostics($resource_id);
// */
//############################################################ end main

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
/*
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
*/
?>