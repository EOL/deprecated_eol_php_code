<?php
namespace php_active_record;
/* Image bundles for the next classifier - DATA-1865
estimated execution time:

php5.6 image_bundle_classifier.php jenkins '{"task": "task_1"}'
php5.6 image_bundle_classifier.php jenkins '{"task": "task_3a"}'
php5.6 image_bundle_classifier.php jenkins '{"task": "task_3c"}'

php5.6 image_bundle_classifier.php jenkins '{"task": "task_2"}'
php5.6 image_bundle_classifier.php jenkins '{"task": "task_3b"}'



1. Herbarium Sheets
https://editors.eol.org/other_files/bundle_images/classifier/herbarium_sheets.txt
https://editors.eol.org/other_files/bundle_images/classifier/herbarium_sheets_download.txt

2. Maps [3246]
https://editors.eol.org/other_files/bundle_images/classifier/maps.txt

3a. Zoological illustrations
https://editors.eol.org/other_files/bundle_images/classifier/Zoological_illustrations.txt
https://editors.eol.org/other_files/bundle_images/classifier/Zoological_illustrations_download.txt

3b. Phylogeny images [1017] [1034]
https://editors.eol.org/other_files/bundle_images/classifier/Phylogeny_images.txt

3c. Botanical illustrations
https://editors.eol.org/other_files/bundle_images/classifier/Botanical_illustrations.txt
https://editors.eol.org/other_files/bundle_images/classifier/Botanical_illustrations_download.txt

As of 24 Sep 2020
/extra/other_files/bundle_images/classifier/herbarium_sheets.txt: [3000]
/extra/other_files/bundle_images/classifier/herbarium_sheets_download.txt: [3000]
/extra/other_files/bundle_images/classifier/Zoological_illustrations.txt: [7067]
/extra/other_files/bundle_images/classifier/Zoological_illustrations_download.txt: [7067]
/extra/other_files/bundle_images/classifier/Botanical_illustrations.txt: [1144]
/extra/other_files/bundle_images/classifier/Botanical_illustrations_download.txt: [1144]
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/ImageBundleClassifierAPI');
$timestart = time_elapsed();

// print_r($argv);
$params['jenkins_or_cron']   = @$argv[1]; //irrelevant here
$params['json']              = @$argv[2]; //useful here
$param = json_decode($params['json'], true);
print_r($param);
$task = $param['task'];

$func = new ImageBundleClassifierAPI();
if($task == 'task_1')       $func->task_1_Herbarium_Sheets();
elseif($task == 'task_2')       $func->task_2_Maps("maps");
elseif($task == 'task_3a')  $func->task_3a_Zoological_illustrations();
elseif($task == 'task_3b')  $func->task_2_Maps("Phylogeny_images");
elseif($task == 'task_3c')  $func->task_3c_Botanical_illustrations();
else exit("\nNo specified task.\n");
$func->print_totals();
?>