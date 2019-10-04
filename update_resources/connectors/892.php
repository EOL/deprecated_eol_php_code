<?php
namespace php_active_record;
/* GBIF dwc-a resources: country nodes
SPG provides mappings for values and URI's. The DWC-A file is requested from GBIF's web service.
This connector assembles the data and generates the EOL archive for ingestion.
estimated execution time: this will vary depending on how big the archive file is.

DATA-1582 GBIF national node type records- Brazil
measurement_or_fact         70725   49470
occurrence                  23159   12434
taxon                       12662   5954

892	Monday 2018-03-12 02:44:43 AM	{"measurement_or_fact.tab":49469,"occurrence.tab":12433,"taxon.tab":5953}

classification resource:
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/GBIFCountryTypeRecordAPI');
$timestart = time_elapsed();

/* local
$params["citation_file"] = "http://localhost/cp_new/GBIF_dwca/countries/Brazil/Citation Mapping Brazil.xlsx";
$params["dwca_file"]    = "http://localhost/cp_new/GBIF_dwca/countries/Brazil_0010183-190918142434337.zip";
$params["uri_file"]     = "http://localhost/cp_new/GBIF_dwca/countries/Brazil/GBIF Brazil mapping.xlsx";
*/

//remote
$params["citation_file"] = "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/GBIF_dwca/countries/Brazil/Citation Mapping Brazil.xlsx";
$params["dwca_file"]    = "https://editors.eol.org/other_files/GBIF_DwCA/Brazil_0010183-190918142434337.zip";
$params["uri_file"]     = "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/GBIF_dwca/countries/Brazil/GBIF Brazil mapping.xlsx";

$params["dataset"]      = "GBIF";
$params["country"]      = "Brazil";
$params["type"]         = "structured data";
$params["resource_id"]  = 892;

// $params["type"]         = "classification resource";
// $params["resource_id"]  = 1;

$resource_id = $params["resource_id"];
$func = new GBIFCountryTypeRecordAPI($resource_id);
$func->export_gbif_to_eol($params);
Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
?>