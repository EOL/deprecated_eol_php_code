<?php
namespace php_active_record;

require_library('connectors/DiscoverLife_KeysAPI');
class test_connector_discoverlifekeys_api extends SimpletestUnitBase
{
    function testDiscoverLife_KeysAPI()
    {
        $name = "Agapostemon coloradinus";
        $fields[] = '<a href="http://www.discoverlife.org/mp/20q?guide=Agapostemon">Identification key for Agapostemon of North America</a>';
        $fields[] = '<a href="http://www.discoverlife.org/mp/20q?guide=Agapostemon">Identification key for Agapostemon of South America</a>';

        require_library('CheckIfNameHasAnEOLPage');
        $func = new CheckIfNameHasAnEOLPage();
        $arr = $func->check_if_name_has_EOL_page($name);
        $if_name_has_page_in_EOL = $arr[0];
        $xml_from_api            = $arr[1];
        if(!$if_name_has_page_in_EOL) //test will fail if API is down
        $taxon = array();
        $taxon = $func->get_taxon_simple_stat($name, $xml_from_api);
        $taxon["keys"] = array();
        foreach($fields as $field) $taxon["keys"][] = $field;

        $arr = DiscoverLife_KeysAPI::get_discoverlife_taxa($taxon,array());
        $taxa = $arr[0];
        $this->assertTrue(is_array($taxa), 'Taxa should be an array');
        $taxon = $taxa[0];
        $this->assertIsA($taxon, 'SchemaTaxon', 'Response should be an array of SchemaTaxon');
        $dataObject = $taxon->dataObjects[0];
        $this->assertIsA($dataObject, 'SchemaDataObject', 'Taxon should have a data object');
    }
}
?>