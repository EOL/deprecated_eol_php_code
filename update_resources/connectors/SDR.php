<?php
namespace php_active_record;
/*
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/SummaryDataResourcesAPI');

// $a = array(2,3,4); print_r($a);
// array_unshift($a, 1); print_r($a);
// exit;

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

$timestart = time_elapsed();
$resource_id = 'SDR';
$func = new SummaryDataResourcesAPI($resource_id);

// $func->generate_page_id_txt_files();        return; //important initial step
// $func->generate_children_of_taxa_usingDH(); return; //the big long program

// $func->test_basal_values_parent();       return;
// $func->print_basal_values();      return;

// $func->print_parent_basal_values();      return;
// $func->print_lifeStage_statMeth();       return;


// $func->test_taxon_summary();            return;
// $func->print_taxon_summary();            return;
$func->test_parent_taxon_summary();     return;
// $func->print_parent_taxon_summary();     return;


$func->start();
// Functions::finalize_dwca_resource($resource_id);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec . " seconds";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>



















