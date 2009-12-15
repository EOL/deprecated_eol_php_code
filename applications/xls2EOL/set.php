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

/*
$id=26; //for WORMS
$fld = "accesspoint_url";
$value = "";

$id=79;  //public health image library
$fld = "service_type_id";
$value = 2;
*/

$id=89; //for DiArk
$fld = "accesspoint_url";
$value = "http://www.goenmr.de/~bham/diark2009_12_15_2nd.xml";

$query="Select resources.$fld, resources.title, resources.id From resources Where resources.id = $id";
$result = $mysqli->query($query);    
while($row=$result->fetch_assoc()){print "$row[$fld] -- $row[title] -- $wrap";}

$query="update resources set $fld = '$value' Where id = $id";
$update = $mysqli->query($query);    

$query="Select resources.$fld, resources.title, resources.id From resources Where resources.id = $id";
$result = $mysqli->query($query);    
while($row=$result->fetch_assoc()){print "$row[$fld] -- $row[title] -- $wrap";}


