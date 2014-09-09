<?php
namespace php_active_record;
/* connector for FishBase
estimated execution time:
Provider provides text file. Connector parses it and assembles the EOL DWC-A.

                    Sep-9
taxon (with syn):   92515
media_resource:     224584
vernacular:         234617
agent.tab:          144
reference:          32739

*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/FishBaseArchiveAPI');
$timestart = time_elapsed();
$resource_id = 42;
$fishbase = new FishBaseArchiveAPI(false, $resource_id);
$fishbase->get_all_taxa($resource_id);

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
    Functions::count_rows_from_text_file(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "/media_resource.tab");
    Functions::count_rows_from_text_file(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "/vernacular_name.tab");
    Functions::count_rows_from_text_file(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "/agent.tab");
    Functions::count_rows_from_text_file(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "/reference.tab");
}

/* Generating the EOL XML
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/FishBaseAPI');
$timestart = time_elapsed();
$resource_id = 42;
$fishbase = new FishBaseAPI();
$fishbase->get_all_taxa($resource_id);
Functions::set_resource_status_to_force_harvest($resource_id);
*/

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = $elapsed_time_sec seconds             \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes  \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\n\n Done processing.";
?>