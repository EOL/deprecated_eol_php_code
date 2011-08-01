<?php
/* connector for Tropicos
estimated execution time:

Note: Tropicos web service goes down 7-8am Eastern
*/

$timestart = microtime(1);
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/TropicosAPI');
$GLOBALS['ENV_DEBUG'] = false;

$resource_id = 218;

TropicosAPI::start_process($resource_id);

//TropicosAPI::get_all_taxa($resource_id);

$elapsed_time_sec = microtime(1)-$timestart;
echo "\n";
echo "elapsed time = $elapsed_time_sec sec              \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " min   \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hr \n";
echo date('Y-m-d h:i:s a', time())."\n";
exit("\n\n Done processing.");

/*
problem chars
\xe2\x80\x93
\xe2\x80\x93
*/
?>