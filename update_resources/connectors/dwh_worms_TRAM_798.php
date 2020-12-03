<?php
namespace php_active_record;
/* WoRMS Extract for Dynamic Hierarchy - https://eol-jira.bibalex.org/browse/TRAM-798
                 WoRMS Extract for DH2 - https://eol-jira.bibalex.org/browse/TRAM-988 
   
estimated execution time: Took 1 min 27 sec (eol-archive)
WoRMS_DH	Wednesday 2018-08-22 06:55:05 PM	{"taxon.tab":63297} - eol-archive
WoRMS_DH	Thursday 2018-08-23 08:27:28 AM	    {"taxon.tab":53272} - eol-archive
WoRMS_DH	Wednesday 2019-03-27 08:01:50 AM	{"taxon.tab":57539} - eol-archive
WoRMS_DH	Tuesday 2019-03-26 11:00:40 AM	    {"taxon.tab":57539} - MacMini

Start TRAM-988:
Mac Mini
WoRMS2EoL_zip	Thu 2020-09-17 06:56:00 AM	{"taxon.tab":633588, "time_elapsed":{"sec":668.88, "min":11.15, "hr":0.19}}
WoRMS_DH	    Thu 2020-09-17 06:42:58 AM	{"taxon.tab":213534, "time_elapsed":false}

eol-archive
WoRMS2EoL_zip	Thu 2020-09-17 07:00:41 AM	{"taxon.tab":633588, "time_elapsed":{"sec":363.4, "min":6.06, "hr":0.1}}
WoRMS2EoL_zip	Tue 2020-09-29 03:59:57 AM	{"taxon.tab":633588, "time_elapsed":{"sec":350.42, "min":5.84, "hr":0.1}}

WoRMS_DH	    Thu 2020-09-17 07:11:22 AM	{"taxon.tab":213534, "time_elapsed":false}
WoRMS_DH	    Mon 2020-09-28 01:42:53 PM	{"taxon.tab":213455, "time_elapsed":false}
WoRMS_DH	    Tue 2020-09-29 03:43:48 AM	{"taxon.tab":214007, "time_elapsed":false} - wrong
WoRMS_DH	    Tue 2020-09-29 07:21:28 AM	{"taxon.tab":213458, "time_elapsed":false}
WoRMS_DH	    Wed 2020-12-02 09:32:12 PM	{"taxon.tab":193823, "time_elapsed":false} - expected decrease, more removed branches
WoRMS_DH	    Wed 2020-12-02 11:56:18 PM	{"taxon.tab":193746, "time_elapsed":false} - expected decrease, removed incertae sedis and REMAP_ON_EOL
WoRMS_DH	    Thu 2020-12-03 01:16:22 AM	{"taxon.tab":193739, "time_elapsed":false} - expected decrease, undefined parents after initial run

Duplicates report for Katja:
https://editors.eol.org/eol_php_code/applications/content_server/resources/reports/duplicates.txt

How to run: Mac Mini
php update_resources/connectors/resource_utility.php _ '{"resource_id": "WoRMS2EoL_zip", "task": "add_canonical_in_taxa"}'
php update_resources/connectors/dwh_worms_TRAM_798.php

NOTE: there is no gnparser in Jenkins in eol-archive yet. So the first script should be run in command-line
php update_resources/connectors/resource_utility.php _ '{"resource_id": "WoRMS2EoL_zip", "task": "add_canonical_in_taxa"}'
Then the 2nd script can be run in Jenkins.
*/

// $a1 = array('a','b', 'c');
// $a2 = array('b','a','c');
// $a3 = array_diff($a1, $a2);
// print_r($a3);
// $a3 = array_values($a3); //reindex key
// print_r($a3);
// exit("\n".count($a3)."\n");

// $str = 'In 3My C3art : 11 22 items';
// $str = 'Eualus suckleyi (Stimpson, 1864)';
// $str = 'Halofolliculina annulata (Andrews, 1944) Hadzi, 1951';
// $str = 'Cushmanidea3 grosjeani 2 (Keij, 1957) Ducasse & Vigneaux, 1961';
// $str = "eli boy";
// $int = (int) filter_var($str, FILTER_SANITIZE_NUMBER_INT);
// echo("\n[$int]\n");
// 
// preg_match_all('!\d+!', $str, $matches);
// // preg_match('!\d+!', $str, $matches);
// print_r($matches);
// exit;

// include_once(dirname(__FILE__) . "/../../config/environment.php");
// require_library('connectors/DWH_WoRMS_API');
// $func = new DWH_WoRMS_API(1);
// $sci = "Plagioecia (Gadus) dorsalis (Waters, 1879)";
// $sci = "Paradoxostoma acuminatum Müller, 1894";
// $sci = "Paradoxostoma acuminatum Weller, 1894";
// $arr = $func->call_gnparser($sci);
// print_r($arr);
// echo "\n".@$arr[0]['details'][0]['infragenericEpithet']['value']."\n";
// exit;

/*
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/DWH_WoRMS_API');
$func = new DWH_WoRMS_API(1);
// $sciname = "Haliclona (Gellius) Gray, 1867";
// $sciname = "Haliclona Grant, 1841";
// $canonical = $func->canonical_form_gnparser($sciname);
// echo "\n[$canonical]\n";

// $str = 'eli boy ';
// $num = $func->number_of_words($str);
// echo "\n[$num]\n";

$sciname = "Rutiderma (Rutiderma) compressa Brady & Norman, 1896";
$arr = $func->call_gnparser($sciname);
print_r($arr);
if($subgenus = @$arr[0]['details'][0]['infragenericEpithet']['value']) {
    echo "\n[$subgenus]\n";
}

exit("\n-test-\n");
*/

// $int[0] = 1928;
// $int[1] = 1909;
// $int[2] = 1905;
// print_r($int); asort($int); print_r($int);
// $index = array_keys($int);
// print_r($index);
// exit;

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/DWH_WoRMS_API');
// ini_set('memory_limit','15096M');
// ini_set('memory_limit','6096M');
$GLOBALS['ENV_DEBUG'] = true;
$timestart = time_elapsed();
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