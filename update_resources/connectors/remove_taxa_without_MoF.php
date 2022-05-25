<?php
namespace php_active_record;
/* This is generic way of removing taxa without MoF records.
first client: https://jenkins.eol.org/job/EOL%20Connectors/job/Environmental%20tagger%20for%20EOL%20resources/job/Wikipedia%20EN%20(English)/
              environments_2_eol.php for Wikipedia EN 
Used during Vangelis:   php update_resources/connectors/remove_taxa_without_MoF.php _ '{"resource_id": "617_final"}'
Used during Pensoft:    php update_resources/connectors/remove_taxa_without_MoF.php _ '{"resource_id": "617_ENV"}'

as of latest from Jenkins: OK!
php5.6 remove_taxa_without_MoF.php jenkins '{"resource_id": "wikipedia_en_traits_FTG"}'
       remove_taxa_without_MoF.php _ '{"resource_id": "wikipedia_en_traits_FTG"}'
# OLD: generates wikipedia_en_traits.tar.gz
# NEW: generates wikipedia_en_traits_tmp1.tar.gz
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
// $GLOBALS['ENV_DEBUG'] = true;

// print_r($argv);
$params['jenkins_or_cron'] = @$argv[1]; //not needed here
$param                     = json_decode(@$argv[2], true);
$resource_id = $param['resource_id'];
print_r($param);
/* during Vangelis
if(Functions::is_production()) $dwca_file = '/u/scripts/eol_php_code/applications/content_server/resources/'.$resource_id.'.tar.gz';
else                           $dwca_file = 'http://localhost/eol_php_code/applications/content_server/resources/'.$resource_id.'.tar.gz';
*/
// /* for Pensoft
if(Functions::is_production()) $dwca_file = CONTENT_RESOURCE_LOCAL_PATH . "/$resource_id" . ".tar.gz";
else                           $dwca_file = 'http://localhost/eol_php_code/applications/content_server/resources/'.$resource_id.'.tar.gz';
// */

// /* ---------- customize here ----------
/* during Vangelis:
if($resource_id == '617_final') $resource_id = "wikipedia_en_traits";
*/
    if($resource_id == '617_ENV')                 $resource_id = "wikipedia_en_traits"; //during Pensoft - OLD - OBSOLETE
elseif($resource_id == 'wikipedia_en_traits_FTG') $resource_id = "wikipedia_en_traits_tmp1"; //during Pensoft - NEW LATEST
else exit("\nERROR: resource_id not yet initialized. Will terminate.\n");
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
    $preferred_rowtypes = array(); //best to set this to array() and just set $excluded_rowtypes to taxon
    $excluded_rowtypes = array('http://rs.tdwg.org/dwc/terms/taxon');
    /* These below will be processed in ResourceUtility.php which will be called from DwCA_Utility.php
    http://rs.tdwg.org/dwc/terms/taxon
    */
    $func->convert_archive($preferred_rowtypes, $excluded_rowtypes);
    // Functions::finalize_dwca_resource($resource_id);
    Functions::finalize_dwca_resource($resource_id, false, false); //3rd param false means don't delete working folder yet
    
    // /* New: important to check if all parents have entries.
    require_library('connectors/DWCADiagnoseAPI');
    $func = new DWCADiagnoseAPI();
    $undefined_parents = $func->check_if_all_parents_have_entries($resource_id, true); //2nd param true means output will write to text file
    echo "\nTotal undefined parents:" . count($undefined_parents)."\n"; unset($undefined_parents);
    recursive_rmdir(CONTENT_RESOURCE_LOCAL_PATH.$resource_id."/"); //we can now delete folder after check_if_all_parents_have_entries() - DWCADiagnoseAPI
    // */
}
?>