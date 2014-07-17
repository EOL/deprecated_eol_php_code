<?php
namespace php_active_record;
/*
Before partner provides a TSV file:
estimated execution time: 14 minutes: 40k images | 50 minutes: 72k images

Now partner provides/hosts a DWC-A file. Together with images they also now share text objects as well.
estimated execution time: 55 minutes for:
 images:                114,658
 measurementorfact:     201,088
 taxa:                  17,499
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/MCZHarvardArchiveAPI');

$timestart = time_elapsed();
$resource_id = 201;
$func = new MCZHarvardArchiveAPI($resource_id);

$func->get_all_taxa();

if(filesize(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_working/taxon.tab") > 1000)
{
    if(is_dir(CONTENT_RESOURCE_LOCAL_PATH . $resource_id))
    {
        recursive_rmdir(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_previous");
        rename(CONTENT_RESOURCE_LOCAL_PATH . $resource_id, CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_previous");
    }
    rename(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_working", CONTENT_RESOURCE_LOCAL_PATH . $resource_id);
    rename(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_working.tar.gz", CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".tar.gz");
    Functions::set_resource_status_to_force_harvest($resource_id);
}

// $func->get_mediaURL_for_first_40k_images(); //this is a utility

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>