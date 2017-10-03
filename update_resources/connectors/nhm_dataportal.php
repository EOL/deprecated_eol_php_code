<?php
namespace php_active_record;
/* NHM Data Portal
estimated execution time:
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/NHMDataPortalAPI');
$timestart = time_elapsed();
$resource_id = 1;

/* //for caching...
$func = new NHMDataPortalAPI($resource_id);
$func->get_all_taxa();
exit;
*/

// /* //normal operation
$params["uri_file"]     = "http://localhost/~eolit/cp/NMNH/type_specimen_resource/nmnh mappings.xlsx";
// $params["uri_file"]     = "https://dl.dropboxusercontent.com/u/7597512/NMNH/type_specimen_resource/nmnh mappings.xlsx";

$params["dataset"]      = "NMNH";
$params["type"]         = "structured data";
$params["resource_id"]  = 1;

require_library('connectors/NMNHTypeRecordAPI');
$func = new NMNHTypeRecordAPI($resource_id);
$func->export_nhm_gbif_to_eol($params); // this is using the NHM API service
// */

$resource_id = $params["resource_id"];
Functions::finalize_dwca_resource($resource_id);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>
