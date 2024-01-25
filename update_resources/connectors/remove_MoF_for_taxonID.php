<?php
namespace php_active_record;
/* This is generic way of removing MoF and occurrences for a taxonID.
first client: Try database: https://opendata.eol.org/dataset/try/resource/c55b9051-4125-4c36-ab54-cb56477a8746
                            https://eol-jira.bibalex.org/browse/DATA-1766?focusedCommentId=67789&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-67789

    php update_resources/connectors/remove_MoF_for_taxonID.php _ '{"resource_id": "TRY_temp2", "resource": "remove_MoF_for_taxonID", "resource_name": "Try Database temp2"}'
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
// $GLOBALS['ENV_DEBUG'] = true;

// print_r($argv);
$params['jenkins_or_cron'] = @$argv[1]; //not needed here
$param                     = json_decode(@$argv[2], true);
$resource_id = $param['resource_id'];
print_r($param);

$extension = ".tar.gz";
if($resource_id == "TRY_temp2") $extension = ".zip"; //e.g. TRY_temp2.zip

// /*
if(Functions::is_production()) $dwca_file = CONTENT_RESOURCE_LOCAL_PATH . "/$resource_id" . $extension; //".tar.gz";
else                           $dwca_file = 'http://localhost/eol_php_code/applications/content_server/resources_3/'.$resource_id.$extension;  //'.tar.gz';
// */

// /* ---------- customize here ----------
    if($resource_id == 'TRY_temp2')         $resource_id = "try_dbase_2024";
elseif($resource_id == 'the source')        $resource_id = "final dwca"; //add other resources here...
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
    $preferred_rowtypes = array(); //best to set this to array() and just set $excluded_rowtypes

    if($resource_id == "try_dbase_2024") {
        // /* main operation. MoF and occurrence will be updated in ResourceUtility. taxon and reference will be updated in DwCA_Utility's built-in carry-over
        $excluded_rowtypes = array("http://rs.tdwg.org/dwc/terms/measurementorfact", "http://rs.tdwg.org/dwc/terms/occurrence");
        // "http://eol.org/schema/reference/reference"
        // "http://rs.tdwg.org/dwc/terms/taxon"
        // */
        /* during dev only -- comment in real operation
        $excluded_rowtypes = array("http://rs.tdwg.org/dwc/terms/measurementorfact", "http://rs.tdwg.org/dwc/terms/occurrence", "http://eol.org/schema/reference/reference", "http://rs.tdwg.org/dwc/terms/taxon");
        */
    }
    else exit("\n[$resource_id]: resource_id not yet set up.\n");

    $func->convert_archive($preferred_rowtypes, $excluded_rowtypes);
    Functions::finalize_dwca_resource($resource_id, false, true); //3rd param true means you can delete working folder
    
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