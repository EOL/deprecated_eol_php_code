<?php
namespace php_active_record;
/* NCBI Taxonomy Extract for Dynamic Hierarchy - https://eol-jira.bibalex.org/browse/TRAM-796
estimated execution time: 

NCBI_Taxonomy_Harvest_no_vernaculars	Mon 2020-09-21 06:44:36 AM	{"reference.tab":50881, "taxon.tab":1635339, "time_elapsed":{"sec":1097.65, "min":18.29, "hr":0.3}}
NCBI_Taxonomy_Harvest	                Mon 2020-09-21 07:03:24 AM	{"reference.tab":50881, "taxon.tab":1635339, "vernacular_name.tab":44046, "time_elapsed":{"sec":1078.24, "min":17.97, "hr":0.3}}
NCBI_Taxonomy_Harvest_DH	            Mon 2020-09-21 10:39:33 AM	{"reference.tab":24828, "taxon.tab":172951, "time_elapsed":{"sec":203.53, "min":3.39, "hr":0.06}}

NCBI_Taxonomy_Harvest_no_vernaculars	Wed 2021-01-27 07:34:54 PM	{"reference.tab":50881, "taxon.tab":1635339, "time_elapsed":{"sec":1045.24, "min":17.42, "hr":0.29}}
NCBI_Taxonomy_Harvest	                Wed 2021-01-27 07:53:07 PM	{"reference.tab":50881, "taxon.tab":1635339, "vernacular_name.tab":44046, "time_elapsed":{"sec":1044.42, "min":17.41, "hr":0.29}}
NCBI_Taxonomy_Harvest_DH	            Wed 2021-01-27 07:57:00 PM	{"reference.tab":24828, "taxon.tab":172951, "time_elapsed":{"sec":183.75, "min":3.06, "hr":0.05}}

with new more filters
NCBI_Taxonomy_Harvest_no_vernaculars	Wed 2021-01-27 10:52:11 PM	{"reference.tab":50881, "taxon.tab":1635339, "time_elapsed":{"sec":1069.87, "min":17.83, "hr":0.3}}
NCBI_Taxonomy_Harvest	                Wed 2021-01-27 11:10:58 PM	{"reference.tab":50881, "taxon.tab":1635339, "vernacular_name.tab":44046, "time_elapsed":{"sec":1076.39, "min":17.94, "hr":0.3}}
NCBI_Taxonomy_Harvest_DH	            Wed 2021-01-27 11:14:39 PM	{"reference.tab":23683, "taxon.tab":115794, "time_elapsed":{"sec":169.7, "min":2.83, "hr":0.05}}

NCBI_Taxonomy_Harvest_no_vernaculars	Wed 2022-03-16 04:58:30 AM	{"reference.tab":52183, "taxon.tab":1818407, "time_elapsed":{"sec":1258.7, "min":20.98, "hr":0.35}}
NCBI_Taxonomy_Harvest	                Wed 2022-03-16 05:20:53 AM	{"reference.tab":52183, "taxon.tab":1818407, "vernacular_name.tab":44475, "time_elapsed":{"sec":1283.05, "min":21.38, "hr":0.36}}
NCBI_Taxonomy_Harvest_DH	            Wed 2022-03-16 05:25:07 AM	{"reference.tab":23594, "taxon.tab":133416, "time_elapsed":{"sec":193.3, "min":3.22, "hr":0.05}}
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/DWH_NCBI_API');
// ini_set('memory_limit','5096M');
$timestart = time_elapsed();
$resource_id = "NCBI_Taxonomy_Harvest_DH"; //orig
// $resource_id = "2"; //for testing

$with_comnames = true;  //orig
$with_comnames = false; //requested by Katja, to pinpoint the problem in harvesting.

$func = new DWH_NCBI_API($resource_id, $with_comnames);
// $GLOBALS['ENV_DEBUG'] = true;

// /* un-comment in normal operation
$func->start_tram_796();
Functions::finalize_dwca_resource($resource_id, false, false, $timestart); //won't delete working dir. Will be used for stats below.
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