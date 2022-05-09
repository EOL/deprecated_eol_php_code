<?php
namespace php_active_record;
/* First client for DWCA_Measurements_Fix() is to clean SC_unitedstates.tar.gz
Can be used as template for any resource.
1st client:
final_SC_unitedstates	Mon 2021-06-14 10:06:17 AM	{"measurement_or_fact_specific.tab":337634, "occurrence.tab":168817, "reference.tab":2, "taxon.tab":215560, "time_elapsed":{"sec":243.69, "min":4.06, "hr":0.07}}
final_SC_unitedstates	Mon 2021-06-14 10:45:35 PM	{"measurement_or_fact_specific.tab":337634, "occurrence.tab":168817, "reference.tab":2, "taxon.tab":215560, "time_elapsed":{"sec":228.22, "min":3.8, "hr":0.06}}

php update_resources/connectors/dwca_MoF_fix.php _ '{"resource_id":"SC_unitedstates"}'


Other clients:
php update_resources/connectors/dwca_MoF_fix.php _ '{"resource_id":"26_delta_new", "resource":"MoF_normalized"}'
-> WoRMS

php update_resources/connectors/dwca_MoF_fix.php _ '{"resource_id":"22_cleaned_MoF_habitat", "resource":"MoF_normalized"}'
-> Animal Diversity Web

*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
// echo "\n".date("Y_m_d_H_i_s")."\n"; exit;
// $GLOBALS['ENV_DEBUG'] = true;
require_library('connectors/DwCA_Utility');
// ini_set('memory_limit','9096M'); //required

// print_r($argv);
$params['jenkins_or_cron']  = @$argv[1]; //not needed here
$params                     = json_decode(@$argv[2], true);
$resource_id = @$params['resource_id']; 

if(Functions::is_production())  $dwca = 'https://editors.eol.org/eol_php_code/applications/content_server/resources/'.$resource_id.'.tar.gz';
else                            $dwca = 'http://localhost/eol_php_code/applications/content_server/resources/'.$resource_id.'.tar.gz';

// /* ---------- CUSTOMIZE HERE: ----------
if($resource_id == "SC_unitedstates")   $resource_id = "final_SC_unitedstates";
elseif($resource_id == "26_delta_new")              $resource_id = "26_MoF_normalized"; //WoRMS
elseif($resource_id == "22_cleaned_MoF_habitat")    $resource_id = "22_MoF_normalized"; //Animal Diversity Web
else exit("\nresource ID not yet initialized [$resource_id]\n");
// ---------------------------------------- */


$func = new DwCA_Utility($resource_id, $dwca, $params);
$preferred_rowtypes = array();
$excluded_rowtypes = array('http://rs.tdwg.org/dwc/terms/measurementorfact');
$func->convert_archive($preferred_rowtypes, $excluded_rowtypes);
Functions::finalize_dwca_resource($resource_id, true, false, $timestart); //3rd param true means delete folder
$ret = run_utility($resource_id); //check for orphan records in MoF
recursive_rmdir(CONTENT_RESOURCE_LOCAL_PATH.$resource_id."/"); //we can now delete folder after run_utility() - DWCADiagnoseAPI

function run_utility($resource_id)
{
    // /* utility ==========================
    require_library('connectors/DWCADiagnoseAPI');
    $func = new DWCADiagnoseAPI();
    $undefined_parents = $func->check_if_all_parents_have_entries($resource_id, true, false, false, 'parentMeasurementID', 'measurement_or_fact_specific.tab');
    echo "\nTotal undefined parents MoF:" . count($undefined_parents)."\n";
    // ===================================== */
}
?>