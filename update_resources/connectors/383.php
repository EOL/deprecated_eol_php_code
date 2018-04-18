<?php
namespace php_active_record;
/* http://eol.org/content_partners/179/resources/383

383	Tuesday 2018-04-17 11:43:22 PM	{"measurement_or_fact.tab":232027,"occurrence.tab":232027,"reference.tab":12230,"taxon.tab":754947,"vernacular_name.tab":107152} MacMini
383	Monday 2017-12-04 07:31:04 AM	{"measurement_or_fact.tab":233076,"occurrence.tab":233076,"reference.tab":12325,"taxon.tab":756165,"vernacular_name.tab":107909} eol-archive

*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/ITISConnector');
$timestart = time_elapsed();

$resource_id = 383;
$itis = new ITISConnector($resource_id);
$itis->build_archive();

// /* old ways - will stick with old ways
Functions::set_resource_status_to_harvest_requested($resource_id);
$arr = Functions::count_resource_tab_files($resource_id);
Functions::finalize_connector_run($resource_id, json_encode($arr));
if(is_dir(CONTENT_RESOURCE_LOCAL_PATH . $resource_id))               recursive_rmdir(CONTENT_RESOURCE_LOCAL_PATH . $resource_id);
if(is_dir(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_previous")) recursive_rmdir(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_previous");
// */

/* new ways - not fully tested yet - will need to edit the main library ITISConnector.php for this to work
Functions::finalize_dwca_resource($resource_id, false, true);
*/


/*                                      2017
   as of            Sep23    Mar30      Oct2
measurement         186666   189993     232027
occurrence          186666   189993     232027
reference           9116     9303       12230
taxon               651900   656733     754947
vernacular_name     102992   103371     107152
*/


$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n elapsed time = $elapsed_time_sec seconds           \n";
echo " elapsed time = " . $elapsed_time_sec/60 . " minutes  \n";
echo " elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\n\n Done processing.";
?>