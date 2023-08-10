<?php
namespace php_active_record;
/**/
include_once(dirname(__FILE__) . "/../../config/environment.php");
// ini_set('memory_limit','7096M');
// $GLOBALS['ENV_DEBUG'] = true;
$timestart = time_elapsed();

$title      = "Ocean sunfish"; //"Ocean sunfish" en; //"Atlantic cod"; //"Mola mola" es ; //;
$language   = "en"; //"en";
$options    = array('resource_id' => 'wikipedia_revisions', 'expire_seconds' => 60*60*24*10, //10 days cache
                    'download_wait_time' => 1000000, 'timeout' => 10800, 'download_attempts' => 1);
$params = array();
$params['title'] = $title;
$params['language'] = $language;

require_library('connectors/WikipediaRevisionsAPI');
$func = new WikipediaRevisionsAPI($params);
$ret = $func->wikipedia_revision($params);
echo "\nret = [$ret]\n";

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>