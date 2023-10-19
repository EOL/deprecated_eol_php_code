<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();

require_library('connectors/RemoveHTMLTagsAPI');
// $func = new RemoveHTMLTagsAPI();
$str = "the quick brows fox <a href='http://eol.org/page/173'>jumps over,</a> the lazy dog.";
// $func->remove_html_tags($str);

$new = RemoveHTMLTagsAPI::remove_html_tags($str);
echo "\norig: [$str]\n";
echo "\nnew: [$new]\n";

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n elapsed time = " . $elapsed_time_sec/60/60/24 . " days";
echo "\n Done processing.\n";
?>