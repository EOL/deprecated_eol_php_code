<?php

ini_set('display_errors', true);

// make sure you define the proper URL to the root directory of this installation
define('WEB_ROOT', 'http://localhost/eol_php_code/');

$GLOBALS['ENV_DEBUG'] = false;
$GLOBALS['ENV_DEBUG_TO_FILE'] = true;

define('SOLR_SERVER', 'http://localhost:8983/solr/');

define("LOGGING_DB", "eol_logging_test");

?>
