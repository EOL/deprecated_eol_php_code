<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();

require_library('connectors/RemoveHTMLTagsAPI');
$str = "the <span>quick brows</span> fox <a class='myclass' href='http://eol.org/page/173' target='mytarget'>jumps over</a> the lazy dog. <b>This is bold text</b>. <img src='https://mydomain.com/eli.jpg'>My picture </img> in Manila.";
$str = 'the <span>quick brows</span> fox <a class="myclass" href="http://eol.org/page/173" target="mytarget">jumps over</a> the lazy dog. <b>This is bold text</b>. <img src="https://mydomain.com/eli.jpg">My picture </img> in Manila.';
$str = file_get_contents(CONTENT_RESOURCE_LOCAL_PATH."test.txt");
// $str = 'Fortin, Masson et Cie, Paris. <a href="https://biodiversitylibrary.org/page/37029493" target="_blank"><b>link to Plates</b></a>.';
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
