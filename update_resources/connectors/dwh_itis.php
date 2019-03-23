<?php
namespace php_active_record;
/* TRAM-804 */
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/DWH_ITIS_API');
$timestart = time_elapsed();

$dwca_file = "http://localhost/cp/ITIS_DWH/itisMySQLTables.tar.gz";
// $dwca_file = "https://www.itis.gov/downloads/itisMySQLTables.tar.gz";
$resource_id = "itis"; //
$func = new DWH_ITIS_API($resource_id, $dwca_file);

$func->convert_archive(); //main operation
Functions::finalize_dwca_resource($resource_id, false, true);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>
