<?php

include_once(dirname(__FILE__) . "/../config/environment.php");
$GLOBALS['ENV_DEBUG'] = false;

Functions::log("Starting denormalizing");

shell_exec(PHP_BIN_PATH . dirname(__FILE__)."/hierarchies_content.php ENV_NAME=". $GLOBALS['ENV_NAME']);
shell_exec(PHP_BIN_PATH . dirname(__FILE__)."/top_images.php ENV_NAME=". $GLOBALS['ENV_NAME']);
shell_exec(PHP_BIN_PATH . dirname(__FILE__)."/random_taxa.php ENV_NAME=". $GLOBALS['ENV_NAME']);
shell_exec(PHP_BIN_PATH . dirname(__FILE__)."/random_hierarchy_images.php ENV_NAME=". $GLOBALS['ENV_NAME']);
shell_exec(PHP_BIN_PATH . dirname(__FILE__)."/table_of_contents.php ENV_NAME=". $GLOBALS['ENV_NAME']);
//shell_exec(PHP_BIN_PATH . dirname(__FILE__)."/clear_eol_cache.php ENV_NAME=". $GLOBALS['ENV_NAME']);

Functions::log("Ended denormalizing");


?>