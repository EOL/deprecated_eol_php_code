<?php
namespace php_active_record;
/* Tropicos Archive resource
estimated execution time:
*/
// return;
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/TropicosArchiveAPI');

// $url = "http://localhost/eol_php_code/applications/content_server/resources/1.xml";
// $xml = Functions::get_remote_file($url);
// $xml = simplexml_load_string($xml);
// print_r($xml); exit("\n\n");

$timestart = time_elapsed();
$resource_id = 218;
if(!Functions::can_this_connector_run($resource_id)) return;
$func = new TropicosArchiveAPI($resource_id);

$func->get_all_taxa($resource_id);
Functions::finalize_dwca_resource($resource_id);


$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>
