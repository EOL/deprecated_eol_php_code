<?php
namespace php_active_record;
/* IRMNG data
   execution time: 2.23 hours; 2,032,569 records to process

            excl '' status  fixed parent-child
as of:      Aug28       Aug31       Sep7
taxon       1,925,615   1,933,060   1,878,575
occurrence  3,938,768   3,938,768   3,938,768
measurement 3,938,768   3,938,768   3,938,768

*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/IrmngAPI');
$timestart = time_elapsed();
$resource_id = 741;
$func = new IrmngAPI($resource_id);
// $func->get_taxa_without_status_but_with_eol_page(); //utility
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
    Functions::count_rows_from_text_file(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "/taxon.tab");
    Functions::count_rows_from_text_file(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "/occurrence.tab");
    Functions::count_rows_from_text_file(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "/measurement_or_fact.tab");
}

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec . " seconds";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>