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

itis_2020-12-01	Sun 2020-12-06 09:46:37 PM	{"taxon.tab":104571, "vernacular_name.tab":11368, "time_elapsed":{"sec":402.76, "min":6.71, "hr":0.11}}
itis_2020-12-01	Sun 2020-12-06 09:52:34 PM	{"taxon.tab":104571, "vernacular_name.tab":11368, "time_elapsed":false}

eol-archive:
itis_2020-07-28	Tue 2020-09-01 04:17:42 AM	{"taxon.tab":104015, "vernacular_name.tab":11301, "time_elapsed":{"sec":211.03, "min":3.52, "hr":0.06}}
itis_2020-07-28	Tue 2020-09-01 04:18:35 AM	{"taxon.tab":104015, "vernacular_name.tab":11301, "time_elapsed":false}

itis_2020-07-28	Tue 2020-09-01 11:01:33 PM	{"taxon.tab":104014, "vernacular_name.tab":11301, "time_elapsed":{"sec":169.89, "min":2.83, "hr":0.05}}
itis_2020-07-28	Tue 2020-09-01 11:02:23 PM	{"taxon.tab":104014, "vernacular_name.tab":11301, "time_elapsed":false}

itis_2020-12-01	Sun 2020-12-06 09:40:54 PM	{"taxon.tab":104571, "vernacular_name.tab":11368, "time_elapsed":{"sec":161.96, "min":2.7, "hr":0.04}}
itis_2020-12-01	Sun 2020-12-06 09:41:44 PM	{"taxon.tab":104571, "vernacular_name.tab":11368, "time_elapsed":false}

itis_2020-12-01	Wed 2021-01-27 06:27:52 PM	{"taxon.tab":110126, "vernacular_name.tab":15586, "time_elapsed":{"sec":176.86, "min":2.95, "hr":0.05}}
itis_2020-12-01	Wed 2021-01-27 06:28:44 PM	{"taxon.tab":110126, "vernacular_name.tab":15586, "time_elapsed":false}

below acceptable nos. from Mac Mini:
itis_2022-02-28_all_nodes	Thu 2022-03-17 12:41:18 AM	{"taxon.tab":885381, "vernacular_name.tab":120365, "time_elapsed":{"sec":618.69, "min":10.31, "hr":0.17}}
itis_2022-02-28_all_nodes	Thu 2022-03-17 12:54:13 AM	{"taxon.tab":883294, "vernacular_name.tab":120365, "time_elapsed":false}
from eol-archive:
itis_2022-02-28_all_nodes	Thu 2022-03-17 01:23:39 AM	{"taxon.tab":885381, "vernacular_name.tab":120365, "time_elapsed":{"sec":457.63, "min":7.63, "hr":0.13}}
itis_2022-02-28_all_nodes	Thu 2022-03-17 01:30:32 AM	{"taxon.tab":883294, "vernacular_name.tab":120365, "time_elapsed":false}

Reminders: what is in Jenkins eol-archive. Run one after the other, these 2 scripts:
(1)
php5.6 dwh_itis.php jenkins '{"allNodesYN":"0", "resource_id":"itis_2020-07-28"}'
php5.6 dwh_itis.php jenkins '{"allNodesYN":"0", "resource_id":"itis_2020-12-01"}'
php5.6 dwh_itis.php jenkins '{"allNodesYN":"1", "resource_id":"itis_2022-02-28"}'

php update_resources/connectors/dwh_itis.php _ '{"allNodesYN":"0", "resource_id":"itis_2020-07-28"}'
php update_resources/connectors/dwh_itis.php _ '{"allNodesYN":"0", "resource_id":"itis_2020-12-01"}'
php update_resources/connectors/dwh_itis.php _ '{"allNodesYN":"1", "resource_id":"itis_2022-02-28"}'

(2) where date changes
php5.6 synonyms_handling.php jenkins itis_2019-08-28
php5.6 synonyms_handling.php jenkins itis_2020-07-28                #TRAM-987
php5.6 synonyms_handling.php jenkins itis_2020-12-01                #TRAM-987
php5.6 synonyms_handling.php jenkins itis_2022-02-28_all_nodes      #TRAM-996 DH21 synonyms
       synonyms_handling.php _ itis_2022-02-28_all_nodes

*/
/*
Note: Database download files are currently from the 25-Feb-2019 data load.
Note: Database download files are currently from the 02-Dec-2020 data load.

The dates here made as folder names are the dates for dumps/downloads from source partner.
Found here:
https://www.itis.gov/downloads/
e.g. 2020-12-02         {local run in Mac Mini}
e.g. itis_2020-12-02    {jenkins run in eol-archive}

IMPORTANT: Will also need to update 
- INBioAPI.php for the new download date
- synonyms_handling.php
- SynonymsHandlingAPI.php
- DwCA_Utility.php
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/DWH_ITIS_API');
$timestart = time_elapsed();

// print_r($argv);
$params['jenkins_or_cron'] = @$argv[1]; //not needed here
$param                     = json_decode(@$argv[2], true);
$resource_id = $param['resource_id'];
$allNodesYN = $param['allNodesYN'];

if(Functions::is_production()) $dwca_file = "https://www.itis.gov/downloads/itisMySQLTables.tar.gz";
else { // to be manually filled on-demand
    $dwca_file = "http://localhost/cp/ITIS_DWH/2019-02-25/itisMySQLTables.tar.gz";
    $dwca_file = "http://localhost/cp/ITIS_DWH/2020-07-28/itisMySQLTables.tar.gz";
    $dwca_file = "http://localhost/cp/ITIS_DWH/2020-12-01/itisMySQLTables.tar.gz";
    $dwca_file = "http://localhost/cp/ITIS_DWH/2022-02-28/itisMySQLTables.tar.gz";
}
/* old implementation
$resource_id = "itis_2019-02-25";
$resource_id = "itis_2019-03-31";
$resource_id = "itis_2019-08-28"; //run in Sep 27, 2019
$resource_id = "itis_2020-07-28"; //TRAM-987
$resource_id = "itis_2020-12-01"; //TRAM-987
*/

// /* provision Mar 17, 2022: an option to run all existing nodes and those specific nodes listed from: TRAM-987
if($allNodesYN) $resource_id .= "_all_nodes";
// */

// /* main operation
$func = new DWH_ITIS_API($resource_id, $dwca_file, $allNodesYN);
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
