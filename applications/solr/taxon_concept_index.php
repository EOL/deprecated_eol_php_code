<?php

define('ENVIRONMENT', 'slave');
//define('MYSQL_DEBUG', true);
include_once(dirname(__FILE__) . "/../../config/start.php");
$mysqli =& $GLOBALS['mysqli_connection'];


Functions::log("Starting TaxonConceptIndexer");

$indexer = new TaxonConceptIndexer();
$indexer->index();

Functions::log("Ending TaxonConceptIndexer");


?>