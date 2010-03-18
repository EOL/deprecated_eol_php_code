<?php

include_once(dirname(__FILE__) . "/../../config/environment.php");
$mysqli =& $GLOBALS['mysqli_connection'];


Functions::log("Starting TaxonConceptIndexer");

$indexer = new TaxonConceptIndexer();
$indexer->index();

Functions::log("Ending TaxonConceptIndexer");


?>