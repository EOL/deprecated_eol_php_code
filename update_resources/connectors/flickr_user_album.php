<?php
namespace php_active_record;
/*
This is a connector that uses the Flickr API. Parameters passed were the Flickr user_id and album id, and then generates the EOL DWC-A file for it.
First client is 958.php (DATA-1625)
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/FlickrUserAlbumAPI');
$timestart = time_elapsed();
return;
/* change values here:
$params['flickr_user_id']   = '56006259@N06';
$params['photoset_id']      = '72157655302770261'; //this is the album id
$resource_id                = "feller_flora_helvetica";
*/

$func = new FlickrUserAlbumAPI($resource_id);
$func->convert_to_dwca($params);
Functions::finalize_dwca_resource($resource_id);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>
