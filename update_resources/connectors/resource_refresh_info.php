<?php
namespace php_active_record;
/* 
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/ResourceConnectorMngmt');
// ini_set('memory_limit','15096M');
// ini_set('memory_limit','6096M');
// $GLOBALS['ENV_DEBUG'] = true;
$timestart = time_elapsed();
$func = new ResourceConnectorMngmt('');

// /*
$resource_id = 'wikipedia-ext';
$sought['month_year'] = '10_2020'; //month 01-12 for Jan-Dec
$func->get_harvests_for_resource_id($resource_id, array(), $sought); //2nd param is download_options
// */

// /*
$sought['month_year'] = '10_2020'; //month 01-12 for Jan-Dec
$func->get_harvests_for_wikipedias(array(), $sought); //1st param is download_options
// */

?>