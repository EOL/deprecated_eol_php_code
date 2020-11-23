<?php
namespace php_active_record;
/* First client was Wikipedia FR */

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/ResourceConnectorMngmt');
// ini_set('memory_limit','15096M');
// ini_set('memory_limit','6096M');
$GLOBALS['ENV_DEBUG'] = false;
$timestart = time_elapsed();
$func = new ResourceConnectorMngmt('');

/* A utility to investigate one resource:
$GLOBALS['ENV_DEBUG'] = true;
$resource_id = '24'; //'wikipedia-fr';
$sought['month_year'] = '11_2020'; //month 01-12 for Jan-Dec
$func->get_harvests_for_resource_id($resource_id, array(), $sought); //2nd param is download_options
exit;
*/

// /* Main operation

/* Implementation:
php update_resources/connectors/resource_refresh_info.php _ 10_2020
*/

$params['jenkins_or_cron']  = @$argv[1];
$sought['month_year']       = @$argv[2];

// $sought['month_year'] = '10_2020'; //month 01-12 for Jan-Dec
$func->get_harvests_for_wikipedias(array(), $sought); //1st param is download_options
// */

?>