<?php
namespace php_active_record;
/* Feller resource: http://eol.org/content_partners/494/resources/958
This is a connector that uses the Flickr API. Parameters passed were the Flickr user_id and album id, and then generates the EOL DWC-A file for it. - DATA-1625
execution time: 
                            Jul-6
agent.tab             [2]   2
media_resource.tab  [5809]  5611
taxon.tab           [2070]  2068
*/
return; //this is one-time for this specific resource, others in the future maybe periodic.
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/FlickrUserAlbumAPI');
$timestart = time_elapsed();

$params['flickr_user_id']     = '56006259@N06';
$params['photoset_id']        = '72157655302770261'; //this is the album id

$resource_id = 958;
$func = new FlickrUserAlbumAPI($resource_id);
$func->convert_to_dwca($params);
Functions::finalize_dwca_resource($resource_id);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>