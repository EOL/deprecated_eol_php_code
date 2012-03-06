<?php
namespace php_active_record;
/* This connector scrapes the site and assembles BOLDS' higher-level taxa list (hl_master_list.txt)
   estimated execution time: 9 days */

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
require_library('connectors/BoldsAPIpre');

$folder = DOC_ROOT . "update_resources/connectors/files/BOLD";
if(!file_exists($folder)) mkdir($folder , 0777);

if(isset($argv[1])) $call_multiple_instance = false;
else $call_multiple_instance = true;

$resource_id = '81pre';
$bolds = new BoldsAPIpre();
$bolds->initialize_text_files();
$bolds->start_process($resource_id, false);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes  \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
exit("\n\n Done processing.");
?>