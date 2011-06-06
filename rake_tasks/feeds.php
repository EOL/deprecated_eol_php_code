<?php
namespace php_active_record;

include_once(dirname(__FILE__) . "/../config/environment.php");
$GLOBALS['ENV_DEBUG'] = false;
require_library('FeedDenormalizer');

$log = HarvestProcessLog::create(array('process_name' => 'Feeds'));

$feed = new FeedDenormalizer();
$feed->begin_process();

$log->finished();

?>