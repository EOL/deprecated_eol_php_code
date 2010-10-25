<?php

require_library('connectors/AquamapsAPI');

class test_connector_aquamaps_api extends SimpletestUnitBase
{
    function testAquamapsAPI()
    {
        $url = "http://pandanus.eol.org/public/test_resources/aquamaps_species_list2.XML";    
        $arr = AquaMapsAPI::get_aquamaps_taxa($url,array());
        $taxa = $arr[0];  
              
        $this->assertTrue(is_array($taxa), 'Taxa should be an array');        
        
        $taxon = $taxa[0];
        $this->assertIsA($taxon, 'SchemaTaxon', 'Response should be an array of SchemaTaxon');        
        
        $dataObject = $taxon->dataObjects[0];
        $this->assertIsA($dataObject, 'SchemaDataObject', 'Taxon should have a data object');
    }
}

?>