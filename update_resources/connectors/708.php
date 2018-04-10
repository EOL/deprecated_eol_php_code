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

NOTE: this is still run only on local, since addt'l harddisks in Archive aren't installed yet.
*/
return;
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