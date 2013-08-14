<?php
namespace php_active_record;

define('DOWNLOAD_WAIT_TIME', '1000000');  // 2 second wait after every web request
include_once(dirname(__FILE__) . "/../../config/environment.php");
$GLOBALS['ENV_DEBUG'] = false;
define("WIKI_USER_PREFIX", "http://commons.wikimedia.org/wiki/User:");
define("WIKI_PREFIX", "http://commons.wikimedia.org/wiki/");
require_vendor("wikipedia");


$w = new WikimediaHarvester();
$w->begin_wikipedia_harvest();


?>
