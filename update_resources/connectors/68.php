<?php
namespace php_active_record;
/*connector for Dutch Species Catalogue
estimated execution time: 28 minutes
Partner provides an XML from a URL with list of taxa ID's.
The connector loops to this list and compiles each XML to 1 final XML for EOL ingestion.
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
require_library('connectors/DutchSpeciesCatalogueAPI');
$resource_id = 68;
DutchSpeciesCatalogueAPI::combine_all_xmls($resource_id);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
?>
