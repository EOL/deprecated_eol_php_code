<?php
namespace php_active_record;
/* execution time:  */

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/InvasiveSpeciesDataConnector');
$timestart = time_elapsed();
$resource_id = 751;

$func = new InvasiveSpeciesDataConnector($resource_id, "GISD");
$func->generate_invasiveness_data();
Functions::finalize_dwca_resource($resource_id, false, true);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec . " seconds";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>