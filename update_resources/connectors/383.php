<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/ITISConnector');

$resource_id = 383;
$itis = new ITISConnector($resource_id);
$itis->build_archive();
Functions::set_resource_status_to_force_harvest($resource_id);

?>