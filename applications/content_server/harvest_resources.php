#!/usr/local/bin/php
<?php

define('MYSQL_DEBUG', true);
define('DEBUG', true);
//define('DEBUG_TO_FILE', true);
define("ENVIRONMENT", "development");
define("DEBUG_PARSE_TAXON_LIMIT", 100);

$path = "";
//if(preg_match("/^(.*\/)[^\/]+/", $_SERVER["_"], $arr)) $path = $arr[1];
include_once($path."../../config/start.php");

$mysqli =& $GLOBALS['mysqli_connection'];



$mysqli->truncate_tables("development");
Functions::load_fixtures("development");


//exit;
reset_resource_harvest_dates();


$resources = Resource::ready_for_harvesting();
foreach($resources as $resource)
{
    if($resource->id == 111) continue;
    if($resource->id == 222) continue;
    if($resource->id == 333) continue;
    if($resource->id == 444) continue;
    if($resource->id == 555) continue;
    //if($resource->id == 666) continue;
    
    Functions::debug("Starting: ".$resource->id);
    
    //exit;
    
    if($resource->harvested_at && $resource->resource_status_id != ResourceStatus::insert("Published"))
    {
        // remove last harvest event and associated data objects
        
        if($harvest_event = $resource->last_harvest_event())
        {
            $harvest_event->rollback();
        }
    }
    
    $resource->harvest();
}




function reset_resource_harvest_dates()
{
    global $mysqli;
    
    // $mysqli->query("UPDATE resources SET harvested_at = '2005-01-01' WHERE id=111");
    // $mysqli->query("UPDATE resources SET resource_status_id = ".ResourceStatus::insert("Published")." WHERE id=111");
    
    // $mysqli->query("UPDATE resources SET harvested_at = '2005-01-01' WHERE id=222");
    // $mysqli->query("UPDATE resources SET resource_status_id = ".ResourceStatus::insert("Published")." WHERE id=222");
    // 
    // $mysqli->query("UPDATE resources SET harvested_at = '2005-01-01' WHERE id=333");
    // $mysqli->query("UPDATE resources SET resource_status_id = ".ResourceStatus::insert("Published")." WHERE id=333");
    // 
    // $mysqli->query("UPDATE resources SET harvested_at = '2005-01-01' WHERE id=444");
    // $mysqli->query("UPDATE resources SET resource_status_id = ".ResourceStatus::insert("Published")." WHERE id=444");
    // 
    // $mysqli->query("UPDATE resources SET harvested_at = '2005-01-01' WHERE id=555");
    // $mysqli->query("UPDATE resources SET resource_status_id = ".ResourceStatus::insert("Published")." WHERE id=555");
    
    $mysqli->query("UPDATE resources SET harvested_at = '2005-01-01' WHERE id=666");
    $mysqli->query("UPDATE resources SET resource_status_id = ".ResourceStatus::insert("Published")." WHERE id=666");
}



?>