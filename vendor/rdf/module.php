<?php

require_once('RDFDocument.php');
require_once('RDFDocumentElement.php');
require_once('SpeciesProfile.php');
require_once('SpeciesProfileModels.php');
require_once('SPMInfoItem.php');
require_once('TaxonConcepts.php');
require_once('TaxonNames.php');
require_once('InfoItems.php');

define("LSID_RESOLVER",         "http://lsid.tdwg.org/");
define("RDF_NS",                "http://www.w3.org/1999/02/22-rdf-syntax-ns#");
define("RDFS_NS",               "http://www.w3.org/2000/01/rdf-schema#");
define("XSD_NS",                "http://www.w3.org/2001/XMLSchema#");
define("SPM_NS",                "http://rs.tdwg.org/ontology/voc/SpeciesProfileModel#");
define("SPMI_NS",               "http://rs.tdwg.org/ontology/voc/SPMInfoItems#");
define("TC_NS",                 "http://rs.tdwg.org/ontology/voc/TaxonConcept#");
define("TN_NS",                 "http://rs.tdwg.org/ontology/voc/TaxonName#");


?>