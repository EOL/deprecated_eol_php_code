<?php
namespace php_active_record;
/* connector for Avibase - Howard & Moore
estimated execution time: 9 minutes (8.5 hours with synonyms)
Connector scrapes the Avibase site.
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/AvibaseAPI');
$timestart = time_elapsed();
$resource_id = 354;
$avibase = new AvibaseAPI();
$taxonomy = "howardmoore";
/* other available checklists
$taxonomy = "sibleymonroe";
$taxonomy = "clements5";
$taxonomy = "clements";
$taxonomy = "cinfo";
$taxonomy = "ioc";
$taxonomy = "ebird";
$taxonomy = "hbw";
$taxonomy = "aou";
*/
$avibase->get_all_taxa($resource_id, $taxonomy);
Functions::set_resource_status_to_force_harvest($resource_id);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = $elapsed_time_sec seconds             \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes  \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
exit("\n\n Done processing.");
?>