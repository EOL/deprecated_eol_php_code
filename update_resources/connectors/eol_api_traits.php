<?php
namespace php_active_record;
/* used for /lib/EolAPI.php */
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();

/*
$str = "Paratype- 1.5 mm ";
$num = filter_var($str, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION); //better sol'n vs above
$num = str_replace(array("-","+"), "", $num);
echo "\n[$str]";
exit("\n[$num]\n");
*/

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