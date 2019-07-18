<?php
namespace php_active_record;
/* DATA-1748: GBIF map data harvest
This will generate the map data (.json files) for the EOL maps.
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");

echo "\nCACHE_PATH xx 01 is ".CACHE_PATH."\n"; //exit;

require_library('connectors/GBIFoccurrenceAPI_DwCA');
ini_set('memory_limit','10096M'); //15096M
$timestart = time_elapsed();
$GLOBALS['ENV_DEBUG'] = true;

/* for eol-archive
php                             gbif_georeference_dwca.php _ '{"group":"Gadus morhua","divisor":2}'
php update_resources/connectors/gbif_georeference_dwca.php jenkins '{"group":"Gadus morhua","divisor":3}'

## normal operation
#php5.6 gbif_georeference_dwca.php jenkins '{"task":"generate_map_data_using_GBIF_csv_files","divisor":3}'

use this to get the breakdown:
php update_resources/connectors/gbif_georeference_dwca.php jenkins '{"task":"generate_map_data_using_GBIF_csv_files","divisor":20}'

php5.6 generate_map_data_using_GBIF_csv_files.php jenkins '{"group":false,"range":[1,25952],"ctr":1}'
php5.6 generate_map_data_using_GBIF_csv_files.php jenkins '{"group":false,"range":[25952,51903],"ctr":2}'
php5.6 generate_map_data_using_GBIF_csv_files.php jenkins '{"group":false,"range":[51903,77854],"ctr":3}'
php5.6 generate_map_data_using_GBIF_csv_files.php jenkins '{"group":false,"range":[77854,103805],"ctr":4}'
php5.6 generate_map_data_using_GBIF_csv_files.php jenkins '{"group":false,"range":[103805,129756],"ctr":5}'
php5.6 generate_map_data_using_GBIF_csv_files.php jenkins '{"group":false,"range":[129756,155707],"ctr":6}'
php5.6 generate_map_data_using_GBIF_csv_files.php jenkins '{"group":false,"range":[155707,181658],"ctr":7}'
*/

/* sample command line for this script:
php                             gbif_georeference_dwca.php _ '{"group":"Gadus morhua","divisor":2}'
php update_resources/connectors/gbif_georeference_dwca.php _ '{"group":"Gadus morhua","divisor":2}'

php                             gbif_georeference_dwca.php _ '{"task":"save_ids_to_text_from_many_folders"}'
php                             gbif_georeference_dwca.php _ '{"task":"generate_map_data_using_GBIF_csv_files"}'
php                             gbif_georeference_dwca.php _ '{"task":"generate_map_data_using_GBIF_csv_files","sciname":"Ailuropoda melanoleuca","tc_id":328070}'
php update_resources/connectors/gbif_georeference_dwca.php _ '{"task":"generate_map_data_using_GBIF_csv_files","sciname":"Ailuropoda melanoleuca","tc_id":328070}'
php update_resources/connectors/gbif_georeference_dwca.php _ '{"task":"generate_map_data_using_GBIF_csv_files","sciname":"Gadus morhua","tc_id":46564415}'

For eol-archive:
php gbif_georeference_dwca.php jenkins '{"group":"Animalia","divisor":6}'           //~717 million - Took 3 days 15 hr (when API calls are not yet cached)
php gbif_georeference_dwca.php jenkins '{"group":"Plantae","divisor":6}'            //~183 million - Took 1 day 19 hr (when API calls are not yet cached)
php gbif_georeference_dwca.php jenkins '{"group":"Other7Groups","divisor":6}'       //~25 million - Took 5 hr 10 min (when API calls are not yet cached)
*/

// /* Jenkins run: use this block
$func = new GBIFoccurrenceAPI_DwCA();

// print_r($argv);
$params['jenkins_or_cron']   = @$argv[1]; //irrelevant here
$params['json']              = @$argv[2]; //useful here
$arr = json_decode($params['json'], true);

if(($group = @$arr['group']) && ($divisor = @$arr['divisor'])) {
    // $group = 'Gadus morhua'; $divisor = 2; //force assign
    $batches = $func->get_range_batches($group, $divisor);
    print_r($batches);
    //start create temp group indicator files
    for ($x = 1; $x <= $divisor; $x++) {
        $fhandle = Functions::file_open(CONTENT_RESOURCE_LOCAL_PATH . "map_breakdown_".$group."_".$x.".txt", "w"); fclose($fhandle);
    }
    //end
    $func->jenkins_call($group, $batches, "breakdown_GBIF_DwCA_file");
}
if($task = @$arr['task']) {
    if($task == "generate_map_data_using_GBIF_csv_files") {
        if    (($sciname = @$arr['sciname'])       && ($tc_id = @$arr['tc_id']))       $func->generate_map_data_using_GBIF_csv_files($sciname, $tc_id);
        elseif($divisor = @$arr['divisor']) {
            $batches = $func->get_range_batches(false, $divisor, 1845884); //2nd param is divisor; 3rd is total tc_ids from DH file (2237550 old value).
            print_r($batches);
            //start create temp group indicator files
            for ($x = 1; $x <= $divisor; $x++) {
                $fhandle = Functions::file_open(CONTENT_RESOURCE_LOCAL_PATH . "map_generate_".$x.".txt", "w"); fclose($fhandle);
            }
            //end
            echo "\nCACHE_PATH xx 02 is ".CACHE_PATH."\n";
            $func->jenkins_call(false, $batches, $task);
            echo "\nCACHE_PATH xx 03 is ".CACHE_PATH."\n";
        }
        else $func->generate_map_data_using_GBIF_csv_files();
    }
    elseif($task == "breakdown_multimedia_to_gbifID_files") $func->breakdown_multimedia_to_gbifID_files();
    elseif($task == "save_ids_to_text_from_many_folders")   $func->save_ids_to_text_from_many_folders(); //utility, important as last step. This is now added to main program $func->start(); 

    elseif($task == "gen_map_data_forTaxa_with_children") {
        if    (($sciname = @$arr['sciname'])       && ($tc_id = @$arr['tc_id']))       $func->gen_map_data_forTaxa_with_children($sciname, $tc_id);
        elseif($divisor = @$arr['divisor']) {
            $batches = $func->get_range_batches(false, $divisor, 178519); //2nd param is divisor; 3rd is total rows in listOf_order_family_genus.txt
            print_r($batches);
            //start create temp group indicator files
            for ($x = 1; $x <= $divisor; $x++) {
                $fhandle = Functions::file_open(CONTENT_RESOURCE_LOCAL_PATH . "map_generate_".$x.".txt", "w"); fclose($fhandle);
            }
            //end
            echo "\nCACHE_PATH xx 02 is ".CACHE_PATH."\n";
            $func->jenkins_call(false, $batches, $task);
            echo "\nCACHE_PATH xx 03 is ".CACHE_PATH."\n";
        }
        else $func->gen_map_data_forTaxa_with_children();
    }


}
// */

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
