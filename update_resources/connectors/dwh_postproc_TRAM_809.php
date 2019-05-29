<?php
namespace php_active_record;
/* TRAM-809: Add taxonomicStatus to Dynamic Hierarchy V1.1 and fetch synonyms */
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/DH_v1_1_taxonomicStatus_synonyms');
ini_set('memory_limit','7096M'); 
// $GLOBALS['ENV_DEBUG'] = true;
$timestart = time_elapsed();

/*
$ordered_sources = Array('0' => 'trunk', '1' => 'ictv', '2' => 'IOC', '3' => 'ASW', '4' => 'ODO', '5' => 'BOM', '6' => 'ERE', 
    '7' => 'COC', '8' => 'VSP', '9' => 'ONY', '10' => 'EET', '11' => 'NCBI', '12' => 'WOR', '13' => 'CLP', '14' => 'COL');
print_r($ordered_sources);
$recs[] = Array(
            'taxonID' => 53689392,
            'source' => 'COL',
            'scientificName' => 'Thalictrum montanum',
            'taxonomicStatus' => 'synonym',
            'acceptedNameUsageID' => 'xxx'
        );
$recs[] = Array(
            'taxonID' => 222,
            'source' => 'IOC',
            'scientificName' => 'Thalictrum montanum Elix',
            'taxonomicStatus' => 'synonym',
            'acceptedNameUsageID' => 'xxx'
        );
$recs[] = Array(
            'taxonID' => 53689393,
            'source' => 'COL',
            'scientificName' => 'Thalictrum montanum',
            'taxonomicStatus' => 'synonym',
            'acceptedNameUsageID' => 'xxx'
        );
$recs[] = Array(
            'taxonID' => 53689394,
            'source' => 'COL',
            'scientificName' => 'Thalictrum montanum',
            'taxonomicStatus' => 'synonym',
            'acceptedNameUsageID' => 'xxx'
        );
$recs[] = Array(
            'taxonID' => 111,
            'source' => 'ASW',
            'scientificName' => 'Thalictrum montanum',
            'taxonomicStatus' => 'synonym',
            'acceptedNameUsageID' => 'xxx'
        );
print_r($recs);
$cont = true;
foreach($ordered_sources as $source) {
    foreach($recs as $rec) {
        if($source == $rec['source']) {
            $final['retain'] = $rec['taxonID']."_".$rec['scientificName']."_".$rec['acceptedNameUsageID'];
            $cont = false;
            break;
        }
    }
    if(!$cont) break;
}
foreach($recs as $rec) {
    $temp = $rec['taxonID']."_".$rec['scientificName']."_".$rec['acceptedNameUsageID'];
    if($temp != $final['retain']) $final['discard'][] = $rec;
}
print_r($final);
exit("\n-end test-\n");
*/

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
exit;
        // --> input: 
        // $this->main_path."/new_DH_before_step4.txt";                    //last DH output of TRAM-808
        // $this->main_path."/with_higherClassification/1558361160.txt";   //last DH output of TRAM-808 --> **ALWAYS USE with higherClassification
                                                     // 1558827333.txt     //latest May 26, 2019
                                                     // 1559013226.txt     //latest May 28, 2019
        // --> output:
        // $this->main_path_TRAM_809."/new_DH_taxonStatus.txt";            //new DH with taxonomicStatus
*/
/*
$func->create_append_text(); exit("\n-end create_append_text-\n"); //done only once; worked OK
*/
/*
$func->step_2(); //2. Fetch synonyms & metadata from DH sources
// exit;
        // --> input:
        // $this->main_path_TRAM_809."/new_DH_taxonStatus.txt";            //new DH with taxonomicStatus
        // --> output:
        // $this->main_path_TRAM_809."/synonyms.txt";
        // $this->main_path_TRAM_809."/synonyms_removed_in_step3.txt";
*/

/* step 4 is missing, wrong number increment in Jira ticket
$func->step_5(); //5. Add manually curated synonyms
        // --> appends to:
        // $this->main_path_TRAM_809."/synonyms.txt";
*/

/*
$func->step_6(); //6. Deduplicate synonyms
        // --> input:
        // $this->main_path_TRAM_809."/synonyms.txt";
        // --> to be removed from synonyms.txt:
        // $this->main_path_TRAM_809."/synonyms_2be_discarded.txt"; -- generated here as well
        // --> output:
        // $this->main_path_TRAM_809."/synonyms_deduplicated.txt";
// $func->regenerate_synonyms_without_duplicates(); //normally runs after step_6(), coded that way. Or run separately here...during development
*/

// /*
require_library('connectors/DH_minting_synonyms');
$syn = new DH_minting_synonyms(1);
$syn->mint_synonym_ids(); echo "\n-End minting synonyms-\n";            //can run this with add_synonyms_to_DH() AND run_diagnostics()
    // --> use:
    // $this->main_path_TRAM_809."/synonyms_deduplicated.txt";
    // --> output:
    // $this->main_path_TRAM_809."/synonyms_minted.txt";
// */

// /*
echo "\n-Start adding synonyms to final DH-\n";
$func->add_synonyms_to_DH();
    // --> use:
    // $this->main_path_TRAM_809."/synonyms_minted.txt";
    // $this->main_path_TRAM_809."/new_DH_taxonStatus.txt";     //new DH with taxonomicStatus
    // --> output:
    // $this->main_path_TRAM_809."/new_DH_with_synonyms.txt";   //final new DH with synonyms
// */

// /* important utility: check ancestry integrity of new DH
$taxa_file = "/Volumes/AKiTiO4/d_w_h/TRAM-809/new_DH_with_synonyms.txt";
run_diagnostics(false, $taxa_file); exit;
// as of May 29, 2019:
// OK: All parents in taxon.tab have entries. OK
// OK: All acceptedNameUsageID have entries. OK
// */

/*
$func->add_landmark_to_DH(); //TRAM-810: Add landmarks to DH 1.1
    // --> use:
    // $this->main_path_TRAM_809."/new_DH_with_synonyms.txt";   //final new DH with synonyms
    // --> output:
    // $this->main_path_TRAM_809."/new_DH_with_landmarks.txt";  //final new DH
*/


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