<?php

require_once("DarwinCoreHarvester.php");
require_once("DarwinCoreRecordSet.php");
require_once("DarwinCoreTaxon.php");
require_once("DarwinCoreArchiveHarvester.php");
require_once("TaxonImporter.php");




$GLOBALS['DarwinCoreTaxonNamespaces'] = array();
$GLOBALS['DarwinCoreTaxonNamespaceAbbreviations'] = array();
$GLOBALS['DarwinCoreTaxonNamespaceAttributes'] = array();
$GLOBALS['DarwinCoreTaxonDefaultNamespace'] = "http://rs.tdwg.org/dwc/terms/";

load_darwincore_namespace("http://rs.tdwg.org/dwc/terms/", "dwc", dirname(__FILE__)."/tdwg_dwcterms.xsd");
load_darwincore_namespace("http://purl.org/dc/elements/1.1/", "dc", dirname(__FILE__)."/dc_elements.xsd");
load_darwincore_namespace("http://purl.org/dc/terms/", "dcterms", dirname(__FILE__)."/dcterms.xsd");

function load_darwincore_namespace($namespace_uri, $namespace_abbreviation, $schema_uri)
{
    // cannot reuse namespace
    if(isset($GLOBALS['DarwinCoreTaxonNamespaces'][$namespace_uri])) throw new Exception("Cannot redeclare namespace $namespace_uri");
    if(isset($GLOBALS['DarwinCoreTaxonNamespaceAbbreviations'][$namespace_abbreviation])) throw new Exception("Cannot redeclare namespace $namespace_abbreviation");
    if(isset($GLOBALS['DarwinCoreTaxonNamespaceAttributes'][$namespace_uri])) throw new Exception("Cannot assign different namespace to $namespace_uri");
    
    // set as viable namespace
    $GLOBALS['DarwinCoreTaxonNamespaces'][$namespace_uri] = $namespace_abbreviation;
    $GLOBALS['DarwinCoreTaxonNamespaceAbbreviations'][$namespace_abbreviation] = $namespace_uri;
    fetch_schema_elements($namespace_uri, $schema_uri);
}

function fetch_schema_elements($namespace_uri, $schema_uri)
{
    $GLOBALS['DarwinCoreTaxonNamespaceAttributes'][$namespace_uri] = array();
    $xml = simplexml_load_string(file_get_contents($schema_uri));
    if(!$xml) throw new Exception("Cannot access schema at $schema_uri");
    $xml_schema = $xml->children("http://www.w3.org/2001/XMLSchema");
    foreach($xml_schema->element as $e)
    {
        $attr = $e->attributes();
        $attr_name = (string) $attr['name'];
        $GLOBALS['DarwinCoreTaxonNamespaceAttributes'][$namespace_uri][strtolower($attr_name)] = $attr_name;
    }
}

?>