<?php
namespace php_active_record;
/* PaleoDB connector - from https://eol-jira.bibalex.org/browse/TRAM-746
we use their data service to assemble their data and ingest it as structured data
estimated execution time: 

368	Wednesday 2018-04-11 09:41:49 AM	{"MoF":2450420,"occurrence.tab":528288,"taxon.tab":359968,"vernacular_name.tab":4273} - local
368	Thursday 2018-04-12 06:34:45 AM	    {"MoF":2696122,"occurrence.tab":552850,"taxon.tab":359968,"vernacular_name.tab":4273}

368	Wednesday 2018-04-11 11:17:47 AM	{"MoF":2451203,"occurrence.tab":528502,"taxon.tab":360156,"vernacular_name.tab":4366} - eol-archive (without 'ignore' and multiple values yet)
368	Wednesday 2018-04-11 01:05:31 PM	{"MoF":2698963,"occurrence.tab":553064,"taxon.tab":360156,"vernacular_name.tab":4366} - eol-archive (with ignore and multiple values)
368	Thursday 2018-04-12 06:24:10 AM	    {"MoF":2697018,"occurrence.tab":553064,"taxon.tab":360156,"vernacular_name.tab":4366}
368	Monday 2018-04-16 08:26:19 AM	    {"MoF":2687714,"occurrence.tab":550882,"taxon.tab":358530,"vernacular_name.tab":4361} - excluding descendants of parents without entries
368	Thursday 2018-10-18 01:59:53 PM	    {"MoF":2413255,"occurrence.tab":506032,"taxon.tab":367421,"vernacular_name.tab":6142}
368	Tuesday 2018-11-06 11:58:23 PM	    {"MoF":2419162,"occurrence.tab":506549,"taxon.tab":367928,"vernacular_name.tab":6143}
368	Sunday 2018-11-11 12:50:28 AM	    {"MoF":2419162,"occurrence.tab":506549,"taxon.tab":367927,"vernacular_name.tab":6143}

368	Thursday 2019-05-30 04:19:26 AM	{"MoF":2484786,"occurrence.tab":519707,"taxon.tab":378357,"vernacular_name.tab":6225}
368	Thursday 2019-05-30 04:48:18 AM	{"MoF":2472412,"occurrence.tab":516809,"taxon.tab":376235,"vernacular_name.tab":6213}
368	Thursday 2019-05-30 05:14:58 AM	{"MoF":2484786,"occurrence.tab":519707,"taxon.tab":378357,"vernacular_name.tab":6225}
368	Thursday 2019-05-30 05:44:07 AM	{"MoF":2472370,"occurrence.tab":516802,"taxon.tab":376228,"vernacular_name.tab":6213}

this one is BEFORE the DATA-1814
368	Monday 2019-07-15 09:06:17 AM	{"MoF":2497294,"occurrence.tab":522040,"taxon.tab":379961,"vernacular_name.tab":6234}
368	Monday 2019-07-15 09:35:11 AM	{"MoF":2484781,"occurrence.tab":519113,"taxon.tab":377821,"vernacular_name.tab":6222}
368	Monday 2019-07-15 10:01:25 AM	{"MoF":2497294,"occurrence.tab":522040,"taxon.tab":379961,"vernacular_name.tab":6234}
368	Monday 2019-07-15 10:30:06 AM	{"MoF":2484739,"occurrence.tab":519106,"taxon.tab":377814,"vernacular_name.tab":6222} - consistent OK

this one is AFTER DATA-1814, changes were made already:
368	Monday 2019-07-15 02:03:07 PM	{"MoF":2292095,"occurrence.tab":518515,"taxon.tab":377821,"vernacular_name.tab":6222}
368	Monday 2019-07-15 02:28:18 PM	{"MoF":2304006,"occurrence.tab":521441,"taxon.tab":379961,"vernacular_name.tab":6234}
368	Monday 2019-07-15 02:55:44 PM	{"MoF":2292053,"occurrence.tab":518508,"taxon.tab":377814,"vernacular_name.tab":6222} - expected lesser MoF & occurrence count. Still consistent.
368	Tuesday 2019-08-13 11:25:11 AM	{"MoF_specific":2276891,"occurrence.tab":515360,"taxon.tab":376240,"vernacular_name.tab":5863}
rows of 4
368	Friday 2019-09-06 05:22:44 AM	{"MoF":2331238,"occurrence.tab":525483,"taxon.tab":383179,"vernacular_name.tab":6278}
368	Friday 2019-09-06 05:46:07 AM	{"MoF":2343529,"occurrence.tab":528489,"taxon.tab":385363,"vernacular_name.tab":6289}
368	Friday 2019-09-06 06:11:32 AM	{"MoF":2331196,"occurrence.tab":525476,"taxon.tab":383172,"vernacular_name.tab":6278}
368	Friday 2019-09-06 06:21:45 AM	{"MoF_specific":2317122,"occurrence.tab":522534,"taxon.tab":381701,"vernacular_name.tab":5930}
rows of 4
368	Tuesday 2019-10-01 07:25:02 AM	{"MoF":2350084,"occurrence.tab":530026,"taxon.tab":386089,"vernacular_name.tab":6291}
368	Tuesday 2019-10-01 07:51:52 AM	{"MoF":2337888,"occurrence.tab":527040,"taxon.tab":383910,"vernacular_name.tab":6280}
368_removed_aves	Tuesday 2019-10-01 08:02:43 AM	{"MoF_specific":2323850,"occurrence.tab":524104,"taxon.tab":381735,"vernacular_name.tab":5933}
368_final	Tuesday 2019-10-01 09:07:36 AM	{"MoF_specific":2323850,"occurrence.tab":524104,"taxon.tab":369359,"vernacular_name.tab":5933}
rows of 4
368	Wednesday 2019-10-02 04:59:05 AM	                {"MoF":2350084,"occurrence.tab":530026,"taxon.tab":386089,"vernacular_name.tab":6291}
368	Wednesday 2019-10-02 05:25:42 AM	                {"MoF":2337888,"occurrence.tab":527040,"taxon.tab":383910,"vernacular_name.tab":6280}
368_removed_aves	Wednesday 2019-10-02 06:07:17 AM	{"MoF_specific":2286206,"occurrence.tab":516498,"taxon.tab":378639,"vernacular_name.tab":4540}
368_final	Wednesday 2019-10-02 06:19:59 AM	        {"MoF_specific":2286206,"occurrence.tab":516498,"taxon.tab":369789,"vernacular_name.tab":4540}
rows of 4: Consistent OK
368	Monday 2019-12-02 10:50:38 AM	                    {"MoF":2372980,"occurrence.tab":534984,"taxon.tab":389681,"vernacular_name.tab":6503}
368	Monday 2019-12-02 11:17:09 AM	                    {"MoF":2360542,"occurrence.tab":531952,"taxon.tab":387473,"vernacular_name.tab":6489}
368_removed_aves	Monday 2019-12-02 11:27:38 AM	    {"MoF_specific":2307584,"occurrence.tab":521028,"taxon.tab":382011,"vernacular_name.tab":4564}
368_final	Monday 2019-12-02 11:40:19 AM	            {"MoF_specific":2307584,"occurrence.tab":521028,"taxon.tab":373124,"vernacular_name.tab":4564}

rows of 4: Consistent OK: Removed Polychaeta (id = 7022)
368	Tuesday 2020-02-11 09:21:54 AM	                    {"MoF":2417696,"occurrence.tab":543891,"taxon.tab":396721,"vernacular_name.tab":6979}
368	Tuesday 2020-02-11 09:49:48 AM	                    {"MoF":2404575,"occurrence.tab":540590,"taxon.tab":394262,"vernacular_name.tab":6965}
368_removed_aves	Tuesday 2020-02-11 10:00:35 AM	    {"MoF_specific":2344674,"occurrence.tab":527826,"taxon.tab":387880,"vernacular_name.tab":4692}
368_final	Tuesday 2020-02-11 10:13:35 AM	            {"MoF_specific":2344674,"occurrence.tab":527826,"taxon.tab":378879,"vernacular_name.tab":4692}

368	Wednesday 2020-03-04 05:27:53 AM	                {"MoF":2424685, "occurrence.tab":546235, "taxon.tab":399196, "vernacular_name.tab":7004}
368	Wednesday 2020-03-04 05:57:03 AM	                {"MoF":2411234, "occurrence.tab":542757, "taxon.tab":396564, "vernacular_name.tab":6991}
368_removed_aves	Wednesday 2020-03-04 06:10:26 AM	{"MoF_specific":2330816, "occurrence.tab":525816, "taxon.tab":390179, "vernacular_name.tab":4718}
368_final	Wednesday 2020-03-04 06:24:32 AM	        {"MoF_specific":2330816, "occurrence.tab":525816, "taxon.tab":381141, "vernacular_name.tab":4718}

Removed sea urchin in mRemarks in MoF
368	Friday 2020-03-20 03:33:36 AM	                {"MoF":2423880, "occurrence.tab":547974, "taxon.tab":400901, "vernacular_name.tab":7005}
368	Friday 2020-03-20 04:09:00 AM	                {"MoF":2410227, "occurrence.tab":544391, "taxon.tab":398165, "vernacular_name.tab":6992}
368_removed_aves	Friday 2020-03-20 04:28:09 AM	{"MoF_specific":2329831, "occurrence.tab":527454, "taxon.tab":391782, "vernacular_name.tab":4719}
368_final	Friday 2020-03-20 04:48:20 AM	        {"MoF_specific":2329831, "occurrence.tab":527454, "taxon.tab":382706, "vernacular_name.tab":4719}

Removed sea urchin in mRemarks in MoF and in taxon.tab
368	Friday 2020-03-20 05:23:47 AM	                {"MoF":2423880, "occurrence.tab":547974, "taxon.tab":400901, "vernacular_name.tab":7005}
368	Friday 2020-03-20 05:59:34 AM	                {"MoF":2410227, "occurrence.tab":544391, "taxon.tab":398165, "vernacular_name.tab":6992}
368_removed_aves	Friday 2020-03-20 06:19:00 AM	{"MoF_specific":2309208, "occurrence.tab":522016, "taxon.tab":389063, "vernacular_name.tab":4690}
368_final	Friday 2020-03-20 06:38:52 AM	        {"MoF_specific":2309208, "occurrence.tab":522016, "taxon.tab":378799, "vernacular_name.tab":4690}

Just trait remap
368	Tuesday 2020-03-24 11:53:36 AM	                {"MoF":2423880, "occurrence.tab":547974, "taxon.tab":400901, "vernacular_name.tab":7005}
368	Tuesday 2020-03-24 12:28:08 PM	                {"MoF":2410227, "occurrence.tab":544391, "taxon.tab":398165, "vernacular_name.tab":6992}
368_removed_aves	Tuesday 2020-03-24 12:46:33 PM	{"MoF_specific":2309208, "occurrence.tab":522016, "taxon.tab":389063, "vernacular_name.tab":4690}
368_final	Tuesday 2020-03-24 01:06:17 PM	        {"MoF_specific":2309208, "occurrence.tab":522016, "taxon.tab":378799, "vernacular_name.tab":4690}

368	Monday 2020-07-13 12:16:34 PM	                {"MoF":2468020, "occurrence.tab":560518, "taxon.tab":412089, "vernacular_name.tab":7167}
368	Monday 2020-07-13 12:52:44 PM	                {"MoF":2452059, "occurrence.tab":556102, "taxon.tab":408596, "vernacular_name.tab":7152}
368_removed_aves	Monday 2020-07-13 01:12:19 PM	{"MoF_specific":2348179, "occurrence.tab":533094, "taxon.tab":399217, "vernacular_name.tab":4750}
368_final	Monday 2020-07-13 01:32:31 PM	        {"MoF_specific":2348179, "occurrence.tab":533094, "taxon.tab":388589, "vernacular_name.tab":4750}

368	Thursday 2020-08-06 05:55:58 PM	{"MoF":2474119, "occurrence.tab":561749, "taxon.tab":413147, "vernacular_name.tab":7171, "time_elapsed":false}
368	Thursday 2020-08-06 06:32:56 PM	{"MoF":2458161, "occurrence.tab":557336, "taxon.tab":409654, "vernacular_name.tab":7156, "time_elapsed":false}
368_removed_aves	Thursday 2020-08-06 06:52:45 PM	{"MoF":2354130, "occurrence.tab":534300, "taxon.tab":400261, "vernacular_name.tab":4754, "time_elapsed":false}
368_final	Thursday 2020-08-06 07:13:08 PM	        {"MoF":2354130, "occurrence.tab":534300, "taxon.tab":389610, "vernacular_name.tab":4754, "time_elapsed":false}
Mac Mini:
368_merged_MoF	Wednesday 2020-08-26 05:53:47 AM	{"MoF":2353864, "occurrence.tab":534300, "taxon.tab":389610, "vernacular_name.tab":4754, "time_elapsed":{"sec":2078.36, "min":34.64, "hr":0.58}}
eol-archive:
368_merged_MoF	Wednesday 2020-08-26 09:52:55 AM	{"MoF":2353864, "occurrence.tab":534300, "taxon.tab":389610, "vernacular_name.tab":4754, "time_elapsed":{"sec":1199.53, "min":19.99, "hr":0.33}}

368	Thu 2020-12-03 09:06:57 PM	                    {"MoF":2354502, "occurrence.tab":567911, "taxon.tab":418498, "vernacular_name.tab":7202, "time_elapsed":false}
EXPECTED DECREASE SINCE Now removed in MoF, measurementType=http://purl.obolibrary.org/obo/UBERON_0002104.
368	Thu 2020-12-03 09:43:31 PM	                    {"MoF":2338562, "occurrence.tab":563416, "taxon.tab":414945, "vernacular_name.tab":7186, "time_elapsed":false}
368_removed_aves	Thu 2020-12-03 10:02:04 PM	    {"MoF":2233159, "occurrence.tab":540001, "taxon.tab":405382, "vernacular_name.tab":4756, "time_elapsed":false}
368_final	Thu 2020-12-03 10:22:18 PM	            {"MoF":2233159, "occurrence.tab":540001, "taxon.tab":394515, "vernacular_name.tab":4756, "time_elapsed":false}
368_merged_MoF	Thu 2020-12-03 10:41:40 PM	        {"MoF":2232890, "occurrence.tab":540001, "taxon.tab":394515, "vernacular_name.tab":4756, "time_elapsed":{"sec":1141.51, "min":19.03, "hr":0.32}}
EXPECTED DECREASE IN MoF SINCE: Remove Q1053008 if Arthropoda. *Interestingly occurrence increased, maybe due to new data coming in. Since raw source is from remote partner, not static.
368	                    Tue 2021-02-02 10:30:12 AM	{"MoF":2332102, "occurrence.tab":567170, "taxon.tab":417986, "vernacular_name.tab":7320, "time_elapsed":false}
368_removed_aves	    Tue 2021-02-02 10:49:32 AM	{"MoF":2224916, "occurrence.tab":543337, "taxon.tab":408235, "vernacular_name.tab":4889, "time_elapsed":false}
368_final	            Tue 2021-02-02 11:10:01 AM	{"MoF":2224916, "occurrence.tab":543337, "taxon.tab":397131, "vernacular_name.tab":4889, "time_elapsed":false}
368_merged_MoF	        Tue 2021-02-02 11:30:12 AM	{"MoF":2224647, "occurrence.tab":543337, "taxon.tab":397131, "vernacular_name.tab":4889, "time_elapsed":{"sec":1194.32, "min":19.91, "hr":0.33}}

368	                    Wed 2021-02-03 10:05:00 PM	{"MoF":2332102, "occurrence.tab":567170, "taxon.tab":417986, "vernacular_name.tab":7320, "time_elapsed":false}
368_removed_aves	    Wed 2021-02-03 10:23:54 PM	{"MoF":2224916, "occurrence.tab":543337, "taxon.tab":408235, "vernacular_name.tab":4889, "time_elapsed":false}
368_final	            Wed 2021-02-03 10:43:17 PM	{"MoF":2224916, "occurrence.tab":543337, "taxon.tab":397131, "vernacular_name.tab":4889, "time_elapsed":false}
368_merged_MoF	        Wed 2021-02-03 11:03:11 PM	{"MoF":2224647, "occurrence.tab":543337, "taxon.tab":397131, "vernacular_name.tab":4889, "time_elapsed":{"sec":1174.18, "min":19.57, "hr":0.33}}

368	Fri 2021-08-06 06:37:28 PM	                    {"MoF":2369435, "occurrence.tab":571903, "taxon.tab":423327, "vernacular_name.tab":7430, "time_elapsed":false}
368_removed_aves	Fri 2021-08-06 06:57:35 PM	    {"MoF":2260561, "occurrence.tab":547769, "taxon.tab":413494, "vernacular_name.tab":4994, "time_elapsed":false}
368_final	Fri 2021-08-06 07:18:43 PM	            {"MoF":2260561, "occurrence.tab":547769, "taxon.tab":400373, "vernacular_name.tab":4994, "time_elapsed":false}
368_merged_MoF	Fri 2021-08-06 07:40:06 PM	        {"MoF":2260277, "occurrence.tab":547769, "taxon.tab":400373, "vernacular_name.tab":4994, "time_elapsed":{"sec":1261.64, "min":21.03, "hr":0.35}}

368	Mon 2021-09-06 06:35:21 PM	                    {"MoF":2375114, "occurrence.tab":573032, "taxon.tab":424420, "vernacular_name.tab":7433, "time_elapsed":false}
368_removed_aves	Mon 2021-09-06 06:55:10 PM	    {"MoF":2265979, "occurrence.tab":548847, "taxon.tab":414563, "vernacular_name.tab":4997, "time_elapsed":false}
368_final	Mon 2021-09-06 07:15:27 PM	            {"MoF":2265979, "occurrence.tab":548847, "taxon.tab":401390, "vernacular_name.tab":4997, "time_elapsed":false}
368_merged_MoF	Mon 2021-09-06 07:35:38 PM	        {"MoF":2265695, "occurrence.tab":548847, "taxon.tab":401390, "vernacular_name.tab":4997, "time_elapsed":{"sec":1191.41, "min":19.86, "hr":0.33}}

368	Tue 2021-09-14 10:27:23 PM	                    {"MoF":2377729, "occurrence.tab":573474, "taxon.tab":424719, "vernacular_name.tab":7432, "time_elapsed":false}
368_removed_aves	Tue 2021-09-14 10:46:11 PM	    {"MoF":2268599, "occurrence.tab":549290, "taxon.tab":414862, "vernacular_name.tab":4996, "time_elapsed":false}
368_final	Tue 2021-09-14 11:06:43 PM	            {"MoF":2268599, "occurrence.tab":549290, "taxon.tab":401683, "vernacular_name.tab":4996, "time_elapsed":false}
368_merged_MoF	Tue 2021-09-14 11:27:06 PM	        {"MoF":2268315, "occurrence.tab":549290, "taxon.tab":401683, "vernacular_name.tab":4996, "time_elapsed":{"sec":1206.03, "min":20.1, "hr":0.34}}

368	Wed 2021-10-20 01:37:43 AM	                    {"MoF.tab":2377612, "occurrence.tab":574125, "taxon.tab":425501, "vernacular_name.tab":7433, "time_elapsed":false}
368_removed_aves	Wed 2021-10-20 01:57:31 AM	    {"MoF.tab":2268437, "occurrence.tab":549933, "taxon.tab":415640, "vernacular_name.tab":4997, "time_elapsed":false}
368_final	Wed 2021-10-20 02:19:11 AM	            {"MoF.tab":2268437, "occurrence.tab":549933, "taxon.tab":402458, "vernacular_name.tab":4997, "time_elapsed":false}
368_merged_MoF	Wed 2021-10-20 02:41:08 AM	        {"MoF.tab":2268153, "occurrence.tab":549933, "taxon.tab":402458, "vernacular_name.tab":4997, "time_elapsed":{"sec":1288.37, "min":21.47, "hr":0.36}}
368_merged_MoF	Sun 2022-02-06 07:41:50 PM	        {"MoF.tab":2307902, "occurrence.tab":560044, "taxon.tab":412201, "vernacular_name.tab":5053, "time_elapsed":{"sec":1241.63, "min":20.69, "hr":0.34}}
368_cleaned_MoF	Tue 2022-02-22 04:10:26 AM	        {"MoF.tab":2140043, "occurrence.tab":461721, "taxon.tab":412201, "vernacular_name.tab":5053, "time_elapsed":{"sec":1800.47, "min":30.01, "hr":0.5}} Mac Mini
368_cleaned_MoF	Tue 2022-02-22 05:56:26 AM	        {"MoF.tab":2140043, "occurrence.tab":461721, "taxon.tab":412201, "vernacular_name.tab":5053, "time_elapsed":{"sec":1087.14, "min":18.12, "hr":0.3}} eol->archive
Below occurrences are now fixed - increased as expected:
368_cleaned_MoF	Wed 2022-02-23 07:13:49 PM	        {"MoF.tab":2140043, "occurrence.tab":514004, "taxon.tab":412201, "vernacular_name.tab":5053, "time_elapsed":{"sec":1086.67, "min":18.11, "hr":0.3}}
Below stable delta version: - decrease in occurrences (from 368_cleaned_MoF) is expected:
368_delta	Wed 2022-02-23 08:06:19 PM	            {"MoF.tab":2140043, "occurrence.tab":423710, "taxon.tab":412201, "vernacular_name.tab":5053, "time_elapsed":{"sec":2143.9, "min":35.73, "hr":0.6}} Mac Mini
368_delta	Wed 2022-02-23 09:01:26 PM	            {"MoF.tab":2140043, "occurrence.tab":423710, "taxon.tab":412201, "vernacular_name.tab":5053, "time_elapsed":{"sec":1263.44, "min":21.06, "hr":0.35}} eol-archive

Latest:
368	Thu 2022-03-03 02:14:32 AM	                    {"MoF.tab":2459378, "occurrence.tab":595412, "taxon.tab":443906, "vernacular_name.tab":7587, "time_elapsed":false}
368	Thu 2022-03-03 02:59:26 AM	                    {"MoF.tab":2414830, "occurrence.tab":583359, "taxon.tab":434753, "vernacular_name.tab":7457, "time_elapsed":false}
368_removed_aves	Thu 2022-03-03 03:19:26 AM	    {"MoF.tab":2300702, "occurrence.tab":558986, "taxon.tab":424840, "vernacular_name.tab":5021, "time_elapsed":false}
368_final	Thu 2022-03-03 03:41:00 AM	            {"MoF.tab":2300702, "occurrence.tab":558986, "taxon.tab":411197, "vernacular_name.tab":5021, "time_elapsed":false}
368_merged_MoF	Thu 2022-03-03 04:02:16 AM	        {"MoF.tab":2300418, "occurrence.tab":558986, "taxon.tab":411197, "vernacular_name.tab":5021, "time_elapsed":{"sec":1253.79, "min":20.9, "hr":0.35}}
368_cleaned_MoF	Thu 2022-03-03 04:36:25 AM	        {"MoF.tab":2133022, "occurrence.tab":512922, "taxon.tab":411197, "vernacular_name.tab":5021, "time_elapsed":{"sec":1069.95, "min":17.83, "hr":0.3}}
368_delta	Thu 2022-03-03 04:58:35 AM	            {"MoF.tab":2133022, "occurrence.tab":512922, "taxon.tab":411197, "vernacular_name.tab":5021, "time_elapsed":{"sec":1286.41, "min":21.44, "hr":0.36}}


----------------------------------------------------------------------------
Reminders:
# Six (6) connectors to run, one after the other - OK

php5.6 pbdb_fresh_harvest.php jenkins 
# - this generates 368.tar.gz

# This will adjust newly generated 368.tar.gz. 
# It'll remove all Aves descendants as requested.
php5.6 remove_Aves_children_from_368.php jenkins 
# - this generates 368_removed_aves.tar.gz

# This will remove bad synonyms
php5.6 synonyms_handling.php jenkins 368_final 
# - this generates 368_final.tar.gz

# 4th step: This will merge 2 occurrences into 1
php5.6 368_merge_two_MoF_into_one.php 
# - this generates 368_merged_MoF.tar.gz

# 5the step: This will remove some MoF and put a lifeStage in occurrence
php5.6 pbdb_more_adjustments.php 
# - this generates 368_cleaned_MoF.tar.gz

# 6th step: delta hashing
php5.6 make_hash_IDs_4Deltas.php jenkins '{"task": "", "resource":"Deltas_4hashing", "resource_id":"368_cleaned_MoF"}'
# - this generates 368_delta.tar.gz
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
ini_set('memory_limit','8096M');
$timestart = time_elapsed();
$resource_id = 368;
require_library('connectors/PaleoDBAPI_v2');

// first loop ------------------------------------------------------------- start
$func = new PaleoDBAPI_v2($resource_id); main_proc($func, $resource_id);
$descendant_taxon_ids = utility($resource_id, false); // utility - to remove taxa that are descendants of 'parents without entries'.
$func = new PaleoDBAPI_v2($resource_id); main_proc($func, $resource_id, $descendant_taxon_ids); // 2nd round
$func = new DWCADiagnoseAPI();
if($parents_without_entries = $func->check_if_all_parents_have_entries($resource_id, true)) { //2nd param True means write to text file
    echo "\nparents without entries: ".count($parents_without_entries)."\n"; 
    // print_r($parents_without_entries);
}
else echo "\nAll parents have entries OK (2nd try)\n";
// first loop ------------------------------------------------------------- end

if($parents_without_entries) {
    $parents_without_entries_last_round = $parents_without_entries;
    // 2nd loop
    echo "\nStarting a 2nd loop...\n";
    // first loop ------------------------------------------------------------- start
    $func = new PaleoDBAPI_v2($resource_id); main_proc($func, $resource_id);
    $descendant_taxon_ids = utility($resource_id, $parents_without_entries_last_round); // utility - to remove taxa that are descendants of 'parents without entries'.
    $func = new PaleoDBAPI_v2($resource_id); main_proc($func, $resource_id, $descendant_taxon_ids); // 2nd round
    $func = new DWCADiagnoseAPI();
    if($parents_without_entries = $func->check_if_all_parents_have_entries($resource_id, true)) { //2nd param True means write to text file
        echo "\nparents without entries: ".count($parents_without_entries)."\n"; 
        // print_r($parents_without_entries);
    }
    else echo "\nAll parents have entries OK (2nd try)\n";
    // first loop ------------------------------------------------------------- end
    if($parents_without_entries) echo "\nA 3rd loop is needed.\n";
}

recursive_rmdir(CONTENT_RESOURCE_LOCAL_PATH . $resource_id); //to remove temp folder /368/

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";

function main_proc($func, $resource_id, $descendant_taxon_ids = false)
{
    $func->get_all_taxa($descendant_taxon_ids);
    unset($func);
    Functions::finalize_dwca_resource($resource_id);
}
function utility($resource_id, $prev_parents_without_entries = false)
{
    require_library('connectors/DWCADiagnoseAPI');
    $func = new DWCADiagnoseAPI();
    if($parents_without_entries = $func->check_if_all_parents_have_entries($resource_id, true)) { //2nd param True means write to text file
        /* these 2 entries I got after the first run. This will initialize to zero records on every harvest. This is done manually */
        if($prev_parents_without_entries) $parents_without_entries = array_merge($parents_without_entries, $prev_parents_without_entries);
        echo "\nparents without entries: ".count($parents_without_entries)."\n"; 
        // print_r($parents_without_entries);
    }
    else echo "\nAll parents have entries OK (1st try)\n";

    if($parents_without_entries) {
        /* Given a list of parent_ids, get all descendants of these taxa. Get all the taxon_ids of descendants. */
        $func = new PaleoDBAPI_v2($resource_id);
        $dwca_file = CONTENT_RESOURCE_LOCAL_PATH . "$resource_id".".tar.gz";
        $descendant_taxon_ids = $func->get_descendants_given_parent_ids($dwca_file, $parents_without_entries);
    }
    else $descendant_taxon_ids = array();
    return $descendant_taxon_ids;
}
?>