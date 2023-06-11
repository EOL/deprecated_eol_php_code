<?php
namespace php_active_record;
/*
commons Sunday 2017-11-26 01:34:01 PM   {"agent.tab":19055,"media_resource.tab":909996,"taxon.tab":89054}
71  Saturday 2017-12-02 04:39:38 AM {"agent.tab":19252,"media_resource.tab":912111,"taxon.tab":89748}
71  Tuesday 2017-12-19 02:05:02 AM  {"agent.tab":19813,"media_resource.tab":932989,"taxon.tab":89753,"vernacular_name.tab":291572}
71  Wednesday 2018-01-03 08:40:51 PM{"agent.tab":19369,"media_resource.tab":935068,"taxon.tab":89806,"vernacular_name.tab":291603}
71  Saturday 2018-04-21 06:58:29 AM {"agent.tab":20658,"media_resource.tab":946750,"taxon.tab":89694,"vernacular_name.tab":291271}
71	Wednesday 2018-05-02 04:59:07 AM{"agent.tab":20658,"media_resource.tab":946750,"taxon.tab":89694,"vernacular_name.tab":291271}
71	Wednesday 2018-06-06 06:59:52 AM{"agent.tab":21060,"media_resource.tab":943420,"taxon.tab":89739,"vernacular_name.tab":291235}
71	Thursday 2018-07-05 11:59:10 AM	{"agent.tab":21251,"media_resource.tab":946050,"taxon.tab":89873,"vernacular_name.tab":291188}

71	Saturday 2018-10-06 04:19:13 AM	 {"agent.tab": 22435,"media_resource.tab": 956972,"taxon.tab": 90844,"vernacular_name.tab":291267}
71	Monday 2018-11-19 11:36:53 AM	 {"agent.tab":267873,"media_resource.tab":1167960,"taxon.tab":113398,"vernacular_name.tab":315324} ??? big increase...will investigate
71	Wednesday 2018-11-21 02:05:16 AM {"agent.tab":267877,"media_resource.tab":1167960,"taxon.tab":113398,"vernacular_name.tab":315324}
71	Saturday 2018-11-24 09:54:22 PM	 {"agent.tab":268413,"media_resource.tab":1168639,"taxon.tab":113399,"vernacular_name.tab":315324}
71	Monday 2018-11-26 08:31:46 PM	 {"agent.tab":268819,"media_resource.tab":1169511,"taxon.tab":114046,"vernacular_name.tab":316641}
71	Tuesday 2018-11-27 09:04:20 PM	 {"agent.tab":268815,"media_resource.tab":1169511,"taxon.tab":114046,"vernacular_name.tab":316641}
71	Thursday 2018-11-29 03:56:14 AM	 {"agent.tab":268392,"media_resource.tab":1169511,"taxon.tab":114046,"vernacular_name.tab":316641}
71	Tuesday 2018-12-11 09:38:50 AM	 {"agent.tab":272340,"media_resource.tab":1220196,"taxon.tab":123249,"vernacular_name.tab":327414}
71	Tuesday 2019-01-08 09:43:26 PM	 {"agent.tab":272517,"media_resource.tab":1237531,"taxon.tab":123849,"vernacular_name.tab":327898}
71	Thursday 2019-01-17 07:48:12 PM	 {"agent.tab":272517,"media_resource.tab":1237531,"taxon.tab":123849,"vernacular_name.tab":327898}
71	Saturday 2019-02-09 03:19:59 PM	 {"agent.tab":278157,"media_resource.tab":1300397,"taxon.tab":128099,"vernacular_name.tab":330621}
71	Sunday 2019-03-03 03:50:57 PM	 {"agent.tab":189430,"media_resource.tab":1298998,"taxon.tab":128348,"vernacular_name.tab":330901} decrease but looks OK still
71	Thursday 2019-03-07 10:13:39 AM	 {"agent.tab":189457,"media_resource.tab":1299729,"taxon.tab":128348,"vernacular_name.tab":330906} consistent with last run
71	Friday 2019-04-12 08:34:05 PM	 {"agent.tab":189886,"media_resource.tab":1315303,"taxon.tab":128676,"vernacular_name.tab":729662} expected big increase in vernaculars
71	Thursday 2019-06-06 04:31:09 AM	 {"agent.tab":190674,"media_resource.tab":1324658,"taxon.tab":129200,"vernacular_name.tab":734354} consistent OK
71	Friday 2019-08-09 01:56:21 AM	 {"agent.tab":191768,"media_resource.tab":1340788,"taxon.tab":129802,"vernacular_name.tab":748550} conistent OK
71	Monday 2019-11-11 04:56:47 AM	 {"agent.tab":192916,"media_resource.tab":1373427,"taxon.tab":130672,"vernacular_name.tab":757699,"time_elapsed":{"sec":75396.18,"min":1256.6,"hr":20.94}} conistent OK
71	Tuesday 2019-12-03 09:37:47 PM	 {"agent.tab":192942, "media_resource.tab":1373995, "taxon.tab":130888, "vernacular_name.tab":764871, "time_elapsed":{"sec":118185.92,"min":1969.77,"hr":32.83,"day":1.37}} consistent OK
71	Friday 2020-04-10 10:45:15 AM	 {"agent.tab":196927, "media_resource.tab":1415286, "taxon.tab":139471, "vernacular_name.tab":813926, "time_elapsed":{"sec":95964.61, "min":1599.41, "hr":26.66, "day":1.11}} consistent OK
71	Sunday 2020-07-26 10:24:37 AM	 {"agent.tab":199589, "media_resource.tab":1448582, "taxon.tab":141903, "vernacular_name.tab":829198, "time_elapsed":{"sec":355504.68, "min":5925.08, "hr":98.75, "day":4.11}} consistent OK except for time elapsed?
71	Thu 2020-10-08 07:35:33 PM	     {"agent.tab":201017, "media_resource.tab":1469784, "taxon.tab":143231, "vernacular_name.tab":865272, "time_elapsed":{"sec":104842.09, "min":1747.37, "hr":29.12, "day":1.21}}
71	Tue 2020-12-08 11:27:38 PM	     {"agent.tab":202505, "media_resource.tab":1490601, "taxon.tab":144044, "vernacular_name.tab":869681, "time_elapsed":{"sec":112827.97, "min":1880.47, "hr":31.34, "day":1.31}}
71	Fri 2021-02-12 07:37:48 PM	     {"agent.tab":380186, "media_resource.tab":1497199, "taxon.tab":145396, "vernacular_name.tab":880106, "time_elapsed":{"sec":138060.39, "min":2301.01, "hr":38.35, "day":1.6}}
71	Sat 2021-04-10 05:26:37 AM	     {"agent.tab":350462, "media_resource.tab":1452540, "taxon.tab":147435, "vernacular_name.tab":893189, "time_elapsed":{"sec":147685.35, "min":2461.42, "hr":41.02, "day":1.71}}
-> normal increase in taxa but strangely decrease in text object.
71	Mon 2021-06-07 10:41:39 PM	     {"agent.tab":357556, "media_resource.tab":1467878, "taxon.tab":148596, "vernacular_name.tab":899297, "time_elapsed":{"sec":131012.16, "min":2183.54, "hr":36.39, "day":1.52}}
-> steady increase OK
71	Sun 2021-09-05 01:41:01 PM	     {"agent.tab":367898, "media_resource.tab":1486900, "taxon.tab":150220, "vernacular_name.tab":918796, "time_elapsed":{"sec":177607.13, "min":2960.12, "hr":49.34, "day":2.06}} consistent increase
-> good increase since fixing/updating the ranges
71	Mon 2022-01-24 05:48:29 AM	     {"agent.tab":392784, "media_resource.tab":1549432, "taxon.tab":162683, "vernacular_name.tab":991723, "time_elapsed":{"sec":179080.86, "min":2984.68, "hr":49.74, "day":2.07}} consistent OK
71	Thu 2022-04-28 09:47:13 PM	     {"agent.tab":792529, "media_resource.tab":1556142, "taxon.tab":163083, "vernacular_name.tab":993598, "time_elapsed":{"sec":264401.7, "min":4406.7, "hr":73.44, "day":3.06}}
71	Thu 2022-07-07 03:40:44 AM       {"agent.tab":509946, "media_resource.tab":985603, "taxon.tab":95205, "vernacular_name.tab":618360, "time_elapsed":{"sec":242653.86, "min":4044.23, "hr":67.4, "day":2.81}}
-> big decrease - will wait for the latest run
71	Wed 2022-07-13 06:33:56 PM	     {"agent.tab":788702, "media_resource.tab":1248218, "taxon.tab":154018, "vernacular_name.tab":973908, "time_elapsed":{"sec":5001.45, "min":83.36, "hr":1.39}}


*this one (71_new) eventually becomes 71.tar.gz
71_new	Wednesday 2018-10-17 05:10:07 AM{"agent.tab": 22435,"media_resource.tab": 956972,"taxon.tab": 90844}
71_new	Monday 2018-11-19 11:48:24 AM   {"agent.tab":267873,"media_resource.tab":1167960,"taxon.tab":113398} --- big increase, not sure yet if anomaly or legit increase
71_new	Wednesday 2018-11-21 02:16:00 AM{"agent.tab":267877,"media_resource.tab":1167960,"taxon.tab":113398}
71_new	Saturday 2018-11-24 10:05:39 PM {"agent.tab":268413,"media_resource.tab":1168639,"taxon.tab":113399}
71_new	Monday 2018-11-26 08:43:16 PM   {"agent.tab":268819,"media_resource.tab":1169511,"taxon.tab":114046}
71_new	Tuesday 2018-11-27 09:15:51 PM	{"agent.tab":268815,"media_resource.tab":1169511,"taxon.tab":114046}
71_new	Thursday 2018-11-29 04:07:29 AM	{"agent.tab":268392,"media_resource.tab":1169511,"taxon.tab":114046}
71_new	Tuesday 2018-12-11 09:50:47 AM	{"agent.tab":272340,"media_resource.tab":1220196,"taxon.tab":123249}
71_new	Tuesday 2019-01-08 09:55:37 PM	{"agent.tab":272517,"media_resource.tab":1237531,"taxon.tab":123849}
71_new	Thursday 2019-01-17 08:00:07 PM	{"agent.tab":272517,"media_resource.tab":1237531,"taxon.tab":123849}
71_new	Saturday 2019-02-09 03:32:49 PM	{"agent.tab":278157,"media_resource.tab":1300397,"taxon.tab":128099}
71_new	Sunday 2019-03-03 04:04:22 PM	{"agent.tab":189430,"media_resource.tab":1298998,"taxon.tab":128348} decrease but looks OK still
71_new	Thursday 2019-03-07 10:26:05 AM	{"agent.tab":189457,"media_resource.tab":1299729,"taxon.tab":128348} consistent with last run
71_new	Friday 2019-04-12 08:48:05 PM	{"agent.tab":189886,"media_resource.tab":1315303,"taxon.tab":128676} expected big increase in vernaculars
71_new	Thursday 2019-06-06 04:46:47 AM	{"agent.tab":190674,"media_resource.tab":1324658,"taxon.tab":129200} consistent OK
71_new	Monday 2019-08-12 04:31:40 AM	{"agent.tab":191768,"media_resource.tab":1340788,"taxon.tab":129802} consistent OK - after fixing in DwCA_Utility the expire_seconds to zero 0
71_new	Monday 2019-11-11 05:13:27 AM	{"agent.tab":192916,"media_resource.tab":1373427,"taxon.tab":130672,"time_elapsed":false} consistent OK
71_new	Tuesday 2019-12-03 09:53:59 PM	{"agent.tab":192942, "media_resource.tab":1373995, "taxon.tab":130888, "time_elapsed":false} consistent OK
71_new	Friday 2020-04-10 11:08:52 AM	{"agent.tab":196927, "media_resource.tab":1415286, "taxon.tab":139471, "time_elapsed":false} consistent OK
71_new	Sunday 2020-07-26 10:48:51 AM	{"agent.tab":199589, "media_resource.tab":1448582, "taxon.tab":141903, "time_elapsed":false} consistent OK
71_new	Thu 2020-10-08 08:00:42 PM	    {"agent.tab":201017, "media_resource.tab":1469784, "taxon.tab":143231, "time_elapsed":false}
71_new	Tue 2020-12-08 11:51:54 PM	    {"agent.tab":202505, "media_resource.tab":1490601, "taxon.tab":144044, "time_elapsed":false}
71_new	Fri 2021-02-12 08:04:22 PM	    {"agent.tab":380186, "media_resource.tab":1497199, "taxon.tab":145396, "time_elapsed":false}
71_new	Sat 2021-04-10 05:54:03 AM	    {"agent.tab":350462, "media_resource.tab":1452540, "taxon.tab":147435, "time_elapsed":false}
-> normal increase in taxa but strangely decrease in text object.
71_new	Mon 2021-06-07 11:08:54 PM	    {"agent.tab":357556, "media_resource.tab":1467878, "taxon.tab":148596, "time_elapsed":false}
-> steady increase OK
71_new	Sun 2021-09-05 02:09:57 PM	    {"agent.tab":367898, "media_resource.tab":1486900, "taxon.tab":150220, "time_elapsed":false}
71_new	Mon 2022-01-24 06:18:22 AM	    {"agent.tab":392784, "media_resource.tab":1549432, "taxon.tab":162683, "time_elapsed":false} consistent OK
71_new	Thu 2022-04-28 10:20:41 PM	    {"agent.tab":792529, "media_resource.tab":1556142, "taxon.tab":163083, "time_elapsed":false}
71_new	Thu 2022-07-07 04:00:25 AM	    {"agent.tab":509946, "media_resource.tab":985603, "taxon.tab":95205, "time_elapsed":false}
-> big decrease - seems erroneous. Next run below is better:
71_new	Wed 2022-07-13 05:39:27 PM	    {"agent.tab":788702, "media_resource.tab":1248218, "taxon.tab":154008, "time_elapsed":false}
71_new	Wed 2022-07-13 09:19:21 PM	    {"agent.tab":788702, "media_resource.tab":1248218, "taxon.tab":154018, "time_elapsed":false}
-> first run using new scheme, no more or limited API calls consistent OK
71_new	Sat 2022-09-10 04:49:57 PM	    {"agent.tab":800385, "media_resource.tab":1263636, "taxon.tab":154455, "time_elapsed":false} - consistent inc. OK


71_delta	Mon 2022-02-14 09:02:25 AM	{"agent.tab":392784, "media_resource.tab":1549432, "taxon.tab":162683, "time_elapsed":{"sec":1031.02, "min":17.18, "hr":0.29}}
71_delta	Thu 2022-04-28 10:42:15 PM	{"agent.tab":792529, "media_resource.tab":1556142, "taxon.tab":163083, "time_elapsed":{"sec":1283.01, "min":21.38, "hr":0.36}}
71_delta	Fri 2022-05-27 05:17:46 AM	{"agent.tab":792529, "media_resource.tab":1556142, "taxon.tab":163092, "time_elapsed":{"sec":4633.78, "min":77.23, "hr":1.29}}
-> new utility: filled-up missing parent entries 163092
71_delta	Thu 2022-07-07 07:22:56 AM	{"agent.tab":509946, "media_resource.tab":985603, "taxon.tab":110157, "time_elapsed":{"sec":7742.5, "min":129.04, "hr":2.15}}
-> big decrease - seems erroneous. Next run below is better:
71_delta	Wed 2022-07-13 07:21:21 PM	{"agent.tab":788702, "media_resource.tab":1248218, "taxon.tab":154018, "time_elapsed":{"sec":3744.34, "min":62.41, "hr":1.04}}
71_delta	Wed 2022-07-13 10:04:30 PM	{"agent.tab":788702, "media_resource.tab":1248218, "taxon.tab":154018, "time_elapsed":{"sec":1257.01, "min":20.95, "hr":0.35}}
-> first run using new scheme, no more or limited API calls consistent OK


71	Wed 2022-07-13 06:33:56 PM	{"agent.tab":788702, "media_resource.tab":1248218, "taxon.tab":154018, "vernacular_name.tab":973908, "time_elapsed":{"sec":5001.45, "min":83.36, "hr":1.39}}
71	Thu 2022-09-08 12:58:00 PM	{"agent.tab":800385, "media_resource.tab":1263636, "taxon.tab":0,      "vernacular_name.tab":1004483, "time_elapsed":{"sec":67555.65, "min":1125.93, "hr":18.77}}
71	Sat 2022-09-10 01:55:50 PM	{"agent.tab":921085, "media_resource.tab":1338503, "taxon.tab":154444, "vernacular_name.tab":1005143, "time_elapsed":{"sec":1432.36, "min":23.87, "hr":0.4}}
-> non-unique media, agent, vernaculars
71	Sat 2022-09-10 02:36:48 PM	{"agent.tab":800385, "media_resource.tab":1263636, "taxon.tab":154444, "vernacular_name.tab":1004483, "time_elapsed":{"sec":1520.51, "min":25.34, "hr":0.42}}
-> OK
71	Sat 2022-09-10 04:19:53 PM	{"agent.tab":800385, "media_resource.tab":1263636, "taxon.tab":154455, "vernacular_name.tab":1004483, "time_elapsed":{"sec":5343.71, "min":89.06, "hr":1.48}}
71	Mon 2022-12-05 07:00:42 PM	{"agent.tab":799148, "media_resource.tab":1263927, "taxon.tab":153843, "vernacular_name.tab":1004436, "time_elapsed":{"sec":290748.99, "min":4845.82, "hr":80.76, "day":3.37}}

wikimedia_comnames	Wed 2022-07-13 09:26:10 PM	{"taxon.tab":154018, "vernacular_name.tab":973908, "time_elapsed":{"sec":404.65, "min":6.74, "hr":0.11}}
wikimedia_comnames	Sat 2022-09-10 04:57:15 PM	{"taxon.tab":154455, "vernacular_name.tab":1004483, "time_elapsed":{"sec":434.01, "min":7.23, "hr":0.12}}
wikimedia_comnames	Mon 2022-12-05 07:50:40 PM	{"taxon.tab":153854, "vernacular_name.tab":1004436, "time_elapsed":{"sec":1290.23, "min":21.5, "hr":0.36}}
wikimedia_comnames	Fri 2023-02-24 06:11:01 PM	{"taxon.tab":154527, "vernacular_name.tab":1004878, "time_elapsed":{"sec":1279.15, "min":21.32, "hr":0.36}}
wikimedia_comnames	Tue 2023-03-14 07:05:47 PM	{"taxon.tab":166733, "vernacular_name.tab":1051497, "time_elapsed":{"sec":1427.04, "min":23.78, "hr":0.4}}
wikimedia_comnames	Sat 2023-06-10 04:54:33 AM	{"taxon.tab":168005, "vernacular_name.tab":1060128, "time_elapsed":{"sec":1390.36, "min":23.17, "hr":0.39}}

71_delta	Wed 2022-07-13 10:04:30 PM	{"agent.tab":788702, "media_resource.tab":1248218, "taxon.tab":154018, "time_elapsed":{"sec":1257.01, "min":20.95, "hr":0.35}}
71_delta	Sat 2022-09-10 05:37:07 PM	{"agent.tab":800385, "media_resource.tab":1263636, "taxon.tab":154455, "time_elapsed":{"sec":1299.87, "min":21.66, "hr":0.36}}
71_delta	Mon 2022-12-05 09:07:49 PM	{"agent.tab":799148, "media_resource.tab":1263927, "taxon.tab":153854, "time_elapsed":{"sec":3588.89, "min":59.81, "hr":1}}
71_delta	Fri 2023-02-24 07:29:26 PM	{"agent.tab":808872, "media_resource.tab":1276265, "taxon.tab":154527, "time_elapsed":{"sec":3688.85, "min":61.48, "hr":1.02}}
71_delta	Tue 2023-03-14 08:32:19 PM	{"agent.tab":838397, "media_resource.tab":1321583, "taxon.tab":166733, "time_elapsed":{"sec":4057.66, "min":67.63, "hr":1.13}}
71_delta	Sat 2023-06-10 06:19:55 AM	{"agent.tab":851208, "media_resource.tab":1337612, "taxon.tab":168005, "time_elapsed":{"sec":4005.2, "min":66.75, "hr":1.11}}

--------------------------
wikidata-hierarchy	        Tue 2022-02-08 10:02:56 AM	{"taxon.tab":3365727, "time_elapsed":{"sec":353281.95, "min":5888.03, "hr":98.13, "day":4.09}}
wikidata-hierarchy-final	Mon 2022-02-14 08:48:27 AM	{"taxon.tab":3365214, "time_elapsed":{"sec":5338.23, "min":88.97, "hr":1.48}}
wikidata-hierarchy-final	Wed 2022-02-16 01:41:50 AM	{"taxon.tab":3365214, "time_elapsed":{"sec":5814.62, "min":96.91, "hr":1.62}}

when doing tests locally:
php update_resources/connectors/wikidata.php _ generate_resource
php update_resources/connectors/wikidata.php _ generate_resource_force

historical investigations:
/01 EOL Projects ++/Wikimedia run status/2018 07 06 consoleText.txt
*/

/* for DATA-1784 - https://eol-jira.bibalex.org/browse/DATA-1784?focusedCommentId=63160&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-63160
2-steps on how to extract 71.tar.gz ---> works OK
mkdir 71
tar xvzf 71.tar.gz -C 71/
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/WikiHTMLAPI');
require_library('connectors/WikipediaAPI');
require_library('connectors/WikiDataAPI');
$timestart = time_elapsed();
$GLOBALS['ENV_DEBUG'] = false; //orig value should be -> false
/* during dev only*
ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);
$GLOBALS['ENV_DEBUG'] = true; //set to true during development
*/

/*
$orig = array(1,2,3,4,8,9);
$labels = array(5,6,7,2,4);
// $diff = array_intersect($labels, $orig);
$diff = array_intersect($orig, $labels);
$diff = array_values($diff); //reindex key
print_r($diff);
exit("\n-end-\n");
*/

/*
$str = "abcd|e|||";
echo "\n[$str]\n";
$str = Functions::remove_this_last_char_from_str($str, "|");
echo "\n[$str]\n";
exit;
*/

/* testing routines...
$str = "Year: [https://www.flickr.com/search/?tags=bookyear1841 1841] ([https://www.flickr.com/search/?tags=bookdecade1840 1840s])";
$str =  "Publisher: [https://www.flickr.com/search/?tags=bookpublisherLeiden_A_Arnz_comp_ Leiden, A. Arnz &amp; comp.]";
echo "\n$str\n";
echo wiki2html($str);
exit("\n");
*/

/* utility
$func = new WikiDataAPI($resource_id, "");
$func->process_wikimedia_txt_dump(); //initial verification of the wikimedia dump file. Not part of the normal operation
exit("\n Finished: just exploring... \n");
*/

/* sample command eol-archive:
php5.6 wikidata.php jenkins generate_resource 1 200000 1of6
php5.6 wikidata.php jenkins generate_resource
php5.6 wikidata.php jenkins generate_resource_force

when debugging in MacMini, to generate DwCA - works OK
php update_resources/connectors/wikidata.php _ generate_resource_force
*/

/* for wikidata taxonomy (DATA-1668) --- IMPORTANT: BE SURE TO RUN THIS AFTER Wikipedia EN (80.tar.gz)
php5.6 wikidata.php jenkins generate_wikidata_taxonomy
php update_resources/connectors/wikidata.php _ generate_wikidata_taxonomy
*/

// print_r($argv);
$params['jenkins_or_cron']  = @$argv[1]; //irrelevant here
$params['json']             = @$argv[2]; //useful here
$vparams = json_decode($params['json'], true);
print_r($vparams);

/* old
$params['task']             = @$argv[2];
$params['range_from']       = @$argv[3];
$params['range_to']         = @$argv[4];
$params['actual']           = @$argv[5];
*/
// /* new
$params['task']             = $vparams['task'];
$params['range_from']       = @$vparams['range_from'];
$params['range_to']         = @$vparams['range_to'];
$params['actual']           = @$vparams['actual'];
$params['divisor']          = @$vparams['divisor'];
// */
print_r($params);

/* a utility --- but just a test - working OK --- we have the same thing below, this on top is just a test.
if(@$params['task'] == "generate_wikidata_taxonomy") {
    $resource_id = 'wikidata-hierarchy';
    require_library('connectors/DWCADiagnoseAPI');
    $func = new DWCADiagnoseAPI();
    $undefined = $func->check_if_all_parents_have_entries($resource_id, true); //true means output will write to text file
    echo "\nUndefined parents: ".count($undefined)."\n";
}
exit("\n-end test-\n");
*/

// /* main operation
if($params['task'] == 'generate_wikidata_taxonomy') {
    $resource_id = 'wikidata-hierarchy';
    $func = new WikiDataAPI($resource_id, "en", "taxonomy"); //will generate hierarchy resource. [wikidata-hierarchy]
}
else {
    $resource_id = 71; //Wikimedia Commons is EOL resource = 71 //historical commons.tar.gz also exists on Nov 2017
    /* orig
    $func = new WikiDataAPI($resource_id, "en", "wikimedia"); //Used for Commons - total taxa = 2,208,086
    */
    // exit("\ngoes here\n");

    // /* **************************** new section for wikimedia ver2 ****************************
    $actual = @$params['actual'];
    if($actual) $resource_id .= "_".$actual;
    else { //meaning ready to finalize DwCA. Series 1of6, 2of6 - 6of6 are now done.
        if($params['task'] == 'generate_resource') {
            echo "\nReady to aggregate now...\n";
            return;
        }
    }
    $func = new WikiDataAPI($resource_id, "en", "wikimedia", array(), false, false, false);

    /* good debug: March 12, 2023
    $arr = $func->get_object('Q1130386');
    print_r($arr); exit("\n-stop-\n"); // confirms the new common names exists
    */

    // 7th param is false means running ver2
    // ******************************************************************************************* */
}

/* new utility for investigation -> moved to wikidata_test.php */

if($params['task'] == 'debug') { /* if you want to debug or test something: php update_resources/connectors/wikidata.php _ debug */
    $filename = @$params['range_from'];
    $func->test($filename); exit("\n-End Debug-\n");
}

if($params['task'] == 'test') { /*to run tests: php update_resources/connectors/wikidata.php _ test */
    $func->run_test(); exit("\n-End Tests-\n");
}

if(@$params['task'] == "create_all_taxon_dump") {
    $func->create_all_taxon_dump();     //step 1 (ran 1 connector)
    //Getting ready for next step:
    $txtfile = CONTENT_RESOURCE_LOCAL_PATH . "wikimedia_filenames_" . ".txt";           if(file_exists($txtfile)) unlink($txtfile);
    $txtfile = CONTENT_RESOURCE_LOCAL_PATH . "wikimedia_filenames_status_" . ".txt";    if(file_exists($txtfile)) unlink($txtfile);
}
elseif(@$params['task'] == "save_all_media_filenames") {
    $status = $func->save_all_media_filenames($params['task'], $params['range_from'], $params['range_to'], $params['actual']);  //step 2 (ran 6 connectors bec of lookup caching. Then ran 1 connector to finalize.)
    if($status) echo "\n---Can now proceed to next step...---\n\n";
    else exit(1);
}
elseif(@$params['task'] == "create_then_fill_commons_data") {                                      //step 3 (ran 1 connector)
    $func = new WikiDataAPI($resource_id, "");

    //these 2 functions are ran one after the other, preferably. This is to process a new WikiMedia dump

    echo "\nstart...create_temp_files_based_on_wikimedia_filenames()\n";
    $func->create_temp_files_based_on_wikimedia_filenames();     //create blank json files
    echo "\nend...create_temp_files_based_on_wikimedia_filenames()\n";

    echo "\nstart...fill_in_temp_files_with_wikimedia_dump_data()\n";
    $func->fill_in_temp_files_with_wikimedia_dump_data();        //fill-in those blank json files
    echo "\nend...fill_in_temp_files_with_wikimedia_dump_data()\n";
    echo("\n ==Finished preparing new WikiMedia dump== \n");

    //blank dirs removed, blank anyway at this point.
    $dirs = array('71_working', '71_1of6_working', '71_2of6_working', '71_3of6_working', '71_4of6_working', '71_5of6_working', '71_6of6_working');
    foreach($dirs as $dir) {
        if(is_dir($dir)) {
            $delete_dir = CONTENT_RESOURCE_LOCAL_PATH . $dir;
            recursive_rmdir($delete_dir); echo ("\n blank directory removed: [$delete_dir]");
        }
    }
}
elseif(@$params['task'] == "generate_resource" || @$params['task'] == "generate_resource_force"
                                               || @$params['task'] == "generate_wikidata_taxonomy") { //step 4 (ran 6 connectors initially)
    /* orig when just 1 connector
    $func->generate_resource();
    Functions::finalize_dwca_resource($resource_id);
    */
    $status_arr = $func->generate_resource($params['task'], $params['range_from'], $params['range_to'], $params['actual']);  //step 4 (ran 6 connectors bec of lookup caching. Then ran 1 connector to finalize.)
    if($status_arr[0]) {
        echo "\n".$params['actual']." -- finished\n";
        // if($status_arr[1]) { //orig - new ver can always finalize
        if(true) {
            echo "\n---Can now proceed - finalize dwca...CCC---\n\n";
            if(@$params['task'] == "generate_wikidata_taxonomy") $deleteFolderYN = false;
            else                                                 $deleteFolderYN = true;
            Functions::finalize_dwca_resource($resource_id, true, $deleteFolderYN, $timestart); //2nd param true means big file, 3rd param true means to delete working folder
            
            // /* now here is the part to check
            $what_generation_status = "wikimedia_generation_status_";
            if($func->finalize_media_filenames_ready($what_generation_status, $params['divisor'])) {
                aggregate_6partial_wikimedias($timestart, 71, $params['divisor']);
                delete_temp_files_and_others();
                $tmp = array('resource_id' => 71);
                inject_jenkins_run($tmp, 'fill_up_undefined_parents');
                inject_jenkins_run($tmp, 'Back_to_Wikimedia_Run');
            }
            else echo "\nNot yet ready [$what_generation_status]\n";
            // */
        }
        else echo "\nCannot finalize dwca yet 111.\n";
    }
    else exit(1);
}
// */

// /* utility
if(@$params['task'] == "generate_wikidata_taxonomy") {
    require_library('connectors/DWCADiagnoseAPI');
    $func = new DWCADiagnoseAPI();
    $undefined = $func->check_if_all_parents_have_entries($resource_id, true); //true means output will write to text file
    echo "\nUndefined parents (wikidata.php): ".count($undefined)."\n";
    $working_dir = CONTENT_RESOURCE_LOCAL_PATH . $resource_id;
    recursive_rmdir($working_dir); echo ("\n working directory removed: [$working_dir]");
}
// */

// BOLDS_DumpsServiceAPI.php
// private function add_needed_parent_entries($trials)

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n elapsed time = " . $elapsed_time_sec/60/60/24 . " days";
echo "\n Done processing.\n";

function aggregate_6partial_wikimedias($timestart, $resource_id, $divisor)
{
    require_library('connectors/DwCA_Aggregator_Functions');
    require_library('connectors/DwCA_Aggregator');
    $langs = array();
    //71_1of2.tar.gz... and so on
    //71_2of2.tar.gz... and so on
    
    //string generate the partials 1-6:
    for ($i = 1; $i <= $divisor; $i++) $langs[] = $resource_id."_".$i."of".$divisor;
    print_r($langs);

    // $resource_id .= '_ELI'; //debug only
    echo "\nProcessing [$resource_id] partials:[".count($langs)."]...\n";
    $func = new DwCA_Aggregator($resource_id, NULL, 'regular'); //'regular' not 'wikipedia' which is used in wikipedia aggregate resource
    $func->combine_DwCAs($langs);
    Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
}
function delete_temp_files_and_others($resource_id = false)
{   /*
    wikimedia_debug_2022-07-05 H_i_s.txt
    */
    $paths[] = CONTENT_RESOURCE_LOCAL_PATH . "wikimedia_generation_status_*.txt";
    // $paths[] = CONTENT_RESOURCE_LOCAL_PATH . "wikimedia_debug_*.txt";
    // $paths[] = CONTENT_RESOURCE_LOCAL_PATH . "wikipedia-_*of6.tar.gz";
    // if($resource_id) $paths[] = CONTENT_RESOURCE_LOCAL_PATH . $resource_id."_*of6.tar.gz"; //e.g. 80_1of6.tar.gz 
    foreach($paths as $path) {
        foreach(glob($path) as $filename) {
            echo "\n[$filename] [".filesize($filename)."] - ";
            if(unlink($filename)) echo "deleted OK\n";
            else                  echo "deletion failed\n";
        }
    }
}
function inject_jenkins_run($params, $what)
{
    require_library('connectors/MultipleConnJenkinsAPI');
    $funcj = new MultipleConnJenkinsAPI();
    if($what == 'fill_up_undefined_parents') {
        /*
        fill_up_undefined_parents.php jenkins '{"resource_id": "71", "source_dwca": "71", "resource": "fillup_missing_parents"}'
        */
        $resource_id = $params['resource_id'];
        echo "\ntry to fillup_missing_parents...\n";
        $arr_info = array();
        $arr_info['resource_id'] = $resource_id;
        $arr_info['connector'] = 'fill_up_undefined_parents';
        $funcj->jenkins_call_single_run($arr_info, "fillup missing parents");
    }
    elseif($what == 'Back_to_Wikimedia_Run') {
        $resource_id = $params['resource_id'];
        echo "\ntry to Back_to_Wikimedia_Run...\n";
        $arr_info = array();
        $arr_info['resource_id'] = $resource_id;
        $arr_info['connector'] = 'Back_to_Wikimedia_Run';
        $funcj->jenkins_call_single_run($arr_info, "Back to Wikimedia Run");
    }
    else exit("\nTask not specified.\n");
}

/*
this is a request made by wikimedia harvester (71): this 2 are same, first is a subset of the 2nd. And one is urlencoded() other is not.
http://commons.wikimedia.org/w/api.php?action=query&format=json&prop=imageinfo|categories&iiprop=url|mime|mediatype&cllimit=500&redirects&titles=File%3AROSMARINUS+OFFICINALIS+-+MORROCURT+-+IB-976+(Roman%C3%AD).JPG
http://commons.wikimedia.org/w/api.php?action=query&format=json&prop=imageinfo%7Ccategories&iiprop=url%7Cmime%7Cmediatype&cllimit=500&redirects&titles=File%3AROSMARINUS+OFFICINALIS+-+MORROCURT+-+IB-976+%28Roman%C3%AD%29.JPG%7CFile%3ACapra+hircus+aegagrus-cropped.jpg%7CFile%3AEschrichtius+robustus+01-cropped.jpg%7CFile%3AMonachus+monachus+-+Museo+civico+di+storia+naturale+%28Milan%29-cropped.jpg%7CFile%3AMustela+putorius+01-cropped.jpg%7CFile%3ACondylactis+gigantea+%28giant+Caribbean+sea+anemone%29+closeup.jpg%7CFile%3A20140922-cephalopholis+hemistiktos.jpg%7CFile%3APelecanus+occidentalis+in+flight+at+Bodega+Bay.jpg%7CFile%3AUdotea+flabellum+%28mermaid%27s+fan+alga%29+Bahamas.jpg%7CFile%3ARhipocephalus+phoenix+%28pinecone+alga%29+%28San+Salvador+Island%2C+Bahamas%29.jpg%7CFile%3APadina+boergesenii+%28leafy+rolled-blade+algae+Bahamas.jpg%7CFile%3ABird%27s-nest+Fern+Asplenium+nidus+Leaves+2.jpg%7CFile%3ABird%27s-nest+Fern+Asplenium+nidus+Leaves+1.jpg%7CFile%3ABlack+Caterpillar+Fern+Scyphularia+pentaphylla+Leaves.JPG%7CFile%3ABlunt-lobed+Woodsia+obtusa+Winter+Foliage.JPG%7CFile%3AChinese+Elm+Ulmus+parvifolia+%2832-0052-A%29+Bark+1.JPG%7CFile%3AChinese+Elm+Ulmus+parvifolia+%2832-0052-A%29+Bark+2.JPG%7CFile%3AJapanese+Maple+Acer+palmatum+%282012-055-A%29+Trunk+Bark.JPG%7CFile%3AJapanese+Alder+Alnus+japonica+%2881-305-A%29+Base+Bark.JPG%7CFile%3AJapanese+Maple+Acer+palmatum+%282010-012-A%29+Bark+Closeup+1.JPG%7CFile%3AJapanese+Maple+Acer+palmatum+%282010-012-A%29+Bark+Closeup+2.JPG%7CFile%3AJapanese+Zelkova+serrata+%2881-502-L%29+Upper+Trunk+Bark.JPG%7CFile%3AMiyabe+Maple+Acer+miyabei+%2851-292-A%29+Trunk+Bark+Closeup.JPG%7CFile%3AJapanese+Zelkova+serrata+%2881-502-L%29+Lower+Trunk+Bark.JPG%7CFile%3AMiyabe+Maple+Acer+miyabei+%2851-292-A%29+Trunk+Bark.JPG%7CFile%3ARed+Maple+Acer+rubrum+%2832-0877-A%29+Trunk+Bark.JPG%7CFile%3AWhite+Ash+Fraxinus+americana+%2854-0751-A%29+Trunk+Bark.JPG%7CFile%3ANerita+versicolor+%28four-toothed+nerite+snails%29+Bahamas.jpg%7CFile%3ACenchritis+muricatus+%28beaded+periwinkle+snails%29+Bahamas.jpg%7CFile%3AMonodelphis+domestica+skeleton+-+ZooKeys+465-10.png%7CFile%3AMonodelphis+brevicaudata+skull+-+ZooKeys+465-08.png%7CFile%3APeradectes+molars.png%7CFile%3APediomys+molars.png%7CFile%3AScreen+on+the+supermarket+shelf+%282654814813%29.jpg%7CFile%3ASacpha+Hodgson.jpg%7CFile%3AA+Sulphur+Crested+White+Cockatoo+%28Cacatua+galerita%29%2C+Cronulla%2C+NSW+Australia.jpg%7CFile%3AGOCZA%C5%81KOWICE+ZDR%C3%93J%2C+AB.+071.JPG%7CFile%3AVexillum+ebenus+01.JPG%7CFile%3ASCANDIX+PECTEN-VENERIS+-+AGUDA+-+IB-049+%28Agulles+de+pastor%29.JPG%7CFile%3ASCANDIX+PECTEN-VENERIS+-+AGUDA+-+IB-069+%28Agulles+de+pastor%29.JPG%7CFile%3ACRATAEGUS+MONOGYNA+-+AGUDA+-+IB-082+%28Ar%C3%A7%29.JPG%7CFile%3ACRATAEGUS+MONOGYNA+-+AGUDA+-+IB-089+%28Ar%C3%A7%29.JPG%7CFile%3ATagetes+erecta+23122014+%284%29.jpg%7CFile%3ACalendula+officinalis+27122014+%286%29.jpg%7CFile%3AFlowers+of+Judas+tree.jpg%7CFile%3ASyntrichia+montana+%28a%2C+164114-475524%29+9259.JPG%7CFile%3ASyntrichia+montana+%28a%2C+164114-475524%29+9262.JPG%7CFile%3ASyntrichia+montana+%28a%2C+164114-475524%29+9263.JPG%7CFile%3ASyntrichia+montana+%28a%2C+164114-475524%29+9264.JPG%7CFile%3ASyntrichia+montana+%28a%2C+164114-475524%29+9265.JPG&continue=

http://commons.wikimedia.org/w/api.php?action=query&format=json&prop=imageinfo|categories&iiprop=url|mime|mediatype&cllimit=500&redirects&titles=File:Whales are Paraphyletic.png
*/
/* wikimedia stats:
commons	Sunday 2017-11-26 01:34:01 PM	{"agent.tab":19055,"media_resource.tab":909996,"taxon.tab":89054}
Statistics
    http://rs.tdwg.org/dwc/terms/taxon:
        Total: 73533
    http://eol.org/schema/agent/agent:
        Total: 17793
    http://eol.org/schema/media/document:
        Total by type:
            http://purl.org/dc/dcmitype/StillImage: 806464
            http://purl.org/dc/dcmitype/MovingImage: 6223
            http://purl.org/dc/dcmitype/Sound: 1610
        Total by license:
            http://creativecommons.org/licenses/publicdomain/: 192680
            http://creativecommons.org/licenses/by/3.0/: 495316
            http://creativecommons.org/licenses/by-sa/3.0/: 124755
            http://creativecommons.org/licenses/by-nc/3.0/: 731
            No known copyright restrictions: 804
            http://creativecommons.org/licenses/by-nc-sa/3.0/: 11
        Total by language:
            en: 814297
        Total by format:
            image/jpeg: 769547
            image/png: 18712
            image/svg+xml: 3954
            video/ogg: 4498
            image/tiff: 12955
            audio/ogg: 1581
            image/gif: 1296
            video/webm: 1725
            audio/x-wav: 29
        Total: 814297
*/
?>