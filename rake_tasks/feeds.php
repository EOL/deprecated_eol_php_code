<?php

include_once(dirname(__FILE__) . "/../config/environment.php");
$GLOBALS['ENV_DEBUG'] = false;
require_library('FeedDenormalizer');

$log = HarvestProcessesLog::create('Feeds');

$feed = new FeedDenormalizer();
$feed->begin_process();

$log->finished();

?>