<?php
namespace php_active_record;
/* DATA-1748: GBIF map data harvest
This will generate the map data (.json files) for the EOL maps.
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/GBIFoccurrenceAPI_DwCA');
$timestart = time_elapsed();
$GLOBALS['ENV_DEBUG'] = true;

/* for eol-archive
php gbif_georeference_dwca.php _ '{"group":"Gadus morhua","divisor":2}'
php update_resources/connectors/gbif_georeference_dwca.php jenkins '{"group":"Gadus morhua","divisor":3}'

*/


/* sample command line for this script:
php                             gbif_georeference_dwca.php _ '{"group":"Gadus morhua","divisor":2}'
php update_resources/connectors/gbif_georeference_dwca.php _ '{"group":"Gadus morhua","divisor":2}'

php                             gbif_georeference_dwca.php _ '{"task":"save_ids_to_text_from_many_folders"}'
php                             gbif_georeference_dwca.php _ '{"task":"generate_map_data_using_GBIF_csv_files"}'
php                             gbif_georeference_dwca.php _ '{"task":"generate_map_data_using_GBIF_csv_files","sciname":"Ailuropoda melanoleuca","tc_id":328070}'
php update_resources/connectors/gbif_georeference_dwca.php _ '{"task":"generate_map_data_using_GBIF_csv_files","sciname":"Ailuropoda melanoleuca","tc_id":328070}'

*/

$func = new GBIFoccurrenceAPI_DwCA();

print_r($argv);
$params['jenkins_or_cron']   = @$argv[1]; //irrelevant here
$params['json']              = @$argv[2]; //useful here
$arr = json_decode($params['json'], true);

if($group = @$arr['group'] && $divisor = @$arr['divisor']) {
    // $group = 'Gadus morhua'; $divisor = 2; //force assign
    $batches = $func->get_range_batches($group, $divisor);
    print_r($batches);
    $func->jenkins_call($group, $batches);
    // exit("\n-end test-\n");
}
if($task = @$arr['task']) {
    if($task == "generate_map_data_using_GBIF_csv_files") {
        // print_r($arr); exit;
        if(($sciname = @$arr['sciname']) && ($tc_id = @$arr['tc_id'])) {
            $func->generate_map_data_using_GBIF_csv_files($sciname, $tc_id);
        }
        else $func->generate_map_data_using_GBIF_csv_files();
    }
    elseif($task == "save_ids_to_text_from_many_folders")     $func->save_ids_to_text_from_many_folders(); //utility, important as last step. This is now added to main program $func->start(); 
}


/* Original. Use this if you want run one instance of the connector. Very long to finish.
$func = new GBIFoccurrenceAPI_DwCA();
$func->start();
*/

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";

?>
