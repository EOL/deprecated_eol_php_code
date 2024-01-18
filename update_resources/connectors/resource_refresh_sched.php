<?php
namespace php_active_record;
/* standalone, no class or library to call 

php update_resources/connectors/resource_refresh_sched.php _ 1
-> cache expires now
php update_resources/connectors/resource_refresh_sched.php
-> cache expires after 11 hours

Final report:
https://editors.eol.org/eol_php_code/applications/content_server/resources/reports/jenkins_refresh_status.txt
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$GLOBALS['ENV_DEBUG'] = false;  //set to false in production
/* during dev only
ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);
$GLOBALS['ENV_DEBUG'] = true; //set to true when debugging
*/
require_library('connectors/CKAN_API_AccessAPI');
$timestart = time_elapsed();

$params['jenkins_or_cron']  = @$argv[1]; //not needed here
$expire_now_YN              = @$argv[2];

if($expire_now_YN) $GLOBALS['expire_seconds'] = 0;          // expires now
else               $GLOBALS['expire_seconds'] = 60*60*11;   // 11 hours cache bec it is designed to run every 12 hours. Unless on-demand, which can expire now.

$main_urls = array( "http://160.111.248.39:8081/job/EOL_Connectors/", 
                    "http://160.111.248.39:8081/job/Environmental%20tagger%20for%20EOL%20resources/", 
                    "http://160.111.248.39:8081/job/13_TreatmentBank/", 
                    "http://160.111.248.39:8081/job/14_Kubitzki_et_al/", 
                    "http://160.111.248.39:8081/job/Flickr%20connectors/", 
                    "http://160.111.248.39:8081/job/GBIF%20Countries%20Download/", 
                    "http://160.111.248.39:8081/job/GBIF%20Country%20Nodes/", 
                    "http://160.111.248.39:8081/job/GBIF%20map%20data%20harvest/", 
                    "http://160.111.248.39:8081/job/images_Inaturalist/", 
                    "http://160.111.248.39:8081/job/NMNH_images/", 
                    "http://160.111.248.39:8081/job/Wikimedia%20Commons/", 
                    "http://160.111.248.39:8081/job/Wikipedia%20in%20different%20languages/",
                    "http://160.111.248.39:8081/job/08%20Routine%20Utilities/");

// $main_urls = array("http://localhost:8080/job/EOL_Connectors/", "http://localhost:8080/job/Backup%20Activities/"); //during dev only

$main_final = array();
foreach($main_urls as $main_url) { $final = array();
    $main_url = transform_url_2_api_call($main_url);
    // $main_url = "http://".JENKINS_USER_TOKEN."@".JENKINS_DOMAIN."/job/"."EOL_Connectors"."/api/json"; //working //during dev only
    $main_name = format_name($main_url);
    if($obj = call_jenkins_api($main_url)) { // print_r($obj->jobs); //exit;
        $total = count($obj->jobs); echo "\nTotal [$main_name]: [$total]\n";
        $i = 0;
        foreach($obj->jobs as $job) { $i++; echo "\n[$main_name] $i of $total\n"; // print_r($job); //exit;
            echo "\nName: ".$job->name."\n";
            /* stdClass Object(
                [_class] => hudson.model.FreeStyleProject
                [name] => 24
                [url] => http://localhost:8080/job/EOL_Connectors/job/24/
                [color] => red
            )*/
            $new_url = transform_url_2_api_call($job->url);
            if($obj2 = call_jenkins_api($new_url)) { // print_r($obj2); exit;
                if($o = @$obj2->lastSuccessfulBuild) { // print_r($o); 
                    /* stdClass Object(
                        [_class] => hudson.model.FreeStyleBuild
                        [number] => 1
                        [url] => http://localhost:8080/job/EOL_Connectors/job/42/1/
                    )*/
                    $new_url = transform_url_2_api_call($o->url);
                    if($obj3 = call_jenkins_api($new_url)) { // print_r($obj3); exit;
                        $date_last_successful_build = convert_timestamp($obj3->timestamp);
                        echo "\n --- date_last_successful_build: [$date_last_successful_build]\n"; 
                        $final[$date_last_successful_build] = $job->name;
                    }                        
                }
            }
        } //end foreach()
        // print_r($final);
        krsort($final); 
        // print_r($final); //good debug
    }
    $main_final[$main_name] = $final;
} //end main foreach()
print_r($main_final);
write_to_text_file($main_final);

/* Functions below: */
function write_to_text_file($arr)
{
    $report = CONTENT_RESOURCE_LOCAL_PATH."reports/jenkins_refresh_status.txt";
    $WRITE = fopen($report, "w");
    fwrite($WRITE, "Generated on: ".date("Y-m-d h:i A")."\n\n");

    foreach($arr as $name => $recs) {
        fwrite($WRITE, $name."\n");
        foreach($recs as $date => $task_name) {
            fwrite($WRITE, "--- ".$date." -> ".$task_name."\n");
        }
        fwrite($WRITE, "\n");
    }
    fclose($WRITE);
}
function format_name($main_url)
{   // [http://eli:110b974f5af197e940eeded9b5b19efe22@localhost:8080/job/EOL_Connectors/api/json]
    if(preg_match("/\/job\/(.*?)\/api\/json/ims", $main_url, $arr)) {
        return str_replace("%20", "_", $arr[1]);
    }
    return $main_url;
}
function transform_url_2_api_call($url)
{
    $new_url = str_replace('http://', "http://".JENKINS_USER_TOKEN."@", $url);
    return $new_url."api/json";
}
function call_jenkins_api($url)
{
    if($json = Functions::lookup_with_cache($url, array('expire_seconds' => $GLOBALS['expire_seconds']))) { //12 hrs cache, designed to run 2 times a day. Every 12 hours. Thus using *11 (12-1).
        return json_decode($json);
    }
}
function convert_timestamp($timestamp)
{
    // return date("M.d.Y h:i:s A", ($timestamp/1000));
    return date("Y-m-d h:i:s A", ($timestamp/1000));
}
Functions::get_time_elapsed($timestart);
?>