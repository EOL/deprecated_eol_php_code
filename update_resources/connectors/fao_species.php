<?php
namespace php_active_record;
/*
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/FAOSpeciesAPI');
ini_set('memory_limit','7096M'); //required for checking uniqueness of measurementID
$timestart = time_elapsed();
$resource_id = 'fao_species';
$func = new FAOSpeciesAPI($resource_id);
$func->start();
Functions::finalize_dwca_resource($resource_id);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec . " seconds";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>