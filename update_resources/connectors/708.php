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
708	Monday 2018-10-15 10:46:55 PM	{"measurement_or_fact.tab":839374,"occurrence.tab":732245,"reference.tab":201727,"taxon.tab":226794}
708	Saturday 2018-10-27 01:01:27 AM	{"measurement_or_fact.tab":839374,"occurrence.tab":732245,"reference.tab":201727,"taxon.tab":226794}
708	Monday 2018-11-19 02:20:19 PM	{"measurement_or_fact.tab":742382,"occurrence.tab":645486,"reference.tab":201728,"taxon.tab":226795} - as expected traits reduced
708	Tuesday 2018-11-20 03:11:52 AM	{"measurement_or_fact.tab":742382,"occurrence.tab":645486,"reference.tab":201728,"taxon.tab":196193} - as expected traits and taxa reduced
708	Tuesday 2018-11-20 05:13:32 AM	{"measurement_or_fact.tab":742382,"occurrence.tab":645486,"reference.tab":198537,"taxon.tab":196193} - as expected traits, taxa, refs are reduced
708	Saturday 2018-11-24 02:48:32 AM	{"measurement_or_fact.tab":742382,"occurrence.tab":645486,"reference.tab":198537,"taxon.tab":196193}
708	Sunday 2018-11-25 04:26:42 AM	{"measurement_or_fact.tab":742382,"occurrence.tab":645486,"reference.tab":198537,"taxon.tab":196193}

NOTE: started running this eol-archive in Aug 6, 2018
*/

exit("\nThis is now obsolete.\nThe last version of the DwCA is here:\nhttps://editors.eol.org/eol_php_code/applications/content_server/resources/708_25Nov2018.tar.gz\nSee 708.php for more details.\n\n");
/*
A new connector (708_new.php) was created to parse above DwCA per adjustments here:
https://eol-jira.bibalex.org/browse/DATA-1768?focusedCommentId=63624&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-63624
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