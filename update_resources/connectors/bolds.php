<?php
namespace php_active_record;
/* https://eol-jira.bibalex.org/browse/TRAM-737
BOLDS connector for new API service
estimated execution time:
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
// $GLOBALS['ENV_DEBUG'] = false;
$timestart = time_elapsed();
$resource_id = "a";

/* tests...

$taxRank = "family";
$index['subspecies'] = 0;
$index['species'] = 1;
$index['genus'] = 2;
$index['subfamily'] = 3;
$index['family'] = 4;
$index['order'] = 5;
$index['class'] = 6;

$ranks = array("subspecies", "species", "genus", "subfamily", "family", "order", "class", "phylum");

foreach($ranks as $key => $rank) {
    if($key > $index[$taxRank]) {
        echo "\n $key - $rank";
        
    }
}

// $json = Functions::lookup_with_cache("http://www.boldsystems.org/index.php/API_Tax/TaxonData?taxId=30367&dataTypes=all");
// print_r(json_decode($json, true));
exit("\n");
*/

/* using API
require_library('connectors/BOLDS_APIServiceAPI');
$func = new BOLDS_APIServiceAPI($resource_id);
$func->start_using_api();
*/

// /* using Dumps
require_library('connectors/BOLDS_DumpsServiceAPI');
$func = new BOLDS_DumpsServiceAPI($resource_id);
$func->start_using_dump();
// */

Functions::finalize_dwca_resource($resource_id, false);


$func = new DWCADiagnoseAPI();
if($undefined = $func->check_if_all_parents_have_entries($resource_id, true)) { //2nd param True means write to text file
    $arr['parents without entries'] = $undefined;
    print_r($arr);
}
else echo "\nAll parents have entries OK\n";



$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>
