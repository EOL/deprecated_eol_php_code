<?php
namespace php_active_record;
$GLOBALS['ENV_DEBUG'] = true;
include_once(dirname(__FILE__) . "/../../config/environment.php");

$time_start = time_elapsed();
require_library('EOLStats');
$stats = new EOLStats();
$stats->save_eol_stats(); //  hours
       
$time_elapsed_sec = time_elapsed() - $time_start;
echo "\n elapsed time = $time_elapsed_sec seconds             ";
echo "\n elapsed time = " . $time_elapsed_sec/60 . " minutes  ";
echo "\n elapsed time = " . $time_elapsed_sec/60/60 . " hours ";
?>