<?php
namespace php_active_record;
/* DATA-1799 
Upon close investigation, the six (6) resources in question have each an entry in taxon.tab where a taxon doesn't have a taxonID.
And this blank taxonID is also used in the media_resource.tab.
So this connector will assign a taxonID for the blank taxonID in taxon.tab and will use that new taxonID to fill-in the blank taxonID in media_resource.tab.
It is also confirmed that the missing taxonID entry in taxon.tab is the corresponding missing taxonID entry in media_resource.tab.
*/

// ini_set('error_reporting', E_ALL);
// ini_set('display_errors', true);
// define('DOWNLOAD_WAIT_TIME', '300000'); // .3 seconds wait time
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
// $GLOBALS['ENV_DEBUG'] = false;
require_library('connectors/DwCA_Utility');
// ini_set('memory_limit','7096M'); //required


$source_filenames = "Entiminae.zip,EOL_119_final.tar.gz,EOL_180_final.tar.gz,EOL_256_final.tar.gz,EOL_374_final.tar.gz,Soapberry_bugs_of_the_world.zip";
// $source_filenames = "Entiminae.zip";
$source_filenames = explode(",", $source_filenames);
// print_r($source_filenames); exit;

foreach($source_filenames as $source_filename) {
    $resource_id = str_replace(array(".tar",".gz",".zip"), "", $source_filename);
    process_dwca($resource_id, $source_filename);
}

function process_dwca($resource_id, $source_filename)
{
    $func = new DwCA_Utility($resource_id, DOC_ROOT . "../cp/DATA_1799_orig_resources/" . $source_filename);
    $options['row_types'][] = "http://rs.tdwg.org/dwc/terms/Taxon";
    $options['row_types'][] = "http://eol.org/schema/media/Document";
    $options['Jira']     = "DATA-1799"; // some taxa don't have taxonID
    $func->convert_archive_customize_tab($options);
    Functions::finalize_dwca_resource($resource_id, false, true); //2nd param false means not a big file, 3rd param true means delete working folder in CONTENT_RESOURCE_LOCAL_PATH
}
function resources_list($format)
{
    $arr = array();
    foreach(glob(CONTENT_RESOURCE_LOCAL_PATH . "/".$format) as $filename) {
        $pathinfo = pathinfo($filename, PATHINFO_BASENAME);
        $arr[$pathinfo] = '';
    }
    return array_keys($arr);
}

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = $elapsed_time_sec seconds             \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes  \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\n\n Done processing.";
?>