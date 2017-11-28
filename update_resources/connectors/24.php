<?php
namespace php_active_record;
/* http://eol.org/content_partners/6/resources/24 - DATA-1705
This is a generic script that will convert EOL XML to EOL DWC-A

After 24.php, then run dwca_utility.php _ 24
Both scripts will generate 24.tar.gz.

Stats:
24	Tuesday 2017-11-28 03:58:39 AM	{"agent.tab":158,"measurement_or_fact.tab":28655,"media_resource.tab":169091,"occurrence.tab":28655,"taxon.tab":16514} - eol-archive
24	Tuesday 2017-11-28 02:36:35 AM	{"agent.tab":158,"measurement_or_fact.tab":28660,"media_resource.tab":169091,"occurrence.tab":28660,"taxon.tab":16516} - mac mini
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/ConvertEOLtoDWCaAPI');
$GLOBALS['ENV_DEBUG'] = true;
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
Functions::finalize_dwca_resource($resource_id);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>