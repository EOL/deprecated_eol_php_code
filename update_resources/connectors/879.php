<?php
namespace php_active_record;
/* DATA-1544 Structured data from wikipedia for fungi

undefined needs mapping: as of Mar 14, 2018. Might need to ask Jen to update spreadsheet mapping used.
Array(
    [edibility: edible or can cause allergic reactions] => 
    [edibility: edible, but unpalatable or unknown] => 
    [edibility: edible or psychoactive] => 
    [edibility: psychoactive or '] => 
    [spore print is purple-black to olive] => 
    [edibility: not recommended or deadly] => 
    [edibility: can cause allergic reactions or deadly] => 
)

undefined needs mapping: as of Jan 27, 2021. Might need to ask Jen to update spreadsheet mapping used.
Array(
    [edibility: psychoactive or '] => 
    [edibility: inedible or psychoactive] => 
    [edibility: edible or psychoactive] => 
    [spore print is purple-black to olive] => 
    [edibility: edible or can cause allergic reactions] => 
    [cap is flat or offset] => 
    [edibility: deadly or choice] => 
    [edibility: inedible or poisonous] => 
    [gills on hymenium or flat] => 
    [cap is convex or offset] => 
    [spore print is yellow to olive] => 
    [hymenium is subdecurrent or adnate] => 
)

879	Wednesday 2018-03-14 12:30:56 AM	{"measurement_or_fact.tab":9875,"occurrence.tab":6250,"taxon.tab":882} - with measurementID
879	Wednesday 2018-03-14 01:11:54 AM	{"measurement_or_fact.tab":6628,"occurrence.tab":6250,"taxon.tab":882} - with unique measurementID
879	Tue 2021-01-26 08:27:03 PM	        {"measurement_or_fact.tab":5155, "occurrence.tab":4881, "taxon.tab":901, "time_elapsed":false}
879	Tue 2021-01-26 09:02:19 PM	        {"measurement_or_fact.tab":6707, "occurrence.tab":6359, "taxon.tab":901, "time_elapsed":false}
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/WikipediaMycologicalAPI');
$timestart = time_elapsed();
$resource_id = 879;
$func = new WikipediaMycologicalAPI($resource_id);
$func->get_all_taxa();
Functions::finalize_dwca_resource($resource_id, false, true);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec . " seconds";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>