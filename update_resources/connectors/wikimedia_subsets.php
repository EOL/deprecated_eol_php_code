<?php
namespace php_active_record;
/* DATA-1770: separate wikimedia into an images resource and a common names resource 
This will rename 71.tar.gz from the long Jenkins run to 71_orig.tar.gz
The remaining 71.tar.gz is the resource with just images.
The wikimedia_comnames.tar.gz is the resource with just vernaculars.

wikimedia_comnames	Wednesday 2018-10-17 05:02:39 AM	{"taxon.tab": 90844,"vernacular_name.tab":291267}
wikimedia_comnames	Monday 2018-11-19 11:38:23 AM	    {"taxon.tab":113398,"vernacular_name.tab":315324} --- big increase, not sure yet if anomaly or legit increase
wikimedia_comnames	Wednesday 2018-11-21 02:06:35 AM    {"taxon.tab":113398,"vernacular_name.tab":315324}
wikimedia_comnames	Saturday 2018-11-24 09:55:46 PM	    {"taxon.tab":113399,"vernacular_name.tab":315324}

*this one (71_new) eventually becomes 71.tar.gz
71_new	Wednesday 2018-10-17 05:10:07 AM	{"agent.tab": 22435,"media_resource.tab": 956972,"taxon.tab": 90844}
71_new	Monday 2018-11-19 11:48:24 AM	    {"agent.tab":267873,"media_resource.tab":1167960,"taxon.tab":113398} --- big increase, not sure yet if anomaly or legit increase
71_new	Wednesday 2018-11-21 02:16:00 AM	{"agent.tab":267877,"media_resource.tab":1167960,"taxon.tab":113398}
*/

// ini_set('error_reporting', E_ALL);
// ini_set('display_errors', true);
// define('DOWNLOAD_WAIT_TIME', '300000'); // .3 seconds wait time
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
// $GLOBALS['ENV_DEBUG'] = false;
require_library('connectors/DwCA_Utility');
ini_set('memory_limit','7096M'); //required

$resource_id = 'wikimedia_comnames';
$func = new DwCA_Utility($resource_id, CONTENT_RESOURCE_LOCAL_PATH . "71" . ".tar.gz");
$preferred_rowtypes = array('http://rs.tdwg.org/dwc/terms/taxon', 'http://rs.gbif.org/terms/1.0/vernacularname');
$func->convert_archive($preferred_rowtypes);
Functions::finalize_dwca_resource($resource_id, false, true); //2nd param false means not a big file, 3rd param true means delete working folder in CONTENT_RESOURCE_LOCAL_PATH
$func = '';

$resource_id = '71_new';
$func = new DwCA_Utility($resource_id, CONTENT_RESOURCE_LOCAL_PATH . "71" . ".tar.gz");
$preferred_rowtypes = array('http://rs.tdwg.org/dwc/terms/taxon', 'http://eol.org/schema/agent/agent', 'http://eol.org/schema/media/document');
$func->convert_archive($preferred_rowtypes);
Functions::finalize_dwca_resource($resource_id, false, true);

if(file_exists(CONTENT_RESOURCE_LOCAL_PATH . "71_new.tar.gz")) {
    if(file_exists(CONTENT_RESOURCE_LOCAL_PATH . "71_orig.tar.gz")) unlink(CONTENT_RESOURCE_LOCAL_PATH . "71_orig.tar.gz");
    Functions::file_rename(CONTENT_RESOURCE_LOCAL_PATH . "71.tar.gz", CONTENT_RESOURCE_LOCAL_PATH . "71_orig.tar.gz");
    Functions::file_rename(CONTENT_RESOURCE_LOCAL_PATH . "71_new.tar.gz", CONTENT_RESOURCE_LOCAL_PATH . "71.tar.gz");
}

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = $elapsed_time_sec seconds             \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes  \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\n\n Done processing.";
?>