<?php
namespace php_active_record;
// /* Converting Student Contribution HTML to MediaWiki
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
require_library('HTML2MediaWikiAPI_StudCont');
$func = new HTML2MediaWikiAPI_StudCont();
$func->start();
// */

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = $elapsed_time_sec seconds             \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes  \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\n\n Done processing.";
?>