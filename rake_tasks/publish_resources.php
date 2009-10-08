<?php

define('DEBUG', true);
define('MYSQL_DEBUG', true);
define('DEBUG_TO_FILE', true);

include_once(dirname(__FILE__)."/../config/start.php");

$mysqli =& $GLOBALS['mysqli_connection'];


$resources = Resource::ready_for_publishing();
foreach($resources as $resource)
{
    echo "\nPublishing $resource->title ($resource->id)\n\n";
    $resource->publish();
}

// shell_exec(dirname(__FILE__)."/denormalize_tables");
// shell_exec(dirname(__FILE__)."/clear_eol_cache.php");

?>