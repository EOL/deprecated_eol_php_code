<?php
namespace php_active_record;
/* TRAM-804 
itis_2019-02-25	Wednesday 2019-03-27 08:02:55 AM	{"taxon.tab":798950,"vernacular_name.tab":112697}

itis_2019-03-31	Sunday 2019-04-21 10:10:02 PM	    {"taxon.tab":799768,"vernacular_name.tab":112703} Mac Mini
itis_2019-03-31	Sunday 2019-04-21 10:26:39 PM	    {"taxon.tab":799768,"vernacular_name.tab":112703} eol-archive

itis_2019-08-28	Friday 2019-09-27 10:32:35 AM	{"taxon.tab":804287,"vernacular_name.tab":113136} Mac Mini
itis_2019-08-28	Friday 2019-09-27 10:47:46 AM	{"taxon.tab":804287,"vernacular_name.tab":113136} eol-archive
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/DWH_ITIS_API');
$timestart = time_elapsed();

/*
Note: Database download files are currently from the 25-Feb-2019 data load.
*/

// $dwca_file = "http://localhost/cp/ITIS_DWH/2019-02-25/itisMySQLTables.tar.gz";
$dwca_file = "https://www.itis.gov/downloads/itisMySQLTables.tar.gz";
$resource_id = "itis_2019-02-25";
$resource_id = "itis_2019-03-31";
$resource_id = "itis_2019-08-28"; //run in Sep 27, 2019

// /* main operation
$func = new DWH_ITIS_API($resource_id, $dwca_file);
$func->start(); //main operation
Functions::finalize_dwca_resource($resource_id, false, true);
// */

run_diagnostics($resource_id);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";

Function run_diagnostics($resource_id) // utility - takes time for this resource but very helpful to catch if all parents have entries.
{
    require_library('connectors/DWCADiagnoseAPI');
    $func = new DWCADiagnoseAPI();
    // $func->check_unique_ids($resource_id); //takes time

    $undefined = $func->check_if_all_parents_have_entries($resource_id, true); //true means output will write to text file
    if($undefined) echo "\nERROR: There is undefined parent(s): ".count($undefined)."\n";
    else           echo "\nOK: All parents in taxon.tab have entries.\n";

    $undefined = $func->check_if_all_parents_have_entries($resource_id, true, false, array(), "acceptedNameUsageID"); //true means output will write to text file
    if($undefined) echo "\nERROR: There is undefined acceptedNameUsageID(s): ".count($undefined)."\n";
    else           echo "\nOK: All acceptedNameUsageID have entries.\n";
}
?>
