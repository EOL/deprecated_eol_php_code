<?php
namespace php_active_record;
/* This is a library that handles GBIF download requests using their API */
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/GBIFdownloadRequestAPI');
$timestart = time_elapsed();
/*
php update_resources/connectors/gbif_download_request.php _ '{"task":"send_download_request", "taxon":"Animalia"}'
php update_resources/connectors/gbif_download_request.php _ '{"task":"send_download_request", "taxon":"Plantae"}'
php update_resources/connectors/gbif_download_request.php _ '{"task":"send_download_request", "taxon":"Others"}'

php update_resources/connectors/gbif_download_request.php _ '{"task":"send_download_request", "taxon":"Gadus ogac"}'
php update_resources/connectors/gbif_download_request.php _ '{"task":"start_download", "taxon":"Gadus ogac"}'
php update_resources/connectors/gbif_download_request.php _ '{"task":"start_download", "taxon":"Gadus morhua"}'


Animalia
php update_resources/connectors/gbif_download_request.php _ '{"task":"check_download_request_status", "download_key": "0017907-200613084148143"}'

1,181,679,863 - new
1,031,353,123 - old

Plantae
php update_resources/connectors/gbif_download_request.php _ '{"task":"check_download_request_status", "download_key": "0018041-200613084148143"}'
php update_resources/connectors/gbif_download_request.php _ '{"task":"check_download_request_status", "download_key": "0081144-200221144449610"}'
245,670,485 - new
239,862,739 - old

Others:
php update_resources/connectors/gbif_download_request.php _ '{"task":"check_download_request_status", "download_key": "0017549-200613084148143"}'
php update_resources/connectors/gbif_download_request.php _ '{"task":"check_download_request_status", "download_key": "0081146-200221144449610"}'

44,359,969 - new
44,610,800 - old


Gadus ogac
php update_resources/connectors/gbif_download_request.php _ '{"task":"check_download_request_status", "download_key": "0018059-200613084148143"}'


*/
// print_r($argv);
$params['jenkins_or_cron']   = @$argv[1]; //irrelevant here
$params['json']              = @$argv[2]; //useful here
$fields = json_decode($params['json'], true);
$task = $fields['task'];
$taxon = @$fields['taxon'];
$download_key = @$fields['download_key'];

//############################################################ start main
$resource_id = "1";
$func = new GBIFdownloadRequestAPI($resource_id);
if($task == 'send_download_request') $func->send_download_request($taxon);
if($task == 'start_download') $func->start_download($taxon);
if($task == 'check_download_request_status') $func->check_download_request_status($download_key);
//############################################################ end main

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>