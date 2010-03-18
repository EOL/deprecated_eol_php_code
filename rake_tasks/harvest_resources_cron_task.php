<?php


include_once(dirname(__FILE__) . "/../config/environment.php");

// this checks to make sure we only have one instance of this script running
// if there are more than one then it means we're still harvesting something from yesterday
if(Functions::grep_processlist('harvest_resources') > 1) exit;


Functions::log("Starting harvesting");
$resources = Resource::ready_for_harvesting();
foreach($resources as $resource)
{
    //if($resource->id != 71) continue;
    
    echo $resource->id."\n";
    $resource->harvest();
}
Functions::log("Ended harvesting");



// clear the cache in case some images were unpublished but still referenced in denormalized tables
shell_exec(PHP_BIN_PATH . dirname(__FILE__)."/clear_eol_cache.php");

// sleep for 20 minutes to allow changes from transactions to propegate
sleep_production(1200);

// publish all pending resources
shell_exec(PHP_BIN_PATH . dirname(__FILE__)."/publish_resources.php");

// denormalize tables
shell_exec(PHP_BIN_PATH . dirname(__FILE__)."/denormalize_tables.php");

// finally, clear the cache
shell_exec(PHP_BIN_PATH . dirname(__FILE__)."/clear_eol_cache.php");


?>