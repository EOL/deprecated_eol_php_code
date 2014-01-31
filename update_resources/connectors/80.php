<?php
namespace php_active_record;
define('DOWNLOAD_WAIT_TIME', '1000000');  // 1 second wait after every web request
include_once(dirname(__FILE__) . "/../../config/environment.php");
define("WIKI_USER_PREFIX", "http://en.wikipedia.org/wiki/User:");
require_vendor("wikipedia");
//$GLOBALS['ENV_DEBUG'] = false;


$harvester = new WikipediaHarvester();
$harvester->begin_wikipedia_harvest();


?>
