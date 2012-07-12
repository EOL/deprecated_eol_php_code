<?php
namespace php_active_record;
/* connector for Ecomare
Partner provides DWC-A file
estimated execution time: 18 minutes
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
require_library('connectors/EcomareAPI');
$resource_id = 414;

$dwca_file = "http://localhost/~eolit/ecomare.zip";
$dwca_file = "http://dl.dropbox.com/u/7597512/Ecomare/ecomare.zip";

$func = new EcomareAPI();
$taxa = $func->get_all_taxa($dwca_file);
$xml = \SchemaDocument::get_taxon_xml($taxa);

$resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml";
$OUT = fopen($resource_path, "w");
fwrite($OUT, $xml);
fclose($OUT);

Functions::set_resource_status_to_force_harvest($resource_id);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = " . $elapsed_time_sec . " seconds   \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes   \n";
exit("\n\n Done processing.");
?>