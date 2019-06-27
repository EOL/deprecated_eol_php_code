<?php
namespace php_active_record;
/* DATA-1812 
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/GloBIDataAPI');
$timestart = time_elapsed();
$resource_id = "globi";
$func = new GloBIDataAPI($resource_id);

$func->start();
Functions::finalize_dwca_resource($resource_id, false, true);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>
