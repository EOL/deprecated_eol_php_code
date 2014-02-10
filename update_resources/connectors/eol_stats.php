<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/EolStatsDataConnector');

$resource_id = 'eol_stats';
$connector = new EolStatsDataConnector($resource_id);
$connector->begin();

?>
