<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/BioInfoDataConnector');

$resource_id = 13;
$connector = new BioInfoDataConnector($resource_id);
$connector->build_archive();
Functions::set_resource_status_to_force_harvest($resource_id);

?>