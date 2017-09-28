<?php
namespace php_active_record;
/* Reef Life Survey (http://reeflifesurvey.imas.utas.edu.au/static/landing.html) for FreeData 

                                2017-Jun-06
observations.txt rows total:    508343

*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/FreeDataAPI');
$timestart = time_elapsed();

/*
//local - during development
$params['Global reef fish dataset'] = "http://localhost/cp/FreshData/ReefLifeSurvey/M1_DATA.csv";
$params['Invertebrates']            = "http://localhost/cp/FreshData/ReefLifeSurvey/M2_INVERT_DATA.csv";
*/

// /*
//remote - actual
$params['Global reef fish dataset'] = "http://geoserver-rls.imas.utas.edu.au/geoserver/RLS/ows?service=WFS&version=1.0.0&request=GetFeature&typeName=RLS:M1_DATA&outputFormat=csv";
$params['Invertebrates']            = "http://geoserver-rls.imas.utas.edu.au/geoserver/RLS/ows?service=WFS&version=1.0.0&request=GetFeature&typeName=RLS:M2_INVERT_DATA&outputFormat=csv";
// */

$func = new FreeDataAPI("reef-life-survey");
$func->generate_ReefLifeSurvey_archive($params);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>
