<?php
namespace php_active_record;
/* used for /lib/Eol_v3_API.php */
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
$GLOBALS['ENV_DEBUG'] = false; //orig value should be -> false ... especially in eol-archive server

require_library('connectors/Eol_v3_API');
$resource_id = 'eol';
$func = new Eol_v3_API($resource_id);

// print_r($argv);
$params['jenkins_or_cron']   = @$argv[1]; //irrelevant here
$params['json']              = @$argv[2]; //useful here
$arr = json_decode($params['json'], true);
print_r($arr);
/*Array(
    [range] => Array(
            [0] => 1271125
            [1] => 1906687
        )
    [ctr] => 3
)
*/
$func->generate_stats($arr); //normal operation

/* START continue lifeline of Jenkins event --------------------------------------------- */
require_library('connectors/MultipleConnJenkinsAPI');
$funcj = new MultipleConnJenkinsAPI();
$filename = CONTENT_RESOURCE_LOCAL_PATH."part_EOL_stats_COUNTER.txt";
if($funcj->check_indicator_files_if_ready_2finalize_YN($filename, $arr['divisor'])) {
    echo "\nfinalizing now...\n";
    $arr['range'] = array(1, $arr['total_count']);
    $arr['ctr'] = 0;
    $func->generate_stats($arr); //finalize report
}
else echo "\ncannot finalize yet\n";
/* END continue lifeline of Jenkins event ----------------------------------------------- */


$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n elapsed time = " . $elapsed_time_sec/60/60/24 . " days";
echo "\n Done processing.\n";
?>
