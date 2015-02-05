<?php
namespace php_active_record;
/* GBIF dwc-a resources: country nodes:
SPG provides mappings for values and URI's. The DWC-A file is requested from GBIF's web service.
This connector assembles the data and generates the EOL archive for ingestion.
estimated execution time: this will vary depending on how big the archive file is.

DATA-1578 GBIF national node type records- Netherlands
measurement_or_fact         [29989] 418450
occurrence                  [9997]  139484
taxon                       [3214]  52763
classification resource:
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/GBIFCountryTypeRecordAPI');
$timestart = time_elapsed();

$params["dwca_file"]    = "http://localhost/~eolit/cp/GBIF_dwca/countries/Netherlands/Netherlands.zip";
$params["uri_file"]     = "http://localhost/~eolit/cp/GBIF_dwca/countries/Netherlands/GBIF Netherlands mapping.xlsx";
$params["dwca_file"]    = "https://dl.dropboxusercontent.com/u/7597512/GBIF_dwca/countries/Netherlands/Netherlands.zip";
$params["uri_file"]     = "https://dl.dropboxusercontent.com/u/7597512/GBIF_dwca/countries/Netherlands/GBIF Netherlands mapping.xlsx";
$params["dataset"]      = "GBIF";
$params["country"]      = "Netherlands";
$params["type"]         = "structured data";
$params["resource_id"]  = 887;

// $params["type"]         = "classification resource";
// $params["resource_id"]  = 1;

$resource_id = $params["resource_id"];
$func = new GBIFCountryTypeRecordAPI($resource_id);
$func->export_gbif_to_eol($params);
if(filesize(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_working/taxon.tab") > 1000)
{
    if(is_dir(CONTENT_RESOURCE_LOCAL_PATH . $resource_id))
    {
        recursive_rmdir(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_previous");
        rename(CONTENT_RESOURCE_LOCAL_PATH . $resource_id, CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_previous");
    }
    rename(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_working", CONTENT_RESOURCE_LOCAL_PATH . $resource_id);
    rename(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_working.tar.gz", CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".tar.gz");
    Functions::set_resource_status_to_force_harvest($resource_id);
    Functions::count_resource_tab_files($resource_id);
}
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>