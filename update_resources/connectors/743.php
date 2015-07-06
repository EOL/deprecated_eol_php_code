<?php
namespace php_active_record;
/*
Need structured data connector for BHL number of records per family for GGI  (DATA-1417)
Partner provides services for us to get the total number of records per family
estimated execution time:
*/
return;
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/NCBIGGIqueryAPI');
$timestart = time_elapsed();
$resource_id = 743;
$func = new NCBIGGIqueryAPI($resource_id, "bhl_info");
$func->get_all_taxa();
Functions::finalize_dwca_resource($resource_id);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>