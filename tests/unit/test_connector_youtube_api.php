<?php
namespace php_active_record;
require_library('connectors/YouTubeAPI');
class test_connector_youtube_api extends SimpletestUnitBase
{
    function testYouTubeAPI()
    {          
        $yt = new YouTubeAPI();
        $users['ile1731'] = 1;
        $users = $yt->assign_video_ids(array_keys($users));
        foreach($users as $user)
        {
            $num_rows = count($user["video_ids"]);
            foreach($user["video_ids"] as $video_id)
            {
                $record = $yt->build_data($video_id);
                if($record) 
                {
                    $arr = YouTubeAPI::get_youtube_taxa($record, array());
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
}
?>