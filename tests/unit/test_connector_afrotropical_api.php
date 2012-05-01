<?php
namespace php_active_record;

require_library('connectors/AfrotropicalAPI');

class test_connector_afrotropical_api extends SimpletestUnitBase
{
    function testAfrotropicalAPI()
    {
        // $url = "http://pandanus.eol.org/public/test_resources/EOLexportFruitfly_fixed_4test.xml";
        // 
        // $arr = AfrotropicalAPI::get_afrotropical_taxa($url,array());
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