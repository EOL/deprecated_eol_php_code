<?php
namespace php_active_record;
/* estimated execution time: 5 minutes */

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/ClementsAPI');

$timestart = time_elapsed();
$resource_id = 527;
$func = new ClementsAPI($resource_id);

$data_dump_url = DOC_ROOT . "/update_resources/connectors/files/Clements/Clements Checklist 6.7 small.xls";

$func->get_all_taxa(); // you can pass $data_dump_url
if(filesize(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_working/taxon.tab") > 1000)
{
    if(is_dir(CONTENT_RESOURCE_LOCAL_PATH . $resource_id))
    {
        recursive_rmdir(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_previous");
        rename(CONTENT_RESOURCE_LOCAL_PATH . $resource_id, CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_previous");
    }
    rename(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_working", CONTENT_RESOURCE_LOCAL_PATH . $resource_id);
    Functions::set_resource_status_to_force_harvest($resource_id);
}

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>
