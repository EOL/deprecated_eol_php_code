#!/usr/local/bin/php
<?php

//define('DEBUG', true);
//define('MYSQL_DEBUG', true);
//define('DEBUG_TO_FILE', true);

include_once(dirname(__FILE__)."/../config/start.php");

$GLOBALS['mysqli_connection'] = load_mysql_environment(ENVIRONMENT);


// this checks to make sure we only have one instance of this script running
// if there are more than one then it means we're still harvesting something from yesterday
if(Functions::grep_processlist('harvest_resources') > 1) exit;


Functions::log("Starting harvesting");
$resources = Resource::ready_for_harvesting();
foreach($resources as $resource)
{
    if(isset($GLOBALS['lifedesks_to_ignore']) && preg_match("/(".implode('|', $GLOBALS['lifedesks_to_ignore']).")\.lifedesks\.org/", $resource->accesspoint_url)) continue;
    //if($resource->id != 71) continue;
    
    echo $resource->id."\n";
    $resource->harvest();
}
Functions::log("Ended harvesting");



// sleep for 20 minutes to allow changes from transactions to propegate
if(defined('ENVIRONMENT') && ENVIRONMENT =='development') sleep(1);
else sleep(1200);


// publish all pending resources
shell_exec("/usr/local/bin/php ".dirname(__FILE__)."/publish_resources.php");

// denormalize tables
shell_exec("/usr/local/bin/php ".dirname(__FILE__)."/denormalize_tables.php");

// finally, clear the cache
shell_exec("/usr/local/bin/php ".dirname(__FILE__)."/clear_eol_cache.php");


?>