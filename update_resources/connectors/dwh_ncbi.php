<?php
namespace php_active_record;
/* NCBI Taxonomy Harvest - https://eol-jira.bibalex.org/browse/TRAM-795
estimated execution time: 1.4913670666667 hours
                          1.1065693763889 hours with references
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/DWH_NCBI_API');
// ini_set('memory_limit','5096M');
$timestart = time_elapsed();
$resource_id = "NCBI_Taxonomy_Harvest"; //orig
// $resource_id = "1e";
$func = new DWH_NCBI_API($resource_id);
$GLOBALS['ENV_DEBUG'] = true;

/* un-comment in normal operation
$func->start();
Functions::finalize_dwca_resource($resource_id);
*/

// /* utility - takes time for this resource but very helpful to catch if all parents have entries.
require_library('connectors/DWCADiagnoseAPI');
$func = new DWCADiagnoseAPI();

$undefined = $func->check_if_all_parents_have_entries($resource_id, true); //true means output will write to text file
if($undefined) {
    echo "\nThere is undefined parent(s): ".count($undefined)."\n";
}
else echo "\nAll parents have entries.\n";

// $undefined = $func->check_if_all_parents_have_entries($resource_id, true, false, array(), "acceptedNameUsageID"); //true means output will write to text file
// if($undefined) {
//     echo "\nThere is undefined acceptedNameUsageID(s): ".count($undefined)."\n";
// }
// else echo "\nAll acceptedNameUsageID have entries.\n";



// */

/* this will delete the working dir
$dir = CONTENT_RESOURCE_LOCAL_PATH."/".$resource_id;
if(is_dir($dir)) recursive_rmdir($dir);
*/

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>