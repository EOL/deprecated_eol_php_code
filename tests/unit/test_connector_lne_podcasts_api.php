<?php

require_library('connectors/LearningEducationAPI');

class test_connector_lne_podcasts_api extends SimpletestUnitBase
{
    function testLearningEducationAPI()
    {
        $taxon["Pandea rubra"] = "Red Paper Latern Jellyfish";
        
        $GLOBALS['hard_coded_taxon'] = $taxon;
        $GLOBALS['sound_objects'] = LearningEducationAPI::prepare_sound_objects();

        $arr = LearningEducationAPI::get_taxa();
        $taxon = $arr[0];
        $this->assertIsA($taxon, 'SchemaTaxon', 'Response should be an array of SchemaTaxon');
        $dataObject = $taxon->dataObjects[0];
        $this->assertIsA($dataObject, 'SchemaDataObject', 'Taxon should have a data object');
    }
}
?>