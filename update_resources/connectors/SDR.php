<?php
namespace php_active_record;
/*
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/SummaryDataResourcesAPI');

// echo "\n".date("Y-M-d")."\n";  exit;

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
//Jen's roots:
$str = "http://purl.obolibrary.org/obo/ENVO_00000144, http://purl.obolibrary.org/obo/ENVO_00000463, http://purl.obolibrary.org/obo/ENVO_00000856, 
http://purl.obolibrary.org/obo/ENVO_00002009, http://purl.obolibrary.org/obo/ENVO_00002033, http://purl.obolibrary.org/obo/ENVO_01000204, 
http://purl.obolibrary.org/obo/ENVO_01000206, http://purl.obolibrary.org/obo/ENVO_00000446, http://purl.obolibrary.org/obo/ENVO_00002030, 
http://purl.obolibrary.org/obo/ENVO_01001305";

//Jen's tips:
$str = "http://purl.obolibrary.org/obo/ENVO_00000144, http://purl.obolibrary.org/obo/ENVO_00000463, 
http://purl.obolibrary.org/obo/ENVO_00000856, http://purl.obolibrary.org/obo/ENVO_00002009, 
http://purl.obolibrary.org/obo/ENVO_00002033, http://purl.obolibrary.org/obo/ENVO_01000204, 
http://purl.obolibrary.org/obo/ENVO_01000206, http://purl.obolibrary.org/obo/ENVO_00000035, 
http://purl.obolibrary.org/obo/ENVO_00000233, http://purl.obolibrary.org/obo/ENVO_00000301, 
http://purl.obolibrary.org/obo/ENVO_00000097, http://purl.obolibrary.org/obo/ENVO_00000106, 
http://purl.obolibrary.org/obo/ENVO_00000112, http://purl.obolibrary.org/obo/ENVO_00000170, 
http://purl.obolibrary.org/obo/ENVO_00000222, http://purl.obolibrary.org/obo/ENVO_00000261, 
http://purl.obolibrary.org/obo/ENVO_00000303, http://purl.obolibrary.org/obo/ENVO_01000177, 
http://purl.obolibrary.org/obo/ENVO_01000179, http://purl.obolibrary.org/obo/ENVO_00000015, 
http://purl.obolibrary.org/obo/ENVO_00000016, http://purl.obolibrary.org/obo/ENVO_00000208, 
http://purl.obolibrary.org/obo/ENVO_00000264, http://purl.obolibrary.org/obo/ENVO_01000047, 
http://purl.obolibrary.org/obo/ENVO_00000020, http://purl.obolibrary.org/obo/ENVO_00000033, 
http://purl.obolibrary.org/obo/ENVO_00002011, http://purl.obolibrary.org/obo/ENVO_00002010, 
http://purl.obolibrary.org/obo/ENVO_00002019, http://purl.obolibrary.org/obo/ENVO_01000020, 
http://purl.obolibrary.org/obo/ENVO_00000111, http://purl.obolibrary.org/obo/ENVO_01000228, 
http://purl.obolibrary.org/obo/ENVO_00000023, http://purl.obolibrary.org/obo/ENVO_00000887, 
http://purl.obolibrary.org/obo/ENVO_00000890";

//Jen's set 1
$str = "http://purl.obolibrary.org/obo/ENVO_00000144, http://purl.obolibrary.org/obo/ENVO_00000463, http://purl.obolibrary.org/obo/ENVO_00000856, 
http://purl.obolibrary.org/obo/ENVO_00002009, http://purl.obolibrary.org/obo/ENVO_00002033, http://purl.obolibrary.org/obo/ENVO_01000204, 
http://purl.obolibrary.org/obo/ENVO_01000206, http://purl.obolibrary.org/obo/ENVO_00000097, http://purl.obolibrary.org/obo/ENVO_00000106, 
http://purl.obolibrary.org/obo/ENVO_00000112, http://purl.obolibrary.org/obo/ENVO_00000170, http://purl.obolibrary.org/obo/ENVO_00000222, 
http://purl.obolibrary.org/obo/ENVO_00000261, http://purl.obolibrary.org/obo/ENVO_00000303, http://purl.obolibrary.org/obo/ENVO_01000177, 
http://purl.obolibrary.org/obo/ENVO_01000179, http://purl.obolibrary.org/obo/ENVO_00002010, http://purl.obolibrary.org/obo/ENVO_00002019, 
http://purl.obolibrary.org/obo/ENVO_01000020, http://purl.obolibrary.org/obo/ENVO_00000043, http://purl.obolibrary.org/obo/ENVO_00000300, 
http://purl.obolibrary.org/obo/ENVO_00000447, http://purl.obolibrary.org/obo/ENVO_00000873, http://purl.obolibrary.org/obo/ENVO_01000174, 
http://purl.obolibrary.org/obo/ENVO_01000253";

//Jen's set 2

$str = "http://purl.obolibrary.org/obo/ENVO_00000144, http://purl.obolibrary.org/obo/ENVO_00000463, http://purl.obolibrary.org/obo/ENVO_00000856, 
http://purl.obolibrary.org/obo/ENVO_00002009, http://purl.obolibrary.org/obo/ENVO_00002033, http://purl.obolibrary.org/obo/ENVO_01000204, 
http://purl.obolibrary.org/obo/ENVO_01000206, http://purl.obolibrary.org/obo/ENVO_00000097, http://purl.obolibrary.org/obo/ENVO_00000106, 
http://purl.obolibrary.org/obo/ENVO_00000112, http://purl.obolibrary.org/obo/ENVO_00000170, http://purl.obolibrary.org/obo/ENVO_00000222, 
http://purl.obolibrary.org/obo/ENVO_00000261, http://purl.obolibrary.org/obo/ENVO_00000303, http://purl.obolibrary.org/obo/ENVO_01000177, 
http://purl.obolibrary.org/obo/ENVO_01000179, http://purl.obolibrary.org/obo/ENVO_00002010, http://purl.obolibrary.org/obo/ENVO_00002019, 
http://purl.obolibrary.org/obo/ENVO_01000020, http://purl.obolibrary.org/obo/ENVO_00000043, http://purl.obolibrary.org/obo/ENVO_00000300, 
http://purl.obolibrary.org/obo/ENVO_00000447, http://purl.obolibrary.org/obo/ENVO_00000873, http://purl.obolibrary.org/obo/ENVO_01000174, 
http://purl.obolibrary.org/obo/ENVO_00000873";

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