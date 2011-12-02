<?php
namespace php_active_record;

require_library('connectors/InotaxaAPI');

class test_connector_inotaxa_api extends SimpletestUnitBase
{
    function testInotaxaAPI()
    {
        //$path= DOC_ROOT . "/update_resources/connectors/files/INOTAXA/";
        //$url = $path . "BCA_coleoptv4p3_taXMLit_v4-03-UTF8.xml";
        /*
        $url = "http://pandanus.eol.org/public/test_resources/BCA_coleoptv4p3_taXMLit_v4-03-UTF8.xml";
    
        $taxa = InotaxaAPI::get_inotaxa_taxa($url,"test");
        $this->assertTrue(is_array($taxa), 'Taxa should be an array');
        
        $taxon = $taxa[0];
        $this->assertIsA($taxon, 'SchemaTaxon', 'Response should be an array of SchemaTaxon');
        
        $dataObject = $taxon->dataObjects[0];
        $this->assertIsA($dataObject, 'SchemaDataObject', 'Taxon should have a data object');
        */
    }
}

?>