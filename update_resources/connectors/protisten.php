<?php
namespace php_active_record;
/* Protisten.de gallery - https://eol-jira.bibalex.org/browse/DATA-1802
protisten	Thursday 2019-03-21 11:05:34 AM	{"agent.tab":1,"media_resource.tab":1587,"taxon.tab":556}
protisten	Thursday 2019-03-28 09:40:16 PM	{"agent.tab":1,"media_resource.tab":1587,"taxon.tab":556}
protisten	Monday 2019-04-15 12:53:10 AM	{"agent.tab":1,"media_resource.tab":1587,"taxon.tab":1050}
protisten	Thursday 2019-09-19 11:49:40 PM	{"agent.tab":1,"media_resource.tab":1701,"taxon.tab":1089}
protisten	Monday 2019-11-04 03:23:38 AM	{"agent.tab":1,"media_resource.tab":1701,"taxon.tab":1089,"time_elapsed":false}
protisten	Monday 2019-11-11 10:05:34 AM	{"agent.tab":1,"media_resource.tab":1701,"taxon.tab":1089,"time_elapsed":{"sec":19.97,"min":0.33,"hr":0.01}}
            Monday 2019-11-11 10:40:00 AM	{"agent.tab":1,"media_resource.tab":1705,"taxon.tab":1093} consistent OK
protisten	Monday 2020-02-10 02:20:24 AM	{"agent.tab":1, "media_resource.tab":1708, "taxon.tab":1092, "time_elapsed":{"sec":1283.94,"min":21.4,"hr":0.36}} consistent OK
protisten	Monday 2020-04-13 12:07:10 AM	{"agent.tab":1, "media_resource.tab":1842, "taxon.tab":1125, "time_elapsed":{"sec":1345.06, "min":22.42, "hr":0.37}} re-harvested upon request, consistent OK
protisten	Sun 2020-05-10 01:59:33 AM	{"agent.tab":1, "media_resource.tab":1842, "taxon.tab":1125, "time_elapsed":{"sec":32.79, "min":0.55, "hr":0.01}}
protisten	Wed 2020-06-10 02:27:23 AM	{"agent.tab":1, "media_resource.tab":1842, "taxon.tab":1124, "time_elapsed":{"sec":1701.28, "min":28.35, "hr":0.47}}
protisten	Fri 2020-07-10 01:59:33 AM	{"agent.tab":1, "media_resource.tab":1842, "taxon.tab":1124, "time_elapsed":{"sec":32.95, "min":0.55, "hr":0.01}}
protisten	Mon 2020-08-10 02:22:28 AM	{"agent.tab":1, "media_resource.tab":1842, "taxon.tab":1124, "time_elapsed":{"sec":1408.54, "min":23.48, "hr":0.39}}
protisten	Sat 2020-08-29 11:32:40 AM	{"agent.tab":1, "media_resource.tab":1842, "taxon.tab":1124, "time_elapsed":{"sec":1389.91, "min":23.17, "hr":0.39}}
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
$resource_id = "protisten";
// /* un-comment in real operation
require_library('connectors/Protisten_deAPI');
$func = new Protisten_deAPI($resource_id);
$func->start();
Functions::finalize_dwca_resource($resource_id, false, false, $timestart); //3rd param true means to delete working resource folder
// */

/* utility */
require_library('connectors/DWCADiagnoseAPI');
$func = new DWCADiagnoseAPI();
// $func->check_unique_ids($resource_id); //takes time
$undefined = $func->check_if_all_parents_have_entries($resource_id, true); //true means output will write to text file
if($undefined) echo "\nERROR: There is undefined parent(s): ".count($undefined)."\n";
else           echo "\nOK: All parents in taxon.tab have entries.\n";

recursive_rmdir(CONTENT_RESOURCE_LOCAL_PATH . '/' . $resource_id); // remove working dir

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n elapsed time = " . $elapsed_time_sec . " seconds";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing\n";
?>