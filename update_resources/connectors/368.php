<?php
namespace php_active_record;
/* PaleoDB connector - we use their data services to assemble their data and ingest it as structured data
estimated execution time: 40 minutes | 1.9 hours
This resource is formerly id = 719

            Sep 4
taxon       [234423]
occurrence  [972262]
measurement [972262]
vernacular  [3753]
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/PaleoDBAPI');
$timestart = time_elapsed();
$resource_id = 368;
$func = new PaleoDBAPI($resource_id);
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
    Functions::count_resource_tab_files($resource_id);
}
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";

?>