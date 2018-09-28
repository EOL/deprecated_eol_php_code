<?php
namespace php_active_record;
/*
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/SummaryDataResourcesAPI');

/*
$url = "http://eol.org/schema/terms/Habitat";
// $url = "http://biocol.org/urn:lsid:biocol.org:col:34613";
print_r(pathinfo($url)); exit("\n".pathinfo($url, PATHINFO_BASENAME)."\n");
*/

/*
$a = array(1,2,3,4); print_r($a);
$last = array_pop($a); 
print_r($a);
echo "\nlast: [$last]\n"; exit;
*/

/*
$str = "http://purl.obolibrary.org/obo/ENVO_00000020, http://purl.obolibrary.org/obo/ENVO_00000043, http://purl.obolibrary.org/obo/ENVO_00000065, 
http://purl.obolibrary.org/obo/ENVO_00000067, http://purl.obolibrary.org/obo/ENVO_00000081, http://purl.obolibrary.org/obo/ENVO_00000086, 
http://purl.obolibrary.org/obo/ENVO_00000220, http://purl.obolibrary.org/obo/ENVO_00000264, http://purl.obolibrary.org/obo/ENVO_00000360, 
http://purl.obolibrary.org/obo/ENVO_00000446, http://purl.obolibrary.org/obo/ENVO_00001995, http://purl.obolibrary.org/obo/ENVO_00002000, 
http://purl.obolibrary.org/obo/ENVO_00002033, http://purl.obolibrary.org/obo/ENVO_01000206, http://purl.obolibrary.org/obo/ENVO_01001305, 
http://purl.obolibrary.org/obo/ENVO_00000078, http://purl.obolibrary.org/obo/ENVO_00000113, http://purl.obolibrary.org/obo/ENVO_00000144, 
http://purl.obolibrary.org/obo/ENVO_00000261, http://purl.obolibrary.org/obo/ENVO_00000316, http://purl.obolibrary.org/obo/ENVO_00000320, 
http://purl.obolibrary.org/obo/ENVO_00000358, http://purl.obolibrary.org/obo/ENVO_00000486, http://purl.obolibrary.org/obo/ENVO_00000572, 
http://purl.obolibrary.org/obo/ENVO_00000856, http://purl.obolibrary.org/obo/ENVO_00002030, http://purl.obolibrary.org/obo/ENVO_00002040, 
http://purl.obolibrary.org/obo/ENVO_01000204, http://purl.obolibrary.org/obo/ENVO_00000002, http://purl.obolibrary.org/obo/ENVO_00000016, 
http://eol.org/schema/terms/temperate_grasslands_savannas_and_shrublands, http://purl.obolibrary.org/obo/ENVO_01001125";

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
$func->start();
// Functions::finalize_dwca_resource($resource_id);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec . " seconds";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>