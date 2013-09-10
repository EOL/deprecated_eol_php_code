<?php
namespace php_active_record;
/* NBII images
estimated execution time: 
    Numbers:
    6,243 images
    30.4 GB in size
    983 taxa
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/NbiiImagesAPI');
$timestart = time_elapsed();
$resource_id = 679;
$func = new NbiiImagesAPI($resource_id);

/* 
$func->save_before_site_goes_dark("http://localhost/~eolit/all.xml"); exit; //utility
$func->unlink_files(); exit; //utility 
$func->get_all_taxa("http://localhost/~eolit/animals_behavior_grooming.xml"); // or 'all.xml'
*/

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
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec . " seconds \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>