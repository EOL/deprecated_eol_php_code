<?php
namespace php_active_record;
/* connector for Tropicos
estimated execution time:

last run: 2011 06 14
taxon               = 80680
dwc:ScientificName  = 80680
synonym             = 130746
dataObjects         = 370963
texts               = 71545
images              = 299418

Note: Tropicos web service goes down 7-8am Eastern
*/

// date_default_timezone_set('US/Eastern');
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
require_library('connectors/TropicosAPI');
$resource_id = 218;

if(isset($argv[1])) $call_multiple_instance = false;
else $call_multiple_instance = true;

TropicosAPI::start_process($resource_id, $call_multiple_instance);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = $elapsed_time_sec seconds             \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes  \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo date('Y-m-d h:i:s a', time()) . "\n";
exit("\n\n Done processing.");

/*
problem chars
\xe2\x80\x93
\xe2\x80\x93
*/
?>