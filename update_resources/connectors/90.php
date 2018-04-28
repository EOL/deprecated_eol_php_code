<?php
namespace php_active_record;
/*
http://www.iucn-tftsg.org/pub-chron/
estimated execution time: 6 minutes
Connector screen scrapes the partner website.
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/ConservationBiologyTurtlesAPI');
$timestart = time_elapsed();

$resource_id = 90;
$func = new ConservationBiologyTurtlesAPI($resource_id);
$func->get_all_taxa();

Functions::finalize_dwca_resource($resource_id, false, true);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = $elapsed_time_sec seconds             \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes  \n";
exit("\n\n Done processing.");
?>