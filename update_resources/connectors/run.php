<?php
namespace php_active_record;
/* This is the generic library to handle multiple connectors using Jenkins */
include_once(dirname(__FILE__) . "/../../config/environment.php");

echo "\nCACHE_PATH xx 01 is ".CACHE_PATH."\n";

require_library('connectors/MultipleConnJenkinsAPI');
ini_set('memory_limit','4096M');
$timestart = time_elapsed();
$GLOBALS['ENV_DEBUG'] = false;

/*
php5.6                    run.php jenkins '{"connector":"eol_v3_api.php", "divisor":6, "task":"initial"}'
php update_resources/connectors/run.php _ '{"connector":"eol_v3_api.php", "divisor":6, "task":"initial"}'

php5.6                    run.php jenkins '{"connector":"gen_wikipedia_by_lang", "divisor":6, "task":"initial", "langx":"sh"}'
*/

$funcj = new MultipleConnJenkinsAPI();
// print_r($argv);
$params['jenkins_or_cron']   = @$argv[1]; //irrelevant here
$params['json']              = @$argv[2]; //useful here
$arr = json_decode($params['json'], true);
// print_r($params);
print_r($arr);

if($arr['task'] == 'initial') { //this is where to get e.g. the total number of rows/records/taxa to which we will divide. Customized for every connector.
    if($arr['connector'] == 'eol_v3_api.php') { //customization part
        /* execution time: 7 days using 6 connectors */
        require_library('connectors/Eol_v3_API');
        $resource_id = '';
        // /* working OK - uncomment in real operation
        $func = new Eol_v3_API($resource_id);
        if(Functions::is_production()) $path = "/extra/other_files/DWH/from_OpenData/EOL_dynamic_hierarchyV1Revised/taxa.txt";
        else                           $path = "/Volumes/AKiTiO4/other_files/from_OpenData/EOL_dynamic_hierarchyV1Revised/taxa.txt";
        $total_count = $func->process_all_eol_taxa_using_DH($path, $purpose = 'count only');
        // */
        // $total_count = 1906685; //debug only force-assign
        $arr['total_count'] = $total_count;
        echo "\ntotal_count: $total_count\n";
        
        if($arr['divisor']) $batches = $funcj->get_range_batches($total_count, $arr['divisor']);
        else                $batches[] = array(1, $total_count);
        print_r($batches);
        $arr['batches'] = $batches;
        
        //start create temp group indicator files
        for ($x = 1; $x <= $arr['divisor']; $x++) {
            $filename = "part_EOL_stats_".$x.".txt";
            $fhandle = Functions::file_open(CONTENT_RESOURCE_LOCAL_PATH . $filename, "w"); fclose($fhandle);
            echo "\ncreate indicator file: [$filename]";
        }
        //end
        $funcj->jenkins_call($arr, "generate_stats"); //finally make the call
    }
    elseif($arr['connector'] == 'gen_wikipedia_by_lang') {
        $total_count = 2500000;
        $arr['total_count'] = $total_count;
        echo "\ntotal_count: $total_count\n";
        
        if($arr['divisor']) $batches = $funcj->get_range_batches($total_count, $arr['divisor']);
        else                $batches[] = array(1, $total_count);
        print_r($batches);
        $arr['batches'] = $batches;
        //start create temp group indicator files
        // not needed here
        //end
        $funcj->jenkins_call($arr, "generate_stats"); //finally make the call
    }
    elseif($arr['connector'] == 'xxx.php') { //customization part
    }
    else exit("\nNot yet initialized, will terminate\n");
}

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n elapsed time = " . $elapsed_time_sec/60/60/24 . " days";
echo "\n Done processing.\n";
?>
