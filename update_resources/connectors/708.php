<?php
namespace php_active_record;
/*
execution time: 11 hours when API calls are already cached
Environments EOL
https://jira.eol.org/browse/DATA-1487
                                Apr2
taxa:               230,808     217,964
measurementorfact:  1,052,641   940,408
occurrence                      940,408

708	Tuesday 2018-03-27 03:01:54 PM	{"measurement_or_fact.tab":1266616,"occurrence.tab":955315,"reference.tab":215861,"taxon.tab":227582} - MacMini only, since still limited space in Archive.
708	Wednesday 2018-03-28 02:48:18 PM{"measurement_or_fact.tab":1174597,"occurrence.tab":955315,"reference.tab":215861,"taxon.tab":227582}
708	Tuesday 2018-04-10 01:13:22 AM	{"measurement_or_fact.tab":1153984,"occurrence.tab":936636,"reference.tab":215125,"taxon.tab":226794} - after changes here: https://eol-jira.bibalex.org/browse/DATA-1739?focusedCommentId=62373&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-62373

from eol-archive:
708	Wednesday 2018-08-22 07:40:52 AM{"measurement_or_fact.tab":1153984,"occurrence.tab":936636,"reference.tab":215125,"taxon.tab":226794}
708	Wednesday 2018-10-03 09:41:10 AM{"measurement_or_fact.tab":1148933,"occurrence.tab":933687,"reference.tab":215125,"taxon.tab":226794}
708	Thursday 2018-10-04 05:51:27 AM	{"measurement_or_fact.tab":1110583,"occurrence.tab":898509,"reference.tab":215125,"taxon.tab":226794}

NOTE: started running this eol-archive in Aug 6, 2018
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/EnvironmentsEOLDataConnector');
ini_set('memory_limit','7096M'); //required for checking uniqueness of measurementID
$timestart = time_elapsed();
$resource_id = 708;
$func = new EnvironmentsEOLDataConnector($resource_id);
$func->generate_EnvEOL_data();
$func = NULL;
Functions::finalize_dwca_resource($resource_id);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec . " seconds";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
// $func->list_folders_with_corrupt_files(); //utility
?>