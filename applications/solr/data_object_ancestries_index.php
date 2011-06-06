<?php

include_once(dirname(__FILE__) . "/../../config/environment.php");
$GLOBALS['ENV_DEBUG'] = false;

$log = HarvestProcessLog::create(array('process_name' => 'DataObjectAncestriesIndexer'));

$indexer = new DataObjectAncestriesIndexer();
$indexer->index();

$log->finished();

?>