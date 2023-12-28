<?php
namespace php_active_record;
/* 
task originated from: https://eol-jira.bibalex.org/browse/DATA-1917
First client is: TreatmentBank

php update_resources/connectors/clade_filters_4_habitats.php _ '{"resource_id":"TreatmentBank_adjustment_01"}'
-> generates TreatmentBank_adjustment_02.tar.gz
-> in Jenkins it is renamed to TreatmentBank_final.tar.gz

TreatmentBank_adjustment_02	Thu 2022-11-17 09:46:02 AM	{"MoF.tab":1673761, "occurrence_specific.tab":1673761, "taxon.tab":470607, "time_elapsed":{"sec":2142.42, "min":35.71, "hr":0.6}} mac mini
TreatmentBank_adjustment_02	Thu 2022-11-17 09:40:44 AM	{"MoF.tab":1673761, "occurrence_specific.tab":1673761, "taxon.tab":470607, "time_elapsed":{"sec":1177.72, "min":19.63, "hr":0.33}} eol-archive

*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
// $GLOBALS['ENV_DEBUG'] = true;

// print_r($argv);
$params['jenkins_or_cron']  = @$argv[1]; //not needed here
$params                     = json_decode(@$argv[2], true);
$resource_id = @$params['resource_id']; 

if(Functions::is_production())  $dwca_file = 'https://editors.eol.org/eol_php_code/applications/content_server/resources/'.$resource_id.'.tar.gz';
else                            $dwca_file = 'http://localhost/eol_php_code/applications/content_server/resources/'.$resource_id.'.tar.gz';

// /* ---------- CUSTOMIZE HERE: ----------
if($resource_id == "TreatmentBank_adjustment_01") $resource_id = "TreatmentBank_adjustment_02";   //TreatmentBank between tasks
else exit("\nresource ID not yet initialized [$resource_id]\n");
// ---------------------------------------- */

process_resource_url($dwca_file, $resource_id, $timestart);

function process_resource_url($dwca_file, $resource_id, $timestart)
{
    require_library('connectors/DwCA_Utility');
    $func = new DwCA_Utility($resource_id, $dwca_file);

    if($resource_id == 'TreatmentBank_adjustment_02') {
        $preferred_rowtypes = array();
        $excluded_rowtypes = array('http://rs.tdwg.org/dwc/terms/taxon', 
                                   'http://rs.tdwg.org/dwc/terms/measurementorfact', 'http://rs.tdwg.org/dwc/terms/occurrence');
    }
    else exit("\nresource ID not yet initialized [$resource_id]\n");
    
    /* $excluded_rowtypes will be processed in CladeSpecificFilters4Habitats_API.php */
    $func->convert_archive($preferred_rowtypes, $excluded_rowtypes);
    Functions::finalize_dwca_resource($resource_id, false, false, $timestart); //3rd param false means don't delete working folder yet
    
    // /* New: important to check if all parents have entries.
    require_library('connectors/DWCADiagnoseAPI');
    $func = new DWCADiagnoseAPI();
    $undefined_parents = $func->check_if_all_parents_have_entries($resource_id, true); //2nd param true means output will write to text file
    echo "\nTotal undefined parents:" . count($undefined_parents)."\n"; unset($undefined_parents);
    recursive_rmdir(CONTENT_RESOURCE_LOCAL_PATH.$resource_id."/"); //we can now delete folder after check_if_all_parents_have_entries() - DWCADiagnoseAPI
    // */
}
?>