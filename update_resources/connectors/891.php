<?php
namespace php_active_record;
/* WEB-5843 Import Smithsonian type specimen data to TraitBank
                                        2017      START   2017      from eol-archive            local   remote  remote
                    14Jan       16Apr   2Nov      NEW IPT 3Nov      3Nov latest 6Nov    13Nov   14Nov   14Nov   4Dec
measurement_or_fact 4,768,256   4768226 4,768,196 ---     4,348,898 4,380,745   4380783 4380784 4380784 4380784 4388232
occurrence          468,454     468454  468,450   ---     350,602   351,781     351786  351786  351786  351786  352216
taxon               295903      295903  295,899   ---     255,887   256,211     256211  256211  256211  256211  256316
media                                                                                   221045  143979  143944  47515
agents                                                                                  322     98      98      84

891	Monday 2017-12-04 07:13:36 AM	{"agent.tab":84,"measurement_or_fact.tab":4388232,"media_resource.tab":47515,"occurrence.tab":352216,"taxon.tab":256316} - eol-archive
891	Monday 2018-03-12 09:29:40 AM	{"agent.tab":86,"measurement_or_fact.tab":4402583,"media_resource.tab":137786,"occurrence.tab":352671,"taxon.tab":256523} - eol-archive, no measurementID
891	Tuesday 2018-03-13 03:24:51 AM	{"agent.tab":86,"measurement_or_fact.tab":4402583,"media_resource.tab":137786,"occurrence.tab":352671,"taxon.tab":256523} - with measurementID
891	Wednesday 2018-03-14 03:47:24 PM{"agent.tab":86,"measurement_or_fact.tab":4402580,"media_resource.tab":137786,"occurrence.tab":352671,"taxon.tab":256523} - ran in Archive with more memory to finish DwCADiagnose.
891	Thursday 2018-04-05 10:26:11 AM	{               "measurement_or_fact.tab":5460512,                            "occurrence.tab":352666,"taxon.tab":256523} - after removing media per Katja
891	Thursday 2018-04-05 10:27:25 PM	{               "measurement_or_fact.tab":5460512,                            "occurrence.tab":352666,"taxon.tab":256523}
891	Saturday 2018-04-07 10:56:13 AM	{               "measurement_or_fact.tab":5481279,                            "occurrence.tab":352782,"taxon.tab":256626}

10k records:        9Jan
measurement_or_fact 9218
occurrence          1708
taxon               716

891	Fri 2021-02-12 03:45:50 AM	{"measurement_or_fact.tab":5599755, "occurrence.tab":357414, "taxon.tab":258448, "time_elapsed":false}
891	Fri 2021-03-12 03:47:08 AM	{"measurement_or_fact.tab":5613072, "occurrence.tab":358203, "taxon.tab":259147, "time_elapsed":false}
891	Thu 2021-04-08 01:59:07 PM	{"measurement_or_fact.tab":5619683, "occurrence.tab":358624, "taxon.tab":259334, "time_elapsed":false}
891	Tue 2021-04-13 04:46:03 AM	{"measurement_or_fact.tab":5619683, "occurrence.tab":358624, "taxon.tab":259334, "time_elapsed":false}
891	Tue 2021-04-13 09:39:43 AM	{"measurement_or_fact.tab":5619683, "occurrence.tab":358624, "taxon.tab":259334, "time_elapsed":false}
891	Wed 2021-04-14 09:04:32 AM	{"measurement_or_fact.tab":5619683, "occurrence.tab":358624, "taxon.tab":259334, "time_elapsed":false}
891	Thu 2021-04-15 04:36:28 AM	{"measurement_or_fact.tab":5619683, "occurrence.tab":358624, "taxon.tab":259334, "time_elapsed":false}
891	Tue 2021-11-09 10:46:41 AM	{"measurement_or_fact.tab":4997635, "occurrence.tab":359204, "taxon.tab":259657, "time_elapsed":false}
Implement this: new task: https://eol-jira.bibalex.org/browse/DATA-1711?focusedCommentId=66523&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-66523
child record in MoF:
    - doesn't have: occurrenceID | measurementOfTaxon
    - has parentMeasurementID
    - has also a unique measurementID, as expected.
minimum cols on a child record in MoF
    - measurementID
    - measurementType
    - measurementValue
    - parentMeasurementID
891	Wed 2021-12-08 11:20:39 AM	{"measurement_or_fact.tab":5005576, "occurrence.tab":359251, "taxon.tab":259695, "time_elapsed":false} - ok consistent
891	Tue 2021-12-14 06:46:56 PM	{"measurement_or_fact.tab":4330055, "occurrence.tab":359251, "taxon.tab":259695, "time_elapsed":false}


*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
ini_set('memory_limit','12096M'); //needed so it can process checking of identifier uniqueness in measurement and occurrence extensions.

/* just a utility - this is already inside -> Functions::finalize_dwca_resource($resource_id);
require_library('connectors/DWCADiagnoseAPI');
$func = new DWCADiagnoseAPI();
$func->check_unique_ids('891');
return;
*/

/*
$url = "https://collections.nmnh.si.edu/services/media.php?env=mammals&irn=7005395";
print_r(pathinfo($url));
$url = "https://collections.nmnh.si.edu/media/index.php?irn=10295934";
print_r(pathinfo($url));
exit("\n");
*/

require_library('connectors/NMNHTypeRecordAPI_v2');
$timestart = time_elapsed();
$GLOBALS['ENV_DEBUG'] = true;

if(Functions::is_production()) {
    $params["dwca_file"]    = "https://collections.mnh.si.edu/ipt/archive.do?r=nmnhdwca"; //obsolete
    $params["dwca_file"]    = ""; // e.g. "https://collections.nmnh.si.edu/ipt/archive.do?r=nmnh_extant_dwc-a&v=1.8"; 
                                  // latest as of 25-Oct-2017. From https://eol-jira.bibalex.org/browse/DATA-1711
                                  // e.g. "https://collections.nmnh.si.edu/ipt/archive.do?r=nmnh_extant_dwc-a&v=1.72" as of Sep 26,2023
                                  // will be generated below get_dwca_download_url()
    $params["uri_file"]     = "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/NMNH/type_specimen_resource/nmnh-mappings.xlsx";
}
else {
    $params["dwca_file"]    = "http://localhost/cp_new/NMNH/type_specimen_resource/dwca-nmnhdwca.zip"; //obsolete
    $params["dwca_file"]    = "http://localhost/cp_new/NMNH/type_specimen_resource/dwca-nmnh_extant_dwc-a-v1.8.zip"; //latest as of 25-Oct-2017
    $params["dwca_file"]    = "http://localhost/cp_new/NMNH/type_specimen_resource/dwca-nmnh_extant_dwc-a-v1.10.zip"; //latest as of 03-Nov-2017
    $params["uri_file"]     = "http://localhost/cp_new/NMNH/type_specimen_resource/nmnh-mappings.xlsx"; //renamed; originally [nmnh mappings.xlsx]
}

$params["row_type"]     = "http://rs.tdwg.org/dwc/terms/occurrence";
$params["location"]     = "occurrence.txt";
$params["dataset"]      = "NMNH";
$params["type"]         = "structured data";
$params["resource_id"]  = 891;

$resource_id = $params["resource_id"];

// $func = new NMNHTypeRecordAPI($resource_id); //old, obsolete for NMNH
$func = new NMNHTypeRecordAPI_v2($resource_id); //latest, but based from the old one

if(Functions::is_production()) {
// if(true) {
    if($params["dwca_file"] = $func->get_dwca_download_url()) {}
    else exit("\nCannot get download URL for the DwCA.\n");
}
print_r($params); //exit;

$func->start($params); //renamed, it was $func->export_gbif_to_eol() before
$func = null;
Functions::finalize_dwca_resource($resource_id, false, false); //3rd row 'false' means not delete working dir

run_utility($resource_id);
recursive_rmdir(CONTENT_RESOURCE_LOCAL_PATH.$resource_id."/"); //we can now delete folder after run_utility() - DWCADiagnoseAPI

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
function run_utility($resource_id)
{   // /* utility ==========================
    require_library('connectors/DWCADiagnoseAPI');
    $func = new DWCADiagnoseAPI();
    $undefined_parents = $func->check_if_all_parents_have_entries($resource_id, true, false, false, 'parentMeasurementID', 'measurement_or_fact.tab');
    echo "\nTotal undefined parents MoF [$resource_id]: " . count($undefined_parents)."\n";
    // ===================================== */
}
?>