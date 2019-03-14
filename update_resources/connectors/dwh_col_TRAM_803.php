<?php
namespace php_active_record;
/* Reharvest & revise COL & CLP for Dynamic Hierarchy - TRAM-803
estimated execution time: 
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/DWH_CoL_API_20Feb2019');
ini_set('memory_limit','15096M');
// ini_set('memory_limit','6096M');
// $GLOBALS['ENV_DEBUG'] = true;
$timestart = time_elapsed();


/* utility - check unique ids
require_library('connectors/DWCADiagnoseAPI');
$func = new DWCADiagnoseAPI();
$resource_id = "Catalogue_of_Life_DH_20Feb2019";            $func->check_unique_ids($resource_id);
$resource_id = "Catalogue_of_Life_Protists_DH_20Feb2019";   $func->check_unique_ids($resource_id);
exit("\n-end utility\n");
*/

//############################################################ start main CoL DH
// /*
$resource_id = "Catalogue_of_Life_DH_20Feb2019"; //to be used in final step
$resource_id = "Catalogue_of_Life_DH_step1";
$func = new DWH_CoL_API_20Feb2019($resource_id);
$func->start_tram_803();
$func = null;
Functions::finalize_dwca_resource($resource_id, true);
// utility - takes time for this resource but very helpful to catch if all parents have entries.
run_diagnostics($resource_id);
// exit("\n-End for now-\n");
// */
//############################################################ end main CoL DH
//############################################################ start CoL Protists
// /*
$resource_id = "Catalogue_of_Life_Protists_DH_20Feb2019"; //to be used in final step
$resource_id = "Catalogue_of_Life_Protists_DH_step1";
$func = new DWH_CoL_API_20Feb2019($resource_id);
$func->start_ColProtists();
$func = null;
Functions::finalize_dwca_resource($resource_id, true);
// utility below - takes time for this resource but very helpful to catch if all parents have entries.
run_diagnostics($resource_id);
$func = false;
// */
//############################################################ end CoL Protists
//############################################################ start CoL Protists fix "NOT ASSIGNED TAXA"
// /*
$resource_id = "Catalogue_of_Life_Protists_DH_step2";
$func = new DWH_CoL_API_20Feb2019($resource_id);
$func->fix_CLP_taxa_with_not_assigned_entries_V2();
Functions::finalize_dwca_resource($resource_id, true);
run_diagnostics($resource_id);
// */
//############################################################ end CoL Protists
//############################################################ start "DUPLICATE TAXA" --- maybe the final step
$resource_id = "Catalogue_of_Life_Protists_DH_step3";
$func = new DWH_CoL_API_20Feb2019($resource_id);
$func->duplicate_process('CLP');
Functions::finalize_dwca_resource($resource_id, true);
run_diagnostics($resource_id);

$resource_id = "Catalogue_of_Life_DH_step2";
$func = new DWH_CoL_API_20Feb2019($resource_id);
$func->duplicate_process('COL');
Functions::finalize_dwca_resource($resource_id, true);
run_diagnostics($resource_id);
//############################################################ end "DUPLICATE TAXA"

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";

Function run_diagnostics($resource_id)
{
    require_library('connectors/DWCADiagnoseAPI');
    $func = new DWCADiagnoseAPI();
    // $func->check_unique_ids($resource_id);

    $undefined = $func->check_if_all_parents_have_entries($resource_id, true); //true means output will write to text file
    if($undefined) echo "\nERROR: There is undefined parent(s): ".count($undefined)."\n";
    else           echo "\nOK: All parents in taxon.tab have entries.\n";

    $undefined = $func->check_if_all_parents_have_entries($resource_id, true, false, array(), "acceptedNameUsageID"); //true means output will write to text file
    if($undefined) echo "\nERROR: There is undefined acceptedNameUsageID(s): ".count($undefined)."\n";
    else           echo "\nOK: All acceptedNameUsageID have entries.\n";
}
?>