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

893	Monday 2018-03-12 03:06:53 AM	{"measurement_or_fact.tab":341938, "occurrence.tab":87627, "taxon.tab":50393}
893	Tue 2021-01-19 10:18:36 AM	    {"measurement_or_fact.tab":341938, "occurrence.tab":87627, "taxon.tab":50393, "time_elapsed":{"sec":374.42, "min":6.24, "hr":0.1}}
893	Fri 2021-02-19 11:18:12 AM	    {"measurement_or_fact.tab":517192, "occurrence.tab":87627, "taxon.tab":50393, "time_elapsed":{"sec":432.29, "min":7.2, "hr":0.12}}
after automating API download request-then-refresh:
893	Thu 2021-05-20 11:38:35 AM	    {"measurement_or_fact.tab":517192, "occurrence.tab":87627, "taxon.tab":50393, "time_elapsed":{"sec":434.71, "min":7.25, "hr":0.12}}
893	Fri 2021-05-21 08:39:30 AM	    {"measurement_or_fact.tab":517192, "occurrence.tab":87627, "taxon.tab":50393, "time_elapsed":{"sec":460.9, "min":7.68, "hr":0.13}}
893	Sat 2022-06-18 02:23:14 AM	    {"measurement_or_fact.tab":760582, "occurrence.tab":129106, "taxon.tab":63050, "time_elapsed":{"sec":696.1, "min":11.6, "hr":0.19}}
893	Sun 2022-07-24 01:52:41 PM	    {"measurement_or_fact.tab":760582, "occurrence.tab":129106, "taxon.tab":63050, "time_elapsed":{"sec":695.83, "min":11.6, "hr":0.19}}
-> after 1 year moderate increase, will check next harvest if consistent

classification resource:
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/GBIFCountryTypeRecordAPI');
$timestart = time_elapsed();

/* local
$params["citation_file"] = "http://localhost/cp_new/GBIF_dwca/countries/Sweden/Citation mapping Sweden.xlsx";
$params["dwca_file"]    = "http://localhost/cp_new/GBIF_dwca/countries/Sweden/Sweden_0010142-190918142434337.zip";
$params["uri_file"]     = "http://localhost/cp_new/GBIF_dwca/countries/Sweden/GBIF Sweden mapping.xlsx";
*/

//remote
$params["citation_file"] = "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/GBIF_dwca/countries/Sweden/Citation mapping Sweden.xlsx";
$params["dwca_file"]    = "https://editors.eol.org/other_files/GBIF_DwCA/Sweden.zip"; //old, constant zip file
$params["dwca_file"]    = "https://editors.eol.org/other_files/GBIF_occurrence/GBIF_Sweden/GBIF_Sweden_DwCA.zip"; //new, changing zip file
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
Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
?>