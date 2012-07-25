<?php
namespace php_active_record;

require_library('connectors/PhotosynthAPI');

class test_connector_photosynth_api extends SimpletestUnitBase
{
    function testPhotosynthAPI()
    {        

        // $id=array();
        // $id["2631672a-0280-4fb2-83fe-1a6cbe33f666"]=true;
        // $arr = PhotosynthAPI::get_photosynth_taxa(100, 0, $id);
        // $taxa = $arr[0];        
        // 
        // $this->assertTrue(is_array($taxa), 'Taxa should be an array');                
        // 
        // $taxon = @$taxa[0];
        // $this->assertIsA($taxon, 'SchemaTaxon', 'Response should be an array of SchemaTaxon');
        // 
        // $dataObject = $taxon->dataObjects[0];
        // $this->assertIsA($dataObject, 'SchemaDataObject', 'Taxon should have a data object');        
    
    }
}
?>