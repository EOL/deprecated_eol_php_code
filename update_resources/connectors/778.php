<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/FaloDataConnector');

$resource_id = 778;
$connector = new FaloDataConnector($resource_id);
$connector->begin();
unset($connector); // To remove temp file and write to log before script ends.

?>
