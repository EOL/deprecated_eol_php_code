<?php
namespace php_active_record;
/* DATA-1615 NHM type data acquisition
                    12-Apr      15Apr
measurement_or_fact [2432673]   2433637
occurrence          [293257]    293256
taxon.tab           [155041]    155041

From DATA-1516, the long query where we download the NHM archive:
http://data.nhm.ac.uk/dataset/collection-specimens/resource/05ff2255-c38a-40c9-b657-4ccb55ab2feb?filters=_f%3AscientificName|_f%3AscientificNameAuthorship|_f%3Aclass|_f%3Afamily|_f%3Agenus|_f%3AspecificEpithet|_f%3AinfraspecificEpithet|_f%3Alocality|_f%3AviceCountry|_f%3Acountry|_f%3ArecordedBy|_f%3AtypeStatus|_f%3AcatalogNumber|_f%3AcollectionCode|_has_type%3Atrue

*/

include_once(dirname(__FILE__) . "/../../config/environment.php");

// Functions::count_resource_tab_files(947); exit;

require_library('connectors/NMNHTypeRecordAPI');
$timestart = time_elapsed();

// /* - local source
$params["dwca_file"]    = "http://localhost/cp/NHM/gbif_dwca.zip"; //OLD data
// measurement_or_fact.tab : [2,421,904]
// occurrence.tab          : [291,843]
// taxon.tab               : [154,682]

$params["dwca_file"]    = "http://localhost/cp/NHM/2016 04 21/gbif_dwca.zip"; //NEW data
// measurement_or_fact.tab : [2,478,939]
// occurrence.tab          : [299,325]
// taxon.tab               : [159,245]

$params["uri_file"]     = "http://localhost/cp/NMNH/type_specimen_resource/nmnh-mappings.xlsx"; //renamed; originally [nmnh mappings.xlsx]
// */

/* - remote source
$params["dwca_file"]    = "http://data.nhm.ac.uk/resources/gbif_dwca.zip"; // true value - is not working aymore, was replaced by Jen's long query URL that works. see DATA-1516.
$params["dwca_file"]    = "https://dl.dropboxusercontent.com/u/7597512/NHM/gbif_dwca.zip";
$params["uri_file"]     = "https://opendata.eol.org/dataset/6a470c75-5a7b-4caf-a9e8-8f95ca0e9820/resource/47df3054-bf18-4c74-b273-01a79a6dc50f/download/nmnh-mappings.xlsx";
*/

$params["row_type"]     = "http://rs.tdwg.org/dwc/terms/occurrence";
$params["location"]     = "occurrence.csv";
$params["dataset"]      = "NHM";
$params["type"]         = "structured data";
$params["resource_id"]  = 947;

$resource_id = $params["resource_id"];
$func = new NMNHTypeRecordAPI($resource_id);
$func->start($params); //renamed, it was $func->export_gbif_to_eol() before
Functions::finalize_dwca_resource($resource_id);
Functions::remove_resource_working_dir($resource_id);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>