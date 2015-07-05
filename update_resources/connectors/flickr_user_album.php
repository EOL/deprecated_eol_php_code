<?php
namespace php_active_record;
/* This is a connector that uses the Flickr API. Parameters passed were the Flickr user_id and album id, and then generates the EOL DWC-A file for it. - DATA-1625
execution time: 

agent.tab 			[2]
media_resource.tab  [5809]
taxon.tab   		[2070]
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/FlickrUserAlbumAPI');
$timestart = time_elapsed();

$params['flickr_user_id'] 	= '56006259@N06';
$params['photoset_id']		= '72157655302770261'; //this is the album id

$resource_id = "feller_flora_helvetica";
$func = new FlickrUserAlbumAPI($resource_id);
$func->convert_to_dwca($params);
if(filesize(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_working/taxon.tab") > 1)
{
    if(is_dir(CONTENT_RESOURCE_LOCAL_PATH . $resource_id))
    {
        recursive_rmdir(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_previous");
        Functions::file_rename(CONTENT_RESOURCE_LOCAL_PATH . $resource_id, CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_previous");
    }
    Functions::file_rename(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_working", CONTENT_RESOURCE_LOCAL_PATH . $resource_id);
    Functions::file_rename(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_working.tar.gz", CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".tar.gz");
    Functions::set_resource_status_to_force_harvest($resource_id);
    Functions::count_resource_tab_files($resource_id);

	if($undefined_uris = Functions::get_undefined_uris_from_resource($resource_id)) print_r($undefined_uris);
    echo "\nUndefined URIs: " . count($undefined_uris) . "\n";

	require_library('connectors/DWCADiagnoseAPI');
	$func = new DWCADiagnoseAPI();
	$func->check_unique_ids($resource_id);
}
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>