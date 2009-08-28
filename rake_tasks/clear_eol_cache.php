#!/usr/local/bin/php
<?php

define('DEBUG', true);
$path = "";
if(preg_match("/^(.*\/)[^\/]+/", $_SERVER["_"], $arr)) $path = $arr[1];
include_once($path."../config/start.php");

$mysqli =& $GLOBALS['mysqli_connection'];




// Check to see if there is an array of URLs which will clear the caches.
// This runs every time we flush the cache daily

if(@!$GLOBALS['clear_cache_urls']) return;
foreach($GLOBALS['clear_cache_urls'] as $clear_cache_url)
{
    Functions::debug("Clearing cache of species pages: " . $clear_cache_url);
    Functions::get_remote_file($clear_cache_url);
}

?>