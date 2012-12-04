<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/PlantConservationAPI');

$resource_id = 498;
$api = new PlantConservationAPI($resource_id);
$api->build_archive();
Functions::set_resource_status_to_force_harvest($resource_id);

?>