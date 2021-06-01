<?php
namespace php_active_record;
/* DATA-1840: replacement connector for three national checklists
    http://api.gbif.org/v1/occurrence/download/request/0027457-190918142434337.zip          Country: Bahrain (BH)
    http://api.gbif.org/v1/occurrence/download/request/0027458-190918142434337.zip          Country: Anguilla (AI)
    http://api.gbif.org/v1/occurrence/download/request/0027503-190918142434337.zip          Country: Aruba (AW)
    e.g.
    php update_resources/connectors/natl_checlist_replacement_conn.php _ Bahrain
    
    wget -q http://api.gbif.org/v1/occurrence/download/request/0027457-190918142434337.zip -O /extra/other_files/GBIF_DwCA/Bahrain_0027457-190918142434337.zip
    wget -q http://api.gbif.org/v1/occurrence/download/request/0027458-190918142434337.zip -O /extra/other_files/GBIF_DwCA/Anguilla_0027458-190918142434337.zip
    wget -q http://api.gbif.org/v1/occurrence/download/request/0027503-190918142434337.zip -O /extra/other_files/GBIF_DwCA/Aruba_0027503-190918142434337.zip

Request from Jen: Sep 9, 2020
Mozambique
https://www.gbif.org/occurrence/search?country=MZ
https://www.geonames.org/1036973

c_BH	Wednesday 2019-10-23 09:13:04 PM	{"measurement_or_fact_specific.tab":2274, "occurrence_specific.tab":1137, "taxon.tab":1137,"time_elapsed":{"sec":151.29,"min":2.52,"hr":0.04}}
c_BH	Thursday 2019-10-24 04:24:57 AM	    {"measurement_or_fact_specific.tab":2274, "occurrence_specific.tab":1137, "reference.tab":87, "taxon.tab":1137,"time_elapsed":{"sec":15.91,"min":0.27,"hr":0}}
c_BH	Tue 2021-05-25 10:42:59 AM	        {"measurement_or_fact_specific.tab":2274, "occurrence_specific.tab":1137, "reference.tab":87, "taxon.tab":1137, "time_elapsed":{"sec":15.67, "min":0.26, "hr":0}}
converted column contributor to child records:
c_BH	Tue 2021-05-25 11:49:07 AM	        {"measurement_or_fact_specific.tab":3676, "occurrence_specific.tab":1137, "reference.tab":87, "taxon.tab":1137, "time_elapsed":{"sec":17.11, "min":0.29, "hr":0}}
brought back contributor as column in MoF:
c_BH	Tue 2021-06-01 12:47:22 AM	        {"measurement_or_fact_specific.tab":2274, "occurrence_specific.tab":1137, "reference.tab":87, "taxon.tab":1137, "time_elapsed":{"sec":15.85, "min":0.26, "hr":0}}

c_AI	Wednesday 2019-10-23 09:14:03 PM	{"measurement_or_fact_specific.tab":3744,"occurrence_specific.tab":1872,"taxon.tab":1872,"time_elapsed":{"sec":134.29,"min":2.24,"hr":0.04}}
c_AI	Thursday 2019-10-24 04:25:14 AM	    {"measurement_or_fact_specific.tab":3744,"occurrence_specific.tab":1872,"reference.tab":102,"taxon.tab":1872,"time_elapsed":{"sec":35.02,"min":0.58,"hr":0.01}}
c_AI	Tue 2021-05-25 10:55:37 AM	        {"measurement_or_fact_specific.tab":3744, "occurrence_specific.tab":1872, "reference.tab":102, "taxon.tab":1872, "time_elapsed":{"sec":37.3, "min":0.62, "hr":0.01}}
converted column contributor to child records:
c_AI	Tue 2021-05-25 11:49:22 AM	        {"measurement_or_fact_specific.tab":6244, "occurrence_specific.tab":1872, "reference.tab":102, "taxon.tab":1872, "time_elapsed":{"sec":45.47, "min":0.76, "hr":0.01}}
brought back contributor as column in MoF:
c_AI	Tue 2021-06-01 12:47:23 AM	        {"measurement_or_fact_specific.tab":3744, "occurrence_specific.tab":1872, "reference.tab":102, "taxon.tab":1872, "time_elapsed":{"sec":34.26, "min":0.57, "hr":0.01}}

c_AW	Wednesday 2019-10-23 09:15:39 PM	{"measurement_or_fact_specific.tab":4458,"occurrence_specific.tab":2229,"taxon.tab":2229,"time_elapsed":{"sec":178.03,"min":2.97,"hr":0.05}}
c_AW	Thursday 2019-10-24 04:26:29 AM	    {"measurement_or_fact_specific.tab":4458,"occurrence_specific.tab":2229,"reference.tab":110,"taxon.tab":2229,"time_elapsed":{"sec":112.79,"min":1.88,"hr":0.03}}
c_AW	Tue 2021-05-25 10:56:48 AM	        {"measurement_or_fact_specific.tab":4458, "occurrence_specific.tab":2229, "reference.tab":110, "taxon.tab":2229, "time_elapsed":{"sec":113.91, "min":1.9, "hr":0.03}}
converted column contributor to child records:
c_AW	Tue 2021-05-25 11:51:23 AM	        {"measurement_or_fact_specific.tab":7413, "occurrence_specific.tab":2229, "reference.tab":110, "taxon.tab":2229, "time_elapsed":{"sec":160.05, "min":2.67, "hr":0.04}}
brought back contributor as column in MoF:
c_AW	Tue 2021-06-01 12:48:52 AM	        {"measurement_or_fact_specific.tab":4458, "occurrence_specific.tab":2229, "reference.tab":110, "taxon.tab":2229, "time_elapsed":{"sec":113.09, "min":1.88, "hr":0.03}}

c_MZ	Wed 2020-09-09 01:48:15 PM	{"measurement_or_fact_specific.tab":47760, "occurrence_specific.tab":23880, "reference.tab":587, "taxon.tab":23880, "time_elapsed":{"sec":1367.41, "min":22.79, "hr":0.38}}
c_MZ	Tue 2021-05-25 11:06:58 AM	{"measurement_or_fact_specific.tab":47760, "occurrence_specific.tab":23880, "reference.tab":587, "taxon.tab":23880, "time_elapsed":{"sec":707.81, "min":11.8, "hr":0.2}}
converted column contributor to child records:
c_MZ	Tue 2021-05-25 12:07:57 PM	{"measurement_or_fact_specific.tab":97305, "occurrence_specific.tab":23880, "reference.tab":587, "taxon.tab":23880, "time_elapsed":{"sec":1140.55, "min":19.01, "hr":0.32}}
brought back contributor as column in MoF:
c_MZ	Tue 2021-06-01 12:58:45 AM	{"measurement_or_fact_specific.tab":47760, "occurrence_specific.tab":23880, "reference.tab":587, "taxon.tab":23880, "time_elapsed":{"sec":691.2, "min":11.52, "hr":0.19}}

*/

/* Original resources based on Anne's query use "SC_" and when replaced by the GBIF occurrence the convention is now "c_".
The change is to not overwrite the original batch. */

include_once(dirname(__FILE__) . "/../../config/environment.php");
// ini_set('memory_limit','8096M');
$timestart = time_elapsed();
$params['jenkins_or_cron']  = @$argv[1];
$ctry_name                  = @$argv[2];

$ctry['Anguilla'] = 'AI';
$ctry['Aruba'] = 'AW';
$ctry['Bahrain'] = 'BH';
$ctry['Mozambique'] = 'MZ';
if(!isset($ctry[$ctry_name])) exit("\nERROR: Wrong country parameter.\n");
else $resource_id = 'c_'.$ctry[$ctry_name];

require_library('connectors/NatlChecklistReplacementAPI');
$func = new NatlChecklistReplacementAPI($resource_id);
// /* main operation
$func->start();
unset($func);
Functions::finalize_dwca_resource($resource_id, false, false, $timestart); //3rd param can be 'true' on normal operation - delete folder
// */

/* utility ========================== from copied template
require_library('connectors/DWCADiagnoseAPI');
$func = new DWCADiagnoseAPI();

$undefined = $func->check_if_all_parents_have_entries($resource_id, true); //true means output will write to text file
if($undefined) echo "\nERROR: There is undefined parent(s): ".count($undefined)."\n";
else           echo "\nOK: All parents in taxon.tab have entries.\n";

$undefined = $func->check_if_all_parents_have_entries($resource_id, true, false, array(), "acceptedNameUsageID"); //true means output will write to text file
if($undefined) echo "\nERROR: There is undefined acceptedNameUsageID(s): ".count($undefined)."\n";
else           echo "\nOK: All acceptedNameUsageID have entries.\n";
===================================== */
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>