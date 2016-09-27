<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/NCBIConnector');

$resource_id = 557;
$ncbi = new NCBIConnector($resource_id);
$ncbi->build_archive();
Functions::set_resource_status_to_harvest_requested($resource_id);

?>