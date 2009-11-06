#!/usr/local/bin/php
<?php
//use to set values in the database
//exit;

//define("ENVIRONMENT", "development");
define("MYSQL_DEBUG", false);
define("DEBUG", false);
include_once(dirname(__FILE__) . "/../../config/start.php");
$mysqli =& $GLOBALS['mysqli_connection'];

$wrap = "\n\n\n";

$id=26; //for WORMS

$query="Select resources.accesspoint_url, resources.title, resources.id From resources Where resources.id = $id";
$result = $mysqli->query($query);    
while($row=$result->fetch_assoc()){print "$row[accesspoint_url] -- $row[title] -- $wrap";}

$query="update resources set accesspoint_url = 'http://pandanus.eol.org/public/EOL_resource/26.xml.gz' Where id = $id";
$update = $mysqli->query($query);    

$query="Select resources.accesspoint_url, resources.title, resources.id From resources Where resources.id = $id";
$result = $mysqli->query($query);    
while($row=$result->fetch_assoc()){print "$row[accesspoint_url] -- $row[title] -- $wrap";}





