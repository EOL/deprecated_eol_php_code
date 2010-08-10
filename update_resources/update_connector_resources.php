<?php

include_once(dirname(__FILE__) . "/../config/environment.php");
system("clear");

$mysqli =& $GLOBALS['mysqli_connection'];

$manager = new ContentManager();


$connectors = Functions::get_files_in_dir(dirname(__FILE__) . "/connectors");
foreach($connectors as $file)
{
    if(!preg_match("/^(.*)\.php$/", $file, $arr)) continue;
    
    $resource = new Resource($arr[1]);
    if(!@$resource->id) continue;
    if(!$resource->ready_to_update()) continue;
    
    // don't do Wikimedia Commons or Wikipedia this way
    if($resource->id==71) continue;
    if($resource->id==80) continue;
    
    echo "$file...\n";
    shell_exec(PHP_BIN_PATH . dirname(__FILE__) . "/connectors/". $file." ENV_NAME=slave");
}

?>