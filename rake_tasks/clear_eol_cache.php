<?php

include_once(dirname(__FILE__) . "/../config/environment.php");

$mysqli =& $GLOBALS['mysqli_connection'];



$log = HarvestProcessLog::create('Clear Cache');

// Check to see if there is an array of URLs which will clear the caches.
// This runs every time we flush the cache daily
if(@!$GLOBALS['clear_cache_urls']) return;
foreach($GLOBALS['clear_cache_urls'] as $clear_cache_url)
{
    debug("Clearing cache of species pages: " . $clear_cache_url);
    Functions::get_remote_file($clear_cache_url);
}

$log->finished();

?>