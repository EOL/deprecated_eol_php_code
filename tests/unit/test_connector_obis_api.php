<?php
namespace php_active_record;

require_library('connectors/ObisAPI');

class test_connector_obis_api extends SimpletestUnitBase
{
    function testObisAPI()
    {
        $url          = DOC_ROOT . "/update_resources/connectors/files/OBIS/OBIS_data_small.csv";
        $url_ancestry = DOC_ROOT . "/update_resources/connectors/files/OBIS/tnames20100825_small.csv";
		$func = new ObisAPI();
		$func->get_all_taxa($resource_id);
        $arr = $func->get_obis_taxa($url, array(), $url_ancestry);
        $taxa = $arr[0];
        $this->assertTrue(is_array($taxa), 'Taxa should be an array');
        $taxon = $taxa[0];
        $this->assertIsA($taxon, 'SchemaTaxon', 'Response should be an array of SchemaTaxon');
        $dataObject = $taxon->dataObjects[0];
        $this->assertIsA($dataObject, 'SchemaDataObject', 'Taxon should have a data object');
    }
}

?>