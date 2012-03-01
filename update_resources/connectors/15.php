<?php
namespace php_active_record;

ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);
define('DOWNLOAD_WAIT_TIME', '300000'); // .3 seconds wait time
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('FlickrAPI');
$GLOBALS['ENV_DEBUG'] = false;


$auth_token = NULL;
if(FlickrAPI::valid_auth_token(FLICKR_PLEARY_AUTH_TOKEN))
{
    $auth_token = FLICKR_PLEARY_AUTH_TOKEN;
}


// create new _temp file
$resource_file = fopen(CONTENT_RESOURCE_LOCAL_PATH . "15_temp.xml", "w+");

// start the resource file with the XML header
fwrite($resource_file, \SchemaDocument::xml_header());

// query Flickr and write results to file
FlickrAPI::get_all_eol_photos($auth_token, $resource_file);

// write the resource footer
fwrite($resource_file, \SchemaDocument::xml_footer());
fclose($resource_file);

// cache the previous version and make this new version the current version
@unlink(CONTENT_RESOURCE_LOCAL_PATH . "15_previous.xml");
@rename(CONTENT_RESOURCE_LOCAL_PATH . "15.xml", CONTENT_RESOURCE_LOCAL_PATH . "15_previous.xml");
rename(CONTENT_RESOURCE_LOCAL_PATH . "15_temp.xml", CONTENT_RESOURCE_LOCAL_PATH . "15.xml");

// set Flickr to force harvest
if(filesize(CONTENT_RESOURCE_LOCAL_PATH . "15.xml") > 600)
{
    $GLOBALS['db_connection']->update("UPDATE resources SET resource_status_id=".ResourceStatus::find_or_create_by_translated_label('Force Harvest')->id." WHERE id=15");
}

?>
