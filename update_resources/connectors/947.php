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
Functions::finalize_dwca_resource($resource_id);
Functions::remove_resource_working_dir($resource_id);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>