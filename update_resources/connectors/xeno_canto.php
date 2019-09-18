<?php
namespace php_active_record;
/* https://eol-jira.bibalex.org/browse/DATA-1816 
execution time: Took 5 hr 11 min (first time, un-cached)

xeno_canto	Tuesday 2019-09-17 05:12:49 PM	{"agent.tab":3482,"media_resource.tab":116748,"taxon.tab":10939,"vernacular_name.tab":10939}

*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
// $GLOBALS['ENV_DEBUG'] = false;
// ini_set('memory_limit','8096M');
$timestart = time_elapsed();
$resource_id = 'xeno_canto';
require_library('connectors/XenoCantoAPI');

$func = new XenoCantoAPI($resource_id);
$func->start();
unset($func);
Functions::finalize_dwca_resource($resource_id);

/* utility ========================== works OK
require_library('connectors/DWCADiagnoseAPI');
$func = new DWCADiagnoseAPI();

$undefined = $func->check_if_all_parents_have_entries($resource_id, true); //true means output will write to text file
if($undefined) echo "\nERROR: There is undefined parent(s): ".count($undefined)."\n";
else           echo "\nOK: All parents in taxon.tab have entries.\n";

$undefined = $func->check_if_all_parents_have_entries($resource_id, true, false, array(), "acceptedNameUsageID"); //true means output will write to text file
if($undefined) echo "\nERROR: There is undefined acceptedNameUsageID(s): ".count($undefined)."\n";
else           echo "\nOK: All acceptedNameUsageID have entries.\n";

===================================== */
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>