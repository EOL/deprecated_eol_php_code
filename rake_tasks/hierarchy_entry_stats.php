<?php

include_once(dirname(__FILE__) . "/../config/environment.php");
$GLOBALS['ENV_DEBUG'] = false;
require_library("HierarchyEntryStats");


Functions::log("Starting hierarchies_stats");

$st = new HierarchyEntryStats();
$st->begin_process();

Functions::log("Ended hierarchies_stats");



?>