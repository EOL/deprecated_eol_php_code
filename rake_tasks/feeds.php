<?php

include_once(dirname(__FILE__) . "/../config/environment.php");
require_library('FeedDenormalizer');

Functions::log("Starting feeds");

$feed = new FeedDenormalizer();
$feed->begin_process();

Functions::log("Ended feeds");

?>