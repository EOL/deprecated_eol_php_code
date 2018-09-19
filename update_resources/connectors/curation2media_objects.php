<?php
namespace php_active_record;
/* This can be a generic connector for CSV DwCA resources - without meta.xml. */

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/OldCuration2MediaObjects');
$timestart = time_elapsed();

$resource_id = 1;
$func = new OldCuration2MediaObjects($resource_id);
$func->start();
// Functions::finalize_dwca_resource($resource_id);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>
