<?php
namespace php_active_record;
/* */

include_once(dirname(__FILE__) . "/../../config/environment.php");
$GLOBALS['ENV_DEBUG'] = false;  //set to false in production
// /* during dev only
ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);
$GLOBALS['ENV_DEBUG'] = true; //set to true when debugging
// */
// ini_set('memory_limit','14096M');
require_library('connectors/CKAN_API_AccessAPI');
$timestart = time_elapsed();


// $func = new CKAN_API_AccessAPI(false, false);
// $func->show_resources_last_refresh();

// http://160.111.248.39:8081/job/EOL_Connectors/api/xml


// define('JENKINS_DOMAIN2', '160.111.248.39:8081');  //for archive; should point to port 8080
// define('JENKINS_CRUMB2', 'Jenkins-Crumb:4f08be4deb91302e9eb9c79f16b9ad77');  //for archive
// define('JENKINS_USER_TOKEN2', 'archive_admin:11f778fbb21fda8ffe3bea628f5d49d936');  //for archive

$url = "http://localhost:8080/job/EOL_Connectors/";
$url = "http://160.111.248.39:8081/job/EOL_Connectors/";

// $url = "http://".JENKINS_USER_TOKEN."@".JENKINS_DOMAIN."/job/"."EOL_Connectors"."/api/json"; //working
$url = transform_url_2_api_call($url);
if($obj = call_jenkins_api($url)) {
    // print_r($obj->jobs); //exit;
    $total = count($obj->jobs); echo "\nTotal: [$total]\n";
    $i = 0;
    foreach($obj->jobs as $job) { $i++; echo "\n$i of $total\n";
        // print_r($job); //exit;
        echo "\nName: ".$job->name."\n";
        /* stdClass Object(
            [_class] => hudson.model.FreeStyleProject
            [name] => 24
            [url] => http://localhost:8080/job/EOL_Connectors/job/24/
            [color] => red
        )*/
        $new_url = transform_url_2_api_call($job->url);
        if($obj2 = call_jenkins_api($new_url)) {
            // print_r($obj); exit;
            if($o = @$obj2->lastSuccessfulBuild) { // print_r($o); 
                /* stdClass Object(
                    [_class] => hudson.model.FreeStyleBuild
                    [number] => 1
                    [url] => http://localhost:8080/job/EOL_Connectors/job/42/1/
                )*/
                $new_url = transform_url_2_api_call($o->url);
                if($obj3 = call_jenkins_api($new_url)) {
                    // print_r($obj); exit;
                    $date_last_successful_build = convert_timestamp($obj3->timestamp);
                    echo "\n --- date_last_successful_build: [$date_last_successful_build]\n"; 
                    $final[$date_last_successful_build] = $job->name;
                    // exit;
                }
                        
            }
        }
    } //end foreach()
    print_r($final);
    krsort($final);
    print_r($final);

} 

function transform_url_2_api_call($url)
{
    $new_url = str_replace('http://', "http://".JENKINS_USER_TOKEN."@", $url);
    return $new_url."api/json";
}
function call_jenkins_api($url)
{
    if($json = Functions::lookup_with_cache($url)) {
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