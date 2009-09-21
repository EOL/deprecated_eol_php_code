<?php

system("clear");
$attr = @$argv[1];
$id = @$argv[2];

if($attr != "-id" || !$id || !is_numeric($id))
{
    echo "\n\n\tforce_download.php -id [resource_id]\n\n";
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
    
    echo "Harvesting $resource->title ($id)\n";
    $resource->harvest();
}else echo "\nNo resource with id $id\n\n";


?>