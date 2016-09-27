<?php
namespace php_active_record;
/* connector for DiscoverLife Maps
estimated execution time: 20 mins.
*/
set_time_limit(0);
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
require_library('connectors/DiscoverLifeAPIv2');
$resource_id = 223;

$folder = DOC_ROOT . "update_resources/connectors/files/DiscoverLife";
if(!file_exists($folder)) mkdir($folder , 0777);

$dl = new DiscoverLifeAPIv2();
$dl->initialize_text_files();
$dl->start_process($resource_id, false);

Functions::set_resource_status_to_harvest_requested($resource_id);
Functions::gzip_resource_xml($resource_id);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "elapsed time = " . $elapsed_time_sec/60/60/24 . " days \n";
echo "\n\n Done processing.";
?>