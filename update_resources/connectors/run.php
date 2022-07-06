<?php
namespace php_active_record;
/* This is the generic library to handle multiple connectors using Jenkins */
include_once(dirname(__FILE__) . "/../../config/environment.php");

echo "\nCACHE_PATH xx 01 is ".CACHE_PATH."\n";

require_library('connectors/MultipleConnJenkinsAPI');
require_library('connectors/MoreFunc4Wikipedia');

ini_set('memory_limit','4096M');
$timestart = time_elapsed();

$GLOBALS['ENV_DEBUG'] = false; //orig value should be -> false
/* during development
ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);
$GLOBALS['ENV_DEBUG'] = true; //set to true during development
*/


/*
php5.6                    run.php jenkins '{"connector":"eol_v3_api.php", "divisor":6, "task":"initial"}'
php update_resources/connectors/run.php _ '{"connector":"eol_v3_api.php", "divisor":6, "task":"initial"}'

php5.6                    run.php jenkins '{"connector":"gen_wikipedia_by_lang", "divisor":6, "task":"initial", "langx":"sh"}'
                          run.php jenkins '{"connector":"gen_wikipedia_by_lang", "divisor":6, "task":"initial", "langx":"ce"}'
                          run.php jenkins '{"connector":"gen_wikipedia_by_lang", "divisor":6, "task":"initial", "langx":"ce", "six_coverage":"1st"}'
                          run.php jenkins '{"connector":"gen_wikipedia_by_lang", "divisor":6, "task":"initial", "langx":"cy", "six_coverage":"2nd"}'

php update_resources/connectors/run.php _ '{"connector":"gen_wikimedia", "divisor":6, "task":"initial"}'
php update_resources/connectors/run.php _ '{"connector":"gen_wikimedia", "divisor":2, "task":"initial"}'

*/

// print_r($argv);
$params['jenkins_or_cron']   = @$argv[1]; //irrelevant here
$params['json']              = @$argv[2]; //useful here
$arr = json_decode($params['json'], true);
print_r($arr);

if(!isset($arr['six_coverage'])) $arr['six_coverage'] = "1st";

$funcj = new MultipleConnJenkinsAPI();

// /* new block
if($arr['connector'] == 'gen_wikipedia_by_lang') {
    $func_wp = new MoreFunc4Wikipedia($arr['six_coverage']);
    $language = $arr['langx'];
    if(!$func_wp->is_this_wikipedia_lang_old_YN($language)) {
        echo "\nSeems already recently generated (multiple) [$language]. Run next language...\n";
        if($ret = $func_wp->get_next_lang_after($language)) { //this gets the next 6c lang.
            $arr['langx'] = $ret[0];
            echo "\nA. Next lang is [".$arr['langx']."]\n";
        }
        else exit("\nFor some reason exits...\n");
    }
    else { //needs refresh of dwca, but must need to check first if 'Y' AND should be "6c"
        $info = $func_wp->get_language_info_from_TSV($language);
        print_r($info);
        $lang = $info[0]; $status = $info[1]; $six_conn = $info[2];
        if($status == 'Y' && $six_conn == '6c') echo "\n=PROCEEDx WITH HARVEST for [$language]=\n";
        else {
            echo "\n=CANNOT PROCEEDxx [$language], GO TO NEXT LANGUAGE=\n";
            if($ret = $func_wp->get_next_lang_after($language)) { //this gets the next 6c lang.
                $arr['langx'] = $ret[0];
                echo "\nB. Next lang is [".$arr['langx']."]\n";
            }
        }
    }
}
// */

if($arr['task'] == 'initial') { //this is where to get e.g. the total number of rows/records/taxa to which we will divide. 
                                //Customized for every connector.
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
    elseif($arr['connector'] == 'gen_wikipedia_by_lang' || $arr['connector'] == 'gen_wikimedia') {
        // /* New: un-comment in real operation
        if(Functions::is_production()) {
            // $cmd = "wc -l ".$this->path['wiki_data_json'];
            $cmd = "wc -l "."/extra/dumps/wikidata/latest-all-taxon.json";
            $out = shell_exec($cmd);
            $total_count = str_replace("\n", "", $out);
            echo "\n-----\n[".$total_count."]\n-----\n";
        }
        // */
        // $total_count = 3448535; //as of Jun 5, 2022         //2700000; //old value 2500000 ---> durive dev only, debug only
        // $total_count = 35; //debug only during dev only*
        $arr['total_count'] = $total_count;
        echo "\ntotal_count: $total_count\n";
        
        if($arr['divisor']) $batches = $funcj->get_range_batches($total_count, $arr['divisor']);
        else                $batches[] = array(1, $total_count);
        print_r($batches);
        $arr['batches'] = $batches;
        
        //start create temp group indicator files
        // not needed here
        //end
        echo "\nJust before the call:\n"; print_r($arr);
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
