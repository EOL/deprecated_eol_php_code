<?php

include_once(dirname(__FILE__) . "/../config/environment.php");
require_library("HierarchiesContent");

Functions::log("Starting hierarchies_content");

$hierarchies_content = new HierarchiesContent();
$hierarchies_content->begin_process();

Functions::log("Ended hierarchies_content");

?>