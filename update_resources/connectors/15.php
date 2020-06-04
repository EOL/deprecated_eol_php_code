<?php
namespace php_active_record;
/* execution time (Jenkins) - initial run:      took 3 days 6 hours
                            - suceeding runs:   ???
15	Thursday 2018-04-05 05:02:24 AM	{"agent.tab":1826,"media_resource.tab":224686,"taxon.tab":63145,"vernacular_name.tab":48718}
15	Saturday 2018-05-05 04:52:17 AM	{"agent.tab":1819,"media_resource.tab":225425,"taxon.tab":63428,"vernacular_name.tab":48632}
15	Tuesday 2018-06-05 05:28:10 AM	{"agent.tab":1812,"media_resource.tab":226307,"taxon.tab":63668,"vernacular_name.tab":48725}
15	Thursday 2019-10-17 06:49:21 AM	{"agent.tab":1785,"media_resource.tab":236651,"taxon.tab":65679,"vernacular_name.tab":50276,"time_elapsed":false}
15	Wednesday 2019-12-18 02:06:17 PM{"agent.tab":1782,"media_resource.tab":237585,"taxon.tab":65976,"vernacular_name.tab":50304,"time_elapsed":false} Consistent OK
15	Wednesday 2019-12-25 06:10:40 AM{"agent.tab":1856,"media_resource.tab":260368,"taxon.tab":70486,"vernacular_name.tab":52550,"time_elapsed":false} in between connector updates...
15	Wednesday 2019-12-25 08:53:24 PM{"agent.tab":1856, "media_resource.tab":260368, "taxon.tab":70486, "time_elapsed":false} expected, vernaculars removed. Consistent OK
15	Thursday 2020-06-04 06:52:37 AM	{"agent.tab":1796, "media_resource.tab":238120, "taxon.tab":71147, "time_elapsed":false} -- start new updated connector, no auth_token anymore.
*/
ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);
define('DOWNLOAD_WAIT_TIME', '300000'); // .3 seconds wait time
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
$resource_id = 15;
if(!Functions::can_this_connector_run($resource_id)) return;
require_library('FlickrAPI');
$GLOBALS['ENV_DEBUG'] = false;

$auth_token = NULL;
if(FlickrAPI::valid_auth_token(FLICKR_AUTH_TOKEN)) $auth_token = FLICKR_AUTH_TOKEN;

// create new _temp file
if(!($resource_file = Functions::file_open(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_temp.xml", "w+"))) return;

// start the resource file with the XML header
fwrite($resource_file, \SchemaDocument::xml_header());

// query Flickr and write results to file
// FlickrAPI::get_all_eol_photos($auth_token, $resource_file); //orig
FlickrAPI::get_all_eol_photos($auth_token, $resource_file, NULL, NULL, NULL, $resource_id);

// write the resource footer
fwrite($resource_file, \SchemaDocument::xml_footer());
fclose($resource_file);

// cache the previous version and make this new version the current version
@unlink(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_previous.xml");
Functions::file_rename(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml", CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_previous.xml");
Functions::file_rename(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_temp.xml", CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml");

// set Flickr to Harvest Requested
if(filesize(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml") > 600) Functions::set_resource_status_to_harvest_requested($resource_id);

//fix bad characters
$xml_string = file_get_contents(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml");
$xml_string = Functions::remove_invalid_bytes_in_XML($xml_string);
if(($fhandle = Functions::file_open(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml", "w")))
{
    fwrite($fhandle, $xml_string);
    fclose($fhandle);
}

//compress resource xml
Functions::gzip_resource_xml($resource_id); //un-comment if you want to investigate the XML file

require_library('ResourceDataObjectElementsSetting');
$nmnh = new ResourceDataObjectElementsSetting($resource_id);
$nmnh->call_xml_2_dwca($resource_id, "Flickr files", false, $timestart); //3rd param false means it is not NMNH resource.
?>
