<?php
namespace php_active_record;
/* Animal Demography unit (ADU) Virtual Museum - MammalMAP (31 minutes execution time)
estimated execution time for 12 databases: 59 hours if records_per_page = 20, just set it to 50.
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/ADUVirtualMuseumAPI');
$timestart = time_elapsed();
$resource_id = 716;

$func = new ADUVirtualMuseumAPI($resource_id, "vimma");
$func->get_all_taxa();
if(filesize(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_working/taxon.tab") > 1000)
{
    if(is_dir(CONTENT_RESOURCE_LOCAL_PATH . $resource_id))
    {
        recursive_rmdir(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_previous");
        Functions::file_rename(CONTENT_RESOURCE_LOCAL_PATH . $resource_id, CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_previous");
    }
    Functions::file_rename(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_working", CONTENT_RESOURCE_LOCAL_PATH . $resource_id);
    Functions::file_rename(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_working.tar.gz", CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".tar.gz");
    Functions::set_resource_status_to_force_harvest($resource_id);
}

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";

/*
 182 of 236 [birdpix]
Grabbing http://vmus.adu.org.za//vm_view_db.php?database=birdpix&Records_per_page=20&start=3620&query_id=29738
 investigate 01 process_html() failed. 
*/

?>