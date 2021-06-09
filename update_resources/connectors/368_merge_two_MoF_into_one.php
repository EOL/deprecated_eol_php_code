<?php
namespace php_active_record;
/* From Jen 
   1st client: PaleoDB Tweak: https://eol-jira.bibalex.org/browse/DATA-1831?focusedCommentId=65098&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-65098
   For any given occurrence, if there are (at least) two records for measurementType=http://www.wikidata.org/entity/Q1053008,
   with measurementValues https://www.wikidata.org/entity/Q59099 AND http://www.wikidata.org/entity/Q81875
   (herbivore and carnivore)
   please replace them with a single record.
   
   *This can be a generic template for merging MoF records into one.
   
   php 368_merge_two_MoF_into_one.php
   See stats in pbdb_fresh_harvest.php
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
// $GLOBALS['ENV_DEBUG'] = true;

$resource_id = '368_merged_MoF';
$dwca_file = 'https://editors.eol.org/eol_php_code/applications/content_server/resources/368_final.tar.gz';
// $dwca_file = 'http://localhost/eol_php_code/applications/content_server/resources/368_final.tar.gz';
process_resource_url($dwca_file, $resource_id, $timestart);

function process_resource_url($dwca_file, $resource_id, $timestart)
{
    require_library('connectors/DwCA_Utility');
    $func = new DwCA_Utility($resource_id, $dwca_file);
    $preferred_rowtypes = array();
    /* These 2 will be processed in MergeMoFrecordsAPI.php which will be called from DwCA_Utility.php */
    $excluded_rowtypes = array('http://rs.tdwg.org/dwc/terms/measurementorfact'); //'http://rs.tdwg.org/dwc/terms/occurrence'
    $func->convert_archive($preferred_rowtypes, $excluded_rowtypes);
    Functions::finalize_dwca_resource($resource_id, false, false, $timestart); //3rd param false means don't delete folder
    
    // /* New: check for orphan records in MoF
    require_library('connectors/DWCADiagnoseAPI');
    $func = new DWCADiagnoseAPI();

    $undefined_parents = $func->check_if_all_parents_have_entries($resource_id, true, false, false, 'parentMeasurementID', 'measurement_or_fact_specific.tab');
    echo "\nTotal undefined parents MoF:" . count($undefined_parents)."\n";
    
    recursive_rmdir(CONTENT_RESOURCE_LOCAL_PATH."$resource_id/"); //we can now delete folder after DWCADiagnoseAPI()
    // */
}
?>