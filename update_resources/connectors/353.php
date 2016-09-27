<?php
namespace php_active_record;
/* connector for Avibase
estimated execution time: 20 minutes (10 hours with synonyms)
Connector scrapes the Avibase site.
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/AvibaseAPI');
$timestart = time_elapsed();
$resource_id = 353;
$avibase = new AvibaseAPI();
$taxonomy = "avibase";
$avibase->get_all_taxa($resource_id, $taxonomy);
Functions::set_resource_status_to_harvest_requested($resource_id);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = $elapsed_time_sec seconds             \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes  \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\n\n Done processing.";
?>