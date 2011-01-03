<?php

include_once(dirname(__FILE__) . "/../config/environment.php");
$GLOBALS['ENV_DEBUG'] = false;
require_library("HierarchyEntryStats");


$log = HarvestProcessLog::create('hierarchies_stats');

$st = new HierarchyEntryStats();
$st->begin_process();

$log->finished();

?>