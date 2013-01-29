<?php
namespace php_active_record;
require_library('connectors/ClementsAPI');
class test_connector_clements_api extends SimpletestUnitBase
{
    function testClementsAPI()
    {
        $data_dump_url = "http://dl.dropbox.com/u/7597512/Clements/Clements%20Checklist 6.7 small.xls";
        $archive_folder = "test_clements";
        $connector = new ClementsAPI($archive_folder);
        $connector->get_all_taxa($data_dump_url);
        $this->assertTrue(count($connector->taxa) == 17, 'We should have 17 taxon');
        $taxon = array_shift($connector->taxa);
        $this->assertTrue($taxon->taxonID == "a86fb782fe3d6295ff85f39cda1e2407", 'We should get the right taxon id');
        $this->assertTrue($taxon->scientificName == "Struthio camelus", 'We should get the right scientific name');
        $this->assertTrue($taxon->order == "Struthioniformes", 'We should get the right order name');
        $this->assertTrue($taxon->family == "Struthionidae", 'We should get the right family name');
        $taxon = array_pop($connector->taxa);
        $this->assertTrue($taxon->taxonID == "da59c940ad9da210376522e50d8f6a5f", 'We should get the right taxon id');
        $this->assertTrue($taxon->scientificName == "Casuarius casuarius", 'We should get the right scientific name');
        $this->assertTrue($taxon->order == "Struthioniformes", 'We should get the right order name');
        $this->assertTrue($taxon->family == "Casuariidae", 'We should get the right family name');
    }
}
?>