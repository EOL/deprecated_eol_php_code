<?php
namespace php_active_record;

ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);
define('DOWNLOAD_WAIT_TIME', '300000'); // .3 seconds wait time
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('FlickrAPI');
$timestart = time_elapsed();
$GLOBALS['ENV_DEBUG'] = false;

$resource_id = 544;
$user_id = "61021753@N02"; // BHL BioDivLibrary's photostream -- http://www.flickr.com/photos/61021753@N02

$auth_token = NULL;
if(FlickrAPI::valid_auth_token(FLICKR_PLEARY_AUTH_TOKEN))
{
    $auth_token = FLICKR_PLEARY_AUTH_TOKEN;
}


// create new _temp file
$resource_file = fopen(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_temp.xml", "w+");

// start the resource file with the XML header
fwrite($resource_file, \SchemaDocument::xml_header());

// query Flickr and write results to file
FlickrAPI::get_all_eol_photos($auth_token, $resource_file, $user_id);

// write the resource footer
fwrite($resource_file, \SchemaDocument::xml_footer());
fclose($resource_file);

// cache the previous version and make this new version the current version
@unlink(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_previous.xml");
@rename(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml", CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_previous.xml");
rename(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_temp.xml", CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml");

Functions::set_resource_status_to_force_harvest($resource_id);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = $elapsed_time_sec seconds             \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes  \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\n\n Done processing.";

?>
