<?php
namespace php_active_record;
/* execution time:  */

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/InvasiveSpeciesCompendiumAPI');
$timestart = time_elapsed();
$resource_id = 760;

$func = new InvasiveSpeciesCompendiumAPI($resource_id);
$func->generate_invasiveness_data();
Functions::finalize_dwca_resource($resource_id);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec . " seconds";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>