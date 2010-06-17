<?php

require_library('connectors/PhotosynthAPI');

class test_connector_photosynth_api extends SimpletestUnitBase
{
    function testPhotosynthAPI()
    {
        $taxa = PhotosynthAPI::get_photosynth_taxa(1, 0);
        $this->assertTrue(is_array($taxa), 'Taxa should be an array');
        
        $taxon = $taxa[0];
        $this->assertIsA($taxon, 'SchemaTaxon', 'Response should be an array of SchemaTaxon');
        
        $dataObject = $taxon->dataObjects[0];
        $this->assertIsA($dataObject, 'SchemaDataObject', 'Taxon should have a data object');
    }
}

?>