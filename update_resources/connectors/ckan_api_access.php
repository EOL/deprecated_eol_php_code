<?php
namespace php_active_record;
/* Command-line acces to CKAN API opendata.eol.org
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

/* tests
$date_str = date("Y-m-d H:i:s"); //2010-12-30 23:21:46
echo "\n".$date_str;
// exit;
$datetime = new \DateTime($date_str);
echo "\n".$datetime->format(\DateTime::ATOM); // Updated ISO8601

echo "\n".date(DATE_ISO8601, strtotime($date_str));


exit("\n-end tests-\n");
*/

$params['jenkins_or_cron']  = @$argv[1];
$params['json']             = @$argv[2];

if($GLOBALS['ENV_DEBUG']) { 
    // echo "<pre>"; print_r($params); echo "</pre>"; 
}

/* Array(
    [jenkins_or_cron] => _
    [json] => {"Filename_ID":"","Short_Desc":"" , "timestart":"0.002263" , "newfile_File_A":"File_A_1688396971.tab" , "newfile_File_B":"File_B_1688396971.tsv" , "fileA_taxonID":"EOL-000000095511" , "fileB_taxonID":"eli02" , "uuid":"1688396971" }
)*/

if($val = $params['json'])     $json = $val;
else                           $json = '';

$arr = json_decode($json, true); //print_r($arr); exit("\n-stop muna 1-\n");
$func = new CKAN_API_Access();
$ckan_resource_id = "259b34c9-8752-4553-ab37-f85300daf8f2";
$func->UPDATE_ckan_resource($ckan_resource_id);
Functions::get_time_elapsed($timestart);
?>