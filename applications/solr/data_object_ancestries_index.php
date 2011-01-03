<?php

include_once(dirname(__FILE__) . "/../../config/environment.php");
$GLOBALS['ENV_DEBUG'] = false;

$log = HarvestProcessLog::create('DataObjectAncestriesIndexer');

$indexer = new DataObjectAncestriesIndexer();
$indexer->index();

$log->finished();

?>