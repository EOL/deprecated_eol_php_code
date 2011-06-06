<?php
namespace php_active_record;

class test_content_manager extends SimpletestUnitBase
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
    
    function testWebServerAvailability()
    {
        $file = Functions::get_remote_file(WEB_ROOT . "tests/fixtures/files/test_resource.xml");
        $this->assertPattern("/dataObject/", $file, 'YOUR WEB SERVER MAY NOT BE RUNNING');
    }
    
    function testGrabResource()
    {
        $file = $this->content_manager->grab_file(WEB_ROOT . 'tests/fixtures/files/test_resource.xml', 101010101, "resource");
        $this->assertTrue($file == "101010101.xml", "File name should be same as resource id");
        $this->assertTrue(file_exists(CONTENT_RESOURCE_LOCAL_PATH."101010101.xml"), "File should exist");
        unlink(CONTENT_RESOURCE_LOCAL_PATH."101010101.xml");
    }
    
    function testGrabPartnerImage()
    {
        $file = $this->content_manager->grab_file("http://eolspecies.lifedesks.org/image/view/793", 0, "partner");
        $this->assertPattern("/^[0-9]{6}$/", $file, "File should have 6 digits");
        
        $this->assertTrue(file_exists(CONTENT_PARTNER_LOCAL_PATH."/".$file.".jpg"), "Image should exist");
        
        $this->assertTrue(file_exists(CONTENT_PARTNER_LOCAL_PATH."/".$file."_small.png"), "Should be a small thumbnail");
        $this->assertTrue(file_exists(CONTENT_PARTNER_LOCAL_PATH."/".$file."_large.png"), "Should be a large thumbnail");
    }
    
    function testGrabContentImage()
    {
        //$file = $this->content_manager->grab_file("http://eolspecies.lifedesks.org/image/view/793", 0, "content");
        $file = $this->content_manager->grab_file("http://eolspecies.lifedesks.org/image/view/793", 0, "image");
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
        
        //$file = $this->content_manager->grab_file("http://eolspecies.lifedesks.org/image/view/793", 0, "content");
        $file = $this->content_manager->grab_file("http://eolspecies.lifedesks.org/image/view/793", 0, "image");
        $this->assertPattern("/^[0-9]{15}/", $file, 'Should be able to download images with no file extension');
    }

    function testGrabContentVideo()
    {
        $file = $this->content_manager->grab_file("http://www.crowcastle.net/preston/linux-av/legodrgn.avi", 0, "video");
        $this->assertPattern("/^[0-9]{15}/", $file);        
        if(preg_match("/^([0-9]{4})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{5})$/", $file, $arr))
        {
            $dir = $arr[1]."/".$arr[2]."/".$arr[3]."/".$arr[4]."/";
            $prefix = $arr[5];
            $this->assertTrue(file_exists(CONTENT_LOCAL_PATH."/".$dir.$prefix.".avi"), "Should be an avi file");
        }else $this->assertTrue(false, "Video should match this pattern");        
    }

    function testGrabContentAudio()
    {
        $file = $this->content_manager->grab_file("http://people.sc.fsu.edu/~jburkardt/data/wav/thermo.wav", 0, "audio");
        $this->assertPattern("/^[0-9]{15}/", $file);
        if(preg_match("/^([0-9]{4})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{5})$/", $file, $arr))
        {
            $dir = $arr[1]."/".$arr[2]."/".$arr[3]."/".$arr[4]."/";
            $prefix = $arr[5];
            $this->assertTrue(file_exists(CONTENT_LOCAL_PATH."/".$dir.$prefix.".wav"), "Should be an wav file");
        }else $this->assertTrue(false, "Audio should match this pattern");
    }

}

?>