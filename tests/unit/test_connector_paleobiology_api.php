<?php
namespace php_active_record;
require_library('connectors/PaleobiologyAPI');
class test_connector_paleobiology_api extends SimpletestUnitBase
{
    
    function testPaleobiologyAPI()
    {
        // $paleobiology_connector = new PaleobiologyAPI;
        // $paleobiology_connector->data_dump_url = "http://testpaleodb.geology.wisc.edu/taxa/all.xml?type=synonyms&limit=5&showref=1&showcode=1&suffix=.xml";
        // $paleobiology_connector->get_all_taxa();
        // $this->assertTrue(count($paleobiology_connector->taxa) == 5, 'We should have 5 taxa');
        // 
        // $paleobiology_connector = new PaleobiologyAPI;
        // $taxon_object = $this->prepare_test_taxon_object();
        // $paleobiology_connector->create_instances_from_taxon_object($taxon_object);
        // $this->assertTrue(count($paleobiology_connector->taxa) == 1, 'We should have 1 taxon');
        // $last_taxon = array_pop($paleobiology_connector->taxa);
        // $this->assertTrue($last_taxon->taxonID == 203693, 'We should get the right taxon id');
        // $this->assertTrue($last_taxon->taxonRank == "subspecies", 'We should get the right taxon rank');
        // $this->assertTrue($last_taxon->scientificName == "Aubignyna mariei praemariei", 'We should get the right scientific name');
        // $this->assertTrue($last_taxon->genus == "Aubignyna", 'We should get the right genus');
        // $this->assertTrue($last_taxon->specificEpithet == "mariei", 'We should get the right specific epithet');
        // $this->assertTrue($last_taxon->infraspecificEpithet == "praemariei", 'We should get the right infra specific epithet');
        // $this->assertTrue($last_taxon->parentNameUsageID == 203692, 'We should get the right parent name usage id');
        // $this->assertTrue($last_taxon->scientificNameAuthorship == "Margerel 1989", 'We should get the right scientific name authorship');
        // $this->assertTrue($last_taxon->taxonomicStatus == "valid", 'We should get the right image taxonomic status');
        // $this->assertTrue($last_taxon->namePublishedIn == 'J. P. Margerel. 2009. Les foraminifères benthiques des Faluns du Miocène moyen du Blésois (Loir-et-Cher) et de Mirebeau (Vienne) dans le Centre-Ouest de la France. Geodiversitas 31(3):577-621', 'We should get the right reference');
        // $this->assertTrue($last_taxon->taxonRemarks == "extant: yes", 'We should get the right taxon remarks');
    }
    
    function prepare_test_taxon_object()
    {
        return $taxon_object = @simplexml_load_string("<dwc:Taxon>
                <dwc:taxonID>203693</dwc:taxonID>
                <dwc:taxonRank>subspecies</dwc:taxonRank>
                <dwc:scientificName>Aubignyna mariei praemariei</dwc:scientificName>
                <dwc:genus>Aubignyna</dwc:genus>
                <dwc:specificEpithet>mariei</dwc:specificEpithet>
                <dwc:infraSpecificEpithet>praemariei</dwc:infraSpecificEpithet>
                <dwc:parentNameUsageID>203692</dwc:parentNameUsageID>
                <dwc:scientificNameAuthorship>Margerel 1989</dwc:scientificNameAuthorship>
                <dwc:taxonomicStatus>valid</dwc:taxonomicStatus>
                <dwc:namePublishedIn>J. P. Margerel. 2009. Les foraminifères benthiques des Faluns du Miocène moyen du Blésois (Loir-et-Cher) et de Mirebeau (Vienne) dans le Centre-Ouest de la France. Geodiversitas 31(3):577-621</dwc:namePublishedIn>
                <dwc:taxonRemarks>extant: yes</dwc:taxonRemarks>
            </dwc:Taxon>"
        );
    }
}
?>