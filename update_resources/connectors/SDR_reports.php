<?php
namespace php_active_record;
/*  DATA-1777: Writing resource files
    https://eol-jira.bibalex.org/browse/DATA-1777?focusedCommentId=63478&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-63478
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/SDRreportLib');

// ini_set('memory_limit','7096M'); //required
$timestart = time_elapsed();
$resource_id = 'SDR_all';
$func = new SDRreportLib($resource_id);

/* parent BV report utilities */
$func->gen_SampleSize_4parent_BV('BV', array('7662')); //generate SampleSize lookup table
$func->update_parentBV_reports();

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec . " seconds";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>