<?php
namespace php_active_record;
/* estimated execution time:  56 minutes eol-archive

        31Dec   5Jan    12Jan   15Jan
Taxa:   2056    1951    1944    1936
Image:  3353    3293    3302    3293
agent   1       1       1       1

895	Sunday 2018-08-05 11:50:38 AM	{"agent.tab":1,"media_resource.tab":4339,"taxon.tab":2331} eol-archive
895	Tuesday 2018-08-07 10:35:07 AM	{"agent.tab":1,"media_resource.tab":4339,"taxon.tab":2331} eol-archive

*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/TrekNatureAPI');

$timestart = time_elapsed();
$resource_id = 895;
$func = new TrekNatureAPI($resource_id);
$func->get_all_taxa();
Functions::finalize_dwca_resource($resource_id, false, true);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\n\nDone processing.";
?>
