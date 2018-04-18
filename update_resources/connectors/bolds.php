<?php
namespace php_active_record;
/* https://eol-jira.bibalex.org/browse/TRAM-737
BOLDS connector for new API service
estimated execution time:
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/BOLDSNewAPI');

// $json = Functions::lookup_with_cache("http://www.boldsystems.org/index.php/API_Tax/TaxonData?taxId=205794&dataTypes=all");
// print_r(json_decode($json, true));
// exit;

$timestart = time_elapsed();
$resource_id = "a";
$func = new BOLDSNewAPI($resource_id);

$func->start();
Functions::finalize_dwca_resource($resource_id);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>
