<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../../config/environment.php");
exit("\nObsolete. Now replaced by quaardvark.php.\nLast DwCA from this script is: [22_2017Dec3.tar.gz]\n\n");
/* 22.tar.gz was renamed to 22_2017Dec3.tar.gz
22	Sun 2021-01-10 07:18:59 PM	{"agent.tab":3061, "media_resource.tab":44424, "reference.tab":33048, "taxon.tab":5843, "vernacular_name.tab":4155, "time_elapsed":false}
*/

$new_resource_path = DOC_ROOT . "temp/22.xml.gz";

$download_options = array('cache' => 1, 'expire_seconds' => false, 'timeout' => 60*60); //doesn't expire since partner no longer hosts the file
// $xml = "http://animaldiversity.ummz.umich.edu/XML/adw_eol.xml.gz";
// $xml = "http://localhost/cp/ADW/resource_id_22/adw_eol.xml.gz";
$xml = "https://opendata.eol.org/dataset/99da8344-22a4-4d9d-890d-44ac3fc34a33/resource/12f27411-1aeb-4d49-987e-f868de033da1/download/adweol.xml.gz";
$new_resource = Functions::get_remote_file($xml, $download_options);

if(!($OUT = Functions::file_open($new_resource_path, "w+"))) return;
fwrite($OUT, $new_resource);
fclose($OUT);
shell_exec("gunzip -f ".$new_resource_path);
$new_resource_path = DOC_ROOT . "temp/22.xml";
$xml = file_get_contents($new_resource_path);
// $xml = str_replace("<dc:description>", "<dc:description><![CDATA[", $xml);
// $xml = str_replace("</dc:description>", "]]></dc:description>", $xml);
$xml = preg_replace("/<a>([^<]+)<\/a>/", "\\1", $xml);
if(substr_count($xml, "<?xml") == 0) $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" . $xml;

$old_resource_path = CONTENT_RESOURCE_LOCAL_PATH . "22.xml";
if(!($OUT = Functions::file_open($old_resource_path, "w+"))) return;
fwrite($OUT, $xml);
fclose($OUT);
shell_exec("rm ".$new_resource_path);

$resource_id = 22;
require_library('ResourceDataObjectElementsSetting');
$resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml";
$func = new ResourceDataObjectElementsSetting($resource_id, $resource_path);
$xml_string = file_get_contents($resource_path);
$xml = $func->remove_data_object_of_certain_element_value("mediaURL", "http://animaldiversity.ummz.umich.edu/", $xml_string);
$func->save_resource_document($xml);

$xml_string = file_get_contents($resource_path);
$xml = $func->replace_data_object_element_value("subject", "", "http://eol.org/schema/eol_info_items.xml#Notes", $xml_string);
$func->save_resource_document($xml);

// /* New: Jan 10, 2021
$xml_string = file_get_contents($resource_path);
$xml = $func->remove_data_object_of_certain_element_value("dataType", "http://purl.org/dc/dcmitype/StillImage", $xml_string);
$func->save_resource_document($xml);
// */

// Functions::gzip_resource_xml($resource_id); //un-comment if you want to investigate the 22.xml

//start convert to EOL DwCA
$nmnh = new ResourceDataObjectElementsSetting($resource_id);
$nmnh->call_xml_2_dwca($resource_id, "ADW Web Descriptions");
?>
