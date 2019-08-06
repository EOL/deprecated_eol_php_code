<?php
namespace php_active_record;
/*  DATA-1777: Writing resource files
    https://eol-jira.bibalex.org/browse/DATA-1777?focusedCommentId=63478&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-63478
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/SDR_report');

// ini_set('memory_limit','7096M'); //required
$timestart = time_elapsed();
$resource_id = 'SDR_all';
$func = new SDR_report($resource_id);

// $func->test_basal_values('BV');          return;
// $func->print_basal_values('BV');         //return;   //3.91 hours
// $func->test_parent_basal_values('BV', false);   return; //2nd parm is debugModeYN
// $func->print_parent_basal_values('BV');  //return; //main orig report //92.75 minutes
// $func->print_parent_basal_values('BV', false, false, true);  return; //4th param true means it is debugMode true

$func->gen_SampleSize_4parent_BV('BV', array('7662')); //generate SampleSize lookup table

// /* for multiple page_ids: BV
// $page_ids = array(7662, 4528789, 7675, 7669, 7672, 10647853, 7673, 7674, 4529519, 39311345, 7663, 4524096, 7665, 7677, 7676, 7664, 7670, 7671, 7666, 7667, 7668);
// $page_ids = array(7662);
// $func->print_parent_basal_values('BV', $page_ids, 'Carnivora'); return; //used also for test for SampleSize task
// $page_ids = array(1); $func->print_parent_basal_values('BV', $page_ids, 'Metazoa'); //return;
// foreach($page_ids as $page_id) $final[$page_id] = array('taxonRank' => 'not species', 'Landmark' => 1); //good but not used eventually
// */

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec . " seconds";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>