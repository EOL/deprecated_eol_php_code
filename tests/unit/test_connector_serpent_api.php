<?php
namespace php_active_record;

require_library('connectors/SerpentAPI');

class test_connector_serpent_api extends SimpletestUnitBase
{
    function testSerpentAPI()
    {
        // $url = "http://archive.serpentproject.com/view/species/Actinaria_sp..html";
        //     
        // $arr = SerpentAPI::get_serpent_taxa($url,array());
        // $taxa = $arr[0];
        // $this->assertTrue(is_array($taxa), 'Taxa should be an array');
        // 
        // $taxon = $taxa[0];
        // $this->assertIsA($taxon, 'SchemaTaxon', 'Response should be an array of SchemaTaxon');
        // 
        // $dataObject = $taxon->dataObjects[0];
        // $this->assertIsA($dataObject, 'SchemaDataObject', 'Taxon should have a data object');
    }
}

?>