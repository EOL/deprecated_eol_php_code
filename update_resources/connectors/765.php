<?php
namespace php_active_record;
/* CoralReefFish.com - with image and text objects and trait.
estimated execution time:
765	Tuesday 2019-02-19 02:55:59 AM	{"measurement_or_fact_specific.tab":118,"media_resource.tab":1494,"occurrence_specific.tab":118,"taxon.tab":279} MacMini
765	Tuesday 2019-02-19 03:09:55 AM	{"measurement_or_fact_specific.tab":118,"media_resource.tab":1494,"occurrence_specific.tab":118,"taxon.tab":279} eol-archive
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/CoralReefFishAPI');
$timestart = time_elapsed();
$resource_id = 765;
$func = new CoralReefFishAPI($resource_id);
$func->get_all_taxa();
Functions::finalize_dwca_resource($resource_id);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>