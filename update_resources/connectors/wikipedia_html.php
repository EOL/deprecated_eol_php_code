<?php
namespace php_active_record;
/**/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/WikipediaHtmlAPI');
$GLOBALS['ENV_DEBUG'] = false;
$timestart = time_elapsed();

$func = new WikipediaHtmlAPI();
$func->start();

/* working OK
$filenames = array("80", "es", "957");
$filenames = array("be-x-old", "zh-min-nan");
$filenames = array("80");
foreach($filenames as $filename) {
    $file = "wikipedia-".$filename;
    if(in_array($filename, array("80", "957"))) $file = $filename;
    $func->save_text_to_html($file);
}
*/

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>