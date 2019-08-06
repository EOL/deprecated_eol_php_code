<?php
namespace php_active_record;
/*  DATA-1777: Writing resource files
    https://eol-jira.bibalex.org/browse/DATA-1777?focusedCommentId=63478&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-63478
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/SDRreportLib');

// ini_set('memory_limit','7096M'); //required
$timestart = time_elapsed();

$resource_id = 'parent_basal_values_Carnivora';
$resource_id = 'parent_basal_values';

$func = new SDRreportLib(false, $resource_id);

/* ================================= parent BV report utilities ================================= */
/* required utility - no need to repeat, just run once
$page_ids = array(7662, 4528789, 7675, 7669, 7672, 10647853, 7673, 7674, 4529519, 39311345, 7663, 4524096, 7665, 7677, 7676, 7664, 7670, 7671, 7666, 7667, 7668);
$page_ids = array(7662);
$page_ids = array(); //normal operation //67.89 minutes
$func->gen_SampleSize_4parent_BV('BV', $page_ids); //generate SampleSize lookup table
*/
// /* main program to update reports
$func->update_parentBV_reports();
// */
/* ============================================================================================== */

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec . " seconds";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>