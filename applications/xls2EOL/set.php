<?php
//use to set values in the database

include_once(dirname(__FILE__) . "/../../config/environment.php");
$mysqli =& $GLOBALS['mysqli_connection'];

$wrap = "\n\n";

/*
$id     = 201; //MCZ
$fld    = "resource_status_id";
$value  = 11; //Publish Pending

$id     = 214;  //Vimeo
$fld    = "service_type_id";
$value  = 2;

$id     = 214; //Vimeo
$fld    = "auto_publish";
$value  = 1;

$query="Select resources.$fld, resources.title, resources.id From resources Where resources.id = $id"; $result = $mysqli->query($query);    
while($row=$result->fetch_assoc()){print "$row[$fld] -- $row[title] -- $wrap";}
$query="update resources set $fld = '$value' Where id = $id"; $update = $mysqli->query($query);    
$query="Select resources.$fld, resources.title, resources.id From resources Where resources.id = $id"; $result = $mysqli->query($query);    
while($row=$result->fetch_assoc()){print "$row[$fld] -- $row[title] -- $wrap";}
*/

$date_created = date('Y-m-d');
$time_created = date('H:i:s');
$query="insert into page_stats_marine(names_from_xml,names_in_eol,marine_pages,pages_with_objects,pages_with_vetted_objects,date_created,time_created,active) select 207877,176007,176256,107302,100912,'$date_created','$time_created','n'";
$update = $mysqli->query($query);

print "\n\n --end-- \n\n";
?>