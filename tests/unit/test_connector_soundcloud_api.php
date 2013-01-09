<?php
namespace php_active_record;
require_library('connectors/SoundcloudAPI');
class test_connector_soundcloud_api extends SimpletestUnitBase
{
    function testSoundcloudAPI()
    {
        $func = new SoundcloudAPI;
        $user_id = "30860816"; //Eli Agbayani
        $audio_list_url = $func->soundcloud_domain . "/users/" . $user_id . "/tracks?client_id=" . $func->soundcloud_api_client_id . "&limit=100&offset=0";
        if($xml = Functions::get_hashed_response($audio_list_url))
        {
            foreach($xml->track as $track)
            {
                $arr = $func->get_soundcloud_taxa($track, array());
                if($taxa = $arr[0])
                {
                    $this->assertTrue(is_array($taxa), 'Taxa should be an array');
                    $taxon = $taxa[0];
                    $this->assertIsA($taxon, 'SchemaTaxon', 'Response should be an array of SchemaTaxon');
                    $dataObject = $taxon->dataObjects[0];
                    $this->assertIsA($dataObject, 'SchemaDataObject', 'Taxon should have a data object');            
                }
            }
        }
    }
}
?>