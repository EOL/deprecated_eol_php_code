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
$resource_id = 'wikipedia-fr';
$func = new ResourceConnectorMngmt($resource_id);
$func->get_harvests_for_resource_id($resource_id);
?>