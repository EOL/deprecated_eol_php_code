#!/usr/local/bin/php
<?php

define('DEBUG', true);
define('MYSQL_DEBUG', true);
define('DEBUG_TO_FILE', true);

include_once(dirname(__FILE__)."/../config/start.php");

$mysqli =& $GLOBALS['mysqli_connection'];


$lifedesks_to_ignore = array(
    'indianadunes',
    'micronesianinsects',
    'bivalvia',
    'porifera',
    'imanto',
    'equisetum',
    'menegazzia',
    'cephalopods',
    'taxacom'
);


$resources = Resource::ready_for_harvesting();
foreach($resources as $resource)
{
    if(preg_match("/(".implode('|', $lifedesks_to_ignore).")\.lifedesks\.org/", $resource->accesspoint_url)) continue;
    //if($resource->id != 61) continue;
    
    echo $resource->id."\n";
    $resource->harvest();
}

shell_exec(dirname(__FILE__)."/denormalize_tables");
shell_exec(dirname(__FILE__)."/clear_eol_cache.php");

?>