<?php
namespace php_active_record;
/* GBIF dwc-a resources: country nodes
SPG provides mappings for values and URI's. The DWC-A file is requested from GBIF's web service.
This connector assembles the data and generates the EOL archive for ingestion.
estimated execution time: this will vary depending on how big the archive file is.

https://eol-jira.bibalex.org/browse/DATA-1557
DATA-1557 GBIF national node type records- Germany
Germany:                    10k     5k
taxon:                      6692    3786    80,093
measurementorfact:          28408   14251   639,196
occurrence                  9470    4751    167,663

classification resource:    33,377
873	Monday 2018-03-12 07:20:24 AM	{"taxon.tab":33377} - MacMini
873	Monday 2018-03-12 08:43:59 AM	{"taxon.tab":33351} - editors.eol.org

872	Sunday 2018-03-11 11:11:45 PM	{"measurement_or_fact.tab":639195,"occurrence.tab":167662,"taxon.tab":80072} - local, no measurementID
872	Monday 2018-03-12 12:09:00 AM	{"measurement_or_fact.tab":639195,"occurrence.tab":167662,"taxon.tab":80072} - local with measurementID - MacMini

872	Friday 2019-10-04 08:09:27 PM	{"measurement_or_fact.tab":1049247, "occurrence.tab":271867, "taxon.tab":151012, "time_elapsed":{"sec":1029.14, "min":17.15, "hr":0.29}}
872	Tue 2021-01-19 08:54:17 AM	    {"measurement_or_fact.tab":1049247, "occurrence.tab":271867, "taxon.tab":151012, "time_elapsed":{"sec":1245.83, "min":20.76, "hr":0.35}}
start of moving OCCURRENCE cols to MoF rows with mOfTaxon = false (DATA-1875):
872	Tue 2021-01-19 09:15:26 PM	    {"measurement_or_fact.tab":1863513, "occurrence.tab":271867, "taxon.tab":151012, "time_elapsed":{"sec":1451.96, "min":24.2, "hr":0.4}}
after automating API download request-then-refresh:
872	Thu 2021-05-20 01:05:07 PM	    {"measurement_or_fact.tab":1863513, "occurrence.tab":271867, "taxon.tab":151012, "time_elapsed":{"sec":1434.46, "min":23.91, "hr":0.4}}
872	Sat 2021-12-18 01:16:00 AM	    {"measurement_or_fact.tab":1910814, "occurrence.tab":279289, "taxon.tab":140395, "time_elapsed":{"sec":1557.49, "min":25.96, "hr":0.43}}
872	Fri 2022-03-18 01:18:55 AM	    {"measurement_or_fact.tab":1909706, "occurrence.tab":279129, "taxon.tab":140307, "time_elapsed":{"sec":1734.49, "min":28.91, "hr":0.48}}
872	Sat 2022-06-18 01:17:42 AM	    {"measurement_or_fact.tab":1909706, "occurrence.tab":279129, "taxon.tab":140307, "time_elapsed":{"sec":1660.33, "min":27.67, "hr":0.46}}
872	Sat 2022-06-18 01:17:42 AM	    {"measurement_or_fact.tab":1909706, "occurrence.tab":279129, "taxon.tab":140307, "time_elapsed":{"sec":1660.33, "min":27.67, "hr":0.46}}
872	Sun 2022-07-24 12:46:07 PM	    {"measurement_or_fact.tab":1909706, "occurrence.tab":279129, "taxon.tab":140307, "time_elapsed":{"sec":1709.95, "min":28.5, "hr":0.47}}

for 1k taxa:
measurement_or_fact.tab     [3655]
occurrence.tab              [970]
taxon.tab                   [875]
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/GBIFCountryTypeRecordAPI');
$timestart = time_elapsed();

$cmdline_params['jenkins_or_cron']     = @$argv[1]; //irrelevant here
$cmdline_params['classificationYesNo'] = @$argv[2]; //useful here
// print_r($cmdline_params);

// $a['aaa'] = 'letter a';
// $b['aaa'] = 'letter b';
// $c = array_merge($a, $b);
// print_r($c); exit;

/*
$params["dwca_file"] = "http://localhost/~eolit/cp/GBIF_dwca/atlantic_cod.zip";
$params["dataset"] = "Gadus morhua";
$params["dwca_file"] = "http://localhost/~eolit/cp/GBIF_dwca/birds.zip";
$params["dataset"] = "All audio for birds";
*/

/* local
$params["citation_file"] = "http://127.0.0.1/cp_new/GBIF_dwca/countries/Germany/Citation Mapping Germany.xlsx";
$params["dwca_file"]     = "http://127.0.0.1/cp_new/GBIF_dwca/countries/Germany_0010139-190918142434337.zip";
$params["uri_file"]      = "http://127.0.0.1/cp_new/GBIF_dwca/countries/Germany/germany mappings.xlsx";
*/

// remote
$params["citation_file"] = "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/GBIF_dwca/countries/Germany/Citation Mapping Germany.xlsx";
$params["dwca_file"]     = "https://editors.eol.org/other_files/GBIF_DwCA/Germany_0010139-190918142434337.zip"; //old, constant zip file
$params["dwca_file"]    = "https://editors.eol.org/other_files/GBIF_occurrence/GBIF_Germany/GBIF_Germany_DwCA.zip"; //new, changing zip file
$params["uri_file"]      = "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/GBIF_dwca/countries/Germany/germany mappings.xlsx";

$params["dataset"]      = "GBIF";
$params["country"]      = "Germany";
$params["type"]         = "structured data";
$params["resource_id"]  = 872;


if($cmdline_params['classificationYesNo'] == "classification") { //GBIF national node classification resource: Germany -> http://www.eol.org/content_partners/4/resources/873
    $params["type"]         = "classification resource";
    $params["resource_id"]  = 873;
}

$resource_id = $params["resource_id"];
$func = new GBIFCountryTypeRecordAPI($resource_id);
$func->export_gbif_to_eol($params);
Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
?>