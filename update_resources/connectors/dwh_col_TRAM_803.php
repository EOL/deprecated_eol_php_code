<?php
namespace php_active_record;
/* Reharvest & revise COL & CLP for Dynamic Hierarchy - TRAM-803
estimated execution time: 

eol-archive:
Catalogue_of_Life_Protists_DH_20Feb2019	Thursday 2019-03-28 11:08:24 PM	{"taxon.tab":20223}
Catalogue_of_Life_DH_20Feb2019	        Thursday 2019-03-28 11:38:17 PM	{"taxon.tab":2134024}

MacMini
Catalogue_of_Life_Protists_DH_20Feb2019	Thursday 2019-03-28 11:49:56 PM	{"taxon.tab":20223}
Catalogue_of_Life_DH_20Feb2019	        Friday 2019-03-29 12:37:39 AM	{"taxon.tab":2134024}

*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/DWH_CoL_API_20Feb2019');
ini_set('memory_limit','15096M');
// ini_set('memory_limit','6096M');
// $GLOBALS['ENV_DEBUG'] = true;
$timestart = time_elapsed();

/*
$a = Array(
    '46463756d3068caa095f0cfdd7d5898f' => 1953,     -> should be removed
    '224534e3c7a97f445510d28db81dd34a' => 1973,     -> should be removed
    '88a861a82332663835794693906560bd' => 1880);    -> taxon_id should remain
print_r($a);
asort($a); print_r($a);

foreach($a as $taxon_id => $numeric) break; //get the first taxon_id
exit("\n[$taxon_id]\n");
*/

/* utility - check unique ids
require_library('connectors/DWCADiagnoseAPI');
$func = new DWCADiagnoseAPI();
$resource_id = "Catalogue_of_Life_DH_20Feb2019";            $func->check_unique_ids($resource_id);
$resource_id = "Catalogue_of_Life_Protists_DH_20Feb2019";   $func->check_unique_ids($resource_id);
exit("\n-end utility\n");
*/
//############################################################ start main CoL DH
/*
$resource_id = "Catalogue_of_Life_DH_20Feb2019"; //to be used in final step, just manually rename it to "Catalogue_of_Life_DH_20Feb2019"
$resource_id = "Catalogue_of_Life_DH_step1";
$func = new DWH_CoL_API_20Feb2019($resource_id);
$func->start_tram_803();
$func = null;
Functions::finalize_dwca_resource($resource_id, true);
run_diagnostics($resource_id);
*/
//############################################################ end main CoL DH
//############################################################ start CoL Protists
/*
$resource_id = "Catalogue_of_Life_Protists_DH_20Feb2019"; //to be used in final step, just manually rename it to "Catalogue_of_Life_Protists_DH_20Feb2019"
$resource_id = "Catalogue_of_Life_Protists_DH_step1";
$func = new DWH_CoL_API_20Feb2019($resource_id);
$func->start_ColProtists();
$func = null;
Functions::finalize_dwca_resource($resource_id, true);
run_diagnostics($resource_id);
$func = false;
*/
//############################################################ end CoL Protists
//############################################################ start CLP & COL fix "NOT ASSIGNED TAXA"
/*
$resource_id = "Catalogue_of_Life_Protists_DH_step2";
$func = new DWH_CoL_API_20Feb2019($resource_id);
$func->fix_CLP_taxa_with_not_assigned_entries_V2('CLP');
Functions::finalize_dwca_resource($resource_id, true);
run_diagnostics($resource_id);

$resource_id = "Catalogue_of_Life_DH_step2";            //recently added. Forgot to implement 'not assigned' fix for COL.
$func = new DWH_CoL_API_20Feb2019($resource_id);
$func->fix_CLP_taxa_with_not_assigned_entries_V2('COL');
Functions::finalize_dwca_resource($resource_id, true);
run_diagnostics($resource_id);
*/
//############################################################ end CLP & COL
//############################################################ start "DUPLICATE TAXA" A. Merge duplicate genera
// /*
$resource_id = "Catalogue_of_Life_Protists_DH_step3";
$func = new DWH_CoL_API_20Feb2019($resource_id);
$func->duplicate_process_A('CLP');
Functions::finalize_dwca_resource($resource_id, true);
run_diagnostics($resource_id);

$resource_id = "Catalogue_of_Life_DH_step3";
$func = new DWH_CoL_API_20Feb2019($resource_id);
$func->duplicate_process_A('COL');
Functions::finalize_dwca_resource($resource_id, true);
run_diagnostics($resource_id);
// */
//############################################################ end "DUPLICATE TAXA" A. Merge duplicate genera

//############################################################ start "DUPLICATE TAXA" B. Remove duplicate species & infraspecifics
// /* doesn't seem to have records...initially...:
$resource_id = "Catalogue_of_Life_Protists_DH_20Feb2019";
$func = new DWH_CoL_API_20Feb2019($resource_id);
$func->duplicate_process_B('CLP');
Functions::finalize_dwca_resource($resource_id, true);
run_diagnostics($resource_id);
// */

// /*
$resource_id = "Catalogue_of_Life_DH_20Feb2019";
$func = new DWH_CoL_API_20Feb2019($resource_id);
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