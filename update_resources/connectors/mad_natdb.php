<?php
namespace php_active_record;
/* DATA-1754 */

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/MADtoolNatDBAPI');
$timestart = time_elapsed();

$resource_id = "mad_natdb";
$func = new MADtoolNatDBAPI($resource_id, $dwca_file);

$func->convert_archive(); //main operation
Functions::finalize_dwca_resource($resource_id, false);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>
