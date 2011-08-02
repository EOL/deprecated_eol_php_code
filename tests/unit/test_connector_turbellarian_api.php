<?php
namespace php_active_record;

require_library('connectors/TurbellarianAPI');

class test_connector_turbellarian_api extends SimpletestUnitBase
{
    function testTurbellarianAPI()
    {
        $arr_input = Array
        (
            'Proporidae',
            'Graff, 1882',
            '<a href="/turb2.php?action=15|code=401">diagnosis</a>',
            '<a href="/turb2.php?action=11|code=401|syn=0">literature</a>',
            'http://turbellaria.umaine.edu/turb2.php?action=1&code=401'
        );
    
        /*
        Turbellarian site is down: http://turbellaria.umaine.edu/
      
        $arr = TurbellarianAPI::get_turbellarian_taxa($arr_input,array());
        $taxa = $arr[0];
        $this->assertTrue(is_array($taxa), 'Taxa should be an array');
        
        $taxon = $taxa[0];
        $this->assertIsA($taxon, 'SchemaTaxon', 'Response should be an array of SchemaTaxon');
        
        $dataObject = $taxon->dataObjects[0];
        $this->assertIsA($dataObject, 'SchemaDataObject', 'Taxon should have a data object');
        */
    }
}
?>