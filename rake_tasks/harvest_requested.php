<?php
namespace php_active_record;

$attr = @$argv[1];
$id = @$argv[2];
$opt1 = @$argv[3];
$opt2 = @$argv[4];

$options = array("-download", "-now");

if($attr != "-id" || !$id || !is_numeric($id))
{
    echo "\n\n\tharvest_requested.php -id [resource_id] [-download] [-now]\n\n";
    exit;
}



include_once(dirname(__FILE__) . "/../config/environment.php");

$mysqli =& $GLOBALS['mysqli_connection'];

$resource = Resource::find($id);
if($resource)
{
    if($opt1 == "-download" || $opt2 == "-download")
    {
        if($resource->accesspoint_url && $resource->service_type_id == ServiceType::find_or_create_by_translated_label('EOL Transfer Schema')->id)
        {
            echo "\nDownloading $resource->title ($id)\n";
            $manager = new ContentManager();
            $new_resource_path = $manager->grab_file($resource->accesspoint_url, "resource", array('resource_id' => $resource->id, 'timeout' => 600));
            if(!$new_resource_path)
            {
                $mysqli->update("UPDATE resources SET resource_status_id=".ResourceStatus::find_or_create_by_translated_label("Upload Failed")->id ." WHERE id=$resource->id");
                echo "\n$resource->title ($id) resource download failed\n\n";
                exit;
            }
        }
    }
    
    
    if(!file_exists($resource->resource_file_path()) && !$resource->is_archive_resource())
    {
        echo "\n$resource->title ($id) does not have a resource file\n\n";
        exit;
    }
    
    if($GLOBALS['ENV_DEBUG']) echo "Setting status of $resource->title ($id) to Harvest Requested\n";
    $mysqli->update("UPDATE resources SET resource_status_id = ". ResourceStatus::find_or_create_by_translated_label("Harvest Requested")->id ." where id=$resource->id");
}else echo "\nNo resource with id $id\n\n";


?>
