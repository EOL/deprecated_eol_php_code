<?php
namespace php_active_record;
/* 
Connector for Catalogue of Life hierarchy, data, descriptions
estimated execution time:
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
// $GLOBALS['ENV_DEBUG'] = false;
// ini_set('memory_limit','7096M');
$timestart = time_elapsed();

$resource_id = 'col_trait_text';
require_library('connectors/COL_traits_textAPI');
$func = new COL_traits_textAPI($resource_id);
$func->convert_archive();

Functions::finalize_dwca_resource($resource_id, false);

$func = new DWCADiagnoseAPI();
if($undefined = $func->check_if_all_parents_have_entries($resource_id, true)) { //2nd param True means write to text file
    $arr['parents without entries'] = $undefined;
    print_r($arr);
}
else echo "\nAll parents have entries OK\n";

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>
