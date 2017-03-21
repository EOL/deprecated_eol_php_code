<?php
namespace php_active_record;
/* */
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/WikiDataAPI');
$timestart = time_elapsed();
$resource_id = 1;

// /* //main operation
$func = new WikiDataAPI($resource_id, "es");
$func->get_all_taxa();
// Functions::finalize_dwca_resource($resource_id);
// */


$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";


?>