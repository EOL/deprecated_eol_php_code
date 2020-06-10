<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/GBIFoccurrenceAPI_DwCA');
$timestart = time_elapsed();
$GLOBALS['ENV_DEBUG'] = true;

print_r($argv);

$params['jenkins_or_cron']   = @$argv[1]; //irrelevant here
$params['info']              = @$argv[2]; //useful here
// print_r($params);

$info = json_decode($params['info'], true);
$range = $info['range'];
$group = $info['group'];
$ctr = $info['ctr'];

echo "\nGroup: $group\n";
print_r($range);
$range_from = $range[0];
$range_to = $range[1];

$func = new GBIFoccurrenceAPI_DwCA();
$func->breakdown_GBIF_DwCA_file($group, $range_from, $range_to); //e.g. $group = 'Animalia'
unlink(CONTENT_RESOURCE_LOCAL_PATH . "map_breakdown_".$group."_".$ctr.".txt");

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";

if(are_all_indicator_files_deletedYN($group)) echo "\nCan now go to next step...\n";
else {
    echo "\nCannot yet go to next step.\n";
    exit(1);
}

function are_all_indicator_files_deletedYN($group)
{
    $filename = CONTENT_RESOURCE_LOCAL_PATH . "map_breakdown_".$group."_"."COUNTER".".txt";
    for($i = 1; $i <= 6; $i++) {
        $fn = str_replace('COUNTER', $i, $filename);
        if(file_exists($fn)) return false;
    }
    return true;
}

?>