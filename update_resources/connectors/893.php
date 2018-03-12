<?php
namespace php_active_record;
/* GBIF dwc-a resources: country nodes
SPG provides mappings for values and URI's. The DWC-A file is requested from GBIF's web service.
This connector assembles the data and generates the EOL archive for ingestion.
estimated execution time: this will vary depending on how big the archive file is.

DATA-1579 GBIF national node type records- Sweden
                                    Mar2
measurement_or_fact         262882  341939
occurrence                  87628   87628
taxon                       50395   50395

893	Monday 2018-03-12 03:06:53 AM	{"measurement_or_fact.tab":341938,"occurrence.tab":87627,"taxon.tab":50393}

classification resource:
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/GBIFCountryTypeRecordAPI');
$timestart = time_elapsed();

/* local
$params["citation_file"] = "http://localhost/cp_new/GBIF_dwca/countries/Sweden/Citation mapping Sweden.xlsx";
$params["dwca_file"]    = "http://localhost/cp_new/GBIF_dwca/countries/Sweden/Sweden.zip";
$params["uri_file"]     = "http://localhost/cp_new/GBIF_dwca/countries/Sweden/GBIF Sweden mapping.xlsx";
*/

//remote
$params["citation_file"] = "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/GBIF_dwca/countries/Sweden/Citation mapping Sweden.xlsx";
$params["dwca_file"]    = "https://editors.eol.org/other_files/GBIF_DwCA/Sweden.zip";
$params["uri_file"]     = "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/GBIF_dwca/countries/Sweden/GBIF Sweden mapping.xlsx";

$params["dataset"]      = "GBIF";
$params["country"]      = "Sweden";
$params["type"]         = "structured data";
$params["resource_id"]  = 893;

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