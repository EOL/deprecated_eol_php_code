<?php

include_once(dirname(__FILE__) . "/../config/environment.php");
system("clear");

$log = HarvestProcessLog::create('Update Downloadable Resources');

$mysqli =& $GLOBALS['mysqli_connection'];

$manager = new ContentManager();



$result = $mysqli->query("SELECT id FROM resources WHERE accesspoint_url!='' AND accesspoint_url IS NOT NULL AND service_type_id=".ServiceType::insert("EOL Transfer Schema")." AND refresh_period_hours > 0");
while($result && $row=$result->fetch_assoc())
{
    echo $row["id"]."...\n";
    $resource = new Resource($row["id"]);
    if(!$resource->id) continue;
    
    // check the file's modified date and when it was last harvested
    if(!$resource->ready_to_update() && !$resource->ready_to_harvest(10)) continue;
    
    if($resource->id==11) continue; //biolib.cz
    if($resource->id==42) continue; //fishbase
    if($resource->id==220) continue; //scratchpads http://diptera.myspecies.info/spm/export.xml
    //if($resource->id!=61) continue;
    
    if($resource->accesspoint_url)
    {
        $new_resource_path = $manager->grab_file($resource->accesspoint_url, $resource->id, "resource");
        if(!$new_resource_path)
        {
            $mysqli->update("UPDATE resources SET resource_status_id=".ResourceStatus::insert("Upload Failed")." WHERE id=$resource->id");
        }
    }
}

$log->finished();

?>