<?php
namespace php_active_record;
/* DATA-1840: replacement connector for three national checklists
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
// ini_set('memory_limit','8096M');
$timestart = time_elapsed();

$resource_id = 'gbif_classification';
require_library('connectors/NatlChecklistReplacementConnAPI');

$func = new NatlChecklistReplacementConnAPI($resource_id);
// /* main operation
$func->start();
unset($func);
Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
// */

/* utility ========================== from copied template
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