<?php
namespace php_active_record;
/* TRAM-804 
itis_2019-02-25	Wednesday 2019-03-27 08:02:55 AM	{"taxon.tab":798950,"vernacular_name.tab":112697}

itis_2019-03-31	Sunday 2019-04-21 10:10:02 PM	    {"taxon.tab":799768,"vernacular_name.tab":112703} Mac Mini
itis_2019-03-31	Sunday 2019-04-21 10:26:39 PM	    {"taxon.tab":799768,"vernacular_name.tab":112703} eol-archive

itis_2019-08-28	Friday 2019-09-27 10:32:35 AM	{"taxon.tab":804287,"vernacular_name.tab":113136} Mac Mini
itis_2019-08-28	Friday 2019-09-27 10:47:46 AM	{"taxon.tab":804287,"vernacular_name.tab":113136} eol-archive
itis_2019-08-28	Monday 2019-09-30 06:17:33 AM	{"taxon.tab":802601,"vernacular_name.tab":113136} eol-archive
itis_2019-08-28	Monday 2019-09-30 10:24:36 AM	{"taxon.tab":802601,"vernacular_name.tab":113136} Mac Mini

Mac Mini no removal yet:
itis_2020-07-28	Mon 2020-08-31 03:30:52 AM	{"taxon.tab":836555, "vernacular_name.tab":114061, "time_elapsed":{"sec":654.38, "min":10.91, "hr":0.18}}
with removal
itis_2020-07-28	Mon 2020-08-31 09:35:12 AM	{"taxon.tab":104015, "vernacular_name.tab":11301, "time_elapsed":{"sec":327.24, "min":5.45, "hr":0.09}}

eol-archive:
itis_2020-07-28	Tue 2020-09-01 04:17:42 AM	{"taxon.tab":104015, "vernacular_name.tab":11301, "time_elapsed":{"sec":211.03, "min":3.52, "hr":0.06}}
itis_2020-07-28	Tue 2020-09-01 04:18:35 AM	{"taxon.tab":104015, "vernacular_name.tab":11301, "time_elapsed":false}

itis_2020-07-28	Tue 2020-09-01 11:01:33 PM	{"taxon.tab":104014, "vernacular_name.tab":11301, "time_elapsed":{"sec":169.89, "min":2.83, "hr":0.05}}
itis_2020-07-28	Tue 2020-09-01 11:02:23 PM	{"taxon.tab":104014, "vernacular_name.tab":11301, "time_elapsed":false}


Reminders: what is in Jenkins eol-archive. Run one after the other.
php5.6 dwh_itis.php jenkins
php5.6 synonyms_handling.php jenkins itis_2019-08-28
php5.6 synonyms_handling.php jenkins itis_2020-07-28 #TRAM-987
php5.6 synonyms_handling.php jenkins itis_2020-12-01 #TRAM-987
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/DWH_ITIS_API');
$timestart = time_elapsed();

/*
Note: Database download files are currently from the 25-Feb-2019 data load.
Note: Database download files are currently from the 02-Dec-2020 data load.

The dates here made as folder names are the dates for dumps/downloads from source partner.
Found here:
https://www.itis.gov/downloads/
e.g. 2020-12-02         {local run in Mac Mini}
e.g. itis_2020-12-02    {jenkins run in eol-archive}
*/

if(Functions::is_production()) $dwca_file = "https://www.itis.gov/downloads/itisMySQLTables.tar.gz";
else {
    $dwca_file = "http://localhost/cp/ITIS_DWH/2019-02-25/itisMySQLTables.tar.gz";
    $dwca_file = "http://localhost/cp/ITIS_DWH/2020-07-28/itisMySQLTables.tar.gz";
    $dwca_file = "http://localhost/cp/ITIS_DWH/2020-12-01/itisMySQLTables.tar.gz";
    
}
$resource_id = "itis_2019-02-25";
$resource_id = "itis_2019-03-31";
$resource_id = "itis_2019-08-28"; //run in Sep 27, 2019
$resource_id = "itis_2020-07-28"; //TRAM-987
$resource_id = "itis_2020-12-01"; //TRAM-987

// /* main operation
$func = new DWH_ITIS_API($resource_id, $dwca_file);
$func->start(); //main operation
Functions::finalize_dwca_resource($resource_id, false, false, $timestart); //3rd param should be false so the folder in /resources/ won't be deleted.
                                                                           //it will be used in run_diagnostics()
// */

// /* newly refactored routine
require_library('connectors/DWCADiagnoseAPI');
$func = new DWCADiagnoseAPI();
$func->run_diagnostics($resource_id);
// */

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";

?>
