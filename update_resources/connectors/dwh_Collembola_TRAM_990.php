<?php
namespace php_active_record;
/* Collembola Patch for DH2 - TRAM-990
estimated execution time:

Collembola_DH	Wed 2020-09-23 03:25:04 AM	{"taxon.tab":9301, "time_elapsed":{"sec":1042.85, "min":17.38, "hr":0.29}} - Mac Mini
Collembola_DH	Wed 2020-09-23 05:23:44 AM	{"taxon.tab":9301, "time_elapsed":{"sec":524.36, "min":8.74, "hr":0.15}} - eol-archive
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/DWH_Collembola_API');
// ini_set('memory_limit','15096M');
// ini_set('memory_limit','6096M');
// $GLOBALS['ENV_DEBUG'] = true;
$timestart = time_elapsed();

//############################################################ start main CoL DH
$resource_id = "Collembola_DH";
// /*
$func = new DWH_Collembola_API($resource_id);
$func->start_tram_990();
$func = null;
Functions::finalize_dwca_resource($resource_id, false, false, $timestart);
// */
run_diagnostics($resource_id);
//############################################################ end main CoL DH

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