<?php
namespace php_active_record;
/* DATA-1842: EOL image bundles for Katie
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
$GLOBALS['ENV_DEBUG'] = true; //orig value should be -> false ... especially in eol-archive server

require_library('connectors/Eol_v3_API');
$resource_id = '';
$func = new Eol_v3_API($resource_id);

/* not used here...
// print_r($argv);
$params['jenkins_or_cron']   = @$argv[1]; //irrelevant here
$params['json']              = @$argv[2]; //useful here
$arr = json_decode($params['json'], true);
*/

$eol_page_id = 1;
$eol_page_id = 328672; //Panthera leo
// $eol_page_id = 919224; //wormy guy
$destination = false;
echo "\naaa\n";
$func->get_images_per_eol_page_id($eol_page_id, $destination); //normal operation


$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n elapsed time = " . $elapsed_time_sec/60/60/24 . " days";
echo "\n Done processing.\n";
?>
