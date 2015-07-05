<?php
namespace php_active_record;
/* DATA-1615 NHM type data acquisition
                    12-Apr      15Apr
measurement_or_fact [2432673]   2433637
occurrence          [293257]    293256
taxon.tab           [155041]    155041
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/NMNHTypeRecordAPI');
$timestart = time_elapsed();

/*
//local source
$params["dwca_file"]    = "http://localhost/cp/NHM/gbif_dwca.zip";
$params["uri_file"]     = "http://localhost/cp/NMNH/type_specimen_resource/nmnh mappings.xlsx";
*/

//remote source
$params["dwca_file"]    = "http://data.nhm.ac.uk/resources/gbif_dwca.zip"; // true value - working
$params["dwca_file"]    = "https://dl.dropboxusercontent.com/u/7597512/NHM/gbif_dwca.zip";
$params["uri_file"]     = "https://dl.dropboxusercontent.com/u/7597512/NMNH/type_specimen_resource/nmnh mappings.xlsx";

$params["row_type"]     = "http://rs.tdwg.org/dwc/terms/occurrence";
$params["location"]     = "occurrence.csv";
$params["dataset"]      = "NHM";
$params["type"]         = "structured data";
$params["resource_id"]  = 947;

$resource_id = $params["resource_id"];
$func = new NMNHTypeRecordAPI($resource_id);
$func->export_gbif_to_eol($params);
if(filesize(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_working/taxon.tab") > 1000)
{
    if(is_dir(CONTENT_RESOURCE_LOCAL_PATH . $resource_id))
    {
        recursive_rmdir(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_previous");
        Functions::file_rename(CONTENT_RESOURCE_LOCAL_PATH . $resource_id, CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_previous");
    }
    Functions::file_rename(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_working", CONTENT_RESOURCE_LOCAL_PATH . $resource_id);
    Functions::file_rename(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_working.tar.gz", CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".tar.gz");
    Functions::set_resource_status_to_force_harvest($resource_id);
    Functions::count_resource_tab_files($resource_id);
	if($undefined_uris = Functions::get_undefined_uris_from_resource($resource_id)) print_r($undefined_uris);
    echo "\nUndefined URIs: " . count($undefined_uris) . "\n";
	require_library('connectors/DWCADiagnoseAPI');
	$func = new DWCADiagnoseAPI();
	$func->check_unique_ids($resource_id);
}
Functions::remove_resource_working_dir($resource_id);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>