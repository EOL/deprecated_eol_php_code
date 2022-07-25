<?php
namespace php_active_record;
/* GBIF dwc-a resources: country nodes
SPG provides mappings for values and URI's. The DWC-A file is requested from GBIF's web service.
This connector assembles the data and generates the EOL archive for ingestion.
estimated execution time: this will vary depending on how big the archive file is.

DATA-1583 GBIF national node type records- UK
                            9Feb
measurement_or_fact         499599
occurrence                  135122
taxon                       81258

894	Monday 2018-03-12 03:31:57 AM	{"measurement_or_fact.tab":499598,  "occurrence.tab":135121, "taxon.tab":81241}
after automating API download request-then-refresh:
894	Fri 2021-05-21 09:18:39 AM	    {"measurement_or_fact.tab":3034298, "occurrence.tab":444151, "taxon.tab":233336, "time_elapsed":{"sec":2339, "min":38.98, "hr":0.65}}
894	Sat 2022-06-18 03:07:50 AM	    {"measurement_or_fact.tab":3222117, "occurrence.tab":472440, "taxon.tab":225818, "time_elapsed":{"sec":2667.74, "min":44.46, "hr":0.74}}
894	Sun 2022-07-24 02:37:27 PM	    {"measurement_or_fact.tab":3222117, "occurrence.tab":472440, "taxon.tab":225818, "time_elapsed":{"sec":2678.83, "min":44.65, "hr":0.74}} consistent OK
classification resource:
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/GBIFCountryTypeRecordAPI');
$timestart = time_elapsed();

/* local
$params["citation_file"] = "http://localhost/cp_new/GBIF_dwca/countries/UK/Citation Mapping UK.xlsx";
$params["dwca_file"]     = "http://localhost/cp_new/GBIF_dwca/countries/UK/UK.zip";
$params["uri_file"]      = "http://localhost/cp_new/GBIF_dwca/countries/UK/GBIF UK mapping.xlsx";
*/

// remote
$params["citation_file"] = "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/GBIF_dwca/countries/UK/Citation Mapping UK.xlsx";
$params["dwca_file"]    = "https://editors.eol.org/other_files/GBIF_DwCA/UK_0010147-190918142434337.zip"; //old, constant zip file
$params["dwca_file"]    = "https://editors.eol.org/other_files/GBIF_occurrence/GBIF_UnitedKingdom/GBIF_UnitedKingdom_DwCA.zip"; //new, changing zip file
$params["uri_file"]     = "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/GBIF_dwca/countries/UK/GBIF UK mapping.xlsx";

$params["dataset"]      = "GBIF";
$params["country"]      = "UK";
$params["type"]         = "structured data";
$params["resource_id"]  = 894;

// $params["type"]         = "classification resource";
// $params["resource_id"]  = 1;

$resource_id = $params["resource_id"];
$func = new GBIFCountryTypeRecordAPI($resource_id);
$func->export_gbif_to_eol($params);
Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
?>