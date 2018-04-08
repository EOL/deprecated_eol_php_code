<?php
namespace php_active_record;
/* connector for National Museum of Natural History Image Collection - part of 120 176 341 342 343 344 346
estimated execution time: 1.5 mins.
Connector reads the XML provided by partner and 
- sets the image rating.
- If needed ingests TypeInformation text dataObjects


120	Friday 2018-01-12 01:54:08 AM	{"agent.tab":125,"media_resource.tab":96313,"reference.tab":18760,"taxon.tab":39105}
176	Friday 2018-01-12 01:57:05 AM	{"agent.tab":185,"media_resource.tab":226221,"reference.tab":53755,"taxon.tab":106070}
341	Friday 2018-01-12 01:57:47 AM	{"agent.tab":35,"media_resource.tab":11739,"reference.tab":2757,"taxon.tab":3709}
342	Friday 2018-01-12 01:58:46 AM	{"agent.tab":132,"media_resource.tab":68208,"reference.tab":7302,"taxon.tab":12676}
343	Friday 2018-01-12 01:59:13 AM	{"agent.tab":60,"media_resource.tab":21698,"reference.tab":3387,"taxon.tab":3004}
344	Friday 2018-01-12 01:59:34 AM	{"agent.tab":35,"media_resource.tab":14619,"reference.tab":2679,"taxon.tab":2777}
346	Friday 2018-01-12 02:00:39 AM	{"agent.tab":30,"media_resource.tab":49978,"reference.tab":1950,"taxon.tab":13842}
891	Friday 2018-01-12 03:33:10 AM	{"agent.tab":87,"measurement_or_fact.tab":4392152,"media_resource.tab":137024,"occurrence.tab":352430,"taxon.tab":256379}

120	Monday 2018-03-12 01:54:12 AM	{"agent.tab":126,"media_resource.tab":96372,"reference.tab":18755,"taxon.tab":39090}
176	Monday 2018-03-12 01:57:06 AM	{"agent.tab":187,"media_resource.tab":227028,"reference.tab":53787,"taxon.tab":106117}
341	Monday 2018-03-12 01:57:45 AM	{"agent.tab":35,"media_resource.tab":11739,"reference.tab":2757,"taxon.tab":3709}
342	Monday 2018-03-12 01:58:44 AM	{"agent.tab":134,"media_resource.tab":68591,"reference.tab":7306,"taxon.tab":12694}
343	Monday 2018-03-12 01:59:12 AM	{"agent.tab":60,"media_resource.tab":22799,"reference.tab":3388,"taxon.tab":3069}
344	Monday 2018-03-12 01:59:32 AM	{"agent.tab":36,"media_resource.tab":14663,"reference.tab":2679,"taxon.tab":2786}
346	Monday 2018-03-12 02:00:28 AM	{"agent.tab":40,"media_resource.tab":38738,"reference.tab":1930,"taxon.tab":11966}
891	Monday 2018-03-12 05:01:10 AM	{"agent.tab":86,"measurement_or_fact.tab":4402583,"media_resource.tab":137786,"occurrence.tab":352671,"taxon.tab":256523}

120	Saturday 2018-04-07 09:51:24 AM	{"agent.tab":128,"media_resource.tab":27101,"taxon.tab":47746}
176	Saturday 2018-04-07 09:59:06 AM	{"agent.tab":187,"media_resource.tab":112234,"taxon.tab":106131}
341	Saturday 2018-04-07 09:59:27 AM	{"agent.tab":21,"media_resource.tab":4764,"taxon.tab":3213}
342	Saturday 2018-04-07 09:59:53 AM	{"agent.tab":104,"media_resource.tab":7558,"taxon.tab":9739}
343	Saturday 2018-04-07 10:00:12 AM	{"agent.tab":61,"media_resource.tab":9487,"taxon.tab":3070}
344	Saturday 2018-04-07 10:00:24 AM	{"agent.tab":16,"media_resource.tab":1713,"taxon.tab":2618}
346	Saturday 2018-04-07 10:01:17 AM	{"agent.tab":40,"media_resource.tab":38738,"reference.tab":1930,"taxon.tab":11966}
891	Saturday 2018-04-07 10:56:13 AM	{"measurement_or_fact.tab":5481279,"occurrence.tab":352782,"taxon.tab":256626}

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