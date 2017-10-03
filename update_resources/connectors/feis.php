<?php
namespace php_active_record;
/* DATA-1629
FEIS structured data: habitat and growth
                                Jul22
measurements    5711    6020    5548
occurrences     5372    5681    5548
taxa            315     316     316
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/FEISDataAPI');
$timestart = time_elapsed();
$resource_id = 1;
$func = new FEISDataAPI($resource_id);
$func->get_all_taxa();
Functions::finalize_dwca_resource($resource_id);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>