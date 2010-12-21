<?php

require_library('connectors/ObisAPI');

class test_connector_obis_api extends SimpletestUnitBase
{
    function testObisAPI()
    {
        $url          = DOC_ROOT . "/update_resources/connectors/files/OBIS/depthenv20100825_small.csv";
        $url_ancestry = DOC_ROOT . "/update_resources/connectors/files/OBIS/tnames20100825_small.csv";
    
        $arr = ObisAPI::get_obis_taxa($url,array(),$url_ancestry);
        $taxa = $arr[0];
        $this->assertTrue(is_array($taxa), 'Taxa should be an array');
        
        $taxon = $taxa[0];
        $this->assertIsA($taxon, 'SchemaTaxon', 'Response should be an array of SchemaTaxon');
        
        $dataObject = $taxon->dataObjects[0];
        $this->assertIsA($dataObject, 'SchemaDataObject', 'Taxon should have a data object');
    }
}

?>