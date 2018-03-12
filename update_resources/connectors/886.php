<?php
namespace php_active_record;
/* GBIF dwc-a resources: country nodes
SPG provides mappings for values and URI's. The DWC-A file is requested from GBIF's web service.
This connector assembles the data and generates the EOL archive for ingestion.
estimated execution time: this will vary depending on how big the archive file is.

DATA-1577 GBIF national node type records- France
                                                2March
measurement_or_fact         [27223] 638236      837638
occurrence                  [9075]  212746      212746
taxon                       [4291]  95625       95625
classification resource:
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/GBIFCountryTypeRecordAPI');
$timestart = time_elapsed();

/* local
$params["citation_file"] = "http://localhost/cp_new/GBIF_dwca/countries/France/Citation Mapping France.xlsx";
$params["dwca_file"]     = "http://localhost/cp_new/GBIF_dwca/countries/France/France.zip";
$params["uri_file"]      = "http://localhost/cp_new/GBIF_dwca/countries/France/french GBIF mapping.xlsx";
*/

// remote
$params["citation_file"] = "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/GBIF_dwca/countries/France/Citation Mapping France.xlsx";
$params["dwca_file"]    = "https://editors.eol.org/other_files/GBIF_DwCA/France.zip";
$params["uri_file"]      = "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/GBIF_dwca/countries/France/french GBIF mapping.xlsx";

$params["dataset"]      = "GBIF";
$params["country"]      = "France";
$params["type"]         = "structured data";
$params["resource_id"]  = 886;

// $params["type"]         = "classification resource";
// $params["resource_id"]  = 1;

$resource_id = $params["resource_id"];
$func = new GBIFCountryTypeRecordAPI($resource_id);
$func->export_gbif_to_eol($params);
Functions::finalize_dwca_resource($resource_id);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>