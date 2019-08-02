<?php
namespace php_active_record;
/*  DATA-1777: Writing resource files
    https://eol-jira.bibalex.org/browse/DATA-1777?focusedCommentId=63478&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-63478
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/SummaryDataResourcesAllAPI');

/*
$a[3] = 'three';
$a[1] = 'one';
$a[5] = 'five';
print_r($a);
ksort($a);
print_r($a);
exit("\n");
*/

/*
$children_of[111]['01'] = 'species';
$children_of[111]['02'] = 'species';
$children_of[111]['03'] = 'species';
print_r($children_of);
exit;
*/
/*
$arr = array(2,4,6,8,10);
$arr = array(1,2,3,4);
$arr = array(1,2);
$middle = get_middle_record($arr);
echo "\n$arr[$middle]\n";
exit("\n");
*/

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

// $a = Array(5110, 5083, 1905, 2774383, 8814528, 1, 2910700, 2908256, 2913056);
// $a = array_reverse($a);                                                     print_r($a);
// $temp = $a;
// foreach($a as $id) {
//     array_shift($temp);
//     if(isset($children_of[$id])) $children_of[$id] = array_merge($children_of[$id], $temp);
//     else                         $children_of[$id] = $temp;
//     $children_of[$id] = array_unique($children_of[$id]);
// }

print_r($children_of);
exit("\n");
*/

/*
$str = "http://purl.obolibrary.org/obo/ENVO_00000020, http://purl.obolibrary.org/obo/ENVO_00000043, http://purl.obolibrary.org/obo/ENVO_00000065, http://purl.obolibrary.org/obo/ENVO_00000067, 
http://purl.obolibrary.org/obo/ENVO_00000081, http://purl.obolibrary.org/obo/ENVO_00000086, http://purl.obolibrary.org/obo/ENVO_00000220, http://purl.obolibrary.org/obo/ENVO_00000264, 
http://purl.obolibrary.org/obo/ENVO_00000360, http://purl.obolibrary.org/obo/ENVO_00000446, http://purl.obolibrary.org/obo/ENVO_00001995, http://purl.obolibrary.org/obo/ENVO_00002000, 
http://purl.obolibrary.org/obo/ENVO_00002033, http://purl.obolibrary.org/obo/ENVO_01000206, http://purl.obolibrary.org/obo/ENVO_01001305, http://purl.obolibrary.org/obo/ENVO_00000078, 
http://purl.obolibrary.org/obo/ENVO_00000113, http://purl.obolibrary.org/obo/ENVO_00000144, http://purl.obolibrary.org/obo/ENVO_00000261, http://purl.obolibrary.org/obo/ENVO_00000316, 
http://purl.obolibrary.org/obo/ENVO_00000320, http://purl.obolibrary.org/obo/ENVO_00000358, http://purl.obolibrary.org/obo/ENVO_00000486, http://purl.obolibrary.org/obo/ENVO_00000572, 
http://purl.obolibrary.org/obo/ENVO_00000856, http://purl.obolibrary.org/obo/ENVO_00002030, http://purl.obolibrary.org/obo/ENVO_00002040, http://purl.obolibrary.org/obo/ENVO_01000204, 
http://purl.obolibrary.org/obo/ENVO_00000002, http://purl.obolibrary.org/obo/ENVO_00000016, http://eol.org/schema/terms/temperate_grasslands_savannas_and_shrublands, 
http://purl.obolibrary.org/obo/ENVO_01001125";

$arr = explode(",", $str);
$arr = array_map('trim', $arr);
asort($arr); print_r($arr); 

echo "\n rows: ".count($arr);
foreach($arr as $tip) echo "\n$tip";
exit("\ntotal: ".count($arr)."\n");
*/

/* //tests
$parents = array(1,2,3);
$preferred_terms = array(4,5);
$inclusive = array_merge($parents, $preferred_terms);
print_r($inclusive);
exit("\n-end tests'\n");
*/

/*
$arr = json_decode('["717136"]');
if(!is_array($arr) && is_null($arr)) {
    $arr = array();
    echo "\nwent here 01\n";
}
else {
    echo "\nwent here 02\n";
    print_r($arr);
}
exit("\n");
*/

/*
$a1 = array('45511473' => Array(46557930));
$a2 = array('308533' => Array(1642, 46557930));
$a3 = $a1 + $a2; print_r($a3);
exit("\n");
*/

// $json = "[]";
// $arr = json_decode($json, true);
// if(is_array($arr)) echo "\nis array\n";
// else               echo "\nnot array\n";
// if(is_null($arr)) echo "\nis null\n";
// else               echo "\nnot null\n";
// print_r($arr);
// // if(!is_array($arr) && is_null($arr)) $arr = array();
// exit("\n");

// $file = "/Volumes/AKiTiO4/web/cp/summary data resources/page_ids/99/cd/R96-PK42697173.txt";
// $file = "/Volumes/AKiTiO4/web/cp/summary data resources/page_ids/38/49/R344-PK19315117.txt";
// $json = file_get_contents($file);
// print_r(json_decode($json, true)); exit;

// $terms = array("Braunbär", " 繡球菌", "Eli");
// foreach($terms as $t){
//     echo "\n".$t."\n";
//     // $t = utf8_encode($t); echo "\n".$t."\n";
//     $t = Functions::conv_to_utf8($t); echo "\n".$t."\n";
// }
// exit("\nexit muna\n");

ini_set('memory_limit','7096M'); //required
$timestart = time_elapsed();
$resource_id = 'SDR_all';
$func = new SummaryDataResourcesAllAPI($resource_id);

/* build data files - MySQL tables */
// $func->build_MySQL_table_from_text('DH_lookup'); exit; //used for parent methods. DONE.

// $func->generate_refs_per_eol_pk_MySQL(); return;
// $func->build_MySQL_table_from_csv('metadata_LSM'); //return; //used for method: lifestage and statMeth(); DONE

/* normal operation - DONE worked OK
$func->generate_page_id_txt_files_MySQL('BV');
// $func->generate_page_id_txt_files_MySQL('BVp'); //excluded, same as BV
$func->generate_page_id_txt_files_MySQL('TS');
$func->generate_page_id_txt_files_MySQL('TSp');
$func->generate_page_id_txt_files_MySQL('LSM'); return;
*/

/* preparation for parent basal values. This takes some time.
    // this was first manually done last: Jun 9, 2019 - for ALL TRAIT EXPORT - readmeli.txt for more details
    // INSERT INTO page_ids_Present SELECT DISTINCT t.page_id from SDR.traits_BV t WHERE t.predicate = 'http://eol.org/schema/terms/Present'
    // INSERT INTO page_ids_Habitat SELECT DISTINCT t.page_id from SDR.traits_BV t WHERE t.predicate = 'http://eol.org/schema/terms/Habitat';
    // INSERT INTO page_ids_FLOPO_0900032 SELECT DISTINCT t.page_id from SDR.traits_BV t WHERE t.predicate = 'http://purl.obolibrary.org/obo/FLOPO_0900032';
    $func->pre_parent_basal_values(); return; //Worked OK on the new fresh harvest 'All Trait Export' Jun 13, 2019
*/


/* replaced by: generate_page_id_txt_files_MySQL()
$func->generate_page_id_txt_files(); return; //important initial step
*/

/* hasn't ran this yet for All Trait Export
// $func->generate_children_of_taxa_usingDH(); return; //the big long program                  _ids/56/97/10594877 - check this later  _ids/85/70/2634372_c.t
*/

/*
$func->build_up_children_cache(); exit; //uses pages.csv - long long process... STILL RUNNING...
*/

/* replaced by: generate_refs_per_eol_pk_MySQL()
$func->generate_refs_per_eol_pk(); return; //important step for counting refs per eol_pk
*/

// $func->investigate_metadata_csv(); exit;

// $func->test_basal_values('BV');          return;
// $func->print_basal_values('BV');         //return;   //3.91 hours
// $func->test_parent_basal_values('BV', false);   return; //2nd parm is debugModeYN
// $func->print_parent_basal_values('BV');  //return; //main orig report //92.75 minutes
// $func->print_parent_basal_values('BV', false, false, true);  return; //4th param true means it is debugMode true

// $func->gen_SampleSize_4parent_BV('BV', array('7662'));

// /* for multiple page_ids: BV
$page_ids = array(7662, 4528789, 7675, 7669, 7672, 10647853, 7673, 7674, 4529519, 39311345, 7663, 4524096, 7665, 7677, 7676, 7664, 7670, 7671, 7666, 7667, 7668);
$page_ids = array(7662);
$func->print_parent_basal_values('BV', $page_ids, 'Carnivora'); return;
// $page_ids = array(1); $func->print_parent_basal_values('BV', $page_ids, 'Metazoa'); //return;
// foreach($page_ids as $page_id) $final[$page_id] = array('taxonRank' => 'not species', 'Landmark' => 1); //good but not used eventually
// */

// $func->test_taxon_summary('TS');         return;
// $func->print_taxon_summary('TS');        //return;   //36.30 minutes
// $func->test_parent_taxon_summary('TSp');  return;        //[7665], http://purl.obolibrary.org/obo/RO_0002470
// $func->print_parent_taxon_summary('TSp'); //return; //main orig report - 4.23 hours
// $func->print_parent_taxon_summary('TSp', array('7662' => array('taxonRank' => 'not species', 'Landmark' => 1)), '7662'); return; //not used eventually

/* for multiple page_ids: TS
$page_ids = array(7662, 4528789, 7675, 7669, 7672, 10647853, 7673, 7674, 4529519, 39311345, 7663, 4524096, 7665, 7677, 7676, 7664, 7670, 7671, 7666, 7667, 7668);
$page_ids = array(7662);
// $func->print_parent_taxon_summary('TSp', $page_ids, 'Carnivora'); return;
// $func->print_parent_taxon_summary('TSp', $page_ids, 'Carnivora', true); return; //4th param true means it is debugMode true
$func->print_parent_taxon_summary('TSp', false, false, true); return; //4th param true means it is debugMode true
*/

// $func->test_lifeStage_statMeth('LSM');
// $func->print_lifeStage_statMeth('LSM');   //return; //49.38 min. 48.11 min.

// $func->start();
// Functions::finalize_dwca_resource($resource_id);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec . " seconds";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>