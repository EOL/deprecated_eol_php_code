<?php
namespace php_active_record;
/* DATA-1770: separate wikimedia into an images resource and a common names resource 
This will rename 71.tar.gz from the long Jenkins run to 71_orig.tar.gz
The remaining 71.tar.gz is the resource with just images.
The wikimedia_comnames.tar.gz is the resource with just vernaculars.

wikimedia_comnames	Wednesday 2018-10-17 05:02:39 AM{"taxon.tab": 90844,"vernacular_name.tab":291267}
wikimedia_comnames	Monday 2018-11-19 11:38:23 AM	{"taxon.tab":113398,"vernacular_name.tab":315324} --- big increase, not sure yet if anomaly or legit increase
wikimedia_comnames	Wednesday 2018-11-21 02:06:35 AM{"taxon.tab":113398,"vernacular_name.tab":315324}
wikimedia_comnames	Saturday 2018-11-24 09:55:46 PM	{"taxon.tab":113399,"vernacular_name.tab":315324}
wikimedia_comnames	Monday 2018-11-26 08:33:11 PM	{"taxon.tab":114046,"vernacular_name.tab":316641}
wikimedia_comnames	Tuesday 2018-11-27 09:05:47 PM	{"taxon.tab":114046,"vernacular_name.tab":316641}
wikimedia_comnames	Thursday 2018-11-29 03:57:36 AM	{"taxon.tab":114046,"vernacular_name.tab":316641}
wikimedia_comnames	Tuesday 2018-12-11 09:40:20 AM	{"taxon.tab":123249,"vernacular_name.tab":327414}
wikimedia_comnames	Tuesday 2019-01-08 09:44:54 PM	{"taxon.tab":123849,"vernacular_name.tab":327898}
wikimedia_comnames	Thursday 2019-01-17 07:49:35 PM	{"taxon.tab":123849,"vernacular_name.tab":327898}
wikimedia_comnames	Saturday 2019-02-09 03:21:30 PM	{"taxon.tab":128099,"vernacular_name.tab":330621}
wikimedia_comnames	Sunday 2019-03-03 03:52:33 PM	{"taxon.tab":128348,"vernacular_name.tab":330901}
wikimedia_comnames	Thursday 2019-03-07 10:15:08 AM	{"taxon.tab":128348,"vernacular_name.tab":330906}
wikimedia_comnames	Friday 2019-04-12 08:36:52 PM	{"taxon.tab":128676,"vernacular_name.tab":729662} expected big increase in vernaculars, per DATA-1803
wikimedia_comnames	Thursday 2019-06-06 04:34:08 AM	{"taxon.tab":129200,"vernacular_name.tab":734354} consistent OK
wikimedia_comnames	Monday 2019-08-12 04:19:15 AM	{"taxon.tab":129802,"vernacular_name.tab":748550} consistent OK - after fixing in DwCA_Utility the expire_seconds to zero 0
wikimedia_comnames	Monday 2019-11-11 05:00:07 AM	{"taxon.tab":130672,"vernacular_name.tab":757699,"time_elapsed":false} consistent OK
wikimedia_comnames	Tuesday 2019-12-03 09:41:16 PM	{"taxon.tab":130888, "vernacular_name.tab":764871, "time_elapsed":false} consistent OK
wikimedia_comnames	Friday 2020-04-10 10:50:54 AM	{"taxon.tab":139471, "vernacular_name.tab":813926, "time_elapsed":false} consistent OK
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

if(file_exists(CONTENT_RESOURCE_LOCAL_PATH . "71_new.tar.gz")) unlink(CONTENT_RESOURCE_LOCAL_PATH . "71_new.tar.gz");
$resource_id = '71_new';
$func = new DwCA_Utility($resource_id, CONTENT_RESOURCE_LOCAL_PATH . "71" . ".tar.gz");
$preferred_rowtypes = array('http://rs.tdwg.org/dwc/terms/taxon', 'http://eol.org/schema/agent/agent', 'http://eol.org/schema/media/document');
$func->convert_archive($preferred_rowtypes);
Functions::finalize_dwca_resource($resource_id, false, true);

if(file_exists(CONTENT_RESOURCE_LOCAL_PATH . "71_new.tar.gz")) {
    Functions::file_rename(CONTENT_RESOURCE_LOCAL_PATH . "71.tar.gz", CONTENT_RESOURCE_LOCAL_PATH . "71_".date("Y-m-d").".tar.gz");
    Functions::file_rename(CONTENT_RESOURCE_LOCAL_PATH . "71_new.tar.gz", CONTENT_RESOURCE_LOCAL_PATH . "71.tar.gz");
}

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = $elapsed_time_sec seconds             \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes  \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\n\n Done processing.";
?>