<?php
namespace php_active_record;
/* this is used to investigate a DwCA, and its extensions. */
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
// ini_set('memory_limit','7096M'); //required

$resource_id = 'globi_associations';
// $resource_id = 'globi_associations_final';
run_utility($resource_id);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";

function run_utility($resource_id)
{
    // /* utility ==========================
    require_library('connectors/DWCADiagnoseAPI');
    $func = new DWCADiagnoseAPI();
    
    // /* works OK
    $func->investigate_extension($resource_id, 'association.tab');
    // $func->investigate_extension($resource_id, 'occurrence_specific.tab');
    // */
    
    /* check Associations integrity:
    $ret = $func->check_if_source_and_taxon_in_associations_exist($resource_id, false, 'occurrence_specific.tab');
    echo "\nundefined source occurrence [$resource_id]:" . count(@$ret['undefined source occurrence'])."\n";
    echo "\nundefined target occurrence [$resource_id]:" . count(@$ret['undefined target occurrence'])."\n";
    */
    
    /* all valid tests: OK
    $undefined_parents = $func->check_if_all_parents_have_entries($resource_id, true); //2nd param true means output will write to text file
    echo "\nTotal undefined parents:" . count($undefined_parents)."\n"; unset($undefined_parents);

    $without = $func->get_all_taxa_without_parent($resource_id, true); //true means output will write to text file
    echo "\nTotal taxa without parents:" . count($without)."\n"; unset($without);

    $undefined_parents = $func->check_if_all_parents_have_entries($resource_id, true, false, false, 'parentMeasurementID', 'measurement_or_fact_specific.tab');
    echo "\nTotal undefined parents MoF:" . count($undefined_parents)."\n";
    */
    // ===================================== */
}
?>