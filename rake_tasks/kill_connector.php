<?php
namespace php_active_record;

/* 
This will kill the connector if it is running.
e.g.
php kill_connector.php resource_id
Where resource_id param is numeric.
*/

include_once(dirname(__FILE__) . "/../config/environment.php");

if(isset($argv[1])) $resource_id = $argv[1];
else exit("\n Wrong parameter. Should be: \n     php kill_connector.php resource_id\n The resource_id param is numeric.\n");

if(is_numeric($resource_id)) Functions::kill_running_connectors($resource_id);
else exit("\n Wrong parameter. Should be: \n     php kill_connector.php resource_id\n The resource_id param is numeric.\n");

?>