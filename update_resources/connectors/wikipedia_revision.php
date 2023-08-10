<?php
namespace php_active_record;
/**/
include_once(dirname(__FILE__) . "/../../config/environment.php");
// ini_set('memory_limit','7096M');
$GLOBALS['ENV_DEBUG'] = true; //false; //true;
$timestart = time_elapsed();

$title      = "એશિયાઇ_સિંહ"; //"Ocean sunfish"; //"Ocean sunfish" en; //"Atlantic cod"; //"Mola mola" es ; //;
$language   = "gu"; //"en";

// $title = "Ocean sunfish"; $language = "en";
$options    = array('resource_id' => 'wikipedia_revisions', 'expire_seconds' => 60*60*24*10, //10 days cache
                    'download_wait_time' => 1000000, 'timeout' => 10800, 'download_attempts' => 1);
$params = array();
$params['title'] = urlencode($title);
$params['language'] = $language;

require_library('connectors/WikipediaRevisionsAPI');
$func = new WikipediaRevisionsAPI($params);
$expire_seconds = $func->wikipedia_revision($params);

if($expire_seconds === 0)                   {} //debug("\nExpires now.");
elseif($expire_seconds === false)           {} //debug("\nSame timestamp, does not expire.");
elseif($expire_seconds == "do not proceed") {} //debug("\nWikipedia not found.");
echo "\nexpire_seconds = [$expire_seconds]\n";

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>