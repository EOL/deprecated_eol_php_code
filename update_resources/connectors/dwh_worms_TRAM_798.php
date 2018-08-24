<?php
namespace php_active_record;
/* WoRMS Extract for Dynamic Hierarchy - https://eol-jira.bibalex.org/browse/TRAM-798
estimated execution time: Took 1 min 27 sec (eol-archive)
WoRMS_DH	Wednesday 2018-08-22 06:55:05 PM	{"taxon.tab":63297} - eol-archive
WoRMS_DH	Thursday 2018-08-23 08:27:28 AM	    {"taxon.tab":53272} - eol-archive
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/DWH_WoRMS_API');
// ini_set('memory_limit','15096M');
// ini_set('memory_limit','6096M');
// $GLOBALS['ENV_DEBUG'] = true;
$timestart = time_elapsed();

//############################################################ start WoRMS
/*
$resource_id = "Catalogue_of_Life_DH"; //orig
$func = new DWH_WoRMS_API($resource_id);
$func->start_tram_797();
$func = null;
Functions::finalize_dwca_resource($resource_id, true);
// utility - takes time for this resource but very helpful to catch if all parents have entries.
require_library('connectors/DWCADiagnoseAPI');
$func = new DWCADiagnoseAPI();

$undefined = $func->check_if_all_parents_have_entries($resource_id, true); //true means output will write to text file
if($undefined) echo "\nERROR: There is undefined parent(s): ".count($undefined)."\n";
else           echo "\nOK: All parents in taxon.tab have entries.\n";

$undefined = $func->check_if_all_parents_have_entries($resource_id, true, false, array(), "acceptedNameUsageID"); //true means output will write to text file
if($undefined) echo "\nERROR: There is undefined acceptedNameUsageID(s): ".count($undefined)."\n";
else           echo "\nOK: All acceptedNameUsageID have entries.\n";
// exit("\n-End for now-\n");
*/
//############################################################ end WoRMS
//############################################################ start WoRMS
$resource_id = "WoRMS_DH"; //orig
// $resource_id = 1;
$func = new DWH_WoRMS_API($resource_id);
$func->start_WoRMS();
$func = null;
Functions::finalize_dwca_resource($resource_id, false);
// /* utility - takes time for this resource but very helpful to catch if all parents have entries.
require_library('connectors/DWCADiagnoseAPI');
$func = new DWCADiagnoseAPI();

$undefined = $func->check_if_all_parents_have_entries($resource_id, true); //true means output will write to text file
if($undefined) echo "\nERROR: There is undefined parent(s): ".count($undefined)."\n";
else           echo "\nOK: All parents in taxon.tab have entries.\n";

$undefined = $func->check_if_all_parents_have_entries($resource_id, true, false, array(), "acceptedNameUsageID"); //true means output will write to text file
if($undefined) echo "\nERROR: There is undefined acceptedNameUsageID(s): ".count($undefined)."\n";
else           echo "\nOK: All acceptedNameUsageID have entries.\n";
// */
//############################################################ end WoRMS

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>