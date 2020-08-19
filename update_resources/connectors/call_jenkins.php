<?php
namespace php_active_record;
/* This lib is for php to accept params and forward it to Jenkins to run specific scripts.
First client is in jenkins.eol.org - DATA-1860: branch painting
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");

echo "\nCACHE_PATH xx 01 is ".CACHE_PATH."\n";

require_library('connectors/Jenkins_CallAPI');
ini_set('memory_limit','10096M'); //15096M
$timestart = time_elapsed();
$GLOBALS['ENV_DEBUG'] = true;

/* sample command line for this script:
For eol-backend2:
php call_jenkins.php _ '{"script":"branch painting","resource_ID":640, "prod_OR_beta": "beta"}'
*/

// print_r($argv);
$params['jenkins_or_cron']   = @$argv[1]; //irrelevant here
$params['json']              = @$argv[2]; //useful here
$arr = json_decode($params['json'], true);
print_r($arr); //exit("\n-stop muna-\n");
/* Array(
    [script] => branch painting
    [resource_ID] => 640
    [prod_OR_beta] => beta
)*/

$func = new Jenkins_CallAPI();
$func->jenkins_call($arr);


/* possible implementation as well - Works OK - used in GBIFoccurrenceAPI_DwCA.php
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
}
*/
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>