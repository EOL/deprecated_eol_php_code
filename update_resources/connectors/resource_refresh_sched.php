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

echo (date("M.d.Y h:i:s A", (1692077220764/1000)));

// $func = new CKAN_API_AccessAPI(false, false);
// $func->show_resources_last_refresh();

// http://160.111.248.39:8081/job/EOL_Connectors/api/xml


define('JENKINS_DOMAIN2', '160.111.248.39:8081');  //for archive; should point to port 8080
define('JENKINS_CRUMB2', 'Jenkins-Crumb:4f08be4deb91302e9eb9c79f16b9ad77');  //for archive
define('JENKINS_USER_TOKEN2', 'archive_admin:11f778fbb21fda8ffe3bea628f5d49d936');  //for archive


$url = "http://".JENKINS_USER_TOKEN2."@".JENKINS_DOMAIN2."/job/"."EOL_Connectors"."/api/xml";
if($xml = Functions::lookup_with_cache($url)) {
    print_r($xml);
} 

// http://160.111.248.39:8081/job/EOL_Connectors/api/xml
$c = '/usr/bin/curl -I -X POST -H "'.JENKINS_CRUMB2.'" http://'.JENKINS_USER_TOKEN2.'@'.JENKINS_DOMAIN2.'/job/'.'EOL_Connectors'.'/job/xml';
$c .= " 2>&1";
$out = shell_exec($c);
echo "\n$out\n";


Functions::get_time_elapsed($timestart);
?>