<?php
namespace php_active_record;
/* connector for BHL BioDivLibrary's photostream (cropped images using coordinates from API) -- http://www.flickr.com/photos/61021753@N02 */

ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);
define('DOWNLOAD_WAIT_TIME', '300000'); // .3 seconds wait time
include_once(dirname(__FILE__) . "/../../config/environment.php");

$timestart = time_elapsed();
$GLOBALS['ENV_DEBUG'] = true;

/* ----- start
require_library('FlickrAPI');

$user_id = "61021753@N02"; // BHL BioDivLibrary's photostream -- http://www.flickr.com/photos/61021753@N02
$auth_token = NULL;
if(FlickrAPI::valid_auth_token(FLICKR_AUTH_TOKEN)) $auth_token = FLICKR_AUTH_TOKEN;

$photo_id = 5860461193;
$photo_id = 6070544906;
// $photo_id = 37971173321;
$photo = FlickrAPI::photos_get_info($photo_id, "0ab954923d");
$p = $photo->photo;
$photo->bhl_addtl = FlickrAPI::add_additional_BHL_meta($p);

// print_r($photo);
echo "\n".$photo->photo->id;

// print_r($photo->bhl_addtl);
// print_r($photo->photo->notes);

exit("\nelix\n");
----- end */

$resource_id = "bhl_cropped_images";
require_library('connectors/BHL_Flickr_croppedImagesAPI');
$func = new BHL_Flickr_croppedImagesAPI($resource_id);
$func->start();
Functions::finalize_dwca_resource($resource_id, false, false, $timestart);
?>