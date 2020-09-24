<?php
namespace php_active_record;
/* Image bundles for the next classifier - DATA-1865
estimated execution time:

php5.6 image_bundle_classifier.php jenkins '{"task": "task_1"}'
php5.6 image_bundle_classifier.php jenkins '{"task": "task_3a"}'
php5.6 image_bundle_classifier.php jenkins '{"task": "task_3c"}'

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
elseif($task == 'task_3a')  $func->task_3a_Zoological_illustrations();
elseif($task == 'task_3c')  $func->task_3c_Botanical_illustrations();
else exit("\nNo specified task.\n");
?>