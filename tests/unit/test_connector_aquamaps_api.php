<?php

require_library('connectors/AquamapsAPI');

class test_connector_aquamaps_api extends SimpletestUnitBase
{
    function testAquamapsAPI()
    {
        $path = DOC_ROOT . "/update_resources/connectors/files/AquaMaps/";
        $url = $path . "aquamaps_species_list2.XML";
    
        $taxa = AquaMapsAPI::get_aquamaps_taxa($url);
        $this->assertTrue(is_array($taxa), 'Taxa should be an array');
        
        $taxon = $taxa[0];
        $this->assertIsA($taxon, 'SchemaTaxon', 'Response should be an array of SchemaTaxon');
        
        $dataObject = $taxon->dataObjects[0];
        $this->assertIsA($dataObject, 'SchemaDataObject', 'Taxon should have a data object');
    }
}

?>