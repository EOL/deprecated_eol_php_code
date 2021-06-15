<?php
namespace php_active_record;
/* First client for DWCA_Measurements_Fix() is to clean SC_unitedstates.tar.gz
Can be used as template for any resource. 

final_SC_unitedstates	Mon 2021-06-14 10:06:17 AM	{"measurement_or_fact_specific.tab":337634, "occurrence.tab":168817, "reference.tab":2, "taxon.tab":215560, "time_elapsed":{"sec":243.69, "min":4.06, "hr":0.07}}
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
// echo "\n".date("Y_m_d_H_i_s")."\n"; exit;
// $GLOBALS['ENV_DEBUG'] = true;
require_library('connectors/DwCA_Utility');
// ini_set('memory_limit','9096M'); //required

// /* INPUT:
$resource_id = "final_SC_unitedstates";
$dwca = "https://editors.eol.org/eol_php_code/applications/content_server/resources/SC_unitedstates.tar.gz";
// */

$func = new DwCA_Utility($resource_id, $dwca);
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