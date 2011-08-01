<?php
namespace php_active_record;

require_library('connectors/VimeoAPI');

class test_connector_vimeo_api extends SimpletestUnitBase
{
    function testVimeoAPI()
    {          
        /*
        $rec = array(
            "id"            => "18761917",
            "title"         => "Anarhichas lupus",
            "description"   => "Anarhichas lupus in an aquarium. [dc:license=public domain][eol][taxonomy:binomial=Anarhichas lupus][taxonomy:kingdom=Animalia][taxonomy:phylum=Chordata][taxonomy:class=Actinopterygii][taxonomy:order=Perciformes][taxonomy:family=Anarhichadidae][taxonomy:common=Atlantic wolffish]",
            "url"               => "http://vimeo.com/18761917",
            "upload_date"       => "2011-01-13 16:34:51",
            "thumbnail_small"   => "http://b.vimeocdn.com/ts/118/279/118279154_100.jpg",
            "thumbnail_medium"  => "http://b.vimeocdn.com/ts/118/279/118279154_200.jpg",
            "thumbnail_large"   => "http://b.vimeocdn.com/ts/118/279/118279154_640.jpg",
            "user_name"         => "Eli Agbayani",
            "user_url"              => "http://vimeo.com/user5352360",
            "user_portrait_small"   => "http://a.vimeocdn.com/portraits/defaults/d.30.jpg",
            "user_portrait_medium"  => "http://a.vimeocdn.com/portraits/defaults/d.75.jpg",
            "user_portrait_large"   => "http://a.vimeocdn.com/portraits/defaults/d.100.jpg",
            "user_portrait_huge"    => "http://a.vimeocdn.com/portraits/defaults/d.300.jpg",
            "stats_number_of_likes"     => "0",
            "stats_number_of_plays"     => "0",
            "stats_number_of_comments"  => "0",
            "duration"  => "42",
            "width"     => "160",
            "height"    => "112");
        */
                

        $user = "user5352360"; //Eli Agbayani
        $xml = simplexml_load_file(VIMEO_USER_SERVICE . $user . "/videos.xml");                        
        $num_rows = sizeof($xml->video); $i=0;
        foreach($xml->video as $rec)
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
?>