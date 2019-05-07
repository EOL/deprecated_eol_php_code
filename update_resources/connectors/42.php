<?php
namespace php_active_record;
/* connector for FishBase
estimated execution time:
Provider provides text file. Connector parses it and assembles the EOL DWC-A.
                                                                    2017
                    Sep-9   Sep-17      Mar-17      Jul-27  Dec-6   Sep-19
taxon (with syn):   92515   92854       93235       93409   93769   93769
media_resource:     224584  225596      131234      131638  133384  133384
vernaculars:        234617  234902      236758      236954  244112  244112
agents:             144     145         146         146     146     146
references:         32739   33068       30003       30195   30782   30782
occurrence                              157763      157061  158020  158020
measurements                            173768      175317  176490  176490

42	Wednesday 2017-09-20 10:14:17 PM{"agent.tab":146,"measurement_or_fact.tab":176490,"media_resource.tab":133384,"occurrence.tab":158020,"reference.tab":30782,"taxon.tab":93769,"vernacular_name.tab":244112} eol-archive
42	Saturday 2017-12-09 09:46:55 PM	{"agent.tab":146,"measurement_or_fact.tab":179865,"media_resource.tab":135707,"occurrence.tab":160981,"reference.tab":32237,"taxon.tab":95593,"vernacular_name.tab":248126} Mac Mini
42	Saturday 2017-12-09 11:41:48 PM	{"agent.tab":146,"measurement_or_fact.tab":179865,"media_resource.tab":135707,"occurrence.tab":160981,"reference.tab":32237,"taxon.tab":95593,"vernacular_name.tab":248126} eol-archive
42	Monday 2017-12-11 01:19:27 AM	{"agent.tab":146,"measurement_or_fact.tab":179865,"media_resource.tab":135702,"occurrence.tab":160981,"reference.tab":32237,"taxon.tab":95593,"vernacular_name.tab":248126} Mac Mini
42	Monday 2017-12-11 01:36:02 AM	{"agent.tab":146,"measurement_or_fact.tab":179865,"media_resource.tab":135702,"occurrence.tab":160981,"reference.tab":32237,"taxon.tab":95593,"vernacular_name.tab":248126} eol-archive
42	Friday 2018-03-02 03:42:53 AM	{"agent.tab":146,"measurement_or_fact.tab":179865,"media_resource.tab":135702,"occurrence.tab":160994,"reference.tab":32237,"taxon.tab":95593,"vernacular_name.tab":248126} eol-archive
42	Wednesday 2018-03-07 08:55:05 AM{"agent.tab":146,"measurement_or_fact.tab":177254,"media_resource.tab":135702,"occurrence.tab":158463,"reference.tab":32237,"taxon.tab":95593,"vernacular_name.tab":248126}
42	Wednesday 2018-03-07 07:02:59 PM{"agent.tab":146,"measurement_or_fact.tab":177254,"media_resource.tab":135702,"occurrence.tab":158463,"reference.tab":32237,"taxon.tab":95593,"vernacular_name.tab":248126} all-hash measurementID
42	Thursday 2018-03-08 08:05:55 PM	{"agent.tab":146,"measurement_or_fact.tab":177254,"media_resource.tab":135702,"occurrence.tab":158463,"reference.tab":32237,"taxon.tab":95593,"vernacular_name.tab":248126}
42	Wednesday 2018-06-13 04:15:40 PM{"agent.tab":146,"measurement_or_fact.tab":177254,"media_resource.tab":135702,"occurrence.tab":158463,"reference.tab":32237,"taxon.tab":95593,"vernacular_name.tab":248126}
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/FishBaseArchiveAPI');
$timestart = time_elapsed();
$resource_id = 42;
$func = new FishBaseArchiveAPI(false, $resource_id);

// /* tests
$str = "Western to Atlantic and Eli boy and batman , robin, joker to bahamas island and 1 2 3 4 5:  Massachusetts, USA and Bermuda to the Gulf of Mexico, the Caribbean and southern Brazil";
$func->parse_location_strings($str);
exit("\n-end tests-\n");
// */

$func->get_all_taxa($resource_id);
Functions::finalize_dwca_resource($resource_id, false, true);

/* Generating the EOL XML
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/FishBaseAPI');
$timestart = time_elapsed();
$resource_id = 42;
$fishbase = new FishBaseAPI();
$fishbase->get_all_taxa($resource_id);
Functions::set_resource_status_to_harvest_requested($resource_id);
*/

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = $elapsed_time_sec seconds             \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes  \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\n\n Done processing.";
?>