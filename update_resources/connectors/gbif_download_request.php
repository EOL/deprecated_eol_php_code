<?php
namespace php_active_record;
/* This is a library that handles GBIF download requests using their API */
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/GBIFdownloadRequestAPI');
$timestart = time_elapsed();
/*

This will overwrite any current download request. Run this once ONLY every harvest per taxon group.
php update_resources/connectors/gbif_download_request.php _ '{"task":"send_download_request", "taxon":"Animalia"}'
php update_resources/connectors/gbif_download_request.php _ '{"task":"send_download_request", "taxon":"Plantae"}'
php update_resources/connectors/gbif_download_request.php _ '{"task":"send_download_request", "taxon":"Other7Groups"}'
php update_resources/connectors/gbif_download_request.php _ '{"task":"send_download_request", "taxon":"Gadus ogac"}'

This will generate the .sh file if download is ready. The .sh file is run in Jenkins eol-archive.
php update_resources/connectors/gbif_download_request.php _ '{"task":"start_download", "taxon":"Animalia"}'
php update_resources/connectors/gbif_download_request.php _ '{"task":"start_download", "taxon":"Plantae"}'
php update_resources/connectors/gbif_download_request.php _ '{"task":"start_download", "taxon":"Other7Groups"}'
php update_resources/connectors/gbif_download_request.php _ '{"task":"start_download", "taxon":"Gadus ogac"}'
php update_resources/connectors/gbif_download_request.php _ '{"task":"start_download", "taxon":"Gadus morhua"}'

Sample of .sh files:
#!/bin/sh
curl -L -o 'Animalia_DwCA.zip' -C - http://api.gbif.org/v1/occurrence/download/request/0081142-200221144449610.zip                                    

#!/bin/sh
curl -L -o 'Plantae_DwCA.zip' -C - http://api.gbif.org/v1/occurrence/download/request/0018041-200613084148143.zip

.sh files are run in Jenkins eol-archive:
bash /var/www/html/eol_php_code/update_resources/connectors/files/GBIF/run_Animalia.sh
bash /var/www/html/eol_php_code/update_resources/connectors/files/GBIF/run_Plantae.sh
bash /var/www/html/eol_php_code/update_resources/connectors/files/GBIF/run_Other7Groups.sh

Animalia
1,181,679,863 - new Jul 9, 2020
1,031,353,123 - old

Plantae
245,670,485 - new Jul 9, 2020
239,862,739 - old

Other7Groups:
44,359,969 - new Jul 9, 2020
44,610,800 - old
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