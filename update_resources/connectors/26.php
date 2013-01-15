<?php
namespace php_active_record;
/* connector for WORMS
Partner provides a service to get their list of IDs and another service to use the id to get each taxon information.
estimated execution time:
sample API: http://www.marinespecies.org/aphia.php?p=eol&action=taxdetails&id=571925
*/

define('DOWNLOAD_WAIT_TIME', '200000');
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
require_library('connectors/WormsAPI');
$resource_id = 26;

$worms = new WormsAPI();
$worms->initialize_text_files();
// Functions::kill_running_connectors($resource_id);
$worms->start_process($resource_id, false);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = " . $elapsed_time_sec . " seconds \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo date('Y-m-d h:i:s a', time())."\n";
echo "\n\n Done processing.";
?>
