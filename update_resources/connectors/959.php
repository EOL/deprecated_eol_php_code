<?php
namespace php_active_record;
/* DATA-1626 - execution time (Jenkins) : 13 mins.
                                2017
                        Jul-14  Oct-11
measurement_or_fact.tab [18948] 19421
occurrence.tab          [18877] 19348
taxon.tab               [7044]  7327
959	Friday 2018-03-02 05:19:39 PM	    {"measurement_or_fact.tab":19421,"occurrence.tab":19348,"taxon.tab":7327}
959	Wednesday 2018-03-07 09:15:13 AM	{"measurement_or_fact.tab":19421,"occurrence.tab":19348,"taxon.tab":7327}
959	Wednesday 2018-03-07 06:57:53 PM	{"measurement_or_fact.tab":19421,"occurrence.tab":19348,"taxon.tab":7327} all-hash measurementID
959	Thursday 2018-03-08 07:59:37 PM	    {"measurement_or_fact.tab":19421,"occurrence.tab":19348,"taxon.tab":7327}
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/AmphibiawebDataAPI');
$timestart = time_elapsed();
$resource_id = 959;
$func = new AmphibiawebDataAPI($resource_id);
$func->get_all_taxa();
Functions::finalize_dwca_resource($resource_id);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>