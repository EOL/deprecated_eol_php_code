<?php

include_once(dirname(__FILE__) . "/../config/environment.php");
$GLOBALS['ENV_DEBUG'] = false;
require_library("HierarchiesContent");

$log = HarvestProcessesLog::create('HierarchiesContent');

$hierarchies_content = new HierarchiesContent();
$hierarchies_content->begin_process();

$log->finished();

?>