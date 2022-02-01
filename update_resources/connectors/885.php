<?php
namespace php_active_record;
/* execution time: 3 days, 11 hours in eol-archive
https://jira.eol.org/browse/DATA-1549 iDigBio Portal 
                5k                  4Feb
measurement     6748    1385056     3191194
occurrence      2250    461686      461686
taxon           2157    224065      224065
reference							189866
885	Monday 2018-03-12 02:12:34 PM	{"MoF":3263772, "occurrence.tab":475706, "reference.tab":195897, "taxon.tab":215141}
885	Monday 2018-08-06 12:37:07 PM	{"MoF":3263859, "occurrence.tab":475729, "reference.tab":195897, "taxon.tab":215138} eol-archive
885	Monday 2019-10-07 07:34:29 PM	{"MoF":3263760, "occurrence.tab":475704, "reference.tab":195897, "taxon.tab":215117, "time_elapsed":{"sec":67953.72,"min":1132.56,"hr":18.88}}
885	Fri 2021-01-22 07:13:38 AM	    {"MoF":4590822, "occurrence.tab":475704, "reference.tab":195897, "taxon.tab":215117, "time_elapsed":{"sec":73431.03999999999, "min":1223.85, "hr":20.4}}
885	Tue 2021-03-16 09:55:02 PM	    {"MoF":4588886, "occurrence.tab":475435, "reference.tab":195628, "taxon.tab":215103, "time_elapsed":{"sec":67327.67, "min":1122.13, "hr":18.7}}
885	Thu 2021-11-18 06:07:04 AM	    {"MoF":4588886, "occurrence.tab":475435, "reference.tab":195628, "taxon.tab":215103, "time_elapsed":{"sec":72988.62, "min":1216.48, "hr":20.27}}
Assigned occurrence->lifeStage to 'adult' URI (http://www.ebi.ac.uk/efo/EFO_0001272) for raw lifeStage value = 'copula'.
885	Tue 2021-12-14 08:28:12 PM	    {"MoF":4588886, "occurrence.tab":475435, "reference.tab":195628, "taxon.tab":215103, "time_elapsed":{"sec":67074.33, "min":1117.91, "hr":18.63}}
convert MeasurementOfTaxon=false to child records
885	Thu 2021-12-16 11:16:34 PM	    {"MoF":4588886, "occurrence.tab":475435, "reference.tab":195628, "taxon.tab":215103, "time_elapsed":{"sec":67524.58, "min":1125.41, "hr":18.76}}
remove bad data from source file. e.g. taxon_id = "University". Reported here: https://eol-jira.bibalex.org/browse/DATA-1549?focusedCommentId=66616&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-66616
885	Thu 2022-01-20 01:10:30 AM	    {"MoF":4588860, "occurrence.tab":475432, "reference.tab":195627, "taxon.tab":215102, "time_elapsed":{"sec":71162.84, "min":1186.05, "hr":19.77}}
below start where taxa are cleaned
885	Thu 2022-01-27 03:07:58 AM	    {"MoF":4588860, "occurrence.tab":475432, "reference.tab":195627, "taxon.tab":215027, "time_elapsed":{"sec":69426.34, "min":1157.11, "hr":19.29}}
-> wrong as MoF, Occur and Ref were not deducted from removed taxa
885	Fri 2022-01-28 11:53:51 PM	    {"MoF":4586834, "occurrence.tab":475230, "reference.tab":195613, "taxon.tab":215041, "time_elapsed":{"sec":71989.33, "min":1199.82, "hr":20}}
-> correct as MoF, Occur and Ref are now deducted
885	Mon 2022-01-31 12:45:30 PM	    {"MoF":4586834, "occurrence.tab":475230, "reference.tab":195613, "taxon.tab":215041, "time_elapsed":{"sec":71221.27, "min":1187.02, "hr":19.78}}

Undefined:
[institution] => Array
       (
           [University of California Museum of Paleontology {http://ucmpdb.berkeley.edu}] => 
           [George Safford Torrey Herbarium (CONN) {http://bgbaseserver.eeb.uconn.edu/}] => 
           [MSB Parasite Collection (Arctos) {http://msb.unm.edu/divisions/parasites/index.html}] => 
           [Ohio State University Fish Division (OSUM) {http://fish-division.osu.edu​}] => 
           [Kathryn Kalmbach Herbarium {http://www.botanicgardens.org/content/kathryn-kalmbach-herbarium}] => 
           [Illinois Natural History Survey] => 
           [(BMNH, London, U. K.)] => 
       )
*/
// return;
include_once(dirname(__FILE__) . "/../../config/environment.php");
ini_set('memory_limit','7096M'); //needed so it can process checking of identifier uniqueness in measurement and occurrence extensions.

/* just a utility - this is already inside -> Functions::finalize_dwca_resource($resource_id);
require_library('connectors/DWCADiagnoseAPI');
$func = new DWCADiagnoseAPI();
$func->check_unique_ids(885); //885
return;
*/

require_library('connectors/GBIFCountryTypeRecordAPI');
$timestart = time_elapsed();

/*
//local
$params["dwca_file"]    = "http://localhost/cp/iDigBio/iDigBioTypes.zip";
$params["uri_file"]     = "http://localhost/cp/iDigBio/idigbio mappings.xlsx";
*/

// /*
//remote
$params["dwca_file"]    = "https://editors.eol.org/eol_connector_data_files/iDigBio/iDigBioTypes.zip";
$params["uri_file"]     = "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/iDigBio/idigbio mappings.xlsx";
// */

$params["dataset"]      = "iDigBio";
$params["type"]         = "structured data";

$fields["institutionCode"]  = "institutionCode_uri";
$fields["sex"]              = "sex_uri";
$fields["typeStatus"]       = "typeStatus_uri";
$fields["lifeStage"]        = "lifeStage_uri";
$params["fields"] = $fields;

$params["resource_id"] = 885;

$resource_id = $params["resource_id"];
$func = new GBIFCountryTypeRecordAPI($resource_id);
$func->export_gbif_to_eol($params);
Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
?>