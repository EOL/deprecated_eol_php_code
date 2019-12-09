<?php
namespace php_active_record;
/* https://eol-jira.bibalex.org/browse/DATA-1841?focusedCommentId=64113&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-64113 */

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();

// /* main operation
require_library('connectors/SpeciesChecklistAPI');
$func = new SpeciesChecklistAPI(false, false);
generate_new_dwca($func); //main script to remap terms in all resources under 'water-body-checklists-2019'.

/* 3 specific countries mentioned in DATA-1841
$urls[] = 'https://editors.eol.org/eol_php_code/applications/content_server/resources/SC_mexico.tar.gz';
$urls[] = 'https://editors.eol.org/eol_php_code/applications/content_server/resources/SC_indonesia.tar.gz';
$urls[] = 'https://editors.eol.org/eol_php_code/applications/content_server/resources/SC_unitedstates.tar.gz';
print_r($urls);
foreach($urls as $url) process_resource_url($url);
*/

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";

function generate_new_dwca($func)
{
    /*
    $datasets = array('national-checklists-2019', 'water-body-checklists-2019');
    $datasets = array('national-checklists-2019');
    */
    $datasets = array('water-body-checklists-2019');
    foreach($datasets as $dataset) {
        $urls = $func->get_opendata_resources($dataset);
        
        /* customized adjustments
        $urls = array();
        $urls[] = 'https://opendata.eol.org/dataset/c99917cf-7790-4608-a7c2-5532fb47da32/resource/d8c8ab05-b1c2-46da-af07-3efb34f05bad/download/southpacific.zip';
        */
        
        print_r($urls); //exit;
        $i = 0;
        foreach($urls as $url) { $i++; echo "\n[$i]";
            process_resource_url($url);
            // break; //run 1 url only. debug only
        }
    }
}
function process_resource_url($dwca_file)
{
    require_library('connectors/DwCA_Utility');
    // $dwca_file = 'https://editors.eol.org/eol_php_code/applications/content_server/resources/SC_adriaticsea.tar.gz'; //debug only
    $resource_id = ''.get_basename($dwca_file);
    echo " Processing [$resource_id]...";
    $func = new DwCA_Utility($resource_id, $dwca_file);
    $preferred_rowtypes = array("http://rs.tdwg.org/dwc/terms/taxon", "http://rs.tdwg.org/dwc/terms/occurrence", "http://eol.org/schema/reference/reference");
    $func->convert_archive($preferred_rowtypes);
    Functions::finalize_dwca_resource($resource_id, false, true);
}
function get_basename($url)
{
    $tmp = pathinfo($url, PATHINFO_BASENAME); //e.g. SC_adriaticsea.tar.gz
    $tmp = str_ireplace('.tar.gz', '', $tmp);
    return $tmp; //e.g. SC_adriaticsea
}
?>