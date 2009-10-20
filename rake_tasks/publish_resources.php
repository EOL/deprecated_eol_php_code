<?php

//define('DEBUG', true);
//define('MYSQL_DEBUG', true);
//define('DEBUG_TO_FILE', true);

include_once(dirname(__FILE__)."/../config/start.php");

$GLOBALS['mysqli_connection'] = load_mysql_environment(ENVIRONMENT);

Functions::log("Starting publishing");

$resources = Resource::ready_for_publishing();
foreach($resources as $resource)
{
    echo "\nPublishing $resource->title ($resource->id)\n\n";
    $resource->publish();
}
Functions::log("Ended publishing");

?>