<?php
namespace php_active_record;
/* used for /lib/Eol_v3_API.php */
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
$GLOBALS['ENV_DEBUG'] = false; //orig value should be -> false ... especially in eol-archive server

require_library('connectors/Eol_v3_API');
$resource_id = 'eol';
$func = new Eol_v3_API($resource_id);
$func->generate_stats(); //normal operation

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n elapsed time = " . $elapsed_time_sec/60/60/24 . " days";
echo "\n Done processing.\n";
?>
