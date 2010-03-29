<?php

include_once(dirname(__FILE__) . "/../config/environment.php");
$GLOBALS['ENV_DEBUG'] = false;
require_library('FeedDenormalizer');

Functions::log("Starting feeds");

$feed = new FeedDenormalizer();
$feed->begin_process();

Functions::log("Ended feeds");

?>