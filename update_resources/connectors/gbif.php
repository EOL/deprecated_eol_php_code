<?php
namespace php_active_record;
/* GBIF dwc-a resources
This processes GBIF DwCA datasets.
estimated execution time: this will vary depending on how big the archive file is or how big the dataset is.
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/GBIFdwcaAPI');

$timestart = time_elapsed();
$resource_id = 1;

$params["dwca_file"] = "http://localhost/cp/GBIF_dwca/birds.zip";
$params["dwca_file"] = "http://localhost/cp/GBIF_dwca/countries/Germany.zip";
$params["dataset"] = "All audio for birds";

$func = new GBIFdwcaAPI($resource_id);

// $func->check_parent_child_links(); exit;

$func->export_gbif_to_eol($params);
Functions::finalize_dwca_resource($resource_id);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>
