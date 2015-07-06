<?php
namespace php_active_record;
/*
Need structured data connector for GBIF number of records per family for GGI  (DATA-1370)
Partner provides services for us to get the total number of records per family
estimated execution time:
*/
return;
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/NCBIGGIqueryAPI');
$timestart = time_elapsed();
$resource_id = 731;
$func = new NCBIGGIqueryAPI($resource_id, "gbif_info");
$func->get_all_taxa();
Functions::finalize_dwca_resource($resource_id);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>