<?php
namespace php_active_record;
/* This can be a generic way of adding canonical to taxa.

php update_resources/connectors/add_canonical_in_taxa.php _ '{"resource_id": "WoRMS2EoL_zip"}'
*/
exit("\nNo longer used. Moved to resource_utility.php\n");
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
// $GLOBALS['ENV_DEBUG'] = true;

// print_r($argv);
$params['jenkins_or_cron'] = @$argv[1]; //not needed here
$param                     = json_decode(@$argv[2], true);
$resource_id = $param['resource_id'];
print_r($param);

// /* ---------- customize here ----------
if($resource_id == 'WoRMS2EoL_zip') {
    if(Functions::is_production())  $dwca_file = "http://www.marinespecies.org/export/eol/WoRMS2EoL.zip";
    else                            $dwca_file = "http://localhost/cp/WORMS/WoRMS2EoL.zip";
}
else exit("\nERROR: File not yet initialized. Will terminate\n");
// ----------------------------------------*/
process_resource_url($dwca_file, $resource_id);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";

function process_resource_url($dwca_file, $resource_id)
{
    require_library('connectors/DwCA_Utility');
    $func = new DwCA_Utility($resource_id, $dwca_file);
    $preferred_rowtypes = array('http://rs.tdwg.org/dwc/terms/taxon'); //best to set this to array() and just set $excluded_rowtypes to taxon
    $excluded_rowtypes = array('http://rs.tdwg.org/dwc/terms/taxon');
    /* These below will be processed in ResourceUtility.php which will be called from DwCA_Utility.php
    http://rs.tdwg.org/dwc/terms/taxon
    */
    $func->convert_archive($preferred_rowtypes, $excluded_rowtypes);
    // Functions::finalize_dwca_resource($resource_id);
}
?>