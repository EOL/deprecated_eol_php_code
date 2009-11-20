#!/usr/local/bin/php
<?php
//use to set values in the database
//exit;

define("MYSQL_DEBUG", false);
define("DEBUG", false);
include_once(dirname(__FILE__) . "/../../config/start.php");
$mysqli =& $GLOBALS['mysqli_connection'];

$wrap = "\n\n\n";

$id=79;  //public health image library
$fld = "service_type_id";

$query="Select resources.$fld, resources.title, resources.id From resources Where resources.id = $id";
$result = $mysqli->query($query);    
while($row=$result->fetch_assoc()){print "$row[$fld] -- $row[title] -- $wrap";}

$query="update resources set $fld = 2 Where id = $id";
$update = $mysqli->query($query);    

$query="Select resources.$fld, resources.title, resources.id From resources Where resources.id = $id";
$result = $mysqli->query($query);    
while($row=$result->fetch_assoc()){print "$row[$fld] -- $row[title] -- $wrap";}





