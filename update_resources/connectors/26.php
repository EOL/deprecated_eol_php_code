<?php
namespace php_active_record;
/*
WORMS archive
Now partner provides/hosts a DWC-A file. Connector also converts Distribution text into structured data.
estimated execution time: 2.78 hours

                24Sep'14
agent:          [922]
measurement:    [1172968]
media_resource: [101513]
occurrence:     [279966]
reference:      [319987]
taxon:          [311866]
vernacular:     [42231]

*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/WormsArchiveAPI');
$timestart = time_elapsed();
$resource_id = 26;
$func = new WormsArchiveAPI($resource_id);
$func->get_all_taxa();
if(filesize(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_working/taxon.tab") > 1000) // orig 1000
{
    if(is_dir(CONTENT_RESOURCE_LOCAL_PATH . $resource_id))
    {
        recursive_rmdir(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_previous");
        rename(CONTENT_RESOURCE_LOCAL_PATH . $resource_id, CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_previous");
    }
    rename(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_working", CONTENT_RESOURCE_LOCAL_PATH . $resource_id);
    rename(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_working.tar.gz", CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".tar.gz");
    Functions::set_resource_status_to_force_harvest($resource_id);
    Functions::count_resource_tab_files($resource_id);
}
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>