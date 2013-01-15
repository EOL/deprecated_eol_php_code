<?php
namespace php_active_record;
/* connector for Tropicos
estimated execution time:
Note: Tropicos web service goes down 7-8am Eastern
*/

// date_default_timezone_set('US/Eastern');
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
require_library('connectors/TropicosAPI');
$resource_id = 218;

$folder = DOC_ROOT . "update_resources/connectors/files/Tropicos";
if(!file_exists($folder)) mkdir($folder , 0777);

$tropicos = new TropicosAPI();
$tropicos->initialize_text_files();
//Functions::kill_running_connectors($resource_id);
$tropicos->start_process($resource_id, false);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = $elapsed_time_sec seconds             \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes  \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo date('Y-m-d h:i:s a', time()) . "\n";
echo "\n\n Done processing.";

/*
problem chars
\xe2\x80\x93
\xe2\x80\x93
*/
?>