<?php
namespace php_active_record;
/* Command-line acces to CKAN API opendata.eol.org  --- for DATA-1885: CKAN metadata display adjustments 

php update_resources/connectors/ckan_api_access.php _ "259b34c9-8752-4553-ab37-f85300daf8f2"
or 
php update_resources/connectors/ckan_api_access.php _ "259b34c9-8752-4553-ab37-f85300daf8f2" "06/10/2011 19:00:02"
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$GLOBALS['ENV_DEBUG'] = false;  //set to false in production
// /* during dev only
ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);
$GLOBALS['ENV_DEBUG'] = true; //set to true when debugging
// */
// ini_set('memory_limit','14096M');
require_library('connectors/CKAN_API_Access');
$timestart = time_elapsed();


/* working OK --- good test
$input = '06/10/2011 19:00:02'; //the date format for force-replace a date
// $input = date("m/d/Y H:i:s"); //must be "H" not "h" --- '06/10/2011 19:00:02'
$date = strtotime($input);
echo "\n".$input;
echo "\n".date('d/M/Y h:i:s', $date);
echo "\n".date('M d Y h:i:s', $date);
exit("\n-end tests-\n");
*/

/* tests
$date_str = date("Y-m-d H:i:s"); //2010-12-30 23:21:46
echo "\n".$date_str;
// exit;

$datetime = new \DateTime($date_str);
echo "\n".$datetime->format(\DateTime::ATOM);       // didn't work for CKAN API

echo "\n".date(DATE_ISO8601, strtotime($date_str)); // didn't work for CKAN API

$iso_date_str = str_replace(" ", "T", $date_str);   // worked OK for CKAN API
echo "\n".$iso_date_str;

// 2023-07-17 04:44:59
// 2023-07-17T04:44:59-04:00
// 2023-07-17T04:44:59-0400
// 2020-07-10 15:00:00
exit("\n-end tests-\n");
*/

$params['jenkins_or_cron']  = @$argv[1]; //not needed here
$ckan_resource_id           = @$argv[2];
$forced_date                = @$argv[3];

if(!$ckan_resource_id) exit("\nERROR: Incomplete parameters. No CKAN resource ID.\n");
// $ckan_resource_id = "259b34c9-8752-4553-ab37-f85300daf8f2"; //during dev only

$func = new CKAN_API_Access('EOL resource', $forced_date); //other values: "EOL dump" or "EOL file"
$func->UPDATE_ckan_resource($ckan_resource_id, "Last updated"); //actual CKAN field is "last_modified"

/* tests
$rec = $func->retrieve_ckan_resource_using_id($ckan_resource_id);
print_r($rec);
if($rec['success']) {
    $desc = $rec['result']['description'];
    $desc = $func->format_description($desc);
    echo "\n".$desc."\n";
}
*/

Functions::get_time_elapsed($timestart);
?>