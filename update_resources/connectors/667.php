<?php
namespace php_active_record;
/* AlgaeBase Classification - with text objects
estimated execution time: 3.5 minutes
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/AlgaebaseClassificationAPI');

$timestart = time_elapsed();
$resource_id = 667;
$func = new AlgaebaseClassificationAPI($resource_id);
$func->get_all_taxa();
Functions::finalize_dwca_resource($resource_id);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>