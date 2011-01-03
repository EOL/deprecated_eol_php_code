<?php

include_once(dirname(__FILE__) . "/../config/environment.php");
$GLOBALS['ENV_DEBUG'] = false;
require_library('DenormalizeTables');

Functions::log("Starting denormalizing");

Functions::log("Starting DataObjectsTaxonConcepts");
DenormalizeTables::data_objects_taxon_concepts();
Functions::log("Ended DataObjectsTaxonConcepts");

Functions::log("Starting DataTypesTaxonConcepts");
DenormalizeTables::data_types_taxon_concepts();
Functions::log("Ended DataTypesTaxonConcepts");

Functions::log("Starting TaxonConceptsExploded");
DenormalizeTables::taxon_concepts_exploded();
Functions::log("Ended TaxonConceptsExploded");

shell_exec(PHP_BIN_PATH . dirname(__FILE__)."/table_of_contents.php ENV_NAME=". $GLOBALS['ENV_NAME']);
shell_exec(PHP_BIN_PATH . dirname(__FILE__)."/hierarchies_content.php ENV_NAME=". $GLOBALS['ENV_NAME']);
shell_exec(PHP_BIN_PATH . dirname(__FILE__)."/top_images.php ENV_NAME=". $GLOBALS['ENV_NAME']);
shell_exec(PHP_BIN_PATH . dirname(__FILE__)."/random_hierarchy_images.php ENV_NAME=". $GLOBALS['ENV_NAME']);
//shell_exec(PHP_BIN_PATH . dirname(__FILE__)."/feeds.php ENV_NAME=". $GLOBALS['ENV_NAME']);

Functions::log("Ended denormalizing");


?>
