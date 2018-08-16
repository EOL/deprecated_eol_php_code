<?php
namespace php_active_record;
/* This can be a generic connector for CSV DwCA resources - without meta.xml.

*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/TryDatabaseAPI');
$timestart = time_elapsed();

$dwca_file = "http://localhost/cp/TRY/tryv.aug15.zip";

$resource_id = "try_dbase";
$func = new TryDatabaseAPI($resource_id, $dwca_file);
$func->convert_archive();
Functions::finalize_dwca_resource($resource_id);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>
