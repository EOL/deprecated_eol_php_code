#!/usr/local/bin/php
<?php
//use to set values in the database
//exit;

//define("ENVIRONMENT", "development");
define("MYSQL_DEBUG", true);
define("DEBUG", true);
include_once(dirname(__FILE__) . "/../../config/start.php");
$mysqli =& $GLOBALS['mysqli_connection'];

$wrap = "\n\n";

$query="Select distinct
hierarchy_entries.taxon_concept_id,
data_objects.description,
data_objects.id,
data_objects.curated,
data_objects.published,
data_objects.visibility_id,
data_objects.vetted_id,
data_objects_harvest_events.harvest_event_id
From
data_objects
Inner Join data_objects_taxa ON data_objects.id = data_objects_taxa.data_object_id
Inner Join taxa ON data_objects_taxa.taxon_id = taxa.id
Inner Join hierarchy_entries ON taxa.name_id = hierarchy_entries.name_id
Inner Join data_objects_harvest_events ON data_objects.id = data_objects_harvest_events.data_object_id
where 
hierarchy_entries.taxon_concept_id = 1078018
and data_objects_harvest_events.harvest_event_id = 1132
";
$result = $mysqli->query($query);    
while($row=$result->fetch_assoc())
{
    print "$row[$vetted_id] \n";
}
exit;

/*
$id     = 26; //for WORMS
$fld    = "accesspoint_url";
$value  = "";

$id     = 79;  //public health image library
$fld    = "service_type_id";
$value  = 2;

$id     = 90;  //for Conservation Biology of FW turtles and tortoises
$fld    = "service_type_id";
$value  = 2;

$id     = 89; //for DiArk
$fld    = "accesspoint_url";
$value  = "http://www.goenmr.de/~bham/diark2009_12_15_2nd.xml";
$value  = "http://www.goenmr.de/~bham/diark2009_12_18.xml";

$id     = 68;  //for Dutch Species Catalogue
$fld    = "service_type_id";
$value  = 2;

$id     = 83;  //for MorphBank
$fld    = "service_type_id";
$value  = 2;

$id     = 11;  //for Biolib.cz
$fld    = "service_type_id";
$value  = 2;

*/

$id     = 81;  //for BOLDS
$fld    = "service_type_id";
$value  = 2;

$query="Select resources.$fld, resources.title, resources.id From resources Where resources.id = $id";
$result = $mysqli->query($query);    
while($row=$result->fetch_assoc()){print "$row[$fld] -- $row[title] -- $wrap";}

$query="update resources set $fld = '$value' Where id = $id";
$update = $mysqli->query($query);    

$query="Select resources.$fld, resources.title, resources.id From resources Where resources.id = $id";
$result = $mysqli->query($query);    
while($row=$result->fetch_assoc()){print "$row[$fld] -- $row[title] -- $wrap";}

print "\n\n --end-- \n\n";