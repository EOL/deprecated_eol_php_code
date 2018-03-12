<?php
namespace php_active_record;
/* GBIF dwc-a resources: country nodes
SPG provides mappings for values and URI's. The DWC-A file is requested from GBIF's web service.
This connector assembles the data and generates the EOL archive for ingestion.
estimated execution time: this will vary depending on how big the archive file is.

DATA-1557 GBIF national node type records- Germany
Germany:                    10k     5k
taxon:                      6692    3786    80,093
measurementorfact:          28408   14251   639,196
occurrence                  9470    4751    167,663
classification resource:    33,377

872	Sunday 2018-03-11 11:11:45 PM	{"measurement_or_fact.tab":639195,"occurrence.tab":167662,"taxon.tab":80072} - local, no measurementID
872	Monday 2018-03-12 12:09:00 AM	{"measurement_or_fact.tab":639195,"occurrence.tab":167662,"taxon.tab":80072} - local with measurementID

for 1k taxa:
measurement_or_fact.tab     [3655]
occurrence.tab              [970]
taxon.tab                   [875]

*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/GBIFCountryTypeRecordAPI');
$timestart = time_elapsed();

/*
$params["dwca_file"] = "http://localhost/~eolit/cp/GBIF_dwca/atlantic_cod.zip";
$params["dataset"] = "Gadus morhua";
$params["dwca_file"] = "http://localhost/~eolit/cp/GBIF_dwca/birds.zip";
$params["dataset"] = "All audio for birds";
*/

/*// local
$params["citation_file"] = "http://127.0.0.1/cp_new/GBIF_dwca/countries/Germany/Citation Mapping Germany.xlsx";
$params["dwca_file"]     = "http://127.0.0.1/cp_new/GBIF_dwca/countries/Germany/Germany.zip";
$params["uri_file"]      = "http://127.0.0.1/cp_new/GBIF_dwca/countries/Germany/germany mappings.xlsx";
*/

// remote
$params["citation_file"] = "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/GBIF_dwca/countries/Germany/Citation Mapping Germany.xlsx";
$params["dwca_file"]     = "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/GBIF_dwca/countries/Germany/Germany.zip";
$params["uri_file"]      = "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/GBIF_dwca/countries/Germany/germany mappings.xlsx";

$params["dataset"]      = "GBIF";
$params["country"]      = "Germany";
$params["type"]         = "structured data";
$params["resource_id"]  = 872;

// $params["type"]         = "classification resource";
// $params["resource_id"]  = 873;

$resource_id = $params["resource_id"];
$func = new GBIFCountryTypeRecordAPI($resource_id);
$func->export_gbif_to_eol($params);
Functions::finalize_dwca_resource($resource_id, false, true);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>