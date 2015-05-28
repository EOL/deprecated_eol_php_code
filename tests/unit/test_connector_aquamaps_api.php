<?php
namespace php_active_record;

require_library('connectors/AquamapsAPIv2');

class test_connector_aquamaps_api extends SimpletestUnitBase
{
    function testAquamapsAPIv2()
    {
        // aquamaps webservice is offline: 
        // e.g. http://www.aquamaps.org/webservice/getAMap.php?genus=Gadus&species=morhua
        
        // $url = "http://dl.dropbox.com/u/7597512/AquaMaps/aquamaps_species_list2.XML";
        // $arr = AquamapsAPIv2::get_aquamaps_taxa($url,array());
        // $taxa = $arr[0];
        // $this->assertTrue(is_array($taxa), 'Taxa should be an array');
        // $taxon = $taxa[0];
        // $this->assertIsA($taxon, 'SchemaTaxon', 'Response should be an array of SchemaTaxon');
        // $dataObject = $taxon->dataObjects[0];
        // $this->assertIsA($dataObject, 'SchemaDataObject', 'Taxon should have a data object');
    }
}

?>