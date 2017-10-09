<?php
namespace php_active_record;
/* execution time (Jenkins) - initial run:      took 3 days 6 hours
                            - suceeding runs:   ???
*/
ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);
define('DOWNLOAD_WAIT_TIME', '300000'); // .3 seconds wait time
include_once(dirname(__FILE__) . "/../../config/environment.php");
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
FlickrAPI::get_all_eol_photos($auth_token, $resource_file);

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
// Functions::gzip_resource_xml($resource_id); //un-comment if you want to investigate the XML file

require_library('ResourceDataObjectElementsSetting');
$nmnh = new ResourceDataObjectElementsSetting($resource_id);
$nmnh->call_xml_2_dwca($resource_id, "Flickr files", false); //3rd param false means it is not NMNH resource.
?>
