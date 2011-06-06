<?php
namespace php_active_record;

include_once(dirname(__FILE__) . "/../config/environment.php");
//$GLOBALS['ENV_DEBUG'] = false;

$log = HarvestProcessLog::create(array('process_name' => 'Publishing'));

$resources = Resource::ready_for_publishing();
foreach($resources as $resource)
{
    echo "\nPublishing $resource->title ($resource->id)\n\n";
    $resource->publish();
}
$log->finished();

?>