<?php
namespace php_active_record;
/* DATA-1589 assist EOL China with xml resource

This is a generic script that will convert EOL XML to EOL DWC-A

http://rs.gbif.org/terms/1.0/vernacularname:
                                zh:         2667
                                en:         993
                                Total:      3660

http://rs.tdwg.org/dwc/terms/taxon:         2300
http://eol.org/schema/reference/reference:  1690
http://eol.org/schema/agent/agent:          104

http://purl.org/dc/dcmitype/Text:           5650
http://purl.org/dc/dcmitype/StillImage:     464
                                    Total:  6114

*21.php also uses this script.
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/ConvertEOLtoDWCaAPI');
$timestart = time_elapsed();

$params["eol_xml_file"] = "http://localhost/cp/EOL_China/FaunaSinica_Aves.zip";
$params["eol_xml_file"] = "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/EOL_China/FaunaSinica_Aves.zip";
$params["filename"]     = "FaunaSinica_Aves.xml";
$params["dataset"]      = "EOL China";
$params["resource_id"]  = 412;

/* Sample way to access the generic script of converting EOL XML to EOL DWCA
$params["eol_xml_file"] = "http://localhost/eol_php_code/applications/content_server/resources/511.xml.gz";
$params["filename"]     = "511.xml";
$params["dataset"]      = "EOL XML";
$params["resource_id"]  = 1;
*/

$resource_id = $params["resource_id"];
$func = new ConvertEOLtoDWCaAPI($resource_id);
$func->export_xml_to_archive($params);
Functions::finalize_dwca_resource($resource_id, false, true);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>