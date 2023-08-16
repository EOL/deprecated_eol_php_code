<?php
namespace php_active_record;
/* This will be used in Functions.php. During every run of a connector.
e.g.
php update_resources/connectors/ckan_api_access_using_resourceID.php _ "protisten"
*/

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


$params['jenkins_or_cron']  = @$argv[1]; //not needed here
$eol_resource_id            = @$argv[2];

if(!$eol_resource_id) exit("\nERROR: Incomplete parameters. No EOL resource ID.\n");
// $ckan_resource_id = "protisten"; //during dev only

$forced_date = "";
$func = new CKAN_API_AccessAPI('EOL resource', $forced_date); //other values: "EOL dump" or "EOL file"
$func->update_CKAN_resource_using_EOL_resourceID($eol_resource_id);

Functions::get_time_elapsed($timestart);
?>