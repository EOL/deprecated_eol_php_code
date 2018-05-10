<?php
namespace php_active_record;
/* https://eol-jira.bibalex.org/browse/DATA-1744
Connector for Catalogue of Life hierarchy, data, descriptions
estimated execution time:

col	Thursday 2018-05-10 09:34:05 AM	{"measurement_or_fact.tab":3961387,"media_resource.tab":1150989,"occurrence.tab":2982057,"reference.tab":579322,"taxon.tab":3765285,"vernacular_name.tab":429242}

*/

include_once(dirname(__FILE__) . "/../../config/environment.php");

/* testing...
$uris = Functions::get_eol_defined_uris(false, true);
print_r($uris); exit;
*/

// $GLOBALS['ENV_DEBUG'] = false;
ini_set('memory_limit','15096M');
$timestart = time_elapsed();

$resource_id = 'col';
require_library('connectors/COLDataAPI');
$func = new COLDataAPI($resource_id);
$func->convert_archive();
unset($func);
Functions::finalize_dwca_resource($resource_id, true, true); //2nd param true means a big big file

/*
$func = new DWCADiagnoseAPI();
if($undefined = $func->check_if_all_parents_have_entries($resource_id, true)) { //2nd param True means write to text file
    $arr['parents without entries'] = $undefined;
    print_r($arr);
}
else echo "\nAll parents have entries OK\n";
*/

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>
