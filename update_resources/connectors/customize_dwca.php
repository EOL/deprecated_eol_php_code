<?php
namespace php_active_record;
/* DATA-1779

*/

// ini_set('error_reporting', E_ALL);
// ini_set('display_errors', true);
// define('DOWNLOAD_WAIT_TIME', '300000'); // .3 seconds wait time
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
// $GLOBALS['ENV_DEBUG'] = false;
require_library('connectors/DwCA_Utility');
// ini_set('memory_limit','7096M'); //required

$resource_id = 'EOL_79_final';
$resource_id = 'eli';
$func = new DwCA_Utility($resource_id, CONTENT_RESOURCE_LOCAL_PATH . 'EOL_79_final' . ".tar.gz");

$options['row_type'] = "http://eol.org/schema/media/Document";
$options['fields']   = array("http://ns.adobe.com/xap/1.0/rights/Owner");
$options['Jira']     = "DATA-1779"; // if license is 'public domain', make 'Owner' field blank.

$func->convert_archive_customize_tab($options);
Functions::finalize_dwca_resource($resource_id, false, false); //2nd param false means not a big file, 3rd param true means delete working folder in CONTENT_RESOURCE_LOCAL_PATH

// if(file_exists(CONTENT_RESOURCE_LOCAL_PATH . "71_new.tar.gz")) {
//     if(file_exists(CONTENT_RESOURCE_LOCAL_PATH . "71_orig.tar.gz")) unlink(CONTENT_RESOURCE_LOCAL_PATH . "71_orig.tar.gz");
//     Functions::file_rename(CONTENT_RESOURCE_LOCAL_PATH . "71.tar.gz", CONTENT_RESOURCE_LOCAL_PATH . "71_orig.tar.gz");
//     Functions::file_rename(CONTENT_RESOURCE_LOCAL_PATH . "71_new.tar.gz", CONTENT_RESOURCE_LOCAL_PATH . "71.tar.gz");
// }

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = $elapsed_time_sec seconds             \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes  \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\n\n Done processing.";
?>