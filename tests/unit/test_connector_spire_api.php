<?php
namespace php_active_record;

require_library('connectors/SpireAPI');

class test_connector_spire_api extends SimpletestUnitBase
{
    function testSpireAPI()
    {            
        $GLOBALS['arr_taxa']["genus species"] = Array
             (
                 "desc" => "my species",
                 "objects" => Array
                     (
                         "predator" => Array("pred 1"),
                         "prey" => Array("prey 1")        
                     ),
        
                 "ancestry" => Array
                     (
                         "Family" => "family",
                         "Order" => "order",
                         "Class" => "class",
                         "Phylum" => "phylum"
                     )        
             );
             
        $GLOBALS['arr_taxa']["pred 1"] = Array("desc" => "my predator", "objects" => Array(), "ancestry" => Array());
        $GLOBALS['arr_taxa']["prey 1"] = Array("desc" => "my prey", "objects" => Array(), "ancestry" => Array());             
                
        $arr = SpireAPI::get_spire_taxa("genus species",array());
        $taxa = $arr[0];
        $this->assertTrue(is_array($taxa), 'Taxa should be an array');
        
        $taxon = $taxa[0];
        $this->assertIsA($taxon, 'SchemaTaxon', 'Response should be an array of SchemaTaxon');
        
        $dataObject = $taxon->dataObjects[0];
        $this->assertIsA($dataObject, 'SchemaDataObject', 'Taxon should have a data object');                            
    }
}
?>