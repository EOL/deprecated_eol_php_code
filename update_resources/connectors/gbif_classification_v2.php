<?php
namespace php_active_record;
/* GBIF classification update - https://eol-jira.bibalex.org/browse/DATA-1826 
gbif_classification	    Monday 2019-09-23 09:15:26 PM	{"taxon.tab":2845723}
gbif_classification_pre	Tuesday 2019-11-12 12:52:55 PM	{"taxon.tab":2674301,"time_elapsed":{"sec":452183.7,"min":7536.4,"hr":125.61,"day":5.23}}
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
// $GLOBALS['ENV_DEBUG'] = false;
ini_set('memory_limit','8096M');
$timestart = time_elapsed();
$resource_id = 'gbif_classification_pre';
require_library('connectors/GBIF_classificationAPI_v2');

$func = new GBIF_classificationAPI_v2($resource_id);
// /* main operation --- will generate: gbif_classification_pre.tar.gz. Will run in eol-archive.
$func->start();
unset($func);
Functions::finalize_dwca_resource($resource_id, false, false, $timestart); //3rd param true means delete folder
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
$dwca_file = 'https://editors.eol.org/eol_php_code/applications/content_server/resources/gbif_classification_pre.tar.gz';
$dwca_file = 'http://localhost/eol_php_code/applications/content_server/resources_2/gbif_classification_pre.tar.gz';
require_library('connectors/DwCA_Utility');
$func = new DwCA_Utility($resource_id, $dwca_file);

// Orig in meta.xml has capital letters. Just a note reminder.

$preferred_rowtypes = array();
// This 1 will be processed in GBIF_classificationAPI_v2.php which will be called from DwCA_Utility.php
// http://rs.tdwg.org/dwc/terms/Taxon

$func->convert_archive($preferred_rowtypes);
Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
// */
//====================================================================================================





/* utility ========================== works OK
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