#!/usr/local/bin/php
<?php

define('DEBUG', true);
define('MYSQL_DEBUG', true);
//define('DEBUG_TO_FILE', true);
include_once(dirname(__FILE__)."/../config/start.php");
system("clear");

$mysqli =& $GLOBALS['mysqli_connection'];

$manager = new ContentManager();



$result = $mysqli->query("SELECT id FROM resources WHERE accesspoint_url!='' AND accesspoint_url IS NOT NULL AND service_type_id=".ServiceType::insert("EOL Transfer Schema")." AND refresh_period_hours > 0");
while($result && $row=$result->fetch_assoc())
{
    echo $row["id"]."...\n\n\n";
    $resource = new Resource($row["id"]);
    if(!$resource->id) continue;
    
    // check the file's modified date and when it was last harvested
    if(!$resource->ready_to_update() && !$resource->ready_to_harvest(10)) continue;
    
    if($resource->id==11) continue; //biolib.cz
    if($resource->id==42) continue; //fishbase
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


$connectors = Functions::get_files_in_dir(dirname(__FILE__) . "/connectors");
foreach($connectors as $file)
{
    if(!preg_match("/^(.*)\.php$/", $file, $arr)) continue;
    echo "$file...\n\n\n";
    
    $resource = new Resource($arr[1]);
    if(!$resource->id) continue;
    if(!$resource->ready_to_update()) continue;
    //if($resource->id!=15) continue;
    
    shell_exec(dirname(__FILE__) . "/connectors/". $file);
}

?>