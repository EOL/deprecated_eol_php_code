<?php
namespace php_active_record;
/* NCBI Taxonomy Harvest - https://eol-jira.bibalex.org/browse/TRAM-795
estimated execution time: 20 - 27 mins. with references
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/DWH_NCBI_API');

/* test
$a[11] = '';
$a[13] = '';
$a[15] = '';

$b[11] = '';
$b[24] = '';
$b[26] = '';

$c = ($a + $b);
print_r($c);

exit("\n-end test-\n");
*/

// ini_set('memory_limit','5096M');
$timestart = time_elapsed();
/*
$resource_id = "NCBI_Taxonomy_Harvest";                $with_comnames = true;  //orig
*/
// /*
$resource_id = "NCBI_Taxonomy_Harvest_no_vernaculars"; $with_comnames = false; //requested by Katja, to pinpoint the problem in harvesting.
// */
$func = new DWH_NCBI_API($resource_id, $with_comnames);
// $GLOBALS['ENV_DEBUG'] = true;

// /* un-comment in normal operation
$func->start_tram_795();
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

// vernaculars removed due to harvesting issue with weird chars.
// $undefined = $func->check_if_all_vernaculars_have_entries($resource_id, true); //true means output will write to text file
// if($undefined) echo "\nERROR: There is undefined taxonID(s) in vernacular_name.tab: ".count($undefined)."\n";
// else           echo "\nOK: All taxonID(s) in vernacular_name.tab have entries.\n";

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