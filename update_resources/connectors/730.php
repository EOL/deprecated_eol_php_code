<?php
namespace php_active_record;
/*
Need structured data connector for GGBN Queries for GGI  (DATA-1372)
Partner provides a data portal to grab their data using family names
estimated execution time:
*/
return;
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/NCBIGGIqueryAPI');
$timestart = time_elapsed();
$resource_id = 730;
$func = new NCBIGGIqueryAPI($resource_id, "ggbn_dna_specimen_info");
$func->get_all_taxa();
Functions::finalize_dwca_resource($resource_id);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>