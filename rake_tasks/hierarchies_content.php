<?php
namespace php_active_record;

include_once(dirname(__FILE__) . "/../config/environment.php");
// $GLOBALS['ENV_DEBUG'] = false;
require_library("HierarchiesContent");

$log = HarvestProcessLog::create(array('process_name' => 'HierarchiesContent'));

$hierarchies_content = new HierarchiesContent();
$hierarchies_content->begin_process();

$log->finished();

?>