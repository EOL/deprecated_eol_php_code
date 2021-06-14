<?php
namespace php_active_record;
/* */

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
// echo "\n".date("Y_m_d_H_i_s")."\n"; exit;
// $GLOBALS['ENV_DEBUG'] = true;

require_library('connectors/DwCA_Utility');
// ini_set('memory_limit','9096M'); //required

$resource_id = "SC_unitedstates";
$dwca = "https://editors.eol.org/eol_php_code/applications/content_server/resources/SC_unitedstates.tar.gz";
$func = new DwCA_Utility($resource_id, $dwca);
$preferred_rowtypes = array();
$excluded_rowtypes = array('http://eol.org/schema/association', 'http://rs.tdwg.org/dwc/terms/occurrence', 'http://eol.org/schema/reference/reference');
$func->convert_archive($preferred_rowtypes, $excluded_rowtypes);
Functions::finalize_dwca_resource($resource_id, true, false, $timestart); //3rd param true means delete folder
$ret = run_utility($resource_id); //check if Associations is finally fixed. Should be fixed at this point.
recursive_rmdir(CONTENT_RESOURCE_LOCAL_PATH.$resource_id."/"); //we can now delete folder after run_utility() - DWCADiagnoseAPI


function run_utility($resource_id)
{
    // /* utility ==========================
    require_library('connectors/DWCADiagnoseAPI');
    $func = new DWCADiagnoseAPI();
    $ret = $func->check_if_source_and_taxon_in_associations_exist($resource_id, false, 'occurrence_specific.tab');
    echo "\nundefined source occurrence [$resource_id]:" . count(@$ret['undefined source occurrence'])."\n";
    echo "\nundefined target occurrence [$resource_id]:" . count(@$ret['undefined target occurrence'])."\n";
    return $ret;
    // ===================================== */
}
?>