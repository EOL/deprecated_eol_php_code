<?php
require_library('connectors/MCZHarvardAPI');
class test_connector_mczharvard_api extends SimpletestUnitBase
{
    function testMCZHarvardAPI()
    {
        $taxon = Array
                (
                    "GUID" => "MCZ:Herp:A-28293",
                    "SCIENTIFIC_NAME" => "Phrynobatrachus maculatus",
                    "FULL_TAXON_NAME" => "Chordata Amphibia Lissamphibia Anura Ranoidea Ranidae Phrynobatrachus maculatus",
                    "PHYLCLASS" => "Amphibia",
                    "KINGDOM" => "",
                    "PHYLUM" => "Chordata",
                    "PHYLORDER" => "Anura",
                    "FAMILY" => "Ranidae",
                    "GENUS" => "Phrynobatrachus",
                    "SPECIES" => "maculatus",
                    "SUBSPECIES" => "",
                    "INFRASPECIFIC_RANK" => "",
                    "AUTHOR_TEXT" => "",
                    "taxon_id" => "MCZ:Herp:A-28293"
                );
        $images[0] = Array
                    (
                        "GUID" => "MCZ:Herp:A-28293",
                        "MEDIA_ID" => "1515",
                        "MEDIA_URI" => "http://mczbase.mcz.harvard.edu/herpimages/large/A28293_P_maculatus_P_d.jpg",
                        "MIME_TYPE" => "image/jpeg",
                        "SPEC_LOCALITY" => "Kasane, Bechuanaland",
                        "HIGHER_GEOG" => "Africa, Botswana, Chobe",
                        "TYPESTATUS" => "Paratype",
                        "PARTS" => "whole animal (ethanol)",
                        "COLLECTING_METHOD" => "",
                        "COLLECTORS" => "HLang",
                        "IDENTIFIEDBY" => "FitzSimons",
                        "created" => "04 dec 2008",
                        "LAST_EDIT_DATE" => "25-JUL-01"
                    );
    
        $arr = MCZHarvardAPI::get_MCZHarvard_taxa($taxon,$images,array());
        $taxa = $arr[0];
        $this->assertTrue(is_array($taxa), 'Taxa should be an array');
        
        $taxon = $taxa[0];
        $this->assertIsA($taxon, 'SchemaTaxon', 'Response should be an array of SchemaTaxon');
        
        $dataObject = $taxon->dataObjects[0];
        $this->assertIsA($dataObject, 'SchemaDataObject', 'Taxon should have a data object');
    }
}
?>