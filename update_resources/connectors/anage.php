<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/AnageDataConnector');

$resource_id = 'anage';
$connector = new AnageDataConnector($resource_id);
$connector->build_archive();
// Functions::set_resource_status_to_force_harvest($resource_id);

?>