<?php
namespace php_active_record;

include_once(dirname(__FILE__) . "/../config/environment.php");
$GLOBALS['ENV_DEBUG'] = false;

$log = HarvestProcessLog::create(array('process_name' => 'Flatten Hierarchies'));

$fh = new FlattenHierarchies();
$fh->begin_process();

$log->finished();

?>