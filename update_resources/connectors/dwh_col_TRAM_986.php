<?php
namespace php_active_record;
/* Catalogue of Life Extract for DH2 - TRAM-986
estimated execution time: 

Catalogue_of_Life_DH_2019	Tuesday 2020-08-18 09:53:35 PM	{"taxon.tab":1775492, "time_elapsed":false}
Catalogue_of_Life_DH_2019	Tue 2020-09-22 04:36:35 AM	    {"taxon.tab":1773309, "time_elapsed":false} without Collembola TRAM-990
Catalogue_of_Life_DH_2019	Wed 2021-01-20 02:55:20 AM	    {"taxon.tab":1769184, "time_elapsed":false}
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/DWH_CoL_API_2019AnnualCL');
ini_set('memory_limit','15096M');
// ini_set('memory_limit','6096M');
$GLOBALS['ENV_DEBUG'] = true;
$timestart = time_elapsed();

//############################################################ start main CoL DH
// /*
$resource_id = "Catalogue_of_Life_DH_step1";
$func = new DWH_CoL_API_2019AnnualCL($resource_id);
$func->start_tram_803();
$func = null;
Functions::finalize_dwca_resource($resource_id, true);
run_diagnostics($resource_id);
// */
//############################################################ end main CoL DH
//############################################################ start CLP & COL fix "NOT ASSIGNED TAXA"
// /*
$resource_id = "Catalogue_of_Life_DH_step2";            
$func = new DWH_CoL_API_2019AnnualCL($resource_id);
$func->fix_CLP_taxa_with_not_assigned_entries_V2('COL');
Functions::finalize_dwca_resource($resource_id, true);
run_diagnostics($resource_id);
// */
//############################################################ end CLP & COL
//############################################################ start "DUPLICATE TAXA" A. Merge duplicate genera
// /*
$resource_id = "Catalogue_of_Life_DH_step3";
$func = new DWH_CoL_API_2019AnnualCL($resource_id);
$func->duplicate_process_A('COL');
Functions::finalize_dwca_resource($resource_id, true);
run_diagnostics($resource_id);
// */
//############################################################ end "DUPLICATE TAXA" A. Merge duplicate genera

//############################################################ start "DUPLICATE TAXA" B. Remove duplicate species & infraspecifics
// /*
// $resource_id = "Catalogue_of_Life_DH_2020-08-01"; // still not better than 2019 annual checklist
// $resource_id = "Catalogue_of_Life_DH_2019_05_01"; // still not better than 2019 annual checklist
$resource_id = "Catalogue_of_Life_DH_2019";

$func = new DWH_CoL_API_2019AnnualCL($resource_id);
$func->duplicate_process_B('COL');
Functions::finalize_dwca_resource($resource_id, true);
run_diagnostics($resource_id);
// */
//############################################################ end "DUPLICATE TAXA" B. Remove duplicate species & infraspecifics

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