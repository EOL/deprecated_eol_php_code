<?php

include_once(dirname(__FILE__) . "/../config/environment.php");
$GLOBALS['ENV_DEBUG'] = false;
require_library("HierarchiesContent");
require_library('DenormalizeTables');

Functions::log("Starting hierarchies_content");

DenormalizeTables::data_types_taxon_concepts();

$hierarchies_content = new HierarchiesContent();
$hierarchies_content->begin_process();

Functions::log("Ended hierarchies_content");

?>