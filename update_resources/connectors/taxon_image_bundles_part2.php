<?php
namespace php_active_record;
/* DATA-1845: Additional file format requirements - Chiroptera (for Katie)

php5.6 taxon_image_bundles_part2.php jenkins '{"eol_page_id":7631, "sci":"Chiroptera"}'
php5.6 taxon_image_bundles_part2.php jenkins '{"eol_page_id":747, "sci":"Lepidoptera_test"}'
php5.6 taxon_image_bundles_part2.php jenkins '{"eol_page_id":747, "sci":"Lepidoptera_train"}'

Next batch: Apr 1, 2020
php5.6 taxon_image_bundles_part2.php jenkins '{"eol_page_id":1, "sci":"anura_crops_test_notaug_fin"}'
php5.6 taxon_image_bundles_part2.php jenkins '{"eol_page_id":1, "sci":"carnivora_crops_test_notaug_fin"}'
php5.6 taxon_image_bundles_part2.php jenkins '{"eol_page_id":1, "sci":"coleoptera_crops_test_notaug_fin"}'
php5.6 taxon_image_bundles_part2.php jenkins '{"eol_page_id":1, "sci":"multitaxa_crops_test_notaug_fin"}'
php5.6 taxon_image_bundles_part2.php jenkins '{"eol_page_id":1, "sci":"multitaxa_crops_train_aug_all_fin"}'
php5.6 taxon_image_bundles_part2.php jenkins '{"eol_page_id":1, "sci":"squamata_crops_test_notaug_fin"}'

*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
// $GLOBALS['ENV_DEBUG'] = true; //true only locally; true locally by default

// print_r($argv);
$params['jenkins_or_cron']   = @$argv[1]; //irrelevant here
$params['json']              = @$argv[2]; //useful here
$param = json_decode($params['json']);

require_library('connectors/KatieXMLformatAPI');
$resource_id = 'Katie';
$func = new KatieXMLformatAPI($resource_id, $param);
$func->start(); //normal operation

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n elapsed time = " . $elapsed_time_sec/60/60/24 . " days";
echo "\n Done processing.\n";
?>
