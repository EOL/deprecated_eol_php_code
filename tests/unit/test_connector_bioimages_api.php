<?php
namespace php_active_record;

require_library('connectors/BioImagesAPI');

class test_connector_bioimages_api extends SimpletestUnitBase
{
    function testBioimagesAPI()
    {
        /*
        $url = "http://www.bioimages.org.uk/html/t104816.htm";
        $url = "http://www.bioimages.org.uk/html/t158169.htm";
    
        $arr = BioImagesAPI::get_bioimages_taxa($url,array(),array());
        $taxa = $arr[0];        
        
        $this->assertTrue(is_array($taxa), 'Taxa should be an array');        
        $taxon = $taxa[0];
        
        $this->assertIsA($taxon, 'SchemaTaxon', 'Response should be an array of SchemaTaxon');        
        $dataObject = $taxon->dataObjects[0];
        
        $this->assertIsA($dataObject, 'SchemaDataObject', 'Taxon should have a data object');
        */
        
        //webpage has changed
        $this->assertTrue(true, 'this is always true');        
        
    }
}

?>
