<?php
namespace php_active_record;
/* GBIF classification update - https://eol-jira.bibalex.org/browse/DATA-1826 
From: Mac Mini:
gbif_classification_pre	    Thursday 2019-11-21 05:53:56 AM	{"taxon.tab":3820178, "time_elapsed":{"sec":2516.83,"min":41.95,"hr":0.7}}
gbif_classification_pre	    Thursday 2020-04-16 10:58:38 AM	{"taxon.tab":4438420, "time_elapsed":{"sec":3818.47, "min":63.64, "hr":1.06}}

gbif_classification	        Thursday 2019-11-21 09:34:38 AM	{"taxon.tab":3820178, "time_elapsed":{"sec":3079.33,"min":51.32,"hr":0.86}}
gbif_classification	        Thursday 2020-04-16 10:36:10 PM	{"taxon.tab":4438420, "time_elapsed":{"sec":4015.68, "min":66.93, "hr":1.12}}


From editors.eol.org using Jenkins:
gbif_classification_pre	Friday 2020-04-17 06:41:34 AM	{"taxon.tab":4438420, "time_elapsed":{"sec":2168.85, "min":36.15, "hr":0.6}}
gbif_classification	Friday 2020-04-17 07:28:50 AM	    {"taxon.tab":4438420, "time_elapsed":{"sec":5004.67, "min":83.41, "hr":1.39}}

Mac Mini - slow run in Mac Mini
gbif_classification_pre	                Tuesday 2020-05-19 03:31:58 AM	{"taxon.tab":4438420, "time_elapsed":{"sec":3652.92, "min":60.88, "hr":1.01}}
gbif_classification	                    Tuesday 2020-05-19 04:45:04 AM	{"taxon.tab":4438420, "time_elapsed":{"sec":8039.14, "min":133.99, "hr":2.23}}
gbif_classification_without_ancestry	Tuesday 2020-05-19 05:47:12 AM	{"taxon.tab":4438420, "time_elapsed":{"sec":11767.01, "min":196.12, "hr":3.27}}

eol-archive - much faster to run in eol-archive
gbif_classification_pre	                Tuesday 2020-05-19 03:15:57 AM	{"taxon.tab":4438420, "time_elapsed":{"sec":2315.6, "min":38.59, "hr":0.64}}
gbif_classification	                    Tuesday 2020-05-19 04:05:53 AM	{"taxon.tab":4438420, "time_elapsed":{"sec":5312.28, "min":88.54, "hr":1.48}}
gbif_classification_without_ancestry	Tuesday 2020-05-19 04:45:14 AM	{"taxon.tab":4438420, "time_elapsed":{"sec":7673.19, "min":127.89, "hr":2.13}}
*/
/* Notes as of Nov 9, 2020:
This script (gbif_classification_v2.php) generates 3 DwCA files:
1. gbif_classification_pre.tar.gz              -> a pre step to generate desired DwCA
2. gbif_classification.tar.gz                  -> final classification DwCA
3. gbif_classification_without_ancestry.tar.gz -> another adjustment, ancestry removed
*Both 2 & 3 are used separately historicaly. Each with its intended purpose.

Then came another adjustment from Katja. DATA-1868: Remove surrogates from GBIF classification resource
Connector is gbif_classification_DATA_1868.php

So, in Jenkins we run both scripts:
php5.6 gbif_classification_v2.php jenkins
php5.6 gbif_classification_DATA_1868.php jenkins
# input is gbif_classification_without_ancestry.tar.gz
# generates gbif_classification_final.tar.gz
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
// $GLOBALS['ENV_DEBUG'] = false;
ini_set('memory_limit','8096M');
$timestart = time_elapsed();
$resource_id = 'gbif_classification_pre';
require_library('connectors/GBIF_classificationAPI_v2');

/*
$uris = array('a','b','c','d');
$will_remove_uris = array('b','c');
$uris = array_diff($uris, $will_remove_uris);
print_r($uris);
exit("\n-end test-\n");
*/


$func = new GBIF_classificationAPI_v2($resource_id);
// /* main operation --- will generate: gbif_classification_pre.tar.gz. Will run in eol-archive.
$func->start();
unset($func);
Functions::finalize_dwca_resource($resource_id, false, false, $timestart); //3rd param true means delete folder
check_parents($resource_id);
// */

/* Two Reminders: 
------------------------------------------------------------------------------------------------------
1. For eoldynamichierarchywithlandmarks.zip, the meta.xml is manually edited by Eli.
That is edited rowtype = http://rs.tdwg.org/dwc/terms/taxon
The orig value is wrong (occurrence).
------------------------------------------------------------------------------------------------------
2. For eolpageids.csv, this was also edited by Eli. Added headers, that is column names (DH_id,EOL_id).
e.g. Array(
            [DH_id] => -1
            [EOL_id] => 2913056
        )
------------------------------------------------------------------------------------------------------
*/

//====================================================================================================
/* Use the DH09 EOL_id for the remaining conflicts between the API & DH09 mappings.
      Since I'm using manually edited eoldynamichierarchywithlandmarks/meta.xml (wrong rowtype in meta.xml)
      and
      eolpageids.csv with added headers. SO THIS WILL BE RUN IN Mac Mini ONLY. */
// /*
$resource_id = 'gbif_classification';
if(Functions::is_production()) $dwca_file = 'https://editors.eol.org/eol_php_code/applications/content_server/resources/gbif_classification_pre.tar.gz';
else                           $dwca_file = 'http://localhost/eol_php_code/applications/content_server/resources/gbif_classification_pre.tar.gz';
require_library('connectors/DwCA_Utility');
$func = new DwCA_Utility($resource_id, $dwca_file);

// Orig in meta.xml has capital letters. Just a note reminder.

$preferred_rowtypes = array();
// This 1 will be processed in GBIF_classificationAPI_v2.php which will be called from DwCA_Utility.php
// http://rs.tdwg.org/dwc/terms/Taxon

$func->convert_archive($preferred_rowtypes);
Functions::finalize_dwca_resource($resource_id, false, false, $timestart);
check_parents($resource_id);
// */

// /* Creating DwCA without ancestry columns: per Jen: https://eol-jira.bibalex.org/browse/DATA-1826?focusedCommentId=64864&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-64864
$resource_id = 'gbif_classification_without_ancestry';
if(Functions::is_production()) $dwca_file = 'https://editors.eol.org/eol_php_code/applications/content_server/resources/gbif_classification.tar.gz';
else                           $dwca_file = 'http://localhost/eol_php_code/applications/content_server/resources/gbif_classification.tar.gz';
require_library('connectors/DwCA_Utility');
$func = new DwCA_Utility($resource_id, $dwca_file);

// Orig in meta.xml has capital letters. Just a note reminder.

$preferred_rowtypes = array();
// This 1 will be processed in GBIF_classificationAPI_v2.php which will be called from DwCA_Utility.php
// http://rs.tdwg.org/dwc/terms/Taxon

$func->convert_archive($preferred_rowtypes);
Functions::finalize_dwca_resource($resource_id, false, false, $timestart);
check_parents($resource_id);
// */


//====================================================================================================

function check_parents($resource_id)
{
    // /* utility ========================== works OK
    require_library('connectors/DWCADiagnoseAPI');
    $func = new DWCADiagnoseAPI();

    $undefined = $func->check_if_all_parents_have_entries($resource_id, true); //true means output will write to text file
    if($undefined) echo "\nERROR: There is undefined parent(s): ".count($undefined)."\n";
    else           echo "\nOK: All parents in taxon.tab have entries.\n";

    $undefined = $func->check_if_all_parents_have_entries($resource_id, true, false, array(), "acceptedNameUsageID"); //true means output will write to text file
    if($undefined) echo "\nERROR: There is undefined acceptedNameUsageID(s): ".count($undefined)."\n";
    else           echo "\nOK: All acceptedNameUsageID have entries.\n";
    // ===================================== */
}
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>