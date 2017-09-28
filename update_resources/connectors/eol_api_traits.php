<?php
namespace php_active_record;
/* used for /lib/EolAPI.php */
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
require_library('connectors/EolAPI_Traits');

/*
$strs = array();
$strs[] = "1,100 cm";
$strs[] = "1.39 cm female";
$strs[] = "Paratype- 1.5 mm";
$strs[] = "2.5mm - 10mm";
$strs[] = "51-53 mm";
$strs[] = "< 2.5mm";
$strs[] = ">=100 mm";
$strs[] = "3.5+17 mm";
$func = new EolAPI_Traits($resource_id);
foreach($strs as $str)
{
    $arr = $func->get_correct_Value_from_string($str);
    print_r($arr);
}
exit;
*/

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