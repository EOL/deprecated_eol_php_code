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
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
// ini_set('memory_limit','8096M');
$timestart = time_elapsed();
$params['jenkins_or_cron']  = @$argv[1];
$ctry_name                  = @$argv[2];

$ctry['Anguilla'] = 'AI';
$ctry['Aruba'] = 'AW';
$ctry['Bahrain'] = 'BH';
if(!isset($ctry[$ctry_name])) exit("\nERROR: Wrong country parameter.\n");
else $resource_id = 'c_'.$ctry[$ctry_name];

require_library('connectors/NatlChecklistReplacementConnAPI');
$func = new NatlChecklistReplacementConnAPI($resource_id);
// /* main operation
$func->start();
unset($func);
Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
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