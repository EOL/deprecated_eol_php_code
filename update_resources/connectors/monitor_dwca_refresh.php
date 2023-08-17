<?php
namespace php_active_record;
/* 
IMPORTANT: ALWAYS "chmod 775 MonitorDwCARefreshAPI.php" AFTER UPDATE EOL-ARCHIVE
                             monitor_dwca_refresh.php


php update_resources/connectors/monitor_dwca_refresh.php _ protisten
http://localhost/eol_php_code/update_resources/connectors/monitor_dwca_refresh.php?dwca_id=protisten
https://editors.eol.org/eol_php_code/update_resources/connectors/monitor_dwca_refresh.php?dwca_id=protisten
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$GLOBALS['ENV_DEBUG'] = false;  //set to false in production
/* during dev only
ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);
$GLOBALS['ENV_DEBUG'] = true; //set to true when debugging
*/
// ini_set('memory_limit','14096M');
require_library('connectors/MonitorDwCARefreshAPI');
$timestart = time_elapsed();

$params['jenkins_or_cron']  = @$argv[1]; //not needed here
$dwca_id                    = @$argv[2];
print_r($argv); //exit("\n");

// $dwca_id = "protisten"; //during dev only

// /* for URL parameters:
if(!$dwca_id) $dwca_id = @get_val_var('dwca_id');
// */

/* worked OK using browser access:
e.g. protisten.de
https://editors.eol.org/eol_php_code/update_resources/connectors/monitor_dwca_refresh.php?dwca_id=protisten
*/

if(!$dwca_id) exit("\nERROR: Incomplete parameters. No DwCA ID. [$dwca_id]\n");

$func = new MonitorDwCARefreshAPI();
$found_hits_YN = $func->start($dwca_id, "1st");
if(!$found_hits_YN) {
    $possible_IDs = $func->start($dwca_id, "2nd");
    // if(!$possible_IDs) $func->lookup_CKAN_for_DwCA_ID($dwca_id);
    $func->lookup_CKAN_for_DwCA_ID($dwca_id); //continue always, not dependent of $possible_IDs
}

// Functions::get_time_elapsed($timestart);
function get_val_var($v)
{
    if     (isset($_GET["$v"])) $var = $_GET["$v"];
    elseif (isset($_POST["$v"])) $var = $_POST["$v"];
    if(isset($var)) return $var;
    else return NULL;
}
?>