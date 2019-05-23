<?php
namespace php_active_record;
/* TRAM-809: Add taxonomicStatus to Dynamic Hierarchy V1.1 and fetch synonyms */
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/DH_v1_1_taxonomicStatus_synonyms');
ini_set('memory_limit','7096M'); 
// $GLOBALS['ENV_DEBUG'] = true;
$timestart = time_elapsed();

//############################################################ start main
$resource_id = "DH_v1_1_Stat_Syn";
$func = new DH_v1_1_taxonomicStatus_synonyms($resource_id);

/* from TRAM-808
$func->before_step_2_or_3("new_DH_after_step3", "step 3"); //--> uses [new_DH_after_step3.txt]
                                                           //--> generates [new_DH_before_step4.txt]
$taxa_file = "/Volumes/AKiTiO4/d_w_h/TRAM-808/new_DH_before_step4.txt";
run_diagnostics(false, $taxa_file);
*/

/*
$func->step_1(); //1. Add taxonomicStatus values to DH taxa (14.5 hours execution)
        // --> input: 
        // $this->main_path."/new_DH_before_step4.txt";                    //last DH output of TRAM-808
        // $this->main_path."/with_higherClassification/1558361160.txt";   //last DH output of TRAM-808 --> with higherClassification
        // --> output:
        // $this->main_path_TRAM_809."/new_DH_taxonStatus.txt";            //new DH with taxonomicStatus
*/
/*
$func->create_append_text(); exit("\n-end create_append_text-\n"); //done only once; worked OK
*/
// /*
$func->step_2(); //2. Fetch synonyms & metadata from DH sources
        // --> input:
        // $this->main_path_TRAM_809."/new_DH_taxonStatus.txt";            //new DH with taxonomicStatus
// */

// exit("\n-end for now-\n");
// $func->generate_dwca($resource_id);
// unset($func);
// Functions::finalize_dwca_resource($resource_id, true, false);
// run_diagnostics($resource_id);
//############################################################ end main

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
// /*
Function run_diagnostics($resource_id, $taxa_file = false) // utility - takes time for this resource but very helpful to catch if all parents have entries.
{
    require_library('connectors/DWCADiagnoseAPI');
    $func = new DWCADiagnoseAPI();
    // $func->check_unique_ids($resource_id); //takes time

    $undefined = $func->check_if_all_parents_have_entries($resource_id, true, $taxa_file); //true means output will write to text file
    if($undefined) echo "\nERROR: There is undefined parent(s): ".count($undefined)."\n";
    else           echo "\nOK: All parents in taxon.tab have entries.\n";

    $undefined = $func->check_if_all_parents_have_entries($resource_id, true, $taxa_file, array(), "acceptedNameUsageID"); //true means output will write to text file
    if($undefined) echo "\nERROR: There is undefined acceptedNameUsageID(s): ".count($undefined)."\n";
    else           echo "\nOK: All acceptedNameUsageID have entries.\n";
}
// */
?>