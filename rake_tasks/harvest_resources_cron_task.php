#!/usr/local/bin/php
<?php

define('DEBUG', true);
define('MYSQL_DEBUG', true);
//define('DEBUG_TO_FILE', true);

include_once(dirname(__FILE__)."/../config/start.php");

$mysqli =& $GLOBALS['mysqli_connection'];


$resources = Resource::ready_for_harvesting();
foreach($resources as $resource)
{
    if(isset($GLOBALS['lifedesks_to_ignore']) && preg_match("/(".implode('|', $lifedesks_to_ignore).")\.lifedesks\.org/", $resource->accesspoint_url)) continue;
    //if($resource->id != 71) continue;
    
    echo $resource->id."\n";
    $resource->harvest();
}


// sleep for 15 minutes to allow changes from transactions to propegate
sleep(960);

// publish all pending resources
shell_exec("php ".dirname(__FILE__)."/publish_resources.php");

// denormalize tables
shell_exec("php ".dirname(__FILE__)."/denormalize_tables.php");

// finally, clear the cache
shell_exec("php ".dirname(__FILE__)."/clear_eol_cache.php");

?>