<?php

include_once(dirname(__FILE__) . "/../../config/environment.php");

Functions::log("Starting TaxonConceptIndexer");

$indexer = new TaxonConceptIndexer();
$indexer->index();

Functions::log("Ending TaxonConceptIndexer");


?>