<?php
namespace php_active_record;
/* This is the generic library to handle multiple connectors using Jenkins */
include_once(dirname(__FILE__) . "/../../config/environment.php");

echo "\nCACHE_PATH xx 01 is ".CACHE_PATH."\n"; //exit;

require_library('connectors/MultipleConnJenkinsAPI');
ini_set('memory_limit','4096M');
$timestart = time_elapsed();
$GLOBALS['ENV_DEBUG'] = true;

/*
php5.6 run.php _ '{"connector":"eol_v3_api.php", "divisor":6, "task":"initial"}'


php5.6 run.php jenkins '{"task":'task_x',"range":[1,25952],"ctr":1}'
php5.6 run.php jenkins '{"task":'task_x',"range":[25952,51903],"ctr":2}'
php5.6 run.php jenkins '{"task":'task_x',"range":[51903,77854],"ctr":3}'
php5.6 run.php jenkins '{"task":'task_x',"range":[77854,103805],"ctr":4}'
php5.6 run.php jenkins '{"task":'task_x',"range":[103805,129756],"ctr":5}'
php5.6 run.php jenkins '{"task":'task_x',"range":[129756,155707],"ctr":6}'
php5.6 run.php jenkins '{"task":'task_x',"range":[155707,181658],"ctr":7}'
*/

$func = new MultipleConnJenkinsAPI();
// print_r($argv);
$params['jenkins_or_cron']   = @$argv[1]; //irrelevant here
$params['json']              = @$argv[2]; //useful here
$arr = json_decode($params['json'], true);
// print_r($params);
print_r($arr);

if($arr['task'] == 'initial') { //this is where to get e.g. the total number of rows/records/taxa to which we will divide. Customized for every connector.
    if($arr['connector'] == 'eol_v3_api.php') {
        require_library('connectors/Eol_v3_API');
        $resource_id = '';
        /* working OK - uncomment in real operation
        $func = new Eol_v3_API($resource_id);
        if(Functions::is_production()) $path = "/extra/other_files/DWH/from_OpenData/EOL_dynamic_hierarchyV1Revised/taxa.txt";
        else                           $path = "/Volumes/AKiTiO4/other_files/from_OpenData/EOL_dynamic_hierarchyV1Revised/taxa.txt";
        $total_count = $func->process_all_eol_taxa_using_DH($path, $purpose = 'count only');
        */
        $total_count = 1906685;
        echo "\ntotal_count: $total_count\n";
        $batches = $func->get_range_batches($total_count, $arr['divisor']);
        print_r($batches);
        
        //start create temp group indicator files
        for ($x = 1; $x <= $divisor; $x++) {
            $fhandle = Functions::file_open(CONTENT_RESOURCE_LOCAL_PATH . "part_EOL_stats_".$x.".txt", "w"); fclose($fhandle);
        }
        //end
        $func->jenkins_call($arr['connector'], $batches, "generate_stats");
        
        
    }
    elseif($arr['connector'] == 'xxx.php') {
    }
}





/* Jenkins run: use this block
if(($group = @$arr['group']) && ($divisor = @$arr['divisor'])) {
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
            $batches = $func->get_range_batches(false, $divisor, 2237550); //2nd param is divisor; 3rd is total tc_ids from DH file.
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
}
*/

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n elapsed time = " . $elapsed_time_sec/60/60/24 . " days";
echo "\n Done processing.\n";
?>
