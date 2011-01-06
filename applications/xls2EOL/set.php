<?php
//use to set values in the database
//exit;

include_once(dirname(__FILE__) . "/../../config/environment.php");
$mysqli =& $GLOBALS['mysqli_connection'];

$wrap = "\n\n";

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

$id     = 81;  //for BOLDS
$fld    = "service_type_id";
$value  = 2;

$id     = 63;  //for INOTAXA
$fld    = "service_type_id";
$value  = 2;

$id     = 107;  //for Encyclopedia of Marine Life - Britain and Ireland
$fld    = "service_type_id";
$value  = 2;

$id     = 98;  //Hexacorallians
$fld    = "service_type_id";
$value  = 2;

$id     = 100;  //Conabio
$fld    = "service_type_id";
$value  = 2;

$id     = 108;  //USDA Plants text descriptions
$fld    = "service_type_id";
$value  = 2;

$id     = 93;  //Antarctic Invertebrates
$fld    = "service_type_id";
$value  = 2;

$id     = 111;  //Field Museum Lichen Resource
$fld    = "service_type_id";
$value  = 2;

$id     = 116;  //The Dutch Ascidians Homepage 
$fld    = "service_type_id";
$value  = 2;

$id     = 119;  //Photosynth
$fld    = "service_type_id";
$value  = 2;

$id     = 121;  //Photographic Identification Guide to Larvae at Hydrothermal Vents E.Pacific Resource
$fld    = "service_type_id";
$value  = 2;

$id     = 123;  //AquaMaps
$fld    = "service_type_id";
$value  = 2;

$id     = 106; //for Biodiversity of Tamborine Mountain 
$fld    = "accesspoint_url";
$value  = "http://pandanus.eol.org/public/EOL_resource/kuttner_corrected.xml";

$id     = 123;  //AquaMaps
$fld    = "auto_publish";
$value  = 1;

$id     = 138;  //Afrotropical
$fld    = "service_type_id";
$value  = 2;

$id     = 143;  //Insect Visitors of Illinois Wildflowers 
$fld    = "service_type_id";
$value  = 2;

$id     = 145;  //Natural History Services
$fld    = "service_type_id";
$value  = 2;

$id     = 119;  //Photosynth
$fld    = "auto_publish";
$value  = 1;

$id     = 145;  //Natural History Services
$fld    = "auto_publish";
$value  = 1;

$id     = 168;  //BioImages
$fld    = "service_type_id";
$value  = 2;

$id     = 170;  //SERPENT Media Project
$fld    = "service_type_id";
$value  = 2;

$id     = 171;  //OBIS depth range
$fld    = "service_type_id";
$value  = 2;

$id     = 173;  //CMARZ
$fld    = "service_type_id";
$value  = 2;

$id     = 185;  //Turbellarian database
$fld    = "service_type_id";
$value  = 2;

$id     = 190;  //FishWise Pro
$fld    = "service_type_id";
$value  = 2;

$id     = 201;  //MCZ Harvard
$fld    = "service_type_id";
$value  = 2;

$id     = 33;  //AskNature
$fld    = "service_type_id";
$value  = 2;

*/

$id     = 207; //Sloan-CoML NameLink Project 
$fld    = "resource_status_id";
$value  = 11; //Publish Pending

$query="Select resources.$fld, resources.title, resources.id From resources Where resources.id = $id";
$result = $mysqli->query($query);    
while($row=$result->fetch_assoc()){print "$row[$fld] -- $row[title] -- $wrap";}

$query="update resources set $fld = '$value' Where id = $id";
$update = $mysqli->query($query);    

$query="Select resources.$fld, resources.title, resources.id From resources Where resources.id = $id";
$result = $mysqli->query($query);    
while($row=$result->fetch_assoc()){print "$row[$fld] -- $row[title] -- $wrap";}

print "\n\n --end-- \n\n";

//another one

$id     = 207; //Sloan-CoML NameLink Project 
$fld    = "auto_publish";
$value  = 1; //Publish Pending

$query="Select resources.$fld, resources.title, resources.id From resources Where resources.id = $id";
$result = $mysqli->query($query);    
while($row=$result->fetch_assoc()){print "$row[$fld] -- $row[title] -- $wrap";}

$query="update resources set $fld = '$value' Where id = $id";
$update = $mysqli->query($query);    

$query="Select resources.$fld, resources.title, resources.id From resources Where resources.id = $id";
$result = $mysqli->query($query);    
while($row=$result->fetch_assoc()){print "$row[$fld] -- $row[title] -- $wrap";}

?>