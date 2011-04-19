<?php

include_once(dirname(__FILE__) . "/../config/environment.php");
//$GLOBALS['ENV_DEBUG'] = false;

$log = HarvestProcessLog::create('Publishing');

$resources = Resource::ready_for_publishing();
foreach($resources as $resource)
{
    echo "\nPublishing $resource->title ($resource->id)\n\n";
    $resource->publish();
}
$log->finished();

?>