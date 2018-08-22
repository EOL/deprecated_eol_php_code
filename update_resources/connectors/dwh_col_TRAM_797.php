<?php
namespace php_active_record;
/* Catalogue of Life Extracts for Dynamic Hierarchy - https://eol-jira.bibalex.org/browse/TRAM-797
estimated execution time: 

eol-archive using the old col.tar.gz
Catalogue_of_Life_DH    Wednesday 2018-08-15 04:04:35 AM{"taxon.tab":3620207}
Catalogue_of_Life_DH	Monday 2018-08-20 08:47:19 AM	{"taxon.tab":3676813}   //after a re-harvest of DATA-1744
Catalogue_of_Life_DH	Monday 2018-08-20 09:39:23 AM	{"taxon.tab":3676702}


Catalogue_of_Life_Protists_DH	Wednesday 2018-08-15 04:10:21 AM	{"taxon.tab":20245}
Catalogue_of_Life_Protists_DH	Monday 2018-08-20 08:53:24 AM	    {"taxon.tab":8}     //after a re-harvest of DATA-1744
Catalogue_of_Life_Protists_DH	Monday 2018-08-20 09:45:36 AM	    {"taxon.tab":8}

eol-archive without re-harvest of DATA-1744:
Catalogue_of_Life_DH	        Tuesday 2018-08-21 01:04:27 PM	{"taxon.tab":3620094}
Catalogue_of_Life_Protists_DH	Tuesday 2018-08-21 01:10:36 PM	{"taxon.tab":20220}

local Mac Mini: without re-harvest of DATA-1744:
Catalogue_of_Life_DH	        Tuesday 2018-08-21 11:51:45 AM	{"taxon.tab":3620094}
Catalogue_of_Life_Protists_DH	Tuesday 2018-08-21 12:07:15 PM	{"taxon.tab":20220}
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/DWH_CoL_API');
ini_set('memory_limit','15096M');
// ini_set('memory_limit','6096M');
// $GLOBALS['ENV_DEBUG'] = true;
$timestart = time_elapsed();

//############################################################ start main CoL DH
// /*
$resource_id = "Catalogue_of_Life_DH"; //orig
$func = new DWH_CoL_API($resource_id);
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
// */
//############################################################ end main CoL DH
//############################################################ start CoL Protists
$resource_id = "Catalogue_of_Life_Protists_DH"; //orig
$func = new DWH_CoL_API($resource_id);
$func->start_ColProtists();
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