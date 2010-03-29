<?php

include_once(dirname(__FILE__) . "/../config/environment.php");
//$GLOBALS['ENV_DEBUG'] = false;

Functions::log("Starting publishing");

$resources = Resource::ready_for_publishing();
foreach($resources as $resource)
{
    echo "\nPublishing $resource->title ($resource->id)\n\n";
    $resource->publish();
}
Functions::log("Ended publishing");

?>