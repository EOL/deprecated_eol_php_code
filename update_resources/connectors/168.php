<?php
namespace php_active_record;
/* estimated execution time: 6 minutes */

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/BioImagesAPI');

$timestart = time_elapsed();

$func = new BioImagesAPI;
$func->get_all_taxa();
$partner = "bioimages";
$resource_id = 168;

if(filesize(CONTENT_RESOURCE_LOCAL_PATH . $partner . "_working/taxon.tab") > 1000)
{
    if(is_dir(CONTENT_RESOURCE_LOCAL_PATH . $partner))
    {
        recursive_rmdir(CONTENT_RESOURCE_LOCAL_PATH . $partner . "_previous");
        rename(CONTENT_RESOURCE_LOCAL_PATH . $partner, CONTENT_RESOURCE_LOCAL_PATH . $partner . "_previous");
    }
    rename(CONTENT_RESOURCE_LOCAL_PATH . $partner . "_working", CONTENT_RESOURCE_LOCAL_PATH . $partner);
    Functions::set_resource_status_to_force_harvest($resource_id);
}

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
exit("\nDone processing.\n");

?>
