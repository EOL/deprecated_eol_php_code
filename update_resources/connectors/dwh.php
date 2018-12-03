<?php
namespace php_active_record;
/* This was first used in preparation for the smasher task left by Anne. No Jira ticket then.
Now this task has continued with a ticket from Katja: https://eol-jira.bibalex.org/browse/TRAM-800
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
/* e.g. php dws.php _ gbif */
$cmdline_params['jenkins_or_cron']  = @$argv[1]; //irrelevant here
$cmdline_params['what']             = @$argv[2]; //useful here

require_library('connectors/DHSourceHierarchiesAPI');
$timestart = time_elapsed();
ini_set('memory_limit','7096M'); //required

// /* //main operation
$func = new DHSourceHierarchiesAPI();
$func->start($cmdline_params['what']); 
// */

/* utility ==========================
require_library('connectors/DWCADiagnoseAPI');
$func = new DWCADiagnoseAPI();

$undefined_parents = $func->check_if_all_parents_have_entries($resource_id, true); //true means output will write to text file
echo "\nTotal undefined parents:" . count($undefined_parents)."\n"; unset($undefined_parents);

$without = $func->get_all_taxa_without_parent($resource_id, true); //true means output will write to text file
echo "\nTotal taxa without parents:" . count($without)."\n";
=====================================*/

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>
