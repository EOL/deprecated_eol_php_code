<?php
namespace php_active_record;
/* */
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/WikiDataAPI');
$timestart = time_elapsed();
$resource_id = 1;


// /* //main operation
// $func = new WikiDataAPI($resource_id, "es");
// $func = new WikiDataAPI($resource_id, "fr");
// $func = new WikiDataAPI("957", "de");
$func = new WikiDataAPI($resource_id, "en");
// $func = new WikiDataAPI($resource_id, "cu");


$func->get_all_taxa();
Functions::finalize_dwca_resource($resource_id);
// */

// /* utility
require_library('connectors/DWCADiagnoseAPI');
$func = new DWCADiagnoseAPI();
$func->check_if_all_parents_have_entries($resource_id, true); //true means output will write to text file
// */


$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";

?>