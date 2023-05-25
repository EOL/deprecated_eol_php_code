<?php
namespace php_active_record;
/* 

*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
ini_set('memory_limit','7096M');
$GLOBALS['ENV_DEBUG'] = false;
$timestart = time_elapsed();

$resource_id = 'polytraits_new'; //this replaced the Polytraits.tar.gz resource from polytraits.php


// /* using Dumps
require_library('connectors/PolytraitsNewAPI');
$func = new PolytraitsNewAPI($resource_id);
$func->start();
unset($func);
exit("\n-stop muna-\n");
Functions::finalize_dwca_resource($resource_id, false, false, false);
// */

require_library('connectors/DWCADiagnoseAPI');
$func = new DWCADiagnoseAPI();
if($undefined = $func->check_if_all_parents_have_entries($resource_id, true)) { //2nd param True means write to text file
    $arr['parents without entries'] = $undefined;
    print_r($arr);
}
else echo "\nAll parents have entries OK\n";

recursive_rmdir(CONTENT_RESOURCE_LOCAL_PATH.$resource_id);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>
