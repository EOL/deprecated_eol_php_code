<?php
namespace php_active_record;
require_library('connectors/AvibaseAPI');
class test_connector_avibase_api extends SimpletestUnitBase
{
    function testAvibaseAPI()
    {
        // $avibase = new AvibaseAPI(1, 'ioc', true);
        // $last_taxon = $avibase->get_all_taxa();
        // $this->assertTrue(get_class($last_taxon) == "SchemaTaxon");
        // $this->assertTrue($last_taxon->scientificName == "Crypturellus kerriae");
        // $this->assertTrue($last_taxon->order == "Tinamiformes");
        // $this->assertTrue($last_taxon->family == "Tinamidae");
        // $this->assertTrue($last_taxon->commonNames[0]->name == "Tinama kolumbijská");
        // $this->assertTrue($last_taxon->commonNames[0]->language == "cs");
    }
}
?>