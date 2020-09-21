<?php
namespace php_active_record;
/* NCBI Taxonomy Harvest - https://eol-jira.bibalex.org/browse/TRAM-795
estimated execution time: 20 - 27 mins. with references

php update_resources/connectors/dwh_ncbi_TRAM_795.php _ '{"with_Vernaculars": 1}'
php update_resources/connectors/dwh_ncbi_TRAM_795.php _ '{"with_Vernaculars": 0}'

*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/DWH_NCBI_API');
// ini_set('memory_limit','5096M');
$timestart = time_elapsed();

$params['jenkins_or_cron'] = @$argv[1]; //not needed here
$param                     = json_decode(@$argv[2], true);
$with_Vernaculars = $param['with_Vernaculars'];
if($with_Vernaculars) {
    $resource_id = "NCBI_Taxonomy_Harvest";
    $with_comnames = true;  //orig
}
else {
    $resource_id = "NCBI_Taxonomy_Harvest_no_vernaculars";
    $with_comnames = false; //requested by Katja, to pinpoint the problem in harvesting.
}
$func = new DWH_NCBI_API($resource_id, $with_comnames);
// $GLOBALS['ENV_DEBUG'] = true;
// /* un-comment in normal operation
$func->start_tram_795();
Functions::finalize_dwca_resource($resource_id, false, false, $timestart); //won't delete working dir, will be used in dwh_ncbi_TRAM_796.php
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

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>