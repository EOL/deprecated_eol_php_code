<?php
namespace php_active_record;
/* Command-line acces to CKAN API opendata.eol.org  --- for DATA-1885: CKAN metadata display adjustments 
e.g. Protisten.de
php update_resources/connectors/ckan_api_access.php _ "84c7f07a-8b39-467b-923e-b9e9ef5fa45a"
or 
php update_resources/connectors/ckan_api_access.php _ "259b34c9-8752-4553-ab37-f85300daf8f2" "06/10/2023 19:30:00"
                                                                              that is for June 10, 2023 @ 7:30 PM

php update_resources/connectors/ckan_api_access.php _ "6e24f0df-56ee-470f-b81e-e5a367a65bfb" "07/08/2023 21:41:00" "EOL file"
-> All trait dump file.                                                           --- that is Jul 8, 2023 9:41 PM

e.g. xeno-canto
php update_resources/connectors/ckan_api_access.php _ "09844d24-5880-41aa-a031-b19f688185cb" "09/25/2023 7:30:00"
                                                                              that is for Sep 25, 2023 @ 7:30 AM

IMPORTANT: ALWAYS "chmod 775 ckan_api_access.php" AFTER UPDATE EOL-ARCHIVE
                  "chmod 775 CKAN_API_AccessAPI.php" AFTER UPDATE EOL-ARCHIVE
                  "chmod 775 resource_refresh_sched.php" AFTER UPDATE EOL-ARCHIVE

                  

WoRMS - will work only if you set the CKAN resource to public. Then just toggle it back to private after update.
php update_resources/connectors/ckan_api_access.php _ "a74bcc92-d2a2-4a7b-b18f-b5fd2e3a5b5c"                  
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
$file_type                  = @$argv[4];

print_r($argv); //exit("\n");
if(substr($ckan_resource_id,0,17) == "ckan_resource_id=") $ckan_resource_id = "";

// /* for URL parameters:
if(!$ckan_resource_id) $ckan_resource_id = @get_val_var('ckan_resource_id');
if(!$file_type)        $file_type        = @get_val_var('file_type');
// */
/* worked OK using browser access:
e.g. protisten.de
https://editors.eol.org/eol_php_code/update_resources/connectors/ckan_api_access.php?ckan_resource_id=84c7f07a-8b39-467b-923e-b9e9ef5fa45a
e.g. all-trait dump
https://editors.eol.org/eol_php_code/update_resources/connectors/ckan_api_access.php?ckan_resource_id=6e24f0df-56ee-470f-b81e-e5a367a65bfb
*/
/* worked OK using curl in command-line:
e.g. protisten
curl https://editors.eol.org/eol_php_code/update_resources/connectors/ckan_api_access.php -d ckan_resource_id=84c7f07a-8b39-467b-923e-b9e9ef5fa45a
curl http://localhost/eol_php_code/update_resources/connectors/ckan_api_access.php -d ckan_resource_id=84c7f07a-8b39-467b-923e-b9e9ef5fa45a

e.g. all-trait dump
curl https://editors.eol.org/eol_php_code/update_resources/connectors/ckan_api_access.php -d ckan_resource_id=6e24f0df-56ee-470f-b81e-e5a367a65bfb

below is e.g. Somalia
        https://editors.eol.org/eol_php_code/update_resources/connectors/ckan_api_access.php?ckan_resource_id=a7cf949b-3d47-45cb-b24a-50b7c09085e6&file_type=EOL file
curl https://editors.eol.org/eol_php_code/update_resources/connectors/ckan_api_access.php -d ckan_resource_id=a7cf949b-3d47-45cb-b24a-50b7c09085e6 -d "file_type=EOL file"
*/

if(!$ckan_resource_id) exit("\nERROR: Incomplete parameters. No CKAN resource ID. [$ckan_resource_id]\n");
// $ckan_resource_id = "259b34c9-8752-4553-ab37-f85300daf8f2"; //during dev only
if(!$file_type) $file_type = "EOL resource"; 

$func = new CKAN_API_AccessAPI($file_type, $forced_date); //other values: "EOL dump" or "EOL file"
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
function get_val_var($v)
{
    if     (isset($_GET["$v"])) $var = $_GET["$v"];
    elseif (isset($_POST["$v"])) $var = $_POST["$v"];
    if(isset($var)) return $var;
    else return NULL;
}
?>