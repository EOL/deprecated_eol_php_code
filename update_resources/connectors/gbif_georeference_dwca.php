<?php
namespace php_active_record;
/* DATA-1748: GBIF map data harvest
This will generate the map data (.json files) for the EOL maps.
*/
/*
=================================================================================================================== Manual preparation of GBIF DwCA downloads:
Preparation of the GBIF DwCA downloads: [Animalia, Plantae, Other 7 groups]
1. Click link e.g. https://www.gbif.org/occurrence/download/0012668-181003121212138
2. Then click RERUN QUERY. Follow next steps. Then you will receive an email when your download is ready.
3. Once you get the email, update the curl links in Jenkins: [01 Initialize and download dumps]
4. -end-
=================================================================================================================== Start running Jenkins:
01 Initialize and download dumps
    will open 6 jobs
    with auto-next below
02a breakdown Animalia                              [php5.6 gbif_georeference_dwca.php jenkins '{"group":"Animalia","divisor":6}']
    will open 6 jobs
    with auto-next below
02b breakdown Plantae                               [php5.6 gbif_georeference_dwca.php jenkins '{"group":"Plantae","divisor":6}']
    will open 6 jobs
    with auto-next below
02c breakdown Other7Groups                          [php5.6 gbif_georeference_dwca.php jenkins '{"group":"Other7Groups","divisor":6}']
    will open 6 jobs
    with auto-next below
03 multimedia breakdown                             [php5.6 gbif_georeference_dwca.php jenkins '{"task":"breakdown_multimedia_to_gbifID_files"}']
    with auto-next below
04 Generate order family genus children list txt    [php DHconn.php jenkins]
    with auto-next below
05 Generate map data                                [php5.6 gbif_georeference_dwca.php jenkins '{"task":"generate_map_data_using_GBIF_csv_files","divisor":6}']
    will open 6 jobs
    with auto-next below
11 Generate map data UTILITY ONLY                   [-no need to run- just a utility]
11a Generate map data for genus with descendants    [php5.6 gbif_georeference_dwca.php jenkins '{"task":"gen_map_data_forTaxa_with_children","divisor":6,"rank":"genus"}']
    will open 6 jobs
    with auto-next below
11b Generate map data for family with descendants   [php5.6 gbif_georeference_dwca.php jenkins '{"task":"gen_map_data_forTaxa_with_children","divisor":6,"rank":"family"}']
    will open 6 jobs
    with auto-next below
11c Generate map data for order with descendants    [php5.6 gbif_georeference_dwca.php jenkins '{"task":"gen_map_data_forTaxa_with_children","divisor":6,"rank":"order"}']
    will open 6 jobs
    with auto-next below
12 05 save_ids_to_text_from_many_folders            [php5.6 gbif_georeference_dwca.php jenkins '{"task":"save_ids_to_text_from_many_folders"}']
20 utility_remove_var_data_equals

************************************************************************************************************************************* Animalia:
https://www.gbif.org/occurrence/download/0004680-180730143533302 | 8 August 2018 - 717,329,197 occurrences downloaded
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
https://www.gbif.org/occurrence/download/0012668-181003121212138 | 25 October 2018 - 725,537,711 occurrences downloaded
http://api.gbif.org/v1/occurrence/download/request/0012668-181003121212138.zip
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
https://www.gbif.org/occurrence/download/0081142-200221144449610 | 8 June 2020 - 1,031,353,123 occurrences downloaded
http://api.gbif.org/v1/occurrence/download/request/0081142-200221144449610.zip
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

************************************************************************************************************************************* Plantae:
https://www.gbif.org/occurrence/download/0004688-180730143533302 | 8 August 2018 - 183,255,036 occurrences downloaded
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
https://www.gbif.org/occurrence/download/0012669-181003121212138 | 25 October 2018 - 194,322,504 occurrences downloaded
http://api.gbif.org/v1/occurrence/download/request/0012669-181003121212138.zip
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
https://www.gbif.org/occurrence/download/0081144-200221144449610 | 8 June 2020 - 239,862,739 occurrences downloaded
http://api.gbif.org/v1/occurrence/download/request/0081144-200221144449610.zip
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

************************************************************************************************************************************* Other 7 groups:
https://www.gbif.org/occurrence/download/0005724-180730143533302 | 12 August 2018 - 24,024,940 occurrences downloaded
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
https://www.gbif.org/occurrence/download/0012209-181003121212138 | 24 October 2018 - 62,347,253 occurrences downloaded
http://api.gbif.org/v1/occurrence/download/request/0012209-181003121212138.zip
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
https://www.gbif.org/occurrence/download/0081146-200221144449610 | 8 June 2020 - 44,610,800 occurrences downloaded
http://api.gbif.org/v1/occurrence/download/request/0081146-200221144449610.zip
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

===================================================================================================================
e.g.
download URL:   http://api.gbif.org/v1/occurrence/download/request/0004645-180730143533302.zip
DOI:            https://www.gbif.org/occurrence/download/0004645-180730143533302
----------------------------------------------------
May need to investigate, very big csv files. Before, I needed to delete these files and just use API option since I cannot solve the memory_limit error.
May need to delete it again in next harvest or: the removal of file($csv) in place of FileIterator($csv) may already be good. We'll see...
/extra/other_files/GBIF_occurrence/GBIF_taxa_csv_dwca/2f/6f/2480320.csv - 477M | 499271387
/extra/other_files/GBIF_occurrence/GBIF_taxa_csv_dwca/67/a5/2481720.csv - 163M | 170778770
/extra/other_files/GBIF_occurrence/GBIF_taxa_csv_dwca/d6/8b/2481798.csv - 175M | 182543695 not deleted, will use when fixing memory leak prob.
/extra/other_files/GBIF_occurrence/GBIF_taxa_csv_dwca/bb/4e/2481776.csv - 110M | 115239893
Looks like the removal of file($csv) in place of FileIterator($csv), solved the problem.
----------------------------------------------------
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
Latest version sample: also used as utility to speed up generation. Basically adding more jenkins jobs
php5.6 generate_map_data_using_GBIF_csv_files.php jenkins '{"group":false,"range":[585613,615613],"ctr":10,"rank":""}'
php5.6 generate_map_data_using_GBIF_csv_files.php jenkins '{"group":false,"range":[555613,585613],"ctr":11,"rank":""}'
php5.6 generate_map_data_using_GBIF_csv_files.php jenkins '{"group":false,"range":[525613,555613],"ctr":12,"rank":""}'
php5.6 generate_map_data_using_GBIF_csv_files.php jenkins '{"group":false,"range":[515613,525613],"ctr":13,"rank":""}'

*/

/* sample command line for this script:
php                             gbif_georeference_dwca.php _ '{"group":"Gadus morhua","divisor":2}'
php update_resources/connectors/gbif_georeference_dwca.php _ '{"group":"Gadus morhua","divisor":2}'

php                             gbif_georeference_dwca.php _ '{"task":"save_ids_to_text_from_many_folders"}'
php                             gbif_georeference_dwca.php _ '{"task":"remove_var_data_equals"}'

php                             gbif_georeference_dwca.php _ '{"task":"generate_map_data_using_GBIF_csv_files"}'
php                             gbif_georeference_dwca.php _ '{"task":"generate_map_data_using_GBIF_csv_files","sciname":"Ailuropoda melanoleuca","tc_id":328070}'
php update_resources/connectors/gbif_georeference_dwca.php _ '{"task":"generate_map_data_using_GBIF_csv_files","sciname":"Ailuropoda melanoleuca","tc_id":328070}'
php update_resources/connectors/gbif_georeference_dwca.php _ '{"task":"generate_map_data_using_GBIF_csv_files","sciname":"Gadus morhua","tc_id":46564415}'
php update_resources/connectors/gbif_georeference_dwca.php _ '{"task":"generate_map_data_using_GBIF_csv_files","sciname":"Gadus","tc_id":46564414}'
php update_resources/connectors/gbif_georeference_dwca.php _ '{"task":"generate_map_data_using_GBIF_csv_files","sciname":"Gadus macrocephalus","tc_id":46564417}'

Better to use Jenkins here. Not command-line.
php update_resources/connectors/gbif_georeference_dwca.php _ '{"task":"gen_map_data_forTaxa_with_children","sciname":"Gadus","tc_id":46564414}'
php update_resources/connectors/gbif_georeference_dwca.php _ '{"task":"gen_map_data_forTaxa_with_children","sciname":"Desertifilum","tc_id":35798554}'

php update_resources/connectors/gbif_georeference_dwca.php jenkins '{"task":"gen_map_data_forTaxa_with_children","divisor":1,"rank":"genus"}'
php update_resources/connectors/gbif_georeference_dwca.php jenkins '{"task":"gen_map_data_forTaxa_with_children","divisor":1,"rank":"family"}'
php update_resources/connectors/gbif_georeference_dwca.php jenkins '{"task":"gen_map_data_forTaxa_with_children","divisor":1,"rank":"order"}'


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
            
            $total = shell_exec("wc -l < ".escapeshellarg(CONTENT_RESOURCE_LOCAL_PATH . '/listOf_all_4maps.txt'));
            $total = trim($total);
            $total = $total - 1; //less header row
            
            $batches = $func->get_range_batches(false, $divisor, $total); //2nd param is divisor; 3rd is total tc_ids from DH file where EOLid is not blank
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
    elseif($task == "remove_var_data_equals")   $func->remove_var_data_equals(); //a one-time-utility
    elseif($task == "gen_map_data_forTaxa_with_children") {
        if    (($sciname = @$arr['sciname'])       && ($tc_id = @$arr['tc_id']))       $func->gen_map_data_forTaxa_with_children($sciname, $tc_id);
        elseif($divisor = @$arr['divisor']) {
            
            $total = shell_exec("wc -l < ".escapeshellarg(CONTENT_RESOURCE_LOCAL_PATH . '/listOf_'.$arr['rank'].'_4maps.txt'));
            $total = trim($total);
            $total = $total - 1; //less header row
            
            $batches = $func->get_range_batches(false, $divisor, $total); //2nd param is divisor; 3rd is total rows in listOf_XXXXX_4maps.txt
            print_r($batches);
            //start create temp group indicator files
            for ($x = 1; $x <= $divisor; $x++) {
                $fhandle = Functions::file_open(CONTENT_RESOURCE_LOCAL_PATH . "map_generate_".$arr['rank']."_".$x.".txt", "w"); fclose($fhandle);
            }
            //end
            echo "\nCACHE_PATH xx 02 is ".CACHE_PATH."\n";
            $func->jenkins_call(false, $batches, $task, $arr['rank']);
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
