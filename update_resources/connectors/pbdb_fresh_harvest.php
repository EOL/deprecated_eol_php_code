<?php
namespace php_active_record;
/* PaleoDB connector - from https://eol-jira.bibalex.org/browse/TRAM-746
we use their data service to assemble their data and ingest it as structured data
estimated execution time: 

368	Wednesday 2018-04-11 09:41:49 AM	{"measurement_or_fact.tab":2450420,"occurrence.tab":528288,"taxon.tab":359968,"vernacular_name.tab":4273} - local
368	Thursday 2018-04-12 06:34:45 AM	    {"measurement_or_fact.tab":2696122,"occurrence.tab":552850,"taxon.tab":359968,"vernacular_name.tab":4273}

368	Wednesday 2018-04-11 11:17:47 AM	{"measurement_or_fact.tab":2451203,"occurrence.tab":528502,"taxon.tab":360156,"vernacular_name.tab":4366} - eol-archive (without 'ignore' and multiple values yet)
368	Wednesday 2018-04-11 01:05:31 PM	{"measurement_or_fact.tab":2698963,"occurrence.tab":553064,"taxon.tab":360156,"vernacular_name.tab":4366} - eol-archive (with ignore and multiple values)
368	Thursday 2018-04-12 06:24:10 AM	    {"measurement_or_fact.tab":2697018,"occurrence.tab":553064,"taxon.tab":360156,"vernacular_name.tab":4366}
368	Monday 2018-04-16 08:26:19 AM	    {"measurement_or_fact.tab":2687714,"occurrence.tab":550882,"taxon.tab":358530,"vernacular_name.tab":4361} - excluding descendants of parents without entries
368	Thursday 2018-10-18 01:59:53 PM	    {"measurement_or_fact.tab":2413255,"occurrence.tab":506032,"taxon.tab":367421,"vernacular_name.tab":6142}
368	Tuesday 2018-11-06 11:58:23 PM	    {"measurement_or_fact.tab":2419162,"occurrence.tab":506549,"taxon.tab":367928,"vernacular_name.tab":6143}
368	Sunday 2018-11-11 12:50:28 AM	    {"measurement_or_fact.tab":2419162,"occurrence.tab":506549,"taxon.tab":367927,"vernacular_name.tab":6143}

368	Thursday 2019-05-30 04:19:26 AM	{"measurement_or_fact.tab":2484786,"occurrence.tab":519707,"taxon.tab":378357,"vernacular_name.tab":6225}
368	Thursday 2019-05-30 04:48:18 AM	{"measurement_or_fact.tab":2472412,"occurrence.tab":516809,"taxon.tab":376235,"vernacular_name.tab":6213}
368	Thursday 2019-05-30 05:14:58 AM	{"measurement_or_fact.tab":2484786,"occurrence.tab":519707,"taxon.tab":378357,"vernacular_name.tab":6225}
368	Thursday 2019-05-30 05:44:07 AM	{"measurement_or_fact.tab":2472370,"occurrence.tab":516802,"taxon.tab":376228,"vernacular_name.tab":6213}

this one is BEFORE the DATA-1814
368	Monday 2019-07-15 09:06:17 AM	{"measurement_or_fact.tab":2497294,"occurrence.tab":522040,"taxon.tab":379961,"vernacular_name.tab":6234}
368	Monday 2019-07-15 09:35:11 AM	{"measurement_or_fact.tab":2484781,"occurrence.tab":519113,"taxon.tab":377821,"vernacular_name.tab":6222}
368	Monday 2019-07-15 10:01:25 AM	{"measurement_or_fact.tab":2497294,"occurrence.tab":522040,"taxon.tab":379961,"vernacular_name.tab":6234}
368	Monday 2019-07-15 10:30:06 AM	{"measurement_or_fact.tab":2484739,"occurrence.tab":519106,"taxon.tab":377814,"vernacular_name.tab":6222} - consistent OK

this one is AFTER DATA-1814, changes were made already:
368	Monday 2019-07-15 02:03:07 PM	{"measurement_or_fact.tab":2292095,"occurrence.tab":518515,"taxon.tab":377821,"vernacular_name.tab":6222}
368	Monday 2019-07-15 02:28:18 PM	{"measurement_or_fact.tab":2304006,"occurrence.tab":521441,"taxon.tab":379961,"vernacular_name.tab":6234}
368	Monday 2019-07-15 02:55:44 PM	{"measurement_or_fact.tab":2292053,"occurrence.tab":518508,"taxon.tab":377814,"vernacular_name.tab":6222} - expected lesser MoF & occurrence count. Still consistent.
368	Tuesday 2019-08-13 11:25:11 AM	{"measurement_or_fact_specific.tab":2276891,"occurrence.tab":515360,"taxon.tab":376240,"vernacular_name.tab":5863}
rows of 4
368	Friday 2019-09-06 05:22:44 AM	{"measurement_or_fact.tab":2331238,"occurrence.tab":525483,"taxon.tab":383179,"vernacular_name.tab":6278}
368	Friday 2019-09-06 05:46:07 AM	{"measurement_or_fact.tab":2343529,"occurrence.tab":528489,"taxon.tab":385363,"vernacular_name.tab":6289}
368	Friday 2019-09-06 06:11:32 AM	{"measurement_or_fact.tab":2331196,"occurrence.tab":525476,"taxon.tab":383172,"vernacular_name.tab":6278}
368	Friday 2019-09-06 06:21:45 AM	{"measurement_or_fact_specific.tab":2317122,"occurrence.tab":522534,"taxon.tab":381701,"vernacular_name.tab":5930}
rows of 4
368	Tuesday 2019-10-01 07:25:02 AM	{"measurement_or_fact.tab":2350084,"occurrence.tab":530026,"taxon.tab":386089,"vernacular_name.tab":6291}
368	Tuesday 2019-10-01 07:51:52 AM	{"measurement_or_fact.tab":2337888,"occurrence.tab":527040,"taxon.tab":383910,"vernacular_name.tab":6280}
368_removed_aves	Tuesday 2019-10-01 08:02:43 AM	{"measurement_or_fact_specific.tab":2323850,"occurrence.tab":524104,"taxon.tab":381735,"vernacular_name.tab":5933}
368_final	Tuesday 2019-10-01 09:07:36 AM	{"measurement_or_fact_specific.tab":2323850,"occurrence.tab":524104,"taxon.tab":369359,"vernacular_name.tab":5933}
rows of 4
368	Wednesday 2019-10-02 04:59:05 AM	                {"measurement_or_fact.tab":2350084,"occurrence.tab":530026,"taxon.tab":386089,"vernacular_name.tab":6291}
368	Wednesday 2019-10-02 05:25:42 AM	                {"measurement_or_fact.tab":2337888,"occurrence.tab":527040,"taxon.tab":383910,"vernacular_name.tab":6280}
368_removed_aves	Wednesday 2019-10-02 06:07:17 AM	{"measurement_or_fact_specific.tab":2286206,"occurrence.tab":516498,"taxon.tab":378639,"vernacular_name.tab":4540}
368_final	Wednesday 2019-10-02 06:19:59 AM	        {"measurement_or_fact_specific.tab":2286206,"occurrence.tab":516498,"taxon.tab":369789,"vernacular_name.tab":4540}
rows of 4: Consistent OK
368	Monday 2019-12-02 10:50:38 AM	                    {"measurement_or_fact.tab":2372980,"occurrence.tab":534984,"taxon.tab":389681,"vernacular_name.tab":6503}
368	Monday 2019-12-02 11:17:09 AM	                    {"measurement_or_fact.tab":2360542,"occurrence.tab":531952,"taxon.tab":387473,"vernacular_name.tab":6489}
368_removed_aves	Monday 2019-12-02 11:27:38 AM	    {"measurement_or_fact_specific.tab":2307584,"occurrence.tab":521028,"taxon.tab":382011,"vernacular_name.tab":4564}
368_final	Monday 2019-12-02 11:40:19 AM	            {"measurement_or_fact_specific.tab":2307584,"occurrence.tab":521028,"taxon.tab":373124,"vernacular_name.tab":4564}

rows of 4: Consistent OK: Removed Polychaeta (id = 7022)
368	Tuesday 2020-02-11 09:21:54 AM	                    {"measurement_or_fact.tab":2417696,"occurrence.tab":543891,"taxon.tab":396721,"vernacular_name.tab":6979}
368	Tuesday 2020-02-11 09:49:48 AM	                    {"measurement_or_fact.tab":2404575,"occurrence.tab":540590,"taxon.tab":394262,"vernacular_name.tab":6965}
368_removed_aves	Tuesday 2020-02-11 10:00:35 AM	    {"measurement_or_fact_specific.tab":2344674,"occurrence.tab":527826,"taxon.tab":387880,"vernacular_name.tab":4692}
368_final	Tuesday 2020-02-11 10:13:35 AM	            {"measurement_or_fact_specific.tab":2344674,"occurrence.tab":527826,"taxon.tab":378879,"vernacular_name.tab":4692}

Reminders:
# Three (3) connectors to run, one after the other - OK

# OK
php5.6 pbdb_fresh_harvest.php jenkins #this generates 368.tar.gz

# This will adjust newly generated 368.tar.gz. 
# It'll remove all Aves descendants as requested.
php5.6 remove_Aves_children_from_368.php jenkins #this generates 368_removed_aves.tar.gz

# This will remove bad synonyms
php5.6 synonyms_handling.php jenkins 368_final #this generates 368_final.tar.gz
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