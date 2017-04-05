<?php
namespace php_active_record;
/* used for /lib/EolAPI.php */
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();

require_library('connectors/EolAPI_Traits');

$resource_id = 'eol';
// /* normal operation ============================================
$func = new EolAPI_Traits($resource_id);
$func->start(); //normal operation
// ================================================================*/

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>