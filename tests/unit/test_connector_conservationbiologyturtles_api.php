<?php
namespace php_active_record;
require_library('connectors/ConservationBiologyTurtlesAPI');
class test_connector_conservationbiologyturtles_api extends SimpletestUnitBase
{
    function testConservationBiologyTurtlesAPI()
    {
        $records = array();
        $records[] = Array( "sciname"    => "Actinemys marmorata (Baird and Girard 1852)",
                            "url"        => "http://www.iucn-tftsg.org/cbftt/toc-ind/toc/actinemys-marmorata",
                            "vernacular" => "Western Pond Turtle, Pacific Pond Turtle.",
                            "taxonID"    => "iucn_ssc_Actinemys_marmorata_(Baird_and_Girard_1852)");
        $archive_folder = "test_turtles";
        $connector = new ConservationBiologyTurtlesAPI($archive_folder);
        $connector->get_all_taxa($records);
        $this->assertTrue(count($connector->taxa) == 1, 'We should have 1 taxon');
        $taxon = array_shift($connector->taxa);
        $this->assertTrue($taxon->taxonID == "iucn_ssc_Actinemys_marmorata_(Baird_and_Girard_1852)", 'We should get the right taxon id');
        $this->assertTrue($taxon->scientificName == "Actinemys marmorata (Baird and Girard 1852)", 'We should get the right scientific name');
    }
}
?>