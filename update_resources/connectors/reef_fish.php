<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/ReefFishDataConnector');

$resource_id = 1002;
$connector = new ReefFishDataConnector($resource_id);
$connector->build_archive();
Functions::set_resource_status_to_force_harvest($resource_id);

?>