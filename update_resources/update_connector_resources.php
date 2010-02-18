<?php

define('DEBUG', true);
define('MYSQL_DEBUG', true);
//define('DEBUG_TO_FILE', true);
include_once(dirname(__FILE__)."/../config/start.php");
system("clear");

$mysqli =& $GLOBALS['mysqli_connection'];

$manager = new ContentManager();


$connectors = Functions::get_files_in_dir(dirname(__FILE__) . "/connectors");
foreach($connectors as $file)
{
    if(!preg_match("/^(.*)\.php$/", $file, $arr)) continue;
    echo "$file...\n\n\n";
    
    $resource = new Resource($arr[1]);
    if(!$resource->id) continue;
    if(!$resource->ready_to_update()) continue;
    //if($resource->id==31) continue;
    
    shell_exec(PHP_BIN_PATH . dirname(__FILE__) . "/connectors/". $file);
}

?>