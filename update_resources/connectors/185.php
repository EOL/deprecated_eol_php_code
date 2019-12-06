<?php
namespace php_active_record;
/* Turbellarian Taxonomic Database
estimated execution time:

This is now the new connector: https://eol-jira.bibalex.org/browse/TRAM-709 (June 2018)
185	Thursday 2018-08-02 09:23:38 PM	{"agent.tab":4,"measurement_or_fact.tab":9419,"media_resource.tab":3187,"occurrence.tab":4955,"reference.tab":2283,"taxon.tab":11308} eol-archive
185	Friday 2019-12-06 07:06:21 AM	{"agent.tab":4,"measurement_or_fact.tab":9352,"media_resource.tab":3285,"occurrence.tab":4961,"reference.tab":2296,"taxon.tab":11249,"time_elapsed":{"sec":2230.16,"min":37.17,"hr":0.62}}
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/TurbellarianAPI_v2');
$timestart = time_elapsed();
$resource_id = 185;
$func = new TurbellarianAPI_v2($resource_id);
$func->start();
Functions::finalize_dwca_resource($resource_id, false, false, $timestart);

// /* utility ==========================
require_library('connectors/DWCADiagnoseAPI');
$func = new DWCADiagnoseAPI();

$undefined_parents = $func->check_if_all_parents_have_entries($resource_id, false); //true means output will write to text file
echo "\nTotal undefined parents:" . count($undefined_parents)."\n"; unset($undefined_parents);

// working but may not be useful since there are synonyms and these normally don't have parents
// $without = $func->get_all_taxa_without_parent($resource_id, false); //true means output will write to text file
// echo "\nTotal taxa without parents:" . count($without)."\n";
// print_r($without);
// =====================================*/

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>