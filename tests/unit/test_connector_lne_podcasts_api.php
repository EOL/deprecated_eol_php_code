<?php
namespace php_active_record;

require_library('connectors/LearningEducationAPI');

class test_connector_lne_podcasts_api extends SimpletestUnitBase
{
    function testLearningEducationAPI()
    {
        $arr = LearningEducationAPI::get_all_taxa();
        $taxon = $arr[0];
        $this->assertIsA($taxon, 'SchemaTaxon', 'Response should be an array of SchemaTaxon');
        $dataObject = $taxon->dataObjects[0];
        $this->assertIsA($dataObject, 'SchemaDataObject', 'Taxon should have a data object');
    }
}
?>