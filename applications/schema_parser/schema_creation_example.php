<?php

// This include is necessary as it will include all classes needed within this script
// this will also create a connection to the database for the selected enviroment
// the default environment is development. Check out the README file for more startup info
include_once(dirname(__FILE__) ."/../../config/environment.php");


// within this script I play around with a few ways to set the parameters for the class
// constructors. They all want arrays of parameters, so I just offer different ways to
// create these arrays. The implementor should use their preferred way of doing this





// create a container for all the taxa
$taxa = array();


// create a container for this taxon's parameters
$taxon_params = array();
$taxon_params["identifier"] = "taxon_ursus_maritimus";
$taxon_params["kingdom"] = "Animalia";
$taxon_params["phylum"] = "Chordata";
$taxon_params["class"] = "Mammalia";
$taxon_params["order"] = "Carnivora";
$taxon_params["family"] = "Ursidae";
$taxon_params["genus"] = "Ursus";
$taxon_params["scientificName"] = "Ursus maritimus Phipps, 1774";
$taxon_params["source"] = "http://www.provider.org/id?12121";


// create a container for the taxon's synonyms
$taxon_params["synonyms"] = array();

// create the data object object with the above parameters and add it the taxon's container of data objects
$taxon_params["synonyms"][] = new SchemaSynonym(array("synonym" => "Aus buus", "relationship" => "ambiguous synonym"));





// create a container for the taxon's data objects
$taxon_params["dataObjects"] = array();

// create the agent object
$agent = new SchemaAgent(array( "fullName"  => "Drew Avery",
                                "homepage"  => "http://www.flickr.com/photos/33590535@N06/",
                                "logoURL"   => "http://farm4.static.flickr.com/3256/buddyicons/33590535@N06.jpg?1229887124#33590535@N06",
                                "role"      => "photographer"));

// create a container for this data object's parameters
$object_params = array();
$object_params["identifier"] = "3541747708";
$object_params["title"] = "Polar Bear {Ursus maritimus}";
$object_params["dataType"] = "http://purl.org/dc/dcmitype/StillImage";
$object_params["mimeType"] = "image/jpeg";
$object_params["license"] = "http://creativecommons.org/licenses/by/3.0/";
$object_params["mediaURL"] = "http://farm3.static.flickr.com/2479/3541747708_7f3973b7c7.jpg";
$object_params["agents"] = array($agent);

// create the data object object with the above parameters and add it the taxon's container of data objects
$taxon_params["dataObjects"][] = new SchemaDataObject($object_params);




// a second agent for the text description
$text_agent = new SchemaAgent(array( "fullName"  => "Leary, P",
                                                "role"      => "author"));

// create a container for this data object's parameters
$object_params = array();
$object_params["identifier"] = "text_1234";
$object_params["dataType"] = "http://purl.org/dc/dcmitype/Text";
$object_params["mimeType"] = "text/html";
$object_params["agents"] = array($agent, $text_agent);
$object_params["license"] = "http://creativecommons.org/licenses/by-nc-sa/3.0/";
$object_params["title"] = "A Description of a Polar Bear (Ursus maritimus)";
$object_params["description"] = "This is where the body of the text goes";
$object_params["subjects"] = array(new SchemaSubject(array("label" => "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Description")));

// create the data object object with the above parameters and add it the taxon's container of data objects
$taxon_params["dataObjects"][] = new SchemaDataObject($object_params);




// create the taxon object with all above parameters and add it the list of all taxa
$taxa[] = new SchemaTaxon($taxon_params);


// use the static function print_taxon_xml in the class SchemaDocument to ouput well-formed eol schema xml
// this function will also include an XML header
SchemaDocument::print_taxon_xml($taxa);


// For saving the file
//echo SchemaDocument::get_taxon_xml($taxa);

?>