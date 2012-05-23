<?php
namespace php_active_record;
require_library('connectors/BiopixAPI');
class test_connector_biopix_api extends SimpletestUnitBase
{
    function testBiopixAPI()
    {
        $biopix_connector = new BiopixAPI;
        $line = "Insekter	Abax parallelepipedus	Carabidae	87085	http://www.biopix.com/photos/Abax-parallelepipedus-00037.JPG	http://www.biopix.com/abax-parallelepipedus_photo-87085.aspx	Abax parallelepipedus	MejlgÃ¥rd Skov	1";
        $biopix_connector->create_instances_from_row($line);
        $this->assertTrue(count($biopix_connector->taxa) == 5, 'We should have 5 taxa');
        $last_taxon = array_pop($biopix_connector->taxa);
        $family = array_pop($biopix_connector->taxa);
        $class = array_pop($biopix_connector->taxa);
        $phylum = array_pop($biopix_connector->taxa);
        $kingdom = array_pop($biopix_connector->taxa);
        $this->assertTrue($last_taxon->scientificName == 'Abax parallelepipedus', 'We should get the right scientific name');
        $this->assertTrue($last_taxon->parentNameUsageID == $family->taxonID);
        $this->assertTrue($family->scientificName == 'Carabidae', 'We should get the right family name');
        $this->assertTrue($family->parentNameUsageID == $class->taxonID);
        $this->assertTrue($class->scientificName == 'Insecta', 'We should get the right class name');
        $this->assertTrue($class->parentNameUsageID == $phylum->taxonID);
        $this->assertTrue($phylum->scientificName == 'Arthropoda', 'We should get the right phylum name');
        $this->assertTrue($phylum->parentNameUsageID == $kingdom->taxonID);
        $this->assertTrue($kingdom->scientificName == 'Animalia', 'We should get the right kingdom name');
        $this->assertTrue(!isset($kingdom->parentNameUsageID));
        
        $this->assertTrue(count($biopix_connector->media) == 1, 'We should one image');
        $last_image = array_pop($biopix_connector->media);
        $this->assertTrue($last_image->identifier == 87085, 'We should get the right image identifier');
        $this->assertTrue($last_image->accessURI == 'http://www.biopix.com/photos/Abax-parallelepipedus-00037.JPG', 'We should get the right image accessURI');
        $this->assertTrue($last_image->Rating == 4, 'We should get the right image rating');
    }
}
?>