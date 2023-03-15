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
wikimedia_comnames	Sunday 2020-07-26 10:30:37 AM	{"taxon.tab":141903, "vernacular_name.tab":829198, "time_elapsed":false} consistent OK
wikimedia_comnames	Thu 2020-10-08 07:41:37 PM	    {"taxon.tab":143231, "vernacular_name.tab":865272, "time_elapsed":false}
wikimedia_comnames	Tue 2020-12-08 11:33:38 PM	    {"taxon.tab":144044, "vernacular_name.tab":869681, "time_elapsed":false}
wikimedia_comnames	Fri 2021-02-12 07:43:51 PM	    {"taxon.tab":145396, "vernacular_name.tab":880106, "time_elapsed":false} consistent OK
wikimedia_comnames	Sat 2021-04-10 05:33:14 AM	    {"taxon.tab":147435, "vernacular_name.tab":893189, "time_elapsed":false} consistent OK
wikimedia_comnames	Mon 2021-06-07 10:48:22 PM	    {"taxon.tab":148596, "vernacular_name.tab":899297, "time_elapsed":false}
wikimedia_comnames	Sun 2021-09-05 01:47:50 PM	    {"taxon.tab":150220, "vernacular_name.tab":918796, "time_elapsed":false} big increase since fixing ranges
wikimedia_comnames	Mon 2022-01-24 05:55:59 AM	    {"taxon.tab":162683, "vernacular_name.tab":991723, "time_elapsed":false}
wikimedia_comnames	Wed 2022-04-20 01:02:22 PM	    {"taxon.tab":162683, "time_elapsed":false} --- wrong timing, will run again soon
wikimedia_comnames	Thu 2022-04-28 09:54:45 PM	    {"taxon.tab":163083, "vernacular_name.tab":993598, "time_elapsed":false}
wikimedia_comnames	Fri 2022-05-27 04:52:37 AM	    {"taxon.tab":163092, "vernacular_name.tab":993598, "time_elapsed":{"sec":1322.92, "min":22.05, "hr":0.37}}
-> new utility: filled-up missing parent entries 163092
wikimedia_comnames	Thu 2022-07-07 05:01:16 AM	    {"taxon.tab":110157, "vernacular_name.tab":618360, "time_elapsed":{"sec":3647.58, "min":60.79, "hr":1.01}}
-> big decrease - seems erroneous. Next run below is better:
wikimedia_comnames	Wed 2022-07-13 06:01:04 PM	    {"taxon.tab":154018, "vernacular_name.tab":973908, "time_elapsed":{"sec":1292.89, "min":21.55, "hr":0.36}}
wikimedia_comnames	Wed 2022-07-13 09:26:10 PM	    {"taxon.tab":154018, "vernacular_name.tab":973908, "time_elapsed":{"sec":404.65, "min":6.74, "hr":0.11}}
-> first run using new scheme, no more or limited API calls consistent OK
wikimedia_comnames	Fri 2023-02-24 06:11:01 PM	{"taxon.tab":154527, "vernacular_name.tab":1004878}
wikimedia_comnames	Fri 2023-03-10 01:55:16 PM	{"taxon.tab":154534, "vernacular_name.tab":1004885}
wikimedia_comnames	Tue 2023-03-14 07:05:47 PM	{"taxon.tab":166733, "vernacular_name.tab":1051497, "time_elapsed":{"sec":1427.04, "min":23.78, "hr":0.4}}

Hi Jen,
Using March 9 dump which finished March 10 included new records but not our missing Arabic common name.
wikimedia_comnames	Fri 2023-02-24 06:11:01 PM	{"taxon.tab":154527, "vernacular_name.tab":1004878}
wikimedia_comnames	Fri 2023-03-10 01:55:16 PM	{"taxon.tab":154534, "vernacular_name.tab":1004885}
But now the new common name is clearly showing up in the API service
https://www.wikidata.org/wiki/Special:EntityData/Q1130386.json
So I now just set manually to have Q1130386 to use the API and not the data from dump. Just to be sure we are getting the latest.
I've now re-run the connector once again.
*/

// ini_set('error_reporting', E_ALL);
// ini_set('display_errors', true);
// define('DOWNLOAD_WAIT_TIME', '300000'); // .3 seconds wait time
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
// $GLOBALS['ENV_DEBUG'] = false;
require_library('connectors/DwCA_Utility');
ini_set('memory_limit','7096M'); //required

// This will rename 71.tar.gz from the long Jenkins run to 71_orig.tar.gz
// The remaining 71.tar.gz is the resource with just images.
// The wikimedia_comnames.tar.gz is the resource with just vernaculars.


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