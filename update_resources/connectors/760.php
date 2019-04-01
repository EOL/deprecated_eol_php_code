<?php
namespace php_active_record;
/* execution time: 1 hr 27 min eol-archive
760	Tuesday 2018-08-07 05:35:51 AM	{"measurement_or_fact.tab":163697,"occurrence.tab":163681,"reference.tab":28473,"taxon.tab":2112,"vernacular_name.tab":1785} eol-archive
760	Monday 2019-04-01 12:23:05 AM	{"measurement_or_fact.tab":163699,"occurrence.tab":163683,"reference.tab":28458,"taxon.tab":2112,"vernacular_name.tab":1785} MacMini
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/InvasiveSpeciesCompendiumAPI');
$timestart = time_elapsed();
$resource_id = 760;

$func = new InvasiveSpeciesCompendiumAPI($resource_id);
$func->generate_invasiveness_data();
Functions::finalize_dwca_resource($resource_id, false, true); //3rd param true means to delete folder in /resources/

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec . " seconds";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>