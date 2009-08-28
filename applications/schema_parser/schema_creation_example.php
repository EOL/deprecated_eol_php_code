<?php

// This include is necessary as it will include all classes needed within this script
// this will also create a connection to the database for the selected enviroment
// the default environment is development. Check out the README file for more startup info
include_once("../../config/start.php");


// within this script I play around with a few ways to set the parameters for the class
// constructors. They all want arrays of parameters, so I just offer different ways to
// create these arrays. The implementor should use their preferred way of doing this





// create a container for all the taxa
$taxa = array();


// create a container for this taxon's parameters
$taxon_params = array();
$taxon_params["scientificName"] = "Aus bus Linnaeus";
$taxon_params["genus"] = "Aus";
$taxon_params["family"] = "Ausidae";
$taxon_params["order"] = "Ausoida";
$taxon_params["source"] = "http://www.provider.org/id?12121";
$taxon_params["identifier"] = "taxon_12121";

// create a container for the taxon's synonyms
$taxon_params["synonyms"] = array();

// create a container for the taxon's data objects
$taxon_param["dataObjects"] = array();





// create the data object object with the above parameters and add it the taxon's container of data objects
$taxon_params["synonyms"][] = new SchemaSynonym(array("synonym" => "Aus buus", "relationship" => "ambiguous synonym"));



$agent_parameters = array(  "fullName"  => "Jane Smith",
                            "homepage"  => "http://www.provider.org/employees/jsmith.html",
                            "logoURL"   => "http://www.provider.org/employees/logos/jsmith.png",
                            "role"      => "photographer");


// create a container for this data object's parameters
$object_params = array();
$object_params["identifier"] = "image_98989";
$object_params["dataType"] = "http://purl.org/dc/dcmitype/StillImage";
$object_params["mimeType"] = "image/jpeg";
$object_params["agents"] = array(new SchemaAgent($agent_parameters));

// create the data object object with the above parameters and add it the taxon's container of data objects
$taxon_params["dataObjects"][] = new SchemaDataObject($object_params);





// create a container for this data object's parameters
$object_params = array();
$object_params["identifier"] = "text_1234";
$object_params["dataType"] = "http://purl.org/dc/dcmitype/Text";
$object_params["mimeType"] = "text/html";
$object_params["agents"] = array(new SchemaAgent($agent_parameters));
$object_params["description"] = "This is where the body of the text goes";
$object_params["subjects"] = array(new SchemaSubject(array("label" => "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Description")));

// create the data object object with the above parameters and add it the taxon's container of data objects
$taxon_params["dataObjects"][] = new SchemaDataObject($object_params);




// create the taxon object with all above parameters and add it the list of all taxa
$taxa[] = new SchemaTaxon($taxon_params);


// use the static function print_taxon_xml in the class SchemaConnection to ouput well-formed eol schema xml
// this will not necessarily be valid as there is no check of the enumerated values
SchemaConnection::print_taxon_xml($taxa);


// For saving the file
//echo SchemaConnection::get_taxon_xml($taxa);

?>