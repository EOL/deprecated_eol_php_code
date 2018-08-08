<?php
namespace php_active_record;
/* https://eol-jira.bibalex.org/browse/TRAM-737
BOLDS connector for new API service
estimated execution time: 1 day, 6 hours in eol-archive

81	Monday 2018-08-06 04:35:48 PM	{"agent.tab":1952,"measurement_or_fact.tab":54992,"media_resource.tab":503633,"occurrence.tab":54992,"taxon.tab":392567} eol-archive
81	Monday 2018-08-06 11:44:49 PM	{"agent.tab":1952,"measurement_or_fact.tab":54996,"media_resource.tab":503701,"occurrence.tab":54996,"taxon.tab":392567} eol-archive
81	Monday 2018-08-06 11:44:49 PM	{"agent.tab":1952,"measurement_or_fact.tab":54996,"media_resource.tab":503701,"occurrence.tab":54996,"taxon.tab":392567} eol-archive
81	Tuesday 2018-08-07 12:11:13 PM	{"agent.tab":1920,"measurement_or_fact.tab":54398,"media_resource.tab":502487,"occurrence.tab":54398,"taxon.tab":392563} mac mini
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
ini_set('memory_limit','7096M');
// $GLOBALS['ENV_DEBUG'] = false;
$timestart = time_elapsed();

// $resource_id = "Annelida_new"; //Animals
// $resource_id = "Rhodophyta_new"; //Plants
// $resource_id = "Basidiomycota_new"; //Fungi
// $resource_id = "Protista_new";
$resource_id = '81';
// $resource_id = "Arthropoda"; //Animals
// $resource_id = 'Priapulida'; //smallest phylum

/* tests...
// $json = Functions::lookup_with_cache("http://www.boldsystems.org/index.php/API_Tax/TaxonData?taxId=30367&dataTypes=all");
// print_r(json_decode($json, true));

// $taxids[1]['images'] = array("a",'b','c');
// $taxids[2]['images'] = array("e",'f','g');
// $taxids[1]['parentID'] = "parent of 1";
// $taxids[2]['parentID'] = "parent of 2";
// foreach($taxids as $taxid => $images) {
//     echo "\n taxid is [$taxid]";
//     print_r($images);
// }

require_library('connectors/BOLDS_DumpsServiceAPI');
$func = new BOLDS_DumpsServiceAPI($resource_id);
if($info = $func->get_info_from_page(170890)) {
    print_r($info);
}
exit("\n");
*/

// /* using Dumps
require_library('connectors/BOLDS_DumpsServiceAPI');
$func = new BOLDS_DumpsServiceAPI($resource_id);
$func->start_using_dump();
unset($func);
Functions::finalize_dwca_resource($resource_id, false);
// */

require_library('connectors/DWCADiagnoseAPI');
$func = new DWCADiagnoseAPI();
if($undefined = $func->check_if_all_parents_have_entries($resource_id, true)) { //2nd param True means write to text file
    $arr['parents without entries'] = $undefined;
    print_r($arr);
}
else echo "\nAll parents have entries OK\n";

recursive_rmdir(CONTENT_RESOURCE_LOCAL_PATH.$resource_id);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>
