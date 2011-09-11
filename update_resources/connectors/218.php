<?php
namespace php_active_record;
/*
There is a pending task here. It was finally agreed upon that we will harvest the thumbnail images only from Tropicos.
http://jira.eol.org/browse/DATA-839
*/
exit;

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

$timestart = microtime(1);
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/TropicosAPI');
$GLOBALS['ENV_DEBUG'] = false;

$resource_id = 218;
TropicosAPI::start_process($resource_id);
//TropicosAPI::get_all_taxa($resource_id);

$elapsed_time_sec = microtime(1)-$timestart;
echo "\n";
echo "elapsed time = $elapsed_time_sec seconds             \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes  \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo date('Y-m-d h:i:s a', time())."\n";
exit("\n\n Done processing.");

/*
problem chars
\xe2\x80\x93
\xe2\x80\x93
*/
?>