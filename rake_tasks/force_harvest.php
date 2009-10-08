<?php

system("clear");
$attr = @$argv[1];
$id = @$argv[2];
$opt1 = @$argv[3];
$opt2 = @$argv[4];

$options = array("-download", "-now");

if($attr != "-id" || !$id || !is_numeric($id) || ($opt1 && !in_array($opt1, $options)) || ($opt2 && !in_array($opt2, $options)))
{
    echo "\n\n\tforce_download.php -id [resource_id] [-download] [-now]\n\n";
    exit;
}



define('DEBUG', true);
define('MYSQL_DEBUG', true);
define('DEBUG_TO_FILE', true);

include_once(dirname(__FILE__)."/../config/start.php");

$mysqli =& $GLOBALS['mysqli_connection'];


$lifedesks_to_ignore = array(
    'micronesianinsects',
    'bivalvia',
    'porifera',
    'imanto',
    'equisetum',
    'menegazzia',
    'cephalopods',
    'taxacom'
);

$resource = new Resource($id);
if($resource)
{
    if($opt1 == "-download" || $opt2 == "-download")
    {
        if($resource->accesspoint_url && $resource->service_type_id == ServiceType::insert('EOL Transfer Schema'))
        {
            echo "\nDownloading $resource->title ($id)\n";
            $manager = new ContentManager();
            $new_resource_path = $manager->grab_file($resource->accesspoint_url, $resource->id, "resource");
            if(!$new_resource_path)
            {
                $mysqli->update("UPDATE resources SET resource_status_id=".ResourceStatus::insert("Upload Failed")." WHERE id=$resource->id");
                echo "\n$resource->title ($id) resource download failed\n\n";
                exit;
            }
        }
    }
    
    
    if(isset($GLOBALS['lifedesks_to_ignore']) && preg_match("/(".implode('|', $lifedesks_to_ignore).")\.lifedesks\.org/", $resource->accesspoint_url))
    {
        echo "\n$resource->title ($id) is a LifeDesk that is being ignored\n\n";
        exit;
    }
    if(!file_exists($resource->resource_file_path()))
    {
        echo "\n$resource->title ($id) does not have a resource file\n\n";
        exit;
    }
    
    if($opt1 == "-now" || $opt2 == "-now")
    {
        echo "Harvesting $resource->title ($id)\n";
        $resource->harvest();
    }else
    {
        echo "Setting status of $resource->title ($id) to force harvest\n";
        $mysqli->update("UPDATE resources SET resource_status_id = ". ResourceStatus::insert("Force Harvest")." where id=$resource->id");
    }
}else echo "\nNo resource with id $id\n\n";


?>