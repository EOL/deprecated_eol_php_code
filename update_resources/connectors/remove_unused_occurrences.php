<?php
namespace php_active_record;
/* This is generic way of removing unused occurrences.
first client: GloBI 
    php update_resources/connectors/remove_unused_occurrences.php _ '{"resource_id": "globi_associations_tmp1", "resource": "remove_unused_occurrences", "resource_name": "GloBI"}'
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
// $GLOBALS['ENV_DEBUG'] = true;

// print_r($argv);
$params['jenkins_or_cron'] = @$argv[1]; //not needed here
$param                     = json_decode(@$argv[2], true);
$resource_id = $param['resource_id'];
print_r($param);

// /*
if(Functions::is_production()) $dwca_file = CONTENT_RESOURCE_LOCAL_PATH . "/$resource_id" . ".tar.gz";
else                           $dwca_file = 'http://localhost/eol_php_code/applications/content_server/resources_3/'.$resource_id.'.tar.gz';
// */

// /* ---------- customize here ----------
    if($resource_id == 'globi_associations_tmp1')   $resource_id = "globi_associations_tmp2";
elseif($resource_id == 'the source')                $resource_id = "final dwca"; //add other resources here...
else exit("\nERROR: resource_id not yet initialized. Will terminate.\n");
// ----------------------------------------*/
process_resource_url($dwca_file, $resource_id, $param);


$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";

function process_resource_url($dwca_file, $resource_id, $param)
{
    require_library('connectors/DwCA_Utility');
    $func = new DwCA_Utility($resource_id, $dwca_file, $param);
    $preferred_rowtypes = array(); //best to set this to array() and just set $excluded_rowtypes to occurrence

    // /* main operation. Cannot run [taxon], [reference] and [association] in DwCA_Utility bec it has too many records (memory leak). These 3 extensions will just carry-over.
    // Only the [occurrence] will be updated.
    $excluded_rowtypes = array("http://eol.org/schema/reference/reference", "http://rs.tdwg.org/dwc/terms/taxon", 
                               "http://rs.tdwg.org/dwc/terms/occurrence", "http://eol.org/schema/association");
    // */

    /* These below will be processed in ResourceUtility.php which will be called from DwCA_Utility.php
    http://rs.tdwg.org/dwc/terms/occurrence
    */
    $func->convert_archive($preferred_rowtypes, $excluded_rowtypes);
    Functions::finalize_dwca_resource($resource_id, false, true); //3rd param false means don't delete working folder yet
    
    /* copied template
    New: important to check if all parents have entries.
    require_library('connectors/DWCADiagnoseAPI');
    $func = new DWCADiagnoseAPI();
    $undefined_parents = $func->check_if_all_parents_have_entries($resource_id, true); //2nd param true means output will write to text file
    echo "\nTotal undefined parents:" . count($undefined_parents)."\n"; unset($undefined_parents);
    recursive_rmdir(CONTENT_RESOURCE_LOCAL_PATH.$resource_id."/"); //we can now delete folder after check_if_all_parents_have_entries() - DWCADiagnoseAPI
    */
}
?>