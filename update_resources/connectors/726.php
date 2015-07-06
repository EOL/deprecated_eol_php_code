<?php
namespace php_active_record;
/*
execution time: 1 minute

measurementorfact:  20307   21993   21520
taxon:              988     989     969
occurrence:                 2839    2839

*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/RotifersTypeSpecimenAPI');

$timestart = time_elapsed();
$resource_id = 726;
$func = new RotifersTypeSpecimenAPI($resource_id);

$func->get_all_taxa();

Functions::finalize_dwca_resource($resource_id);


$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec . " seconds";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>