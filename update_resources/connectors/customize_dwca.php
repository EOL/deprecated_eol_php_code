<?php
namespace php_active_record;
/* DATA-1779 */

// ini_set('error_reporting', E_ALL);
// ini_set('display_errors', true);
// define('DOWNLOAD_WAIT_TIME', '300000'); // .3 seconds wait time
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
// $GLOBALS['ENV_DEBUG'] = false;
require_library('connectors/DwCA_Utility');
// ini_set('memory_limit','7096M'); //required

// /* process just one DwCA -- Done
$resource_id = 'EOL_79_final';
process_dwca($resource_id);
// */

/* process many DwCA -- hasn't run yet, but should work just fine.
main();
*/

function main()
{
    $format = "EOL_*.tar.gz";
    $resources = resources_list($format);
    print_r($resources); $i = 0; $total = count($resources);
    foreach($resources as $res) {
        $i++; echo "\n$i of resources total:$total\n";
        $resource_id = str_replace(".tar.gz", "", $res);
        echo "\n[$resource_id]";
        process_dwca($resource_id);
        // break; //debug
    }
}
function process_dwca($resource_id)
{
    $func = new DwCA_Utility($resource_id, CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".tar.gz");
    $options['row_type'] = "http://eol.org/schema/media/Document";
    $options['fields']   = array("http://ns.adobe.com/xap/1.0/rights/Owner");
    $options['Jira']     = "DATA-1779"; // if license is 'public domain', make 'Owner' field blank.
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