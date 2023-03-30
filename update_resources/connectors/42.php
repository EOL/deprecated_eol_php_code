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
42	Wednesday 2018-06-13 04:15:40 PM{"agent.tab":146,"measurement_or_fact.tab":177254,         "media_resource.tab":135702,         "occurrence.tab":158463,"reference.tab":32237,"taxon.tab":95593,"vernacular_name.tab":248126}
Start: expected increase in trait (addt'l mappings from Jen) and expected decrease in vernaculars (per https://eol-jira.bibalex.org/browse/DATA-1639?focusedCommentId=63465&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-63465)
42	Thursday 2019-05-30 10:15:14 AM	{"agent.tab":146,"MoF.tab":177712,"media.tab":135702,"occurrence_specific.tab":161031,"reference.tab":32237,"taxon.tab":95593,"vernacular_name.tab":157469} consistent OK
42	Monday 2019-11-25 08:54:51 AM	{"agent.tab":146,"MoF.tab":177712,"media.tab":135702,"occurrence_specific.tab":161031,"reference.tab":32237,"taxon.tab":95593,"vernacular_name.tab":157469,"time_elapsed":false} consistent OK
started abbreviating to see all:
42	Monday 2020-01-27 10:18:01 AM	{"agent.tab":146, "MoF.tab":177712, "media.tab":135702, "occur.tab":161031, "reference.tab":32237, "taxon.tab":95593, "vernacular_name.tab":157469, "time_elapsed":{"sec":7116.05,"min":118.6,"hr":1.98}}
stable run:
42	Sun 2021-05-30 11:28:38 AM	    {"agent.tab":152, "MoF.tab":335337, "media.tab":104849, "occur.tab":153496, "reference.tab":34778, "taxon.tab":97029, "vernacular_name.tab":184304, "time_elapsed":{"sec":9169.030000000001, "min":152.82, "hr":2.55}}
42_meta_recoded                     {"agent.tab":152, "MoF.tab":335337, "media.tab":104849, "occur.tab":153496, "reference.tab":34778, "taxon.tab":97029, "vernacular_name.tab":184304, "time_elapsed":{"sec":359.03, "min":5.98, "hr":0.1}}
42	Mon 2021-05-31 04:13:41 AM	    {"agent.tab":152, "MoF.tab":335337, "media.tab":104849, "occur.tab":153496, "reference.tab":34786, "taxon.tab":97029, "vernacular_name.tab":183680, "time_elapsed":{"sec":1472.52, "min":24.54, "hr":0.41}}
42_meta_recoded	Mon 2021-05-31 04:19{"agent.tab":152, "MoF.tab":335337, "media.tab":104849, "occur.tab":153496, "reference.tab":34786, "taxon.tab":97029, "vernacular_name.tab":183680, "time_elapsed":{"sec":356.24, "min":5.94, "hr":0.1}}
back contributors as column in MoF
42	Mon 2021-05-31 08:28:25 PM	    {"agent.tab":152, "MoF.tab":167652, "media.tab":104849, "occur.tab":153496, "reference.tab":34786, "taxon.tab":97029, "vernacular_name.tab":183680, "time_elapsed":{"sec":729.67, "min":12.16, "hr":0.2}}
42_meta_recoded	Mon 2021-05-31 08:33{"agent.tab":152, "MoF.tab":167652, "media.tab":104849, "occur.tab":153496, "reference.tab":34786, "taxon.tab":97029, "vernacular_name.tab":183680, "time_elapsed":{"sec":283.66, "min":4.73, "hr":0.08}}
42	Wed 2021-06-09 02:39:00 AM	    {"agent.tab":152, "MoF.tab":167652, "media.tab":104849, "occur.tab":153496, "reference.tab":34786, "taxon.tab":97029, "vernacular_name.tab":183680, "time_elapsed":{"sec":606.58, "min":10.11, "hr":0.17}}
42_meta_recoded	Wed 2021-06-09 02:43{"agent.tab":152, "MoF.tab":167652, "media.tab":104849, "occur.tab":153496, "reference.tab":34786, "taxon.tab":97029, "vernacular_name.tab":183680, "time_elapsed":{"sec":293.5, "min":4.89, "hr":0.08}}
42_meta_recoded	Mon 2022-06-13 02:44{"agent.tab":152, "MoF.tab":167652, "media.tab":104849, "occur.tab":153496, "reference.tab":34786, "taxon.tab":97029, "vernacular_name.tab":183680, "time_elapsed":{"sec":305.35, "min":5.09, "hr":0.08}}
42_meta_recoded	Tue 2022-09-13 04:44{"agent.tab":157, "MoF.tab":170269, "media.tab":105796, "occur.tab":155929, "reference.tab":35694, "taxon.tab":98182, "vernacular_name.tab":186773, "time_elapsed":{"sec":313.35, "min":5.22, "hr":0.09}}
42_meta_recoded	Tue 2022-12-13 04:44{"agent.tab":157, "MoF.tab":170269, "media.tab":105796, "occur.tab":155929, "reference.tab":35695, "taxon.tab":98182, "vernacular_name.tab":186773, "time_elapsed":{"sec":316.35, "min":5.27, "hr":0.09}}
42_meta_recoded	Mon 2023-03-13 04:48{"agent.tab":157, "MoF.tab":170269, "media.tab":105796, "occur.tab":155929, "reference.tab":35695, "taxon.tab":98182, "vernacular_name.tab":186773, "time_elapsed":{"sec":322.75, "min":5.38, "hr":0.09}}
42_meta_recoded	Wed 2023-03-29 06:09{"agent.tab":160, "MoF.tab":171420, "media.tab":106403, "occur.tab":156986, "reference.tab":36512, "taxon.tab":98579, "vernacular_name.tab":187049, "time_elapsed":{"sec":311.12, "min":5.19, "hr":0.09}}
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/ContributorsMapAPI');
require_library('connectors/FishBaseArchiveAPI');
$timestart = time_elapsed();
$resource_id = 42;
$func = new FishBaseArchiveAPI(false, $resource_id);

/* tests
$str = "Western to Atlantic and Eli boy and batman , robin, joker to bahamas island and 1 2 3 4 5:  Massachusetts, USA and Bermuda to the Gulf of Mexico, the Caribbean and southern Brazil";
$str = "Indo-West,Pacific eli:  Red Sea and East Africa to Southeast Asia, north to Japan and south to northern Australia (Ref. 28)";
$str = "Africa:  naturally occurring in coastal rivers of Israel (Ref. 5166), Nile basin (including lake Albert, Edward and Tana), Jebel Marra, Lake Kivu, Lake Tanganyika, Awash River, various Ethiopian lakes, Omo River system, Lake Turkana, Suguta River and Lake Baringo (Ref. 2)";
$arr = $func->parse_location_strings($str);
if($arr) print_r($arr);
else echo "\nnot array\n";
exit("\n-end tests-\n");
*/

$func->get_all_taxa($resource_id);
Functions::finalize_dwca_resource($resource_id, false, true, $timestart);

/* Generating the EOL XML
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/FishBaseAPI');
$timestart = time_elapsed();
$resource_id = 42;
$fishbase = new FishBaseAPI();
$fishbase->get_all_taxa($resource_id);
Functions::set_resource_status_to_harvest_requested($resource_id);
*/
?>