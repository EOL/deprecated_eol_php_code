<?php
namespace php_active_record;
/* connector for National Museum of Natural History Image Collection - part of 120 176 341 342 343 344 346
estimated execution time: 1.5 mins.
Connector reads the XML provided by partner and 
- sets the image rating.
- If needed ingests TypeInformation text dataObjects


120	Monday 2018-03-12 01:54:12 AM	{"agent.tab":126,"media_resource.tab":96372,"reference.tab":18755,"taxon.tab":39090}
120	Saturday 2018-04-07 09:51:24 AM	{"agent.tab":128,"media_resource.tab":27101,"taxon.tab":47746}
120	Sunday 2018-04-08 11:07:04 AM	{"agent.tab":128,"media_resource.tab":27101,"taxon.tab":47746} eol-archive, no more text objects

176	Monday 2018-03-12 01:57:06 AM	{"agent.tab":187,"media_resource.tab":227028,"reference.tab":53787,"taxon.tab":106117}
176	Saturday 2018-04-07 09:59:06 AM	{"agent.tab":187,"media_resource.tab":112234,"taxon.tab":106131}
176	Sunday 2018-04-08 11:15:12 AM	{"agent.tab":187,"media_resource.tab":112234,"taxon.tab":106131} eol-archive, no more text objects

341	Monday 2018-03-12 01:57:45 AM	{"agent.tab":35,"media_resource.tab":11739,"reference.tab":2757,"taxon.tab":3709}
341	Saturday 2018-04-07 09:59:27 AM	{"agent.tab":21,"media_resource.tab":4764,"taxon.tab":3213}
341	Sunday 2018-04-08 11:15:34 AM	{"agent.tab":21,"media_resource.tab":4764,"taxon.tab":3213} eol-archive, no more text objects

342	Monday 2018-03-12 01:58:44 AM	{"agent.tab":134,"media_resource.tab":68591,"reference.tab":7306,"taxon.tab":12694}
342	Saturday 2018-04-07 09:59:53 AM	{"agent.tab":104,"media_resource.tab":7558,"taxon.tab":9739}
342	Sunday 2018-04-08 11:15:59 AM	{"agent.tab":104,"media_resource.tab":7558,"taxon.tab":9739} eol-archive, no more text objects

343	Monday 2018-03-12 01:59:12 AM	{"agent.tab":60,"media_resource.tab":22799,"reference.tab":3388,"taxon.tab":3069}
343	Saturday 2018-04-07 10:00:12 AM	{"agent.tab":61,"media_resource.tab":9487,"taxon.tab":3070}
343	Sunday 2018-04-08 11:16:18 AM	{"agent.tab":61,"media_resource.tab":9487,"taxon.tab":3070} eol-archive, no more text objects

344	Monday 2018-03-12 01:59:32 AM	{"agent.tab":36,"media_resource.tab":14663,"reference.tab":2679,"taxon.tab":2786}
344	Saturday 2018-04-07 10:00:24 AM	{"agent.tab":16,"media_resource.tab":1713,"taxon.tab":2618}
344	Sunday 2018-04-08 11:16:30 AM	{"agent.tab":16,"media_resource.tab":1713,"taxon.tab":2618} eol-archive, no more text objects

346	Monday 2018-03-12 02:00:28 AM	{"agent.tab":40,"media_resource.tab":38738,"reference.tab":1930,"taxon.tab":11966}
346	Saturday 2018-04-07 10:01:17 AM	{"agent.tab":40,"media_resource.tab":38738,"reference.tab":1930,"taxon.tab":11966}
346	Sunday 2018-04-08 11:17:19 AM	{"agent.tab":40,"media_resource.tab":36359,"reference.tab":1930,"taxon.tab":11966} eol-archive, no more text objects

*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$GLOBALS['ENV_DEBUG'] = false;
require_library('ResourceDataObjectElementsSetting');

$timestart = time_elapsed();
$resource_id = 120;

// $resource_path = "http://localhost/cp/OpenData/EOLxml_2_DWCA/nmnh-iz-response.xml.gz"; //local copy
$resource_path = Functions::get_accesspoint_url_if_available($resource_id, "http://collections.mnh.si.edu/services/eol/nmnh-iz-response.xml.gz"); //NMNH Invertebrate Zoology resource
echo "\n processing resource:\n $resource_path \n\n";


$nmnh = new ResourceDataObjectElementsSetting($resource_id, $resource_path, 'http://purl.org/dc/dcmitype/StillImage', 2);
$xml = $nmnh->set_data_object_rating_on_xml_document(); //no params means will use default expire_seconds = 25 days
$xml = $nmnh->fix_NMNH_xml($xml);

//remove text objects per: https://eol-jira.bibalex.org/browse/DATA-1743
$xml = $nmnh->remove_data_object_of_certain_element_value("dataType", "http://purl.org/dc/dcmitype/Text", $xml);

require_library('connectors/INBioAPI');
$xml = INBioAPI::assign_eol_subjects($xml);
$nmnh->save_resource_document($xml);
$nmnh->call_xml_2_dwca($resource_id, "NMNH XML files");

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = $elapsed_time_sec seconds             \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes  \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\n\n Done processing.";
?>