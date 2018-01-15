<?php
namespace php_active_record;
/*
accesspoint_url = "http://dumps.wikimedia.org/commonswiki/latest/commonswiki-latest-pages-articles.xml.bz2";
*/
define('DOWNLOAD_WAIT_TIME', '1000000');  // 2 second wait after every web request
include_once(dirname(__FILE__) . "/../../config/environment.php");
return;
// $GLOBALS['ENV_DEBUG'] = false;
define("WIKI_USER_PREFIX", "http://commons.wikimedia.org/wiki/User:");
define("WIKI_PREFIX", "http://commons.wikimedia.org/wiki/");
require_vendor("wikipedia");

$resource_id = 71;
if(!Functions::can_this_connector_run($resource_id)) return;
$w = new WikimediaHarvester(Resource::find($resource_id));
$w->begin_wikimedia_harvest("update_resources/connectors/files/");

sleep(120); // delay 2 mins.
require_library("ResourceDataObjectElementsSetting");
$resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml";
$func = new ResourceDataObjectElementsSetting($resource_id, $resource_path);
$xml_string = file_get_contents($resource_path);
$xml = $func->remove_data_object_of_certain_element_value("dataType", "", $xml_string);
$func->save_resource_document($xml);
Functions::set_resource_status_to_harvest_requested($resource_id);
?>
