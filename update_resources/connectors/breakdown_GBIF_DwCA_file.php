<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/GBIFoccurrenceAPI_DwCA');
$timestart = time_elapsed();
$GLOBALS['ENV_DEBUG'] = true;


// print_r($argv);
$params['jenkins_or_cron']      = @$argv[1]; //irrelevant here
$params['group']                = @$argv[2]; //useful here
$params['range_from']           = @$argv[3]; //useful
$params['range_from']           = @$argv[4]; //useful
print_r($params);


$func = new GBIFoccurrenceAPI_DwCA();
// $func->breakdown_GBIF_DwCA_file($group = false, $range_from = false, $range_to = false); //e.g. $group = 'Animalia'


$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>