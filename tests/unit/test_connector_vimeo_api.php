<?php
namespace php_active_record;
require_library('connectors/VimeoAPI');
require_once DOC_ROOT . '/vendor/vimeo-vimeo-php-lib-a32ff71/vimeo.php';
class test_connector_vimeo_api extends SimpletestUnitBase
{
    function testVimeoAPI()
    {
        $vimeo = new \phpVimeo("8498d03ee2e3276f878fbbeb2354a1552bfea767", "579812c7f9e9cef30ab1bf088c3d3b92073e115c");
        $user_id = "5352360"; //Eli Agbayani
        if($return = VimeoAPI::vimeo_call_with_retry($vimeo, 'vimeo.videos.getUploaded', array('user_id' => $user_id, 'page' => 1, "full_response" => true)))
        {
            foreach($return->videos->video as $rec)
            {
                $arr = VimeoAPI::get_vimeo_taxa($rec,array());
                $taxa = $arr[0];
                $this->assertTrue(is_array($taxa), 'Taxa should be an array');
                $taxon = $taxa[0];
                $this->assertIsA($taxon, 'SchemaTaxon', 'Response should be an array of SchemaTaxon');
                $dataObject = $taxon->dataObjects[0];
                $this->assertIsA($dataObject, 'SchemaDataObject', 'Taxon should have a data object');
            }
        }
    }
}
?>