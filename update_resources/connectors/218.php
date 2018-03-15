<?php
namespace php_active_record;
/* Tropicos Archive resource
estimated execution time:

218	Thursday 2018-02-08 12:42:33 PM	{"agent.tab":383,"measurement_or_fact.tab":2997020,"media_resource.tab":193473,"occurrence.tab":1498510,"reference.tab":21639,"taxon.tab":1298052}
218	Wednesday 2018-03-14 01:43:01 PM{"agent.tab":383,"measurement_or_fact.tab":1498510,"media_resource.tab":193473,"occurrence.tab":1498510,"reference.tab":21639,"taxon.tab":1298052} with measurementID
*/
return; //hasn't been cached yet in Archive. Waiting for extra harddisk space.
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/TropicosArchiveAPI');
$GLOBALS['ENV_DEBUG'] = true;

// $url = "http://localhost/eol_php_code/applications/content_server/resources/1.xml";
// $xml = Functions::get_remote_file($url);
// $xml = simplexml_load_string($xml);
// print_r($xml); exit("\n\n");

$timestart = time_elapsed();

// /* normal operation
$resource_id = 218;
// $resource_id = "218_sample_with_trait_data";
if(!Functions::can_this_connector_run($resource_id)) return;
$func = new TropicosArchiveAPI($resource_id);
$func->get_all_taxa($resource_id);
unset($func);
Functions::finalize_dwca_resource($resource_id);
// */

/* utility
$resource_id = "";
$func = new TropicosArchiveAPI($resource_id);
$func->get_all_countries($resource_id);
*/

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>
