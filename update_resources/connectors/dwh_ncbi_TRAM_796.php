<?php
namespace php_active_record;
/* NCBI Taxonomy Extract for Dynamic Hierarchy - https://eol-jira.bibalex.org/browse/TRAM-796
estimated execution time: 
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/DWH_NCBI_API');
// ini_set('memory_limit','5096M');
$timestart = time_elapsed();
$resource_id = "NCBI_Taxonomy_Harvest_DH"; //orig
$resource_id = "1"; //for testing
$func = new DWH_NCBI_API($resource_id);
// $GLOBALS['ENV_DEBUG'] = true;

// /* un-comment in normal operation
$func->tram_796_start();
Functions::finalize_dwca_resource($resource_id);
// */

// /* utility - takes time for this resource but very helpful to catch if all parents have entries.
require_library('connectors/DWCADiagnoseAPI');
$func = new DWCADiagnoseAPI();

$undefined = $func->check_if_all_parents_have_entries($resource_id, true); //true means output will write to text file
if($undefined) echo "\nERROR: There is undefined parent(s): ".count($undefined)."\n";
else           echo "\nOK: All parents in taxon.tab have entries.\n";

$undefined = $func->check_if_all_parents_have_entries($resource_id, true, false, array(), "acceptedNameUsageID"); //true means output will write to text file
if($undefined) echo "\nERROR: There is undefined acceptedNameUsageID(s): ".count($undefined)."\n";
else           echo "\nOK: All acceptedNameUsageID have entries.\n";

$undefined = $func->check_if_all_vernaculars_have_entries($resource_id, true); //true means output will write to text file
if($undefined) echo "\nERROR: There is undefined taxonID(s) in vernacular_name.tab: ".count($undefined)."\n";
else           echo "\nOK: All taxonID(s) in vernacular_name.tab have entries.\n";
// */

/* this will delete the working dir --- never used here...
$dir = CONTENT_RESOURCE_LOCAL_PATH."/".$resource_id;
if(is_dir($dir)) recursive_rmdir($dir);
*/

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>