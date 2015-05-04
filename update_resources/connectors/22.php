<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../../config/environment.php");

$new_resource_path = DOC_ROOT . "temp/22.xml.gz";
$new_resource = Functions::get_remote_file("http://animaldiversity.ummz.umich.edu/XML/adw_eol.xml.gz");
// $new_resource = Functions::get_remote_file("http://localhost/~eolit/eli/eol_php_code/applications/content_server/resources/adw_eol.xml.gz");
if(!($OUT = fopen($new_resource_path, "w+")))
{
  debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .$new_resource_path);
  return;
}
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
if(!($OUT = fopen($old_resource_path, "w+")))
{
  debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .$old_resource_path);
  return;
}
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
Functions::set_resource_status_to_force_harvest($resource_id);
Functions::gzip_resource_xml($resource_id);
?>
