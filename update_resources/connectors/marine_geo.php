<?php
namespace php_active_record;
/*
https://docs.google.com/document/d/1a0KFu_tKTztDdBgTuPeaymZvHaFzOgTHSycwVB1CA7s/edit?userstoinvite=eagbayaninf@gmail.com&ts=5d1b6435
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
/*
$GLOBALS['ENV_DEBUG'] = false;
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING); //report all errors except notice and warning
*/
ini_set('memory_limit','7096M');
require_library('connectors/MarineGEOAPI');
$timestart = time_elapsed();
$resource_id = 'MarineGEO';

$func = new MarineGEOAPI($resource_id);

$func->start();
// Functions::finalize_dwca_resource($resource_id, false, false); //3rd true means delete working dir

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec . " seconds";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>