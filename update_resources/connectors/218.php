<?php
namespace php_active_record;
/* Tropicos Archive resource
estimated execution time:
*/
return;
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/TropicosArchiveAPI');

$timestart = time_elapsed();
$resource_id = 218;
$func = new TropicosArchiveAPI($resource_id);

$func->get_all_taxa($resource_id);
Functions::finalize_dwca_resource($resource_id);


$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>
