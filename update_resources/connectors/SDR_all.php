<?php
namespace php_active_record;
/*  DATA-1777: Writing resource files
    https://eol-jira.bibalex.org/browse/DATA-1777?focusedCommentId=63478&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-63478
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/SummaryDataResourcesAllAPI');
// $GLOBALS['ENV_DEBUG'] = false; //false is default in production
if(!$GLOBALS['ENV_DEBUG']) error_reporting(E_ERROR | E_PARSE);

/*
$a = array(5319, 1905, 2774383, 8814528, 1, 2910700, 2908256, 2913056);
$a = array_reverse($a); print_r($a);
$temp = $a;
foreach($a as $id) {
    array_shift($temp);
    if(isset($children_of[$id])) $children_of[$id] = array_merge($children_of[$id], $temp);
    else                         $children_of[$id] = $temp;
    $children_of[$id] = array_unique($children_of[$id]);
}
print_r($children_of);
exit("\n");
*/
/*
$str = "http://purl.obolibrary.org/obo/ENVO_00000020, http://purl.obolibrary.org/obo/ENVO_00000043, http://purl.obolibrary.org/obo/ENVO_00000065";
$arr = explode(",", $str);
$arr = array_map('trim', $arr);
asort($arr); print_r($arr); 
echo "\n rows: ".count($arr);
foreach($arr as $tip) echo "\n$tip";
exit("\ntotal: ".count($arr)."\n");
*/
/*
$file = "/Volumes/AKiTiO4/web/cp/summary data resources/page_ids/99/cd/R96-PK42697173.txt";
$file = "/Volumes/AKiTiO4/web/cp/summary data resources/page_ids/38/49/R344-PK19315117.txt";
$json = file_get_contents($file);
print_r(json_decode($json, true)); exit;
*/

ini_set('memory_limit','7096M'); //required
$timestart = time_elapsed();
$resource_id = 'SDR_all';

/* for every new all-trait-export, must update these vars: Done already for 2019Nov11 */
$folder_date = "20190822";
$folder_date = "20191111";
$folder_date = "20200626";
$func = new SummaryDataResourcesAllAPI($resource_id, $folder_date);

/* command-line syntax
php update_resources/connectors/SDR_all.php _ '{"task":"download_extract_zip_file", "traits_zip_file":"traits_all_202006.zip"}'
e.g. http://varela.csail.mit.edu/~jar/tmp/traits_all_202006.zip
-> run only once every harvest

php update_resources/connectors/SDR_all.php _ '{"task":"build_MySQL_table_from_text"}'      //2.36 minutes
php update_resources/connectors/SDR_all.php _ '{"task":"update_inferred_file"}'             //51.96 seconds
php update_resources/connectors/SDR_all.php _ '{"task":"generate_refs_per_eol_pk_MySQL"}'   //a few mins.
php update_resources/connectors/SDR_all.php _ '{"task":"build_MySQL_table_from_csv"}'       //3.22 minutes
php update_resources/connectors/SDR_all.php _ '{"task":"generate_page_id_txt_files_MySQL"}' //35.18 minutes
php update_resources/connectors/SDR_all.php _ '{"task":"pre_parent_basal_values"}'          //42.46 seconds

In Jenkins, this will run all at the same time. But due to the different delays, each will run 3 mins after the other.
php update_resources/connectors/SDR_all.php _ '{"task":"build_up_children_cache", "delay_in_seconds":0}'
php update_resources/connectors/SDR_all.php _ '{"task":"build_up_children_cache", "delay_in_seconds":180}'
php update_resources/connectors/SDR_all.php _ '{"task":"build_up_children_cache", "delay_in_seconds":360}'
*During the Jun 26, 2020 harvest, I only used 2 connectors since predicate 'habitat' was already excluded. It was fast enough using 2 only.

Actual reports:
php update_resources/connectors/SDR_all.php _ '{"task":"print_lifeStage_statMeth"}'         //1.32 hours
php update_resources/connectors/SDR_all.php _ '{"task":"print_basal_values"}'               //1.65 hours
php update_resources/connectors/SDR_all.php _ '{"task":"print_parent_basal_values"}'        //2.89 hours
php update_resources/connectors/SDR_all.php _ '{"task":"print_taxon_summary"}'              //22.92 minutes
php update_resources/connectors/SDR_all.php _ '{"task":"print_parent_taxon_summary"}'       //12.58 hours
Tests:
php update_resources/connectors/SDR_all.php _ '{"task":"test_basal_values"}'
php update_resources/connectors/SDR_all.php _ '{"task":"test_parent_basal_values"}'
php update_resources/connectors/SDR_all.php _ '{"task":"test_taxon_summary"}'
php update_resources/connectors/SDR_all.php _ '{"task":"test_parent_taxon_summary"}'
php update_resources/connectors/SDR_all.php _ '{"task":"test_lifeStage_statMeth"}'

*/
// print_r($argv);
$params['jenkins_or_cron']   = @$argv[1]; //irrelevant here
$params['json']              = @$argv[2]; //useful here
$fields = json_decode($params['json'], true);
$task = $fields['task']; 
$traits_zip_file = @$fields['traits_zip_file']; //print_r($fields);
$first_tasks = array("download_extract_zip_file", "build_MySQL_table_from_text", "update_inferred_file", "generate_refs_per_eol_pk_MySQL", "build_MySQL_table_from_csv", "generate_page_id_txt_files_MySQL", "pre_parent_basal_values");
if(in_array($task, $first_tasks)) $stop_here = true;
else                              $stop_here = false;

if($task == 'download_extract_zip_file') $func->download_extract_zip_file($traits_zip_file);

// /* build data files - MySQL tables --- worked OK
if($task == 'build_MySQL_table_from_text') $func->build_MySQL_table_from_text('DH_lookup'); //used for parent methods. TO BE RUN EVERY NEW DH. Done already for DHv1.1
            // DH_lookup    1,847,511   DHv1.1
// */

// /* can run one after the other: Done for 2019Aug22 | 2019Nov11 ======================================================== this block worked OK
if($task == 'update_inferred_file') $func->update_inferred_file(); //exit("\n-end 2019Nov11-\n");
    // csv file rows:   1,199,241   2019Nov11
                     // 3,963,652   2020Jun26

if($task == 'generate_refs_per_eol_pk_MySQL') $func->generate_refs_per_eol_pk_MySQL(); //exit("\n-end 2019Nov11-\n");
    // metadata_refs   984,498 2019Aug22
    //               1,207,934 2019Nov11
                  // 1,293,276
                  // 1,207,039 2020Jun26

if($task == 'build_MySQL_table_from_csv') $func->build_MySQL_table_from_csv('metadata_LSM'); //exit("\n-end 2019Nov11-\n"); //used for method: lifestage and statMeth()
    // metadata_LSM    1,727,545   2019Aug22
    //                 1,878,398   2019Nov11
                    // 1,943,618
                    // 2,164,194    2020Jun26

// these four are for the main traits table 
if($task == 'generate_page_id_txt_files_MySQL') { // execution time: 43.49 minutes
    $func->generate_page_id_txt_files_MySQL('BV');
    // $func->generate_page_id_txt_files_MySQL('BVp'); //excluded, same as BV
    $func->generate_page_id_txt_files_MySQL('TS');
    $func->generate_page_id_txt_files_MySQL('TSp');
    $func->generate_page_id_txt_files_MySQL('LSM');
    // traits_BV   2019Aug22   3,525,177
    //             2019Nov11   5,724,786
                            // 5,429,332
                            // 4,458,375    2020Jun26
    // 
    // traits_LSM  2019Aug22   190,833
    //             2019Nov11   309,906
                            // 310,459
                            // 341,280      2020Jun26
    //             
    // traits_TS   2019Aug22   2,178,526
    //             2019Nov11   3,089,998
                            // 3,117,600
                            // 2,638,694    2020Jun26
    // 
    // traits_TSp  2019Aug22   1,402,799
    //             2019Nov11   1,969,893
                            // 2,105,309
                            // 2,553,898    2020Jun26
}

/*
preparation for parent basal values. This takes some time.
this was first manually done last: Jun 9, 2019 - for ALL TRAIT EXPORT - SDR_all_readmeli.txt for more details
INSERT INTO page_ids_Present       SELECT DISTINCT t.page_id from SDR.traits_BV t WHERE t.predicate = 'http://eol.org/schema/terms/Present'
INSERT INTO page_ids_Habitat       SELECT DISTINCT t.page_id from SDR.traits_BV t WHERE t.predicate = 'http://eol.org/schema/terms/Habitat';
INSERT INTO page_ids_FLOPO_0900032 SELECT DISTINCT t.page_id from SDR.traits_BV t WHERE t.predicate = 'http://purl.obolibrary.org/obo/FLOPO_0900032';

$func->pre_parent_basal_values(); return; //Worked OK on the new fresh harvest 'All Trait Export': 2019Jun13 & 2019Aug22. But didn't work anymore for 2019Nov11.
On 2019Nov11. Can no longer accommodate big files, memory-wise I think. Used manual again, login to "mysql>", notes in SDR_all_readmeli.txt instead.
page_ids_FLOPO_0900032  2019Aug22    189,741
                        2019Nov11    160,560
                                     161,111
                                     171,178    2020Jun26

page_ids_Habitat        2019Aug22    344,704
                        2019Nov11    391,046
                                     388,650
                                     1          2020Jun26

page_ids_Present        2019Aug22    1,242,249
                        2019Nov11    1,116,012
                                     1,120,433
                                     1,164,068  2020Jun26
*/
if($task == 'pre_parent_basal_values') $func->pre_parent_basal_values(); //Updated script. Works OK as of Jun 23, 2020. No more manual step needed. Exec time: 64.41 seconds
if($stop_here) {
    elapsed_time($timestart); exit("\n--[$task] end--\n");
}
// ========================================================================================================== */

// /* IMPORTANT STEP - for parent BV and parent TS =============================================================================== should run every new all-trait-export.
if($task == 'build_up_children_cache') { //can run max 3 connectors. auto-breakdown installed. Just 3 connectors so CPU wont max out.
    if($val = @$fields['delay_in_seconds']) sleep($val);
    $func->build_up_children_cache();
    elapsed_time($timestart); exit("\n-- end build_up_children_cache() $folder_date --\n");
}
// use this for single page_id: working OK
// $page_id = 6551609;
// $page_id = 2366;
// $page_id = 46451825; //aborted
// $func->build_up_children_cache($page_id); exit("\n-end build_up_children_cache() for [$page_id]-\n");

// $arr = $func->get_children_from_txt_file($page_id); //check the file path
// print_r($arr); exit("\nJust a utility. Not part of steps.\n");

// $json = file_get_contents("/Volumes/AKiTiO4/web/cp/summary_data_resources/page_ids_20190822/d3/b1/2366_ch.txt");
// $json = file_get_contents("/Volumes/AKiTiO4/web/cp/summary_data_resources/page_ids_20190822/ee/20/6551609_ch.txt");
// $json = file_get_contents("/Volumes/AKiTiO4/web/cp/summary_data_resources/page_ids_20190822/26/dd/2774383_ch.txt");
// $arr = json_decode($json, true); print_r($arr);
// =============================================================================================================================== */
/*
$func->investigate_metadata_csv(); exit("\nJust a utility. Not part of steps.\n");
*/
if($task == 'test_basal_values') $func->test_basal_values('BV');
if($task == 'print_basal_values') $func->print_basal_values('BV');                      //main orig report -- 3.91 hrs
if($task == 'test_parent_basal_values') $func->test_parent_basal_values('BV', false);   //2nd parm is debugModeYN
if($task == 'print_parent_basal_values') $func->print_parent_basal_values('BV');        //main orig report -- 92.75 minutes | 1.25 hrs
// $func->print_parent_basal_values('BV', false, false, true);  return; //4th param true means it is debugMode true

// /* for multiple page_ids: BV
// $page_ids = array(7662, 4528789, 7675, 7669, 7672, 10647853, 7673, 7674, 4529519, 39311345, 7663, 4524096, 7665, 7677, 7676, 7664, 7670, 7671, 7666, 7667, 7668);
// $page_ids = array(7662);
// $func->print_parent_basal_values('BV', $page_ids, 'Carnivora'); return; //used also for test for SampleSize task
// $page_ids = array(1); $func->print_parent_basal_values('BV', $page_ids, 'Metazoa'); //return;
// foreach($page_ids as $page_id) $final[$page_id] = array('taxonRank' => 'not species', 'Landmark' => 1); //good but not used eventually
// */

if($task == 'test_taxon_summary') $func->test_taxon_summary('TS');
if($task == 'print_taxon_summary') $func->print_taxon_summary('TS');                //main orig report - 36.30 minutes | 9.88 minutes | 10.73 minutes
if($task == 'test_parent_taxon_summary') $func->test_parent_taxon_summary('TSp');   //[7665], http://purl.obolibrary.org/obo/RO_0002470
if($task == 'print_parent_taxon_summary') $func->print_parent_taxon_summary('TSp'); //main orig report - 4.23 hrs | 4.89 hrs Aug12'19 | 2.01 hrs | 14.3 hrs Nov14'19
// $func->print_parent_taxon_summary('TSp', array('7662' => array('taxonRank' => 'not species', 'Landmark' => 1)), '7662'); return; //not used eventually

/* for multiple page_ids: TS
$page_ids = array(7662, 4528789, 7675, 7669, 7672, 10647853, 7673, 7674, 4529519, 39311345, 7663, 4524096, 7665, 7677, 7676, 7664, 7670, 7671, 7666, 7667, 7668);
$page_ids = array(7662);
// $func->print_parent_taxon_summary('TSp', $page_ids, 'Carnivora'); return;
// $func->print_parent_taxon_summary('TSp', $page_ids, 'Carnivora', true); return; //4th param true means it is debugMode true
$func->print_parent_taxon_summary('TSp', false, false, true); return; //4th param true means it is debugMode true
*/

if($task == 'test_lifeStage_statMeth') $func->test_lifeStage_statMeth('LSM');
if($task == 'print_lifeStage_statMeth') $func->print_lifeStage_statMeth('LSM');    //main orig report //49.38 min. | 48.11 min. | 1.2 hrs |
elapsed_time($timestart);

function elapsed_time($timestart)
{
    $elapsed_time_sec = time_elapsed() - $timestart;
    echo "\n\n";
    echo "\n elapsed time = " . $elapsed_time_sec . " seconds";
    echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
    echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
    echo "\n Done processing.\n";
}
?>