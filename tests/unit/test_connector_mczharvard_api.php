<?php
namespace php_active_record;
require_library('connectors/MCZHarvardAPI');
class test_connector_mczharvard_api extends SimpletestUnitBase
{
    function testMCZHarvardAPI()
    {
        $data_dump_url = "http://dl.dropbox.com/u/7597512/MCZHarvard/MCZimages_small.tsv";
        $archive_folder = "test_mczharvard";
        $connector = new MCZHarvardAPI($archive_folder);
        $connector->get_all_taxa($data_dump_url);
        $this->assertTrue(count($connector->taxa) == 1, 'We should have 1 taxon');
        $taxon = array_shift($connector->taxa);
        $this->assertTrue($taxon->taxonID == "MCZ_Lepidodactylus_herrei_cebuensis", 'We should get the right taxon id');
        $this->assertTrue($taxon->scientificName == "Lepidodactylus herrei cebuensis", 'We should get the right scientific name');
        $this->assertTrue($taxon->furtherInformationURL == "http://mczbase.mcz.harvard.edu/SpecimenDetail.cfm?collection_object_id=301109", 'We should get the right furtherInformationURL');
    }
}
?>