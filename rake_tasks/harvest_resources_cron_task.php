<?php
namespace php_active_record;

include_once(dirname(__FILE__) . "/../config/environment.php");

$specified_id = @$argv[1];
if(!is_numeric($specified_id)) $specified_id = null;
$fast_for_testing = @$argv[2];
if($fast_for_testing && $fast_for_testing == "--fast") $fast_for_testing = true;
else $fast_for_testing = false;

// this checks to make sure we only have one instance of this script running if
// there are more than one then it means we're still harvesting something from
// yesterday NOTE: change this to 4 to run another process, if you have sent
// SIGSTOP to the first.
if(Functions::grep_processlist('harvest_resources') > 2)
{
  error_log("!! ERROR: There is another harvest_resources process running. Exiting.");
  exit;
}

$log = HarvestProcessLog::create(array('process_name' => 'Harvesting'));
#$previous_resource = Resource::create();
//$resources = Resource::ready_for_harvesting();
// $resources = array(Resource::find(SOME_ID_HERE));
$start_time = time();
$harvested = array();

while((time() - $start_time)/(60*60) < 10)
{
	//sleep the php until resuming the harvest from the rails side
	while(Resource::is_paused() == 1)
		sleep(40);
	//get the resource and check with the previous one
	$resource = Resource::get_ready_resource();

	if (is_null($resource))
		break;

	if (!isset($previous_resource->id))
		$previous_resource->id = -1;

	if ($previous_resource->id == $resource->id){
		$resource->harvesting_failed();
		$previous_resource = $resource;
		continue;
	}else{
		$previous_resource = $resource;
	}

	$GLOBALS['currently_harvesting_resource_id'] = $resource->id;
    // IMPORTANT!
    // We skip a few hard-coded resource IDs, here.
    // TODO - it would be preferable if this flag were in the DB. ...It looks like using a ResourceStatus could achieve the effect.
    // TODO - output a warning if a resource gets skipped.
    if(in_array($resource->id, array(77, 710, 752))) {
      error_log("** SKIPPING hard-coded exception resource " . $resource->id);
      error_log("   (" . $resource->title . ")");
      continue;
    }
    // NOTE that a specified id will get SKIPPED if it's not "ready" for harvesting.
    if($specified_id && $resource->id != $specified_id) {
      error_log("** SKIPPING non-specified resource " . $resource->id);
      error_log("   (" . $resource->title . ")");
      continue;
    };
    if($GLOBALS['ENV_DEBUG']) echo date("Y-m-d", time()) . "++ START HARVEST " .
      $resource->id . " (" . $resource->title . ")\n";

    $validate = true;
    if($GLOBALS['ENV_NAME'] == 'test') $validate = false;

    try {
      $resource->harvest($validate, false, $fast_for_testing);
      array_push($harvested, $resource->id);
    } catch (\Exception $e) {
      echo 'Caught exception: ',  $e->getMessage(), "\n";
      $resource->update_hierarchy_entries_count();
    }
}

if (empty($harvested)) {
  debug("NOTHING HARVESTED. Not enqueing anything for Ruby.\n");
} else {
  debug("Enqueing publish_batch for " + join(', ', $harvested)+"\n");
  \Resque::enqueue('notifications', 'CodeBridge',
    array('cmd' => 'publish_batch', 'resource_ids' => $harvested));
}

$log->finished();
?>
