<?php
namespace php_active_record;
/* http://eol.org/content_partners/6/resources/24 - DATA-1705
This is a generic script that will convert EOL XML to EOL DWC-A

After 24.php, then run dwca_utility.php _ 24
Both scripts will generate 24.tar.gz.

Stats:
24	Tuesday 2017-11-28 02:36:35 AM	{"agent.tab":158,"measurement_or_fact.tab":28660,"media_resource.tab":169091,"occurrence.tab":28660,"taxon.tab":16516} - mac mini

eol-archive
24	Tuesday 2017-11-28 03:58:39 AM	{"agent.tab":158,"measurement_or_fact.tab":28655,"media_resource.tab":169091,"occurrence.tab":28655,"taxon.tab":16514} - eol-archive
24	Thursday 2018-01-11 09:21:02 AM	{"agent.tab":158,"media_resource.tab":169091,"taxon.tab":16054}
24	Thursday 2018-01-11 09:26:29 AM	{"agent.tab":158,"measurement_or_fact.tab":42848,"media_resource.tab":169091,"occurrence.tab":42848,"taxon.tab":16526} - eol-archive
24	Wednesday 2018-03-07 06:52:19 AM{"agent.tab":158,"measurement_or_fact.tab":42728,"media_resource.tab":169091,"occurrence.tab":42538,"taxon.tab":16546}
24	Wednesday 2018-03-07 07:03:49 PM{"agent.tab":158,"measurement_or_fact.tab":42728,"media_resource.tab":169091,"occurrence.tab":42538,"taxon.tab":16546} - all-hash measurementID
24	Thursday 2018-03-08 09:25:21 AM	{"agent.tab":158,"measurement_or_fact.tab":1046808,"media_resource.tab":169091,"occurrence.tab":1022432,"taxon.tab":16546} - with duplicate 'Present' values
24	Thursday 2018-03-08 08:13:53 PM	{"agent.tab":158,"measurement_or_fact.tab":1046808,"media_resource.tab":169091,"occurrence.tab":1022432,"taxon.tab":16546}
24	Sunday 2018-07-15 07:57:48 AM	{"agent.tab":158,"measurement_or_fact.tab":1129851,"media_resource.tab":169091,"occurrence.tab":1105328,"taxon.tab":16663}
24	Wednesday 2018-08-15 01:28:10 AM{"agent.tab":158,                                  "media_resource.tab":169091,                         "taxon.tab":16054}
24	Wednesday 2018-08-15 07:50:31 AM{"agent.tab":158,"measurement_or_fact.tab":1133522,"media_resource.tab":169091,"occurrence.tab":1108999,"taxon.tab":16722}
24	Saturday 2018-09-15 01:28:20 AM	{"agent.tab":158,                                  "media_resource.tab":169091,                         "taxon.tab":16054}
24	Monday 2018-10-15 01:28:17 AM	{"agent.tab":158,                                  "media_resource.tab":169091,                         "taxon.tab":16054}
Tried it again after a few months, still API is down:
24	Monday 2019-07-08 05:49:34 AM	{"agent.tab":158,                                  "media_resource.tab":169091,                         "taxon.tab":16054} their API is still down

24	Tue 2020-12-01 06:45:53 AM	    {"agent.tab":173, "measurement_or_fact_specific.tab":69540, "media_resource.tab":140143, "occurrence_specific.tab":46927, "taxon.tab":16470, "time_elapsed":{"sec":1410.32, "min":23.51, "hr":0.39}}

On Nov 23, 2020 we creted a new connector that will scrape the site.
So we saved the last DwCA for posterity.
[root@eol-archive resources]# cp 24.tar.gz 24_api.tar.gz
[root@eol-archive resources]# cp 24-adjusted.tar.gz 24-adjusted_api.tar.gz
24_new.php is the new connector.
*/
exit("\nObsolete. See 24.php for more details.\n");

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/ConvertEOLtoDWCaAPI');
$GLOBALS['ENV_DEBUG'] = false;
ini_set('memory_limit','4096M'); //314,5728,000
$timestart = time_elapsed();

$resource_id = 24;
$params["eol_xml_file"] = "";

/* old
$params["eol_xml_file"] = Functions::get_accesspoint_url_if_available($resource_id, "http://localhost/cp/OpenData/EOLxml_2_DWCA/AntWeb/eol.xml");
$params["eol_xml_file"] = Functions::get_accesspoint_url_if_available($resource_id, "http://antweb.org/web/eol/eol.xml");
*/

// $params["eol_xml_file"] = "http://localhost/cp/OpenData/EOLxml_2_DWCA/AntWeb/eol.xml";
$params["eol_xml_file"] = "http://antweb.org/web/eol/eol.xml";
$params["filename"]     = "no need to mention here.xml";
$params["dataset"]      = "EOL XML files";
$params["resource_id"]  = $resource_id;

$func = new ConvertEOLtoDWCaAPI($resource_id);
$func->export_xml_to_archive($params, true, 60*60*24*25); // true => means it is an XML file, not an archive file nor a zip file. Expires in 25 days.
// $func->export_xml_to_archive($params, true, false); // comment in normal operation, use above instead.

Functions::finalize_dwca_resource($resource_id, false, true);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>