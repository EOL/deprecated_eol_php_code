<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/ADWDataConnector');

$resource_id = 'adw';
$connector = new ADWDataConnector($resource_id);
$connector->build_archive();
// Functions::set_resource_status_to_harvest_requested($resource_id);

?>