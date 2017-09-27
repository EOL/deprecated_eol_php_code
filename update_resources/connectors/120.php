<?php
namespace php_active_record;
/* connector for National Museum of Natural History Image Collection
estimated execution time: 1.5 mins.
Connector reads the XML provided by partner and 
- sets the image rating.
- If needed ingests TypeInformation text dataObjects
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

require_library('connectors/INBioAPI');
$xml = INBioAPI::assign_eol_subjects($xml);

$nmnh->save_resource_document($xml);
Functions::set_resource_status_to_harvest_requested($resource_id);

$nmnh->call_xml_2_dwca($resource_id, "NMNH XML files");

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = $elapsed_time_sec seconds             \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes  \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\n\n Done processing.";
?>