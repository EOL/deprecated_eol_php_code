<?php
namespace php_active_record;

require_library('connectors/DiscoverLifeAPI');
class test_connector_DiscoverLife_api extends SimpletestUnitBase
{
    function testDiscoverLifeAPI()
    {          
        /*
        $taxon =  Array
                (
                    "Gadus morhua" => "1",
                    "orig_sciname" => "Gadus morhua",
                    "tc_id" => "206692",
                    "sciname" => "Gadus morhua Linnaeus, 1758",
                    "text" => "64",
                    "image" => "75",
                    "total_objects" => "139",
                    "map" => "1",
                    "call_back" => "taxon_concept_id"
                );

        */
        require_library('CheckIfNameHasAnEOLPage');
        $func = new CheckIfNameHasAnEOLPage();
        $name = "Gadus morhua";
        $arr = $func->check_if_name_has_EOL_page($name);
        $if_name_has_page_in_EOL = $arr[0];
        $xml_from_api            = $arr[1];
        if(!$if_name_has_page_in_EOL) //test will fail if API is down
        $taxon = array();
        $taxon = $func->get_taxon_simple_stat($name, $xml_from_api);
        $taxon["map"] = 1;
        if(trim($name) == trim(Functions::canonical_form(trim($taxon['sciname'])))) $taxon["call_back"] = "taxon_concept_id";
        else $taxon["call_back"] = "scientific_name";

        $arr = DiscoverLifeAPI::get_discoverlife_taxa($taxon,array());        
        $taxa = $arr[0];
        $this->assertTrue(is_array($taxa), 'Taxa should be an array');
        $taxon = $taxa[0];
        $this->assertIsA($taxon, 'SchemaTaxon', 'Response should be an array of SchemaTaxon');
        $dataObject = $taxon->dataObjects[0];
        $this->assertIsA($dataObject, 'SchemaDataObject', 'Taxon should have a data object');            
    }
}
?>