<?php

define('DEBUG', true);
include_once(dirname(__FILE__) . "/../config/start.php");

$mysqli =& $GLOBALS['mysqli_connection'];



Functions::log("Starting clear cache");

// Check to see if there is an array of URLs which will clear the caches.
// This runs every time we flush the cache daily
if(@!$GLOBALS['clear_cache_urls']) return;
foreach($GLOBALS['clear_cache_urls'] as $clear_cache_url)
{
    Functions::debug("Clearing cache of species pages: " . $clear_cache_url);
    Functions::get_remote_file($clear_cache_url);
}

Functions::log("Ending clear cache");

?>