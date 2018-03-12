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

10k records:        9Jan
measurement_or_fact 9218
occurrence          1708
taxon               716
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");

/*
$url = "https://collections.nmnh.si.edu/services/media.php?env=mammals&irn=7005395";
print_r(pathinfo($url));
$url = "https://collections.nmnh.si.edu/media/index.php?irn=10295934";
print_r(pathinfo($url));
exit("\n");
*/

require_library('connectors/NMNHTypeRecordAPI_v2');
ini_set('memory_limit','5096M'); //needed so it can process checking of identifier uniqueness in measurement and occurrence extensions.
$timestart = time_elapsed();
$GLOBALS['ENV_DEBUG'] = true;

if(Functions::is_production()) {
    $params["dwca_file"]    = "https://collections.mnh.si.edu/ipt/archive.do?r=nmnhdwca"; //obsolete
    $params["dwca_file"]    = ""; // e.g. "https://collections.nmnh.si.edu/ipt/archive.do?r=nmnh_extant_dwc-a&v=1.8"; 
                                  // latest as of 25-Oct-2017. From https://eol-jira.bibalex.org/browse/DATA-1711
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
    if($params["dwca_file"] = $func->get_dwca_download_url()) {}
    else exit("\nCannot get download URL for the DwCA.\n");
}
print_r($params);

$func->start($params); //renamed, it was $func->export_gbif_to_eol() before
Functions::finalize_dwca_resource($resource_id);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>
