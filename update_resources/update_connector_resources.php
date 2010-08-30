<?php

include_once(dirname(__FILE__) . "/../config/environment.php");
system("clear");
Functions::log("Starting Update Connector Resources");

$mysqli =& $GLOBALS['mysqli_connection'];

$manager = new ContentManager();


$connectors = Functions::get_files_in_dir(dirname(__FILE__) . "/connectors");
foreach($connectors as $file)
{
    if(!preg_match("/^(.*)\.php$/", $file, $arr)) continue;
    
    $resource = new Resource($arr[1]);
    if(!@$resource->id) continue;
    if(!$resource->ready_to_update()) continue;
    
    // resources to skip
    if($resource->id==15) continue; // Flickr
    if($resource->id==71) continue; // Wikimedia Commons
    if($resource->id==80) continue; // Wikipedia
    
    echo "$file...\n";
    shell_exec(PHP_BIN_PATH . dirname(__FILE__) . "/connectors/". $file." ENV_NAME=slave");
}
Functions::log("Ended Update Connector Resources");

?>