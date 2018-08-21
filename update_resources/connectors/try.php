<?php
namespace php_active_record;
/* This can be a generic connector for CSV DwCA resources - without meta.xml. */

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/TryDatabaseAPI');
$timestart = time_elapsed();

/* test
$val = array(1,5,2,3,1,5,100,50,5,2);
$val = array_unique($val);
sort($val);
print_r($val);
exit("-test end-");
*/

$resource_id = "try_dbase";
// $resource_id = 1;
$func = new TryDatabaseAPI($resource_id);
$func->convert_archive();
Functions::finalize_dwca_resource($resource_id);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>
