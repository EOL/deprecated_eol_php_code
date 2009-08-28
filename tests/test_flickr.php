<?php

include_once("TestBase.php");


class test_flickr extends TestBase
{
    function testRequestParameters()
    {
        $parameters = FlickrAPI::request_parameters("flickr.people.getInfo");
        $this->assertIsA($parameters, "array");
        $this->assertTrue(count($parameters) == 2);
    }
    
    function testGenerateSignature()
    {
        $parameters = FlickrAPI::request_parameters("flickr.people.getInfo");
        $signature = FlickrAPI::generate_signature($parameters);
        $this->assertTrue(strlen($signature)==32);
    }
    
    function testEncodeParameters()
    {
        $parameters = FlickrAPI::request_parameters("flickr.people.getInfo");
        $parameters = FlickrAPI::encode_parameters($parameters);
        $this->assertIsA($parameters, "array");
        $this->assertTrue(count($parameters) == 2);
    }
    
    function testGenerateRestURL()
    {
        $url = FlickrAPI::generate_rest_url("flickr.photos.getInfo", array("photo_id" => "2687301130", "secret" => "7874bc6fd3"), 0);
        $this->assertIsA($url, "string");
    }
    
    function testGetEolPhotos()
    {
        $taxa = FlickrAPI::get_eol_photos(10, 5);
        $this->assertIsA($taxa, "array", "Should return an array");
        $this->assertIsA(array_pop($taxa), "SchemaTaxon", "Should return an array of class SchemaTaxon");
    }
}

?>