<?php
namespace php_active_record;
/* connector for National Museum of Natural History Image Collection - part of 120 176 341 342 343 344 346
estimated execution time: 11 secs.
Connector reads the XML provided by partner and 
- sets the image rating.
- If needed ingests TypeInformation text dataObjects
- replaces wrong mimeType value
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
$GLOBALS['ENV_DEBUG'] = false;
require_library('ResourceDataObjectElementsSetting');

$timestart = time_elapsed();
$resource_id = 341; 

// $resource_path = "http://localhost/cp/NMNH/resources/nmnh-birds-response.xml.gz"; //local copy
$resource_path = Functions::get_accesspoint_url_if_available($resource_id, "http://collections.mnh.si.edu/services/eol/nmnh-birds-response.xml.gz"); //Birds resource
echo "\n processing resource:\n $resource_path \n\n";


$nmnh = new ResourceDataObjectElementsSetting($resource_id, $resource_path, 'http://purl.org/dc/dcmitype/StillImage', 2);
$xml = $nmnh->set_data_object_rating_on_xml_document(); //no params means will use default expire_seconds = 25 days
$xml = $nmnh->fix_NMNH_xml($xml);

//remove text objects per: https://eol-jira.bibalex.org/browse/DATA-1743
$xml = $nmnh->remove_data_object_of_certain_element_value("dataType", "http://purl.org/dc/dcmitype/Text", $xml);

require_library('connectors/INBioAPI');
$xml = INBioAPI::assign_eol_subjects($xml);
$xml = other_transformations($xml, $nmnh);
$nmnh->save_resource_document($xml);
$nmnh->call_xml_2_dwca($resource_id, "NMNH XML files");

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = $elapsed_time_sec seconds             \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes  \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\n\n Done processing.";

function other_transformations($xml, $nmnh)
{
    $xml = $nmnh->replace_data_object_element_value("mimeType", "audio/wav", "audio/x-wav", $xml);
    $xml = $nmnh->remove_data_object_of_certain_element_value("mimeType", "audio/x-wav", $xml); // to exclude <dataObject>'s of this element and value

    //from https://github.com/EOL/tramea/issues/296
    $newpath = "https://dl.dropboxusercontent.com/u/5763406/resources/341/mp3/";
    $xml = $nmnh->replace_data_object_element_value("mediaURL", "http://collections.mnh.si.edu/services/media.php?env=birds&irn=10027486", $newpath."MZ000134.MP3", $xml);
    $xml = $nmnh->replace_data_object_element_value("mediaURL", "http://collections.mnh.si.edu/services/media.php?env=birds&irn=10027485", $newpath."MZ000133.MP3", $xml);
    $xml = $nmnh->replace_data_object_element_value("mediaURL", "http://collections.mnh.si.edu/services/media.php?env=birds&irn=10027484", $newpath."MZ000132.MP3", $xml);
    $xml = $nmnh->replace_data_object_element_value("mediaURL", "http://collections.mnh.si.edu/services/media.php?env=birds&irn=10027490", $newpath."MZ000171.MP3", $xml);
    $xml = $nmnh->replace_data_object_element_value("mediaURL", "http://collections.mnh.si.edu/services/media.php?env=birds&irn=10027489", $newpath."MZ000170.MP3", $xml);
    $xml = $nmnh->replace_data_object_element_value("mediaURL", "http://collections.mnh.si.edu/services/media.php?env=birds&irn=10027488", $newpath."MZ000169.MP3", $xml);
    $xml = $nmnh->replace_data_object_element_value("mediaURL", "http://collections.mnh.si.edu/services/media.php?env=birds&irn=10027487", $newpath."MZ000168.MP3", $xml);
    $xml = $nmnh->replace_data_object_element_value("mediaURL", "http://collections.mnh.si.edu/services/media.php?env=birds&irn=10027509", $newpath."MZ000203.MP3", $xml);
    $xml = $nmnh->replace_data_object_element_value("mediaURL", "http://collections.mnh.si.edu/services/media.php?env=birds&irn=10027508", $newpath."MZ000202.MP3", $xml);
    $xml = $nmnh->replace_data_object_element_value("mediaURL", "http://collections.mnh.si.edu/services/media.php?env=birds&irn=10027507", $newpath."MZ000201.MP3", $xml);
    $xml = $nmnh->replace_data_object_element_value("mediaURL", "http://collections.mnh.si.edu/services/media.php?env=birds&irn=10027506", $newpath."MZ000200.MP3", $xml);
    return $xml;
}
?>
