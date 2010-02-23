<?php


define('ENVIRONMENT', 'slave');
//define('DEBUG', true);
include_once(dirname(__FILE__) . "/../../config/start.php");
define("WIKI_USER_PREFIX", "http://en.wikipedia.org/wiki/User:");
Functions::require_module("wikipedia");
$mysqli =& $GLOBALS['mysqli_connection'];

date_default_timezone_set('America/New_York');




$harvester = new WikipediaHarvester();
$harvester->begin_wikipedia_harvest();


?>