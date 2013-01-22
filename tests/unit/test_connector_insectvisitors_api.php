<?php
namespace php_active_record;

require_library('connectors/InsectVisitorsAPI');

class test_connector_insectvisitors_api extends SimpletestUnitBase
{
    function testInsectVisitorsAPI()
    {
        $test_run = true;
        $func = new InsectVisitorsAPI($test_run);
        $page_taxa = $func->get_all_taxa('test'); //made-up resource_id 'test'
        $this->assertTrue(is_array($page_taxa), 'Taxa should be an array');
        foreach($page_taxa as $taxon)
        {
          $this->assertIsA($taxon, 'SchemaTaxon', 'Response should be an array of SchemaTaxon');
          if($dataObject = $taxon->dataObjects[0]) $this->assertIsA($dataObject, 'SchemaDataObject', 'Taxon should have a data object');
        }
    }
}
?>