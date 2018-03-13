<?php
namespace php_active_record;
/* https://jira.eol.org/browse/DATA-1549 iDigBio Portal 
                5k                  4Feb
measurement     6748    1385056     3191194
occurrence      2250    461686      461686
taxon           2157    224065      224065
reference							189866
885	Monday 2018-03-12 02:12:34 PM	{"measurement_or_fact.tab":3263772,"occurrence.tab":475706,"reference.tab":195897,"taxon.tab":215141}
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

// /*
//local
$params["dwca_file"]    = "http://localhost/cp/iDigBio/iDigBioTypes.zip";
$params["uri_file"]     = "http://localhost/cp/iDigBio/idigbio mappings.xlsx";
// */

// /*
//remote
// $params["dwca_file"]    = ""; //will move to Archive once it gets more hardisk space
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
Functions::finalize_dwca_resource($resource_id);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>