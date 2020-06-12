<?php
namespace php_active_record;
/*
WORMS archive
Now partner provides/hosts a DWC-A file. Connector also converts Distribution text into structured data.
estimated execution time: 30 mins, excluding download time of the archive file from partner's server
*Started Jenkins execution 2017-Oct-10. Took 22 mins.
                                                                                                        2017
                24Sep'14    20Nov'14                    1Dec'14     1Apr'15     8Apr    15Jun   8March  6-Oct   10-Oct
agent:          [922]       948                         948         1015        1033    1044    1245    1352    1388
measurement:    [1,172,968] 1,484,488   diff 311,520    293,645     383423      383981  384798  411206  425373  428106
media_resource: [101,513]   102,009     diff 496        102,009     134461      134708  135570  144212  152067  160716
occurrence:     [279,966]   576,055                     291,683     380880      381457  382283          422847  425583
reference:      [319987]    322257                      322257      550506      552080  554390  581465  629030  637206
taxon:          [311866]    313006      diff 1,140      313006      539550      540877  512283  532578  573202  581849
vernacular:     [42231]     42226                       42226       46654       46657   46661   47809   72552   73967
                            
                            2017
with media objects:         6-Oct   10-Oct
[accepted]  =>      327966  332194  337287
[count]     =>      539549  573202  581849
[synonym]   =>      211583  203983  207575
[]          =>                      36987

dynamic hierarchy   27Apr2017   18May2017
[accepted] =>       303570      300105
[synonym] =>        134132      135024
[] =>               5329        0
[count] =>          443031      435129

total no parent:    134134      135026          ? not sure about these counts
/terms/taxon:       443440      435608          ? not sure about these counts

                            10-Oct
Total undefined parents:    2503
Total taxa without parents: 207577

Historical:
26	Tuesday 2017-10-10 12:02:32 AM	{"agent.tab":1388,"measurement_or_fact.tab":428106,"media_resource.tab":160716,"occurrence.tab":425583,"reference.tab":637206,"taxon.tab":581849,"vernacular_name.tab":73967}
26	Monday 2017-12-04 07:53:33 PM	{"agent.tab":1403,"measurement_or_fact.tab":431072,"media_resource.tab":164848,"occurrence.tab":428547,"reference.tab":661032,"taxon.tab":603823,"vernacular_name.tab":74103}
26	Friday 2018-03-02 08:54:46 AM	{"agent.tab":1434,"measurement_or_fact.tab":433025,"media_resource.tab":170476,"occurrence.tab":430498,"reference.tab":671182,"taxon.tab":608250,"vernacular_name.tab":74116}
26	Wednesday 2018-03-07 10:44:40 AM{"agent.tab":1434,"measurement_or_fact.tab":433025,"media_resource.tab":170476,"occurrence.tab":430498,"reference.tab":671182,"taxon.tab":608250,"vernacular_name.tab":74116}
26	Wednesday 2018-03-07 07:09:46 PM{"agent.tab":1434,"measurement_or_fact.tab":433025,"media_resource.tab":170476,"occurrence.tab":430498,"reference.tab":671182,"taxon.tab":608250,"vernacular_name.tab":74116} all-hash measurementID
26	Thursday 2018-03-08 08:14:32 PM	{"agent.tab":1434,"measurement_or_fact.tab":433025,"media_resource.tab":170476,"occurrence.tab":430498,"reference.tab":671182,"taxon.tab":608250,"vernacular_name.tab":74116}
26	Thursday 2018-10-18 02:32:29 AM	{"agent.tab":1533,"measurement_or_fact.tab":539555,"media_resource.tab":92033,"occurrence.tab":535762,"reference.tab":691566,"taxon.tab":625782,"vernacular_name.tab":74148}
26	Thursday 2018-10-18 09:38:17 PM	{"agent.tab":1533,"measurement_or_fact.tab":539555,"media_resource.tab":85041,"occurrence.tab":535762,"reference.tab":691566,"taxon.tab":625782,"vernacular_name.tab":74148}
26	Saturday 2019-08-10 12:25:16 AM	{"agent.tab":1592,"measurement_or_fact.tab":553562,"media_resource.tab":85088,"occurrence.tab":549786,"reference.tab":604956,"taxon.tab":579191,"vernacular_name.tab":79059}
exec time: ~30 minutes

as of Aug 23, 2019
26	Friday 2019-08-23 05:38:00 AM	{"agent.tab":1592,"association.tab":69630,"measurement_or_fact_specific.tab":2853465,"media_resource.tab":85088,"occurrence_specific.tab":2138761,"reference.tab":604956,"taxon.tab":579821,"vernacular_name.tab":79059} Mac Mini
26	Friday 2019-08-23 05:09:26 AM	{"agent.tab":1592,"association.tab":69630,"measurement_or_fact_specific.tab":2853465,"media_resource.tab":85088,"occurrence_specific.tab":2138761,"reference.tab":604956,"taxon.tab":579821,"vernacular_name.tab":79059} eol-archive
26	Friday 2019-08-23 08:53:32 AM	{"agent.tab":1592,"association.tab":69629,"measurement_or_fact_specific.tab":2853465,"media_resource.tab":85088,"occurrence_specific.tab":2138760,"reference.tab":604956,"taxon.tab":579820,"vernacular_name.tab":79059} eol-archive
start below of new traits, no synonyms, only accepted taxa
26	Monday 2019-08-26 05:58:25 AM	{"agent.tab":1592,"association.tab":69107,"measurement_or_fact_specific.tab":3354792,"media_resource.tab":85088,"occurrence_specific.tab":2141506,"reference.tab":604956,"taxon.tab":338472,"vernacular_name.tab":79059} eol-archive, same as Mac Mini
26	Tuesday 2019-09-03 11:43:25 PM	{"agent.tab":1597,"association.tab":73215,"measurement_or_fact_specific.tab":3376634,"media_resource.tab":85232,"occurrence_specific.tab":2160600,"reference.tab":608368,"taxon.tab":340007,"vernacular_name.tab":79081} consistent OK
26	Tuesday 2019-09-10 12:49:04 AM	{"agent.tab":1597,"association.tab":73215,"measurement_or_fact_specific.tab":3376634,"media_resource.tab":85232,"occurrence_specific.tab":2160600,"reference.tab":608368,"taxon.tab":340007,"vernacular_name.tab":79081}
26	Thursday 2019-09-19 11:46:55 AM	{"agent.tab":1597,"association.tab":73215,"measurement_or_fact_specific.tab":3376634,"media_resource.tab":85232,"occurrence_specific.tab":2160600,"reference.tab":608368,"taxon.tab":340007,"vernacular_name.tab":79081}
26	Thursday 2019-10-17 08:47:04 AM	{"agent.tab":1603,"association.tab":76387,"measurement_or_fact_specific.tab":3406848,"media_resource.tab":85431,"occurrence_specific.tab":2180297,"reference.tab":612137,"taxon.tab":341924,"vernacular_name.tab":79111,"time_elapsed":{"min":43.71,"hr":0.73}} consistent OK
26	Sunday 2019-11-10 12:50:29 AM	{"agent.tab":1612,"association.tab":76363,"measurement_or_fact_specific.tab":3410186,"media_resource.tab":85602,"occurrence_specific.tab":2182490,"reference.tab":614535,"taxon.tab":342039,"vernacular_name.tab":79130,"time_elapsed":{"sec":2548.89,"min":42.48,"hr":0.71}}

Based on new Dec 2019: http://www.marinespecies.org/export/eol/WoRMS2EoL.zip.
'http://rs.tdwg.org/dwc/terms/measurementType' == 'Feedingtype' does not exist anymore
So this means there is no more association data from WoRMS.
26	Friday 2019-12-06 07:34:50 AM	{"agent.tab":1615,"measurement_or_fact_specific.tab":3406168,"media_resource.tab":85783,"occurrence_specific.tab":2068198,"reference.tab":616890,"taxon.tab":335022,"vernacular_name.tab":79161,"time_elapsed":{"sec":2669.63,"min":44.49,"hr":0.74}}
26	Sunday 2019-12-15 11:54:39 PM	{"agent.tab":1615,"measurement_or_fact_specific.tab":3406168,"media_resource.tab":85783,"occurrence_specific.tab":2068198,"reference.tab":616890,"taxon.tab":335022,"vernacular_name.tab":79161,"time_elapsed":{"sec":2863.38,"min":47.72,"hr":0.8}}
26	Monday 2019-12-16 01:39:12 PM	{"agent.tab":1615,"measurement_or_fact_specific.tab":3406165,"media_resource.tab":85783,"occurrence_specific.tab":2068198,"reference.tab":616890,"taxon.tab":335022,"vernacular_name.tab":79161,"time_elapsed":{"sec":2689.37,"min":44.82,"hr":0.75}}
26	Thursday 2020-02-06 04:47:05 AM	{"agent.tab":1639,"measurement_or_fact_specific.tab":3416758,"media_resource.tab":86178,"occurrence_specific.tab":2073724,"reference.tab":639196,"taxon.tab":352690,"vernacular_name.tab":79229,"time_elapsed":{"sec":2752.68,"min":45.88,"hr":0.76}}
26	Tuesday 2020-02-11 04:42:40 AM	{"agent.tab":1639,"measurement_or_fact_specific.tab":3414599,"media_resource.tab":86178,"occurrence_specific.tab":2073724,"reference.tab":639196,"taxon.tab":352690,"vernacular_name.tab":79229,"time_elapsed":{"sec":2806.72,"min":46.78,"hr":0.78}}

Start where MoF records with parents in 26_undefined_parentMeasurementIDs.txt will be auto removed:
26	Tuesday 2020-02-11 09:50:00 AM	{"agent.tab":1639,"measurement_or_fact_specific.tab":3414427,"media_resource.tab":86178,"occurrence_specific.tab":2073724,"reference.tab":639196,"taxon.tab":352690,"vernacular_name.tab":79229,"time_elapsed":false}
26	Wednesday 2020-02-12 04:25:35 AM{"agent.tab":1639, "measurement_or_fact_specific.tab":3414427, "media_resource.tab":86178, "occurrence_specific.tab":2073724, "reference.tab":639196, "taxon.tab":352690, "vernacular_name.tab":79229,"time_elapsed":false}
                                    {"agent.tab":1639, "measurement_or_fact_specific.tab":3414427, "media_resource.tab":86178, "occurrence_specific.tab":2073724, "reference.tab":639196, "taxon.tab":352690, "vernacular_name.tab":79229}
26	Monday 2020-03-02 08:55:43 AM	{"agent.tab":1644, "measurement_or_fact_specific.tab":3408286, "media_resource.tab":86519, "occurrence_specific.tab":2106782, "reference.tab":641496, "taxon.tab":353383, "vernacular_name.tab":79240, "time_elapsed":{"sec":2881.02, "min":48.02, "hr":0.8}}
26	Monday 2020-03-02 09:28:04 AM	{"agent.tab":1644, "measurement_or_fact_specific.tab":3405872, "media_resource.tab":86519, "occurrence_specific.tab":2106782, "reference.tab":641496, "taxon.tab":353383, "vernacular_name.tab":79240, "time_elapsed":false}
Expected reduced no. of MoF and Occurrence --- so consistent OK
26	Thursday 2020-03-19 12:04:40 PM	{"agent.tab":1644, "measurement_or_fact_specific.tab":3374264, "media_resource.tab":86519, "occurrence_specific.tab":2082560, "reference.tab":641496, "taxon.tab":353383, "vernacular_name.tab":79240, "time_elapsed":false}
Batches of 2 rows:
26	Friday 2020-06-12 01:22:50 AM	{"agent.tab":1664, "measurement_or_fact_specific.tab":3417987, "media_resource.tab":87379, "occurrence_specific.tab":2121662, "reference.tab":648535, "taxon.tab":355711, "vernacular_name.tab":80309, "time_elapsed":{"sec":3890.43, "min":64.84, "hr":1.08}}
26	Friday 2020-06-12 02:14:25 AM	{"agent.tab":1664, "measurement_or_fact_specific.tab":3414249, "media_resource.tab":87379, "occurrence_specific.tab":2121662, "reference.tab":648535, "taxon.tab":355711, "vernacular_name.tab":80309, "time_elapsed":false}
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");

// $a['eli'] = '222';
// // $a[201306015586596728] = "http://purl.jp/bio/4/id/201306015586596728";
// $b['eli'] = '2s2s2s';
// // $b['kk'] = 'kkk';
// // $b[2] = '333';
// // $c = $a + $b;
// $c = array_merge($a, $b);
// print_r($c); exit("\n-end-\n");

/* testing...
$id = 1;
$id = 607688;
// $json = Functions::lookup_with_cache("http://www.marinespecies.org/rest/AphiaChildrenByAphiaID/$id");
// echo "\n[$json]\n";

// if(Functions::url_already_cached("http://www.marinespecies.org/rest/AphiaChildrenByAphiaID/$id")) echo "\nalready cached\n";
// else echo "\nnot yet cached\n";

$error_no = Functions::fake_user_agent_http_get("http://www.marinespecies.org/rest/AphiaChildrenByAphiaID/$id", array("return_error_no" => true));
echo "\n[$error_no]\n";
if($error_no == 0) echo "\nAccess OK\n";
else echo "Error access";
exit;
*/

/* e.g. php 26.php jenkins taxonomy */
$cmdline_params['jenkins_or_cron']  = @$argv[1]; //irrelevant here
$cmdline_params['what']             = @$argv[2]; //useful here

require_library('connectors/WormsArchiveAPI');
$timestart = time_elapsed();
ini_set('memory_limit','7096M'); //required

if($cmdline_params['what'] == "taxonomy") $resource_id = "26_taxonomy";     //'taxonomy' -> used for DWH
else {                                                                      //'media_objects' is for original resource = 26
    $resource_id = 26;
    $cmdline_params['what'] = "media_objects";
}

// /* //main operation
$func = new WormsArchiveAPI($resource_id);
$func->get_all_taxa($cmdline_params['what']); 
Functions::finalize_dwca_resource($resource_id, false, false, $timestart); //3rd param should be false so it doesn't remove the /26/ folder which will be used below when diagnosing...
// */

/* utility - run this after 6 connectors during build-up
$func = new WormsArchiveAPI($resource_id);
$func->trim_text_files();
exit("\n");
*/

/* utility - Aug 25, 2019: https://eol-jira.bibalex.org/browse/DATA-1827?focusedCommentId=63762&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-63762
This script lists all mtypes and its mvalue that were missing bec. it wasn't initialized by Jen yet. Probably deliberately done to exclude them.
$func = new WormsArchiveAPI($resource_id);
$func->investigate_missing_parents_in_MoF();
exit("\n");
*/

// /* main operation - continued
run_utility($resource_id);
recursive_rmdir(CONTENT_RESOURCE_LOCAL_PATH."26/"); //we can now delete folder after run_utility() - DWCADiagnoseAPI
// */

// ==============================================================================================================================
// /* NEW Feb 11, 2020: start auto-remove children of 26_undefined_parentMeasurementIDs.txt in MoF ------------------------------
if(@filesize(CONTENT_RESOURCE_LOCAL_PATH.'26_undefined_parentMeasurementIDs.txt')) {
    $resource_id = 26;
    $dwca_file = 'https://editors.eol.org/eol_php_code/applications/content_server/resources/26.tar.gz';
    require_library('connectors/DwCA_Utility');
    $func = new DwCA_Utility($resource_id, $dwca_file);

    $preferred_rowtypes = array("http://rs.tdwg.org/dwc/terms/taxon", "http://eol.org/schema/media/document", 
                        "http://eol.org/schema/reference/reference", "http://eol.org/schema/agent/agent", "http://rs.gbif.org/terms/1.0/vernacularname");
    // These 2 will be processed in WoRMS_post_process.php which will be called from DwCA_Utility.php
    // http://rs.tdwg.org/dwc/terms/measurementorfact
    // http://rs.tdwg.org/dwc/terms/occurrence

    $func->convert_archive($preferred_rowtypes);
    Functions::finalize_dwca_resource($resource_id);

    run_utility($resource_id);
    recursive_rmdir(CONTENT_RESOURCE_LOCAL_PATH."26/"); //we can now delete folder after run_utility() - DWCADiagnoseAPI
}
// ------------------------------------------------------------------------------------------------------------------------------ */
// ==============================================================================================================================

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";

function run_utility($resource_id)
{
    // /* utility ==========================
    require_library('connectors/DWCADiagnoseAPI');
    $func = new DWCADiagnoseAPI();

    $undefined_parents = $func->check_if_all_parents_have_entries($resource_id, true); //2nd param true means output will write to text file
    echo "\nTotal undefined parents:" . count($undefined_parents)."\n"; unset($undefined_parents);

    $without = $func->get_all_taxa_without_parent($resource_id, true); //true means output will write to text file
    echo "\nTotal taxa without parents:" . count($without)."\n"; unset($without);

    $undefined_parents = $func->check_if_all_parents_have_entries($resource_id, true, false, false, 'parentMeasurementID', 'measurement_or_fact_specific.tab');
    echo "\nTotal undefined parents MoF:" . count($undefined_parents)."\n";
    // ===================================== */
}
/* as of March 7, 2017
[ranks] => Array
    (
        [kingdom] => 
        [phylum] => 
        [subphylum] => 
        [order] => 
        [class] => 
        [subclass] => 
        [family] => 
        [suborder] => 
        [subfamily] => 
        [superfamily] => 
        [superorder] => 
        [infraorder] => 
        [superclass] => 
        [genus] => 
        [subkingdom] => 
        [tribe] => 
        [subgenus] => 
        [species] => 
        [variety] => 
        [subspecies] => 
        [section] => 
        [subsection] => 
        [form] => 
        [subvariety] => 
        [subform] => 
        [] => 
        [subtribe] => 
    )

[status] => Array
    (
        [accepted] => 
        [synonym] => 
        [] => 
    )

[establishmentMeans] => Array
    (
        [] => 
        [Alien] => 
        [Native - Endemic] => 
        [Native] => 
        [Origin uncertain] => 
        [Origin unknown] => 
        [Native - Non-endemic] => 
    )

[occurrenceStatus] => Array
    (
        [present] => 
        [excluded] => 
        [doubtful] => 
    )
*/
?>