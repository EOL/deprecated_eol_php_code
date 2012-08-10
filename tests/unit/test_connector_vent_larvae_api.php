<?php
namespace php_active_record;

require_library('connectors/HydrothermalVentLarvaeAPI');

class test_connector_vent_larvae_api extends SimpletestUnitBase
{
    function testVentLarvaeAPI()
    {
        // $url = "http://www.whoi.edu/vent-larval-id/MiscSpecies.htm";
        // //$url = "http://pandanus.eol.org/public/test_resources/MiscSpecies.htm";
        // 
        // $taxa = HydrothermalVentLarvaeAPI::get_larvae_taxa($url, array());
        // $this->assertTrue(is_array($taxa), 'Taxa should be an array');
        // 
        // $taxon = $taxa["page_taxa"][0];
        // $this->assertIsA($taxon, 'SchemaTaxon', 'Response should be an array of SchemaTaxon');
        // 
        // $dataObject = $taxon->dataObjects[0];
        // $this->assertIsA($dataObject, 'SchemaDataObject', 'Taxon should have a data object');
    }
}

?>