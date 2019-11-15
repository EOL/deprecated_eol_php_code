<?php
namespace php_active_record;
/* DATA-1842: EOL image bundles for Katie

wget -q https://content.eol.org/data/media/91/b9/c7/740.027116-1.jpg -O /extra/other_files/image_bundles/xxx/740.027116-1.jpg

wget -nc https://content.eol.org/data/media/91/b9/c7/740.027116-1.jpg -O /Volumes/AKiTiO4/other_files/bundle_images/xxx/740.027116-1.jpg

php5.6 taxon_image_bundles.php jenkins '{"eol_page_id":919224, "sci":"wormy guy"}'
php5.6 taxon_image_bundles.php jenkins '{"eol_page_id":328672, "sci":"Panthera leo"}'

php5.6 taxon_image_bundles.php jenkins '{"eol_page_id":695, "sci":"Aves"}'
php5.6 taxon_image_bundles.php jenkins '{"eol_page_id":164, "sci":"Arthropoda"}'
php5.6 taxon_image_bundles.php jenkins '{"eol_page_id":1642, "sci":"Mammalia"}'

*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
$GLOBALS['ENV_DEBUG'] = false; //orig value should be -> false ... especially in eol-archive server

require_library('connectors/Eol_v3_API');
$resource_id = '';
$func = new Eol_v3_API($resource_id);

// print_r($argv);
$params['jenkins_or_cron']   = @$argv[1]; //irrelevant here
$params['json']              = @$argv[2]; //useful here
$param = json_decode($params['json'], true);

if(Functions::is_production()) $path = '/extra/other_files/bundle_images/files/';
else                           $path = '/Volumes/AKiTiO4/other_files/bundle_images/files/';


// $destination = CONTENT_RESOURCE_LOCAL_PATH.'images_for_'.str_replace(" ", "_", $param['sci']).".txt"; //false;

/* 1K bundles
$destination = $path.'images_for_'.str_replace(" ", "_", $param['sci']).".txt"; //false;
$func->get_images_per_eol_page_id($param, array(), $destination, 1000); //normal operation
*/
// /* 20K bundles
$destination = $path.'images_for_'.str_replace(" ", "_", $param['sci'])."_20K.txt"; //false;
$func->get_images_per_eol_page_id($param, array(), $destination, 20000); //normal operation
// */

/* working but not used for now...
$func->bundle_images_4download_per_eol_page_id($param, $destination); //normal operation
*/
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n elapsed time = " . $elapsed_time_sec/60/60/24 . " days";
echo "\n Done processing.\n";
?>
