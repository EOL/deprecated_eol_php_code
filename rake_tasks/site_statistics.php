<?php
namespace php_active_record;

include_once(dirname(__FILE__) . "/../config/environment.php");
//$GLOBALS['ENV_DEBUG'] = false;
require_library('SiteStatistics');

$log = HarvestProcessLog::create(array('process_name' => 'Site Statistics'));

$stats = new SiteStatistics();
$stats->insert_taxa_stats();
$stats->insert_data_object_stats();

$log->finished();

?>