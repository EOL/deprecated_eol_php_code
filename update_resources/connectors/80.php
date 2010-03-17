<?php

$GLOBALS['ENV_NAME'] = 'slave';
include_once(dirname(__FILE__) . "/../../config/environment.php");
define("WIKI_USER_PREFIX", "http://en.wikipedia.org/wiki/User:");
Functions::require_vendor("wikipedia");




exit;
$harvester = new WikipediaHarvester();
$harvester->begin_wikipedia_harvest();


?>