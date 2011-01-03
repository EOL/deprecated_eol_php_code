<?php

include_once(dirname(__FILE__) . "/../config/environment.php");
$GLOBALS['ENV_DEBUG'] = false;
require_library('DenormalizeTables');

$log = HarvestProcessesLog::create('Denormalizing');

$sub_log = HarvestProcessesLog::create('DataObjectsTaxonConcepts');
DenormalizeTables::data_objects_taxon_concepts();
$sub_log->finished();

$sub_log = HarvestProcessesLog::create('DataTypesTaxonConcepts');
DenormalizeTables::data_types_taxon_concepts();
$sub_log->finished();

$sub_log = HarvestProcessesLog::create('TaxonConceptsExploded');
DenormalizeTables::taxon_concepts_exploded();
$sub_log->finished();

shell_exec(PHP_BIN_PATH . dirname(__FILE__)."/table_of_contents.php ENV_NAME=". $GLOBALS['ENV_NAME']);
shell_exec(PHP_BIN_PATH . dirname(__FILE__)."/hierarchies_content.php ENV_NAME=". $GLOBALS['ENV_NAME']);
shell_exec(PHP_BIN_PATH . dirname(__FILE__)."/top_images.php ENV_NAME=". $GLOBALS['ENV_NAME']);
shell_exec(PHP_BIN_PATH . dirname(__FILE__)."/random_hierarchy_images.php ENV_NAME=". $GLOBALS['ENV_NAME']);
// shell_exec(PHP_BIN_PATH . dirname(__FILE__)."/feeds.php ENV_NAME=". $GLOBALS['ENV_NAME']);

$log->finished();

?>
