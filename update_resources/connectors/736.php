<?php
namespace php_active_record;
/* EOL Metrics
estimated execution time:
http://eol.org/content_partners/641/resources/736
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/EolStatsDataConnector');
$timestart = time_elapsed();
$resource_id = 736;
$func = new EolStatsDataConnector($resource_id);
$func->begin();

Functions::finalize_dwca_resource($resource_id);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>
