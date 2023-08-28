<?php
namespace php_active_record;
/* 
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/WikipediaHtmlAPI');
$timestart = time_elapsed();

$filenames = array("80", "wikipedia-es");
$filenames = array("957");

$func = new WikipediaHtmlAPI();

foreach($filenames as $filename) {
    $func->save_text_to_html($filename);
    // break;
}

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>