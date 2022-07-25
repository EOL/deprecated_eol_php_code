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
892	Wed 2021-01-20 10:19:41 PM	    {"measurement_or_fact.tab":527794, "occurrence.tab":75544, "taxon.tab":25667, "time_elapsed":{"sec":411.75, "min":6.86, "hr":0.11}}
892	Thu 2021-02-18 11:35:52 AM	    {"measurement_or_fact.tab":527794, "occurrence.tab":75544, "taxon.tab":25667, "time_elapsed":{"sec":433, "min":7.22, "hr":0.12}}
after applied latest mappings: https://eol-jira.bibalex.org/browse/DATA-1582?focusedCommentId=65633&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-65633
892	Fri 2021-02-19 11:10:52 AM	    {"measurement_or_fact.tab":527794, "occurrence.tab":75544, "taxon.tab":25667, "time_elapsed":{"sec":417.4, "min":6.96, "hr":0.12}}
classification resource:
after automating API download request-then-refresh:
892	Thu 2021-05-20 01:43:13 PM	    {"measurement_or_fact.tab":527794,  "occurrence.tab":75544,  "taxon.tab":25667, "time_elapsed":{"sec":424.03, "min":7.07, "hr":0.12}}
892	Sat 2022-06-18 02:11:30 AM	    {"measurement_or_fact.tab":1409077, "occurrence.tab":206180, "taxon.tab":72150, "time_elapsed":{"sec":1192.74, "min":19.88, "hr":0.33}}
892	Sun 2022-07-24 01:40:52 PM	    {"measurement_or_fact.tab":1409077, "occurrence.tab":206180, "taxon.tab":72150, "time_elapsed":{"sec":1221.79, "min":20.36, "hr":0.34}}
-> after 1 year big increase, will check next harvest if consistent
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
$params["dwca_file"]    = "https://editors.eol.org/other_files/GBIF_DwCA/Brazil_0010183-190918142434337.zip"; //old, constant zip file
$params["dwca_file"]    = "https://editors.eol.org/other_files/GBIF_occurrence/GBIF_Brazil/GBIF_Brazil_DwCA.zip"; //new, changing zip file
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