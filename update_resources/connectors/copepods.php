<?php
namespace php_active_record;
/* https://eol-jira.bibalex.org/browse/DATA-1734
estimated execution time: 4.4 mins. when lookup is cached already.
copepods	Tuesday 2018-02-13 11:14:09 PM	{"measurement_or_fact.tab":21345,"occurrence.tab":18259,"reference.tab":931,"taxon.tab":2644}
copepods	Saturday 2018-03-10 07:46:22 PM	{"measurement_or_fact.tab":21345,"occurrence.tab":18259,"reference.tab":931,"taxon.tab":2644}
copepods	Monday 2018-10-22 10:24:35 AM	{"measurement_or_fact.tab":21345,"occurrence.tab":18259,"reference.tab":925,"taxon.tab":2644}
copepods	Thursday 2019-07-11 08:04:53 AM	{"measurement_or_fact.tab":21345,"occurrence.tab":18259,"reference.tab":925,"taxon.tab":2644}
copepods	Thursday 2019-07-11 08:30:46 AM	{"measurement_or_fact_specific.tab":21345,"occurrence.tab":18259,"reference.tab":925,"taxon.tab":2644} added lifeStage in MoF
*/
// return;
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/MarineCopepodsAPI');
$GLOBALS['ENV_DEBUG'] = true;
$timestart = time_elapsed();

// /* normal operation
$resource_id = "copepods";
$func = new MarineCopepodsAPI($resource_id);
$func->start();
unset($func);
Functions::finalize_dwca_resource($resource_id, false, true);
// */

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>
