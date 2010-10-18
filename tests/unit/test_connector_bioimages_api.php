<?php

require_library('connectors/BioimagesAPI');

class test_connector_bioimages_api extends SimpletestUnitBase
{
    function testBioimagesAPI()
    {
        $url = "http://www.bioimages.org.uk/html/t104816.htm";
    
        $arr = BioimagesAPI::get_bioimages_taxa($url,array(),array());
        $taxa = $arr[0];        
        
        $this->assertTrue(is_array($taxa), 'Taxa should be an array');        
        $taxon = $taxa[0];
        
        $this->assertIsA($taxon, 'SchemaTaxon', 'Response should be an array of SchemaTaxon');        
        $dataObject = $taxon->dataObjects[0];
        
        $this->assertIsA($dataObject, 'SchemaDataObject', 'Taxon should have a data object');
    }
}

?>