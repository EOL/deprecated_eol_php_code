<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/FaloDataConnector');

$resource_id = 'falo';
$connector = new FaloDataConnector($resource_id);
$connector->begin();

?>
