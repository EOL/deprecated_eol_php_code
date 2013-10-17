<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/EnvironmentsDataConnector');

$resource_id = 'environments';
$connector = new EnvironmentsDataConnector($resource_id);
$connector->build_archive();

?>