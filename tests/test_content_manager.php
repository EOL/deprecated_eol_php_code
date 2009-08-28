<?php

include_once("TestBase.php");


class test_content_manager extends TestBase
{
    function setUp()
    {
        parent::setUp();
        $this->content_manager = new ContentManager(0);
    }
    
    function tearDown()
    {
        unset($this->content_manager);
        parent::tearDown();
    }
    
    function testGrabResource()
    {
        $file = $this->content_manager->grab_file("http://www.eol.org/images/eol_logo_header.png", 101, "resource");
        $this->assertTrue($file == "101.png", "File name should be same as resource id");
        $this->assertTrue(file_exists(CONTENT_PARTNER_LOCAL_PATH."/101.png"), "File should exist");
    }
    
    function testGrabPartnerImage()
    {
        $file = $this->content_manager->grab_file("http://www.eol.org/images/eol_logo_header.png", 0, "partner");
        $this->assertPattern("/^[0-9]{4}$/", $file, "File should have 4 digits");
        
        $this->assertTrue(file_exists(CONTENT_PARTNER_LOCAL_PATH."/".$file.".png"), "Image should exist");
        
        if(preg_match("/^([0-9]{4})/", $file, $arr))
        {
            $prefix = $arr[1];
            $this->assertTrue(file_exists(CONTENT_PARTNER_LOCAL_PATH."/".$prefix."_small.png"), "Should be a small thumbnail");
            $this->assertTrue(file_exists(CONTENT_PARTNER_LOCAL_PATH."/".$prefix."_large.png"), "Should be a large thumbnail");
        }else $this->assertTrue(false, "Image should match this pattern");
    }
    
    function testGrabContentImage()
    {
        $file = $this->content_manager->grab_file("http://www.eol.org/images/eol_logo_header.png", 0, "content");
        $this->assertPattern("/^[0-9]{15}/", $file);
        
        if(preg_match("/^([0-9]{4})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{5})$/", $file, $arr))
        {
            $dir = $arr[1]."/".$arr[2]."/".$arr[3]."/".$arr[4]."/";
            $prefix = $arr[5];
            $this->assertTrue(file_exists(CONTENT_LOCAL_PATH."/".$dir.$prefix."_small.jpg"), "Should be a small thumbnail");
            $this->assertTrue(file_exists(CONTENT_LOCAL_PATH."/".$dir.$prefix."_medium.jpg"), "Should be a medium thumbnail");
            $this->assertTrue(file_exists(CONTENT_LOCAL_PATH."/".$dir.$prefix."_large.jpg"), "Should be a large thumbnail");
            $this->assertTrue(file_exists(CONTENT_LOCAL_PATH."/".$dir.$prefix."_orig.jpg"), "Should be an orignial size converted to jpeg");
        }else $this->assertTrue(false, "Image should match this pattern");
    }
}

?>