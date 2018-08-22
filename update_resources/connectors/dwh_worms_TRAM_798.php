<?php
namespace php_active_record;
/* WoRMS Extract for Dynamic Hierarchy - https://eol-jira.bibalex.org/browse/TRAM-798
estimated execution time: 
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/DWH_WoRMS_API');
// ini_set('memory_limit','15096M');
// ini_set('memory_limit','6096M');
// $GLOBALS['ENV_DEBUG'] = true;
$timestart = time_elapsed();

//############################################################ start main CoL DH
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
//############################################################ end main CoL DH
//############################################################ start CoL Protists
$resource_id = "WoRMS_DH"; //orig
$func = new DWH_WoRMS_API($resource_id);
$func->start_WoRMS();
$func = null;
Functions::finalize_dwca_resource($resource_id, true);
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
//############################################################ end CoL Protists

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>