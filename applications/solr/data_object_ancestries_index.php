<?php

include_once(dirname(__FILE__) . "/../../config/environment.php");
$GLOBALS['ENV_DEBUG'] = false;

Functions::log("Starting DataObjectAncestriesIndexer");

$indexer = new DataObjectAncestriesIndexer();
$indexer->index();

Functions::log("Ending DataObjectAncestriesIndexer");


?>