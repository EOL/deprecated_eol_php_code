<?php
namespace php_active_record;

exit("\nObsolete, will terminate.\n");
// These resources: 120 176 341 342 343 344 346
// are now all obsolete.
// We are now only using this one resource: [NMNH images from GBIF export]
// https://opendata.eol.org/dataset/nmnh-images/resource/13c70e17-9983-4cc8-8e16-f446bd168726


/* connector for National Museum of Natural History Image Collection - part of 120 176 341 342 343 344 346
estimated execution time: 33 secs.
Connector reads the XML provided by partner and 
- sets the image rating.
- If needed ingests TypeInformation text dataObjects
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
$GLOBALS['ENV_DEBUG'] = false;
require_library('ResourceDataObjectElementsSetting');

$timestart = time_elapsed();
$resource_id = 342; 

$resource_path = Functions::get_accesspoint_url_if_available($resource_id, "http://collections.mnh.si.edu/services/eol/nmnh-fishes-response.xml.gz"); //Fishes resource
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