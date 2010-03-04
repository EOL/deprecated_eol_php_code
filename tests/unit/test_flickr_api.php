<?php

require_once(dirname(__FILE__) . "/../../lib/FlickrAPI.php");

class test_flickr_api extends SimpletestUnitBase
{
    function testPoolsGetPhotos()
    {
        $response = FlickrAPI::pools_get_photos(FLICKR_EOL_GROUP_ID, "", 1, 1, FLICKR_PLEARY_AUTH_TOKEN);
        $this->assertTrue($response['stat'] == 'ok', 'pools_get_photos should return a stat of OK');
        $this->assertTrue($response->photos['pages'] > 45000, 'EOL pool should have more than 45000 photos');
    }
    
    function testGetEOLPhotos()
    {
        $response = FlickrAPI::pools_get_photos(FLICKR_EOL_GROUP_ID, "", 5, 1, FLICKR_PLEARY_AUTH_TOKEN, FLICKR_PLEARY_USER_ID);
        $this->assertTrue($response->photos['total'] == 2, 'Patrick should have submitted only 2 photos');
        
        $first_photo = $response->photos->photo[0];
        $taxa = FlickrApi::get_taxa_for_photo($first_photo["id"], $first_photo["secret"], FLICKR_PLEARY_AUTH_TOKEN);
        $this->assertTrue(is_array($taxa), 'Taxa should be an array');
        
        $taxon = $taxa[0];
        $this->assertIsA($taxon, 'SchemaTaxon', 'Response should be an array of SchemaTaxon');
        $this->assertTrue($taxon->kingdom == 'Animalia', 'Taxon should have the right kingdom');
        $this->assertTrue($taxon->phylum == 'Chordata', 'Taxon should have the right phylum');
        $this->assertTrue($taxon->class == 'Aves', 'Taxon should have the right class');
        $this->assertTrue($taxon->order == 'Ciconiiformes', 'Taxon should have the right order');
        $this->assertTrue($taxon->family == 'Ardeidae', 'Taxon should have the right family');
        $this->assertTrue($taxon->genus == 'Ardea', 'Taxon should have the right genus');
        $this->assertTrue($taxon->scientificName == 'Ardea herodias', 'Taxon should have the right name');
        
        $this->assertIsA($taxon->commonNames[0], 'SchemaCommonName', 'Taxon should have a common name');
        $this->assertTrue($taxon->commonNames[0]->name == 'Great Blue Heron', 'Taxon should have the right common name');
        
        $dataObject = $taxon->dataObjects[0];
        $this->assertIsA($dataObject, 'SchemaDataObject', 'Taxon should have a data object');
        $this->assertTrue($dataObject->identifier == '2687301130', 'Data object should have the right identifier');
        $this->assertTrue($dataObject->dataType == 'http://purl.org/dc/dcmitype/StillImage', 'Data object should have the right dataType');
        $this->assertTrue($dataObject->mimeType == 'image/jpeg', 'Data object should have the right mimeType');
        $this->assertTrue($dataObject->created == '2008-06-21 07:08:49', 'Data object should have the right created');
        $this->assertTrue($dataObject->title == 'P1030143', 'Data object should have the right title');
        $this->assertTrue($dataObject->license == 'http://creativecommons.org/licenses/by-nc-sa/2.0/', 'Data object should have the right license');
        $this->assertTrue($dataObject->source == 'http://www.flickr.com/photos/pleary/2687301130/', 'Data object should have the right source');
        $this->assertTrue($dataObject->mediaURL == 'http://farm4.static.flickr.com/3057/2687301130_7874bc6fd3.jpg', 'Data object should have the right mediaURL');
        $this->assertTrue($dataObject->location == 'Copenhagen, Hovedstaden, Denmark', 'Data object should have the right location');
        
        $this->assertIsA($dataObject->agents[0], 'SchemaAgent', 'Data object should have an agent');
        $this->assertTrue($dataObject->agents[0]->fullName == 'pleary', 'Agent should have the right fullName');
        $this->assertTrue($dataObject->agents[0]->homepage == 'http://www.flickr.com/photos/11571226@N04', 'Agent should have the right homepage');
        $this->assertTrue($dataObject->agents[0]->role == 'photographer', 'Agent should have the right role');
        
        $this->assertIsA($dataObject->point, 'SchemaPoint', 'Data object should have an point');
        $this->assertTrue($dataObject->point->latitude == '55.670282', 'Point should have the right latitude');
        $this->assertTrue($dataObject->point->longitude == '12.583293', 'Point should have the right longitude');
    }
    
}

?>