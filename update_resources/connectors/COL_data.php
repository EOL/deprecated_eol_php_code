<?php
namespace php_active_record;
/* https://eol-jira.bibalex.org/browse/DATA-1744
Connector for Catalogue of Life hierarchy, data, descriptions
estimated execution time: 5 days 16 hr (eol-archive) - this was before there is low space in eol-archive
                           1 hr 10 min (eol-archive) - this is when addt'l space was added hmmm (Tuesday 2018-08-14 12:20:46 AM).

log in eol-archive:
col	    Thursday 2018-05-10 09:34:05 AM	{"measurement_or_fact.tab":3961387,"media_resource.tab":1150989,"occurrence.tab":2982057,"reference.tab":579322,"taxon.tab":3765285,"vernacular_name.tab":429242}
col_v2	Tuesday 2018-08-14 12:20:46 AM	{"measurement_or_fact.tab":4346654,"media_resource.tab":1162859,"occurrence.tab":3093260,"reference.tab":578811,"taxon.tab":3815897,"vernacular_name.tab":429748}
col	    Wednesday 2018-08-15 05:27:33 AM{"measurement_or_fact.tab":4346654,"media_resource.tab":1162859,"occurrence.tab":3093260,"reference.tab":578811,"taxon.tab":3815897,"vernacular_name.tab":429748}
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");

/* testing...
$uris = Functions::get_eol_defined_uris(false, true);
print_r($uris); exit;
*/

// $GLOBALS['ENV_DEBUG'] = false;
ini_set('memory_limit','15096M'); //15096M
$timestart = time_elapsed();

$resource_id = 'col';
require_library('connectors/CoLDataAPI');
$func = new CoLDataAPI($resource_id);
$func->convert_archive();
unset($func);
Functions::finalize_dwca_resource($resource_id, true, false); //2nd param true means a big big file
                                                              //3rd param is always false bec. the folder will be used by "CoL DH" and "CoL Protists DH"
require_library('connectors/DWCADiagnoseAPI');
$func = new DWCADiagnoseAPI();
if($undefined = $func->check_if_all_parents_have_entries($resource_id, true)) { //2nd param True means write to text file
    $arr['parents without entries total count'] = count($undefined);
    print_r($arr);
}
else echo "\nAll parents have entries OK\n";

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>
