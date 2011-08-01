<?php
namespace php_active_record;

require_library('connectors/INBioAPI');
class test_connector_INBio_api extends SimpletestUnitBase
{
    function testINBioAPI()
    {
        $media = array();
        $media[] = Array(
                        "http://rs.tdwg.org/dwc/terms/taxonID" => "4223",
                        "http://www.pliniancore.org/plic/pcfcore/captionImage1" => "caption of 1st image",
                        "http://www.pliniancore.org/plic/pcfcore/urlImage1" => "http://attila.inbio.ac.cr:7777/pls/portal30//IMAGEDB.GET_BFILE_IMAGE?p_imageId=16778&p_imageResolutionId=2",
                        "http://www.pliniancore.org/plic/pcfcore/scientificDescription" => "Es el segu",
                        "http://www.pliniancore.org/plic/pcfcore/language" => "",
                        "http://www.pliniancore.org/plic/pcfcore/commonNames" => ""
                        );
        $taxa = array();
        $taxa[] = Array(
                        "http://rs.tdwg.org/dwc/terms/taxonID" => "4223",
                        "http://purl.org/dc/terms/language" => "Espanol",
                        "http://rs.tdwg.org/dwc/terms/phylum" => "Pteridophyta",
                        "http://rs.tdwg.org/dwc/terms/kingdom" => "Plantae",
                        "http://rs.tdwg.org/dwc/terms/class" => "Pteropsida",
                        "http://rs.tdwg.org/dwc/terms/scientificName" => "Campyloneurum densifolium",
                        "http://purl.org/dc/terms/modified" => "2008-08-02 17:09:14.0",
                        "http://rs.tdwg.org/dwc/terms/order" => "Filicales",
                        "http://rs.tdwg.org/dwc/terms/genus" => "Campyloneurum",
                        "http://rs.tdwg.org/dwc/terms/family" => "Polypodiaceae"
                       );
       $GLOBALS['fields'] = Array
              (
                  "0" => Array("term" => "http://rs.tdwg.org/dwc/terms/taxonID", "default" => ""),
                  "1" => Array("term" => "http://www.pliniancore.org/plic/pcfcore/scientificDescription", "type" => "", "default" => "")
              );
        $taxon_media = array();
        foreach($media as $m) @$taxon_media[$m['http://rs.tdwg.org/dwc/terms/taxonID']] = $m;
        foreach($taxa as $taxon)
        {
            $taxon_id = @$taxon['http://rs.tdwg.org/dwc/terms/taxonID'];
            $taxon["id"] = $taxon_id;
            $taxon["media"] = $taxon_media[$taxon_id];
            $arr = INBioAPI::get_inbio_taxa($taxon, array());
            $page_taxa = $arr[0];
            $this->assertTrue(is_array($page_taxa), 'Taxa should be an array');
            $taxon = $page_taxa[0];
            $this->assertIsA($taxon, 'SchemaTaxon', 'Response should be an array of SchemaTaxon');
            $dataObject = $taxon->dataObjects[0];
            $this->assertIsA($dataObject, 'SchemaDataObject', 'Taxon should have a data object');
        }
    }
}
?>