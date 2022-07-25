<?php
namespace php_active_record;
/* GBIF dwc-a resources: country nodes:
SPG provides mappings for values and URI's. The DWC-A file is requested from GBIF's web service.
This connector assembles the data and generates the EOL archive for ingestion.
estimated execution time: this will vary depending on how big the archive file is.

DATA-1578 GBIF national node type records- Netherlands
measurement_or_fact         [29989] 418450  533799
occurrence                  [9997]  139484  139484
taxon                       [3214]  52763   52763

887	Monday 2018-03-12 03:12:29 AM	{"measurement_or_fact.tab":533798, "occurrence.tab":139483,"taxon.tab":52758}
after automating API download request-then-refresh:
887	Thu 2021-05-20 01:35:59 PM	    {"measurement_or_fact.tab":202273, "occurrence.tab":31837, "taxon.tab":17599, "time_elapsed":{"sec":415.35, "min":6.92, "hr":0.12}}
-> big decrease but it is what it is
887	Sat 2022-06-18 01:51:29 AM	    {"measurement_or_fact.tab":164148, "occurrence.tab":26382, "taxon.tab":16456, "time_elapsed":{"sec":436.29, "min":7.27, "hr":0.12}}
887	Sun 2022-07-24 01:20:20 PM	    {"measurement_or_fact.tab":164148, "occurrence.tab":26382, "taxon.tab":16456, "time_elapsed":{"sec":454.91, "min":7.58, "hr":0.13}}
-> decrease continued for whatever reason; maybe cleaning in GBIF




classification resource: seems no record logged for this resource yet.
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/GBIFCountryTypeRecordAPI');
$timestart = time_elapsed();

/* local
$params["citation_file"] = "http://localhost/cp_new/GBIF_dwca/countries/Netherlands/Citation mapping Netherlands.xlsx";
$params["dwca_file"]    = "http://localhost/cp_new/GBIF_dwca/countries/Netherlands/Netherlands.zip";
$params["uri_file"]     = "http://localhost/cp_new/GBIF_dwca/countries/Netherlands/GBIF Netherlands mapping.xlsx";
*/

//remote
$params["citation_file"] = "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/GBIF_dwca/countries/Netherlands/Citation mapping Netherlands.xlsx";
$params["dwca_file"]    = "https://editors.eol.org/other_files/GBIF_DwCA/Netherlands_0010181-190918142434337.zip"; //old, constant zip file
$params["dwca_file"]    = "https://editors.eol.org/other_files/GBIF_occurrence/GBIF_Netherlands/GBIF_Netherlands_DwCA.zip"; //new, changing zip file
$params["uri_file"]     = "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/GBIF_dwca/countries/Netherlands/GBIF Netherlands mapping.xlsx";

$params["dataset"]      = "GBIF";
$params["country"]      = "Netherlands";
$params["type"]         = "structured data";
$params["resource_id"]  = 887;

// $params["type"]         = "classification resource";
// $params["resource_id"]  = 1;

$resource_id = $params["resource_id"];
$func = new GBIFCountryTypeRecordAPI($resource_id);
$func->export_gbif_to_eol($params);
Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
?>