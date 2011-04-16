<?php

include_once(dirname(__FILE__) . "/../config/environment.php");
$GLOBALS['ENV_DEBUG'] = false;
require_library('FlattenHierarchies');

$log = HarvestProcessLog::create('Flatten Hierarchies');

$fh = new FlattenHierarchies();
$fh->begin_process();

$log->finished();

?>