<?php
namespace php_active_record;
/*
jamstec	Friday 2019-08-30 10:46:17 AM	{"media_resource.tab":498,"taxon.tab":1220}
jamstec	Saturday 2019-09-14 05:38:08 AM	{"media_resource.tab":498,"taxon.tab":1220}
jamstec	Monday 2019-09-30 12:34:16 PM	{"media_resource.tab":498,"taxon.tab":1220}
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
/*
$GLOBALS['ENV_DEBUG'] = false;
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING); //report all errors except notice and warning
*/
ini_set('memory_limit','7096M');
require_library('connectors/JamstecAPI');
$timestart = time_elapsed();
$resource_id = 'jamstec';

// $a = array('a','b','c');
// $last = array_pop($a);
// exit("\n[$last]\n");

$func = new JamstecAPI($resource_id);
$func->start();
Functions::finalize_dwca_resource($resource_id, false, false); //3rd true means delete working dir

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec . " seconds";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>