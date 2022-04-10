<?php
namespace php_active_record;
/* This is generic way of removing traits that contradict in MoF records.
first client: a task in DATA-1858
https://eol-jira.bibalex.org/browse/DATA-1858?focusedCommentId=66299&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-66299

php5.6 remove_contradicting_traits_from_MoF.php jenkins '{"resource_id": "wikipedia_en_traits_tmp1"}'
       remove_contradicting_traits_from_MoF.php _ '{"resource_id": "wikipedia_en_traits_tmp1"}'
# generates wikipedia_en_traits.tar.gz           OLD
# generates wikipedia_en_traits_tmp2.tar.gz      NEW
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
// $GLOBALS['ENV_DEBUG'] = true;

// print_r($argv);
$params['jenkins_or_cron'] = @$argv[1]; //not needed here
$param                     = json_decode(@$argv[2], true);
$resource_id = $param['resource_id'];
print_r($param);

if(Functions::is_production()) $dwca_file = CONTENT_RESOURCE_LOCAL_PATH . "/$resource_id" . ".tar.gz";
else                           $dwca_file = 'http://localhost/eol_php_code/applications/content_server/resources/'.$resource_id.'.tar.gz';

// /* ---------- customize here ---------- e.g. "wikipedia_en_traits_tmp1" is the source --- "wikipedia_en_traits" is the target resource
if($resource_id == 'wikipedia_en_traits_tmp1') $resource_id = "wikipedia_en_traits_tmp2"; //"wikipedia_en_traits"; orig value
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
    $excluded_rowtypes = array('http://rs.tdwg.org/dwc/terms/measurementorfact', 'http://rs.tdwg.org/dwc/terms/occurrence');
    /* These below will be processed in ResourceUtility.php which will be called from DwCA_Utility.php
    http://rs.tdwg.org/dwc/terms/measurementorfact
    http://rs.tdwg.org/dwc/terms/occurrence
    */
    $func->convert_archive($preferred_rowtypes, $excluded_rowtypes);
    // Functions::finalize_dwca_resource($resource_id); //orig
    Functions::finalize_dwca_resource($resource_id, false, true);
    
}
?>