<?php
namespace php_active_record;

include_once(dirname(__FILE__) . "/../config/environment.php");
system("clear");

$log = HarvestProcessLog::create(array('process_name' => 'Update Downloadable Resources'));

$mysqli =& $GLOBALS['mysqli_connection'];

$manager = new ContentManager();


$resources = array();
$result = $mysqli->query("SELECT id FROM resources WHERE accesspoint_url!='' AND accesspoint_url IS NOT NULL AND service_type_id=".ServiceType::find_or_create_by_translated_label("EOL Transfer Schema")->id." AND refresh_period_hours > 0");
while($result && $row=$result->fetch_assoc())
{
    echo $row["id"]."...\n";
    $resource = Resource::find($row["id"]);
    if(!$resource->id) continue;
    $resources[] = $resource;
}

$more_resources = Resource::ready_for_harvesting();
foreach($more_resources as $resource)
{
    if(!$resource->accesspoint_url) continue;
    if($resource->service_type_id != ServiceType::find_or_create_by_translated_label("EOL Transfer Schema")->id) continue;
    if(!in_array($resource, $resources)) $resources[] = $resource;
}

foreach($resources as $resource)
{
    // check the file's modified date and when it was last harvested
    if(!$resource->ready_to_update() && !$resource->ready_to_harvest(10)) continue;
    
    if($resource->id==11) continue; //biolib.cz
    if($resource->id==42) continue; //fishbase
    // if($resource->id!=59) continue;
    
    if($resource->accesspoint_url)
    {
        echo "$resource->id $resource->accesspoint_url\n";
        $new_resource_path = $manager->grab_file($resource->accesspoint_url, "resource", array('resource_id' => $resource->id, 'timeout' => 600));
        if(!$new_resource_path)
        {
            $mysqli->update("UPDATE resources SET resource_status_id=".ResourceStatus::find_or_create_by_translated_label("Upload Failed")->id." WHERE id=$resource->id");
        }
    }
}

$log->finished();

?>
