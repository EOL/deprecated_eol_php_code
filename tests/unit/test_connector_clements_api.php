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
        $this->assertTrue(count($connector->taxa) == 27, 'We should have 27 taxon');
        $taxon = array_shift($connector->taxa);
        $this->assertTrue($taxon->taxonID == "animalia", 'We should get the right taxon id');
        $this->assertTrue($taxon->scientificName == "Animalia", 'We should get the right scientific name');
        $this->assertTrue($taxon->taxonRank == "kingdom", 'We should get the right rank');
        $taxon = array_pop($connector->taxa);
        $this->assertTrue($taxon->taxonID == "casuarius_casuarius", 'We should get the right taxon id');
        $this->assertTrue($taxon->scientificName == "Casuarius casuarius", 'We should get the right scientific name');
        $this->assertTrue($taxon->parentNameUsageID == "casuarius", 'We should get the right parentNameUsageID');
        $this->assertTrue($taxon->taxonRank == "species", 'We should get the right rank');
    }
}
?>