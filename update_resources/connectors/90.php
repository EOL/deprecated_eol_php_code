<?php
namespace php_active_record;
/*
http://www.iucn-tftsg.org/pub-chron/
estimated execution time: 6 minutes
Connector screen scrapes the partner website.
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/ConservationBiologyTurtlesAPI');
$timestart = time_elapsed();

$resource_id = 90;
$func = new ConservationBiologyTurtlesAPI($resource_id);
$func->get_all_taxa();

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
echo "\n";
echo "elapsed time = $elapsed_time_sec seconds             \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes  \n";
exit("\n\n Done processing.");
?>