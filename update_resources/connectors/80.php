<?php

include_once(dirname(__FILE__) . "/../../config/environment.php");
define("WIKI_USER_PREFIX", "http://en.wikipedia.org/wiki/User:");
require_vendor("wikipedia");
$GLOBALS['ENV_DEBUG'] = false;

exit;
$harvester = new WikipediaHarvester();
$harvester->begin_wikipedia_harvest();


?>