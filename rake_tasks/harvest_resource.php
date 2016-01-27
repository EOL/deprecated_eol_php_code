<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../config/environment.php");

$specified_id = @$argv[1];
if(!is_numeric($specified_id)) {
  error_log("!! ERROR: You must run this script with a single ID.");
  exit;
}
if(in_array($specified_id, array(77, 710, 752))) {
  error_log("EROR: This resource cannot be harvested: " . $resource->id);
  error_log("   (" . $resource->title . ")");
  exit;
}

// this checks to make sure we only have one instance of this script running if
// there are more than one then it means we're still harvesting something from
// yesterday NOTE: change this to 4 to run another process, if you have sent
// SIGSTOP to the first.
if(Functions::grep_processlist('harvest_resource') > 2)
{
  error_log("!! ERROR: There is another harvest_resource process running. Exiting.");
  exit;
}

$log = HarvestProcessLog::create(array('process_name' => 'Harvesting'));
//sleep the php until resuming the harvest from the rails side
while(Resource::is_paused() == 1)
	sleep(40);

$resource = Resource::find($specified_id);
if (is_null($resource)) {
  error_log("!! ERROR: The resource with ID $specified_id was not found.");
  exit;
}
$GLOBALS['currently_harvesting_resource_id'] = $resource->id;
if($GLOBALS['ENV_DEBUG']) echo date("Y-m-d", time()) . "++ START HARVEST " .
  $resource->id . " (" . $resource->title . ")\n";

try {
  $resource->harvest($GLOBALS['ENV_NAME'] == 'test', false, false);
} catch (\Exception $e) {
  echo 'Caught exception: ',  $e->getMessage(), "\n";
  $resource->update_hierarchy_entries_count();
}
$log->finished();
?>
