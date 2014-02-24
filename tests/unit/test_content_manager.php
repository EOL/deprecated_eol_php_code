<?php
namespace php_active_record;

class test_content_manager extends SimpletestUnitBase
{
    function setUp()
    {
        parent::setUp();
        $this->content_manager = new ContentManager();
    }

    function tearDown()
    {
        unset($this->content_manager);
        parent::tearDown();
    }

    function testWebServerAvailability()
    {
        $file = Functions::get_remote_file(WEB_ROOT . 'tests/fixtures/files/test_resource.xml');
        $this->assertPattern('/dataObject/', $file, 'YOUR WEB SERVER MAY NOT BE RUNNING');
    }

    function testGrabResource()
    {
        $file = $this->content_manager->grab_file(WEB_ROOT . 'tests/fixtures/files/test_resource.xml', 'resource', array('resource_id' => 101010101));
        $this->assertTrue($file == '101010101.xml', 'File name should be same as resource id');
        $this->assertTrue(file_exists(CONTENT_RESOURCE_LOCAL_PATH . '101010101.xml'), 'File should exist');
        unlink(CONTENT_RESOURCE_LOCAL_PATH . '101010101.xml');
    }

    function testGrabImage()
    {
        $file = $this->content_manager->grab_file('http://eol.org/assets/v2/icon_taxon.png', 'image');
        $cache_path = ContentManager::cache_path($file);
        $this->assertTrue(file_exists(CONTENT_LOCAL_PATH . $cache_path .'.png'), 'Should upload the image');
        $this->assertTrue(file_exists(CONTENT_LOCAL_PATH . $cache_path .'_orig.jpg'), 'Should be an original size converted to jpeg');
        $this->assertTrue(file_exists(CONTENT_LOCAL_PATH . $cache_path .'_580_360.jpg'), 'Should create thumbnail');
        $this->assertTrue(file_exists(CONTENT_LOCAL_PATH . $cache_path .'_260_190.jpg'), 'Should create thumbnail');
        $this->assertTrue(file_exists(CONTENT_LOCAL_PATH . $cache_path .'_98_68.jpg'), 'Should create thumbnail');
        $this->assertTrue(file_exists(CONTENT_LOCAL_PATH . $cache_path .'_130_130.jpg'), 'Should create thumbnail');
        $this->assertTrue(file_exists(CONTENT_LOCAL_PATH . $cache_path .'_88_88.jpg'), 'Should create thumbnail');
        self::delete_content(CONTENT_LOCAL_PATH . $cache_path);
    }

    function testGrabImageWithNoExtension()
    {
        $file = $this->content_manager->grab_file('http://eolspecies.lifedesks.org/image/view/793', 'image');
        $cache_path = ContentManager::cache_path($file);
        $this->assertTrue(file_exists(CONTENT_LOCAL_PATH . $cache_path . '.jpg'), 'Should upload the image');
        $this->assertTrue(file_exists(CONTENT_LOCAL_PATH . $cache_path . '_orig.jpg'), 'Should be an orignial size converted to jpeg');
        $this->assertTrue(file_exists(CONTENT_LOCAL_PATH . $cache_path . '_580_360.jpg'), 'Should create thumbnail');
        $this->assertTrue(file_exists(CONTENT_LOCAL_PATH . $cache_path . '_260_190.jpg'), 'Should create thumbnail');
        $this->assertTrue(file_exists(CONTENT_LOCAL_PATH . $cache_path . '_98_68.jpg'), 'Should create thumbnail');
        $this->assertTrue(file_exists(CONTENT_LOCAL_PATH . $cache_path . '_130_130.jpg'), 'Should create thumbnail');
        $this->assertTrue(file_exists(CONTENT_LOCAL_PATH . $cache_path . '_88_88.jpg'), 'Should create thumbnail');
        self::delete_content(CONTENT_LOCAL_PATH . $cache_path);
    }

    function testGrabContentVideo()
    {
        $file = $this->content_manager->grab_file('http://www.crowcastle.net/preston/linux-av/legodrgn.avi', 'video');
        $cache_path = ContentManager::cache_path($file);
        $this->assertTrue(file_exists(CONTENT_LOCAL_PATH . $cache_path .'.avi'), 'Should be an avi file');
        self::delete_content(CONTENT_LOCAL_PATH . $cache_path);
    }

    function testGrabContentAudio()
    {
        $file = $this->content_manager->grab_file('http://people.sc.fsu.edu/~jburkardt/data/wav/thermo.wav', 'audio');
        $cache_path = ContentManager::cache_path($file);
        $this->assertTrue(file_exists(CONTENT_LOCAL_PATH . $cache_path .'.wav'), 'Should be an wav file');
        if (defined('SOX_BIN_PATH')) {
            $this->assertTrue(file_exists(CONTENT_LOCAL_PATH . $cache_path .'_orig.png'), 'Should be a spectrogram of the audio, ');
            $this->assertTrue(file_exists(CONTENT_LOCAL_PATH . $cache_path .'_580_360.png'), 'Should create thumbnail');
            $this->assertTrue(file_exists(CONTENT_LOCAL_PATH . $cache_path .'_260_190.png'), 'Should create thumbnail');
            $this->assertTrue(file_exists(CONTENT_LOCAL_PATH . $cache_path .'_98_68.png'), 'Should create thumbnail');
            $this->assertTrue(file_exists(CONTENT_LOCAL_PATH . $cache_path .'_130_130.png'), 'Should create thumbnail');
            $this->assertTrue(file_exists(CONTENT_LOCAL_PATH . $cache_path .'_88_88.png'), 'Should create thumbnail');
        }
        self::delete_content(CONTENT_LOCAL_PATH . $cache_path);
    }

    function testImageRotating()
    {
        $file = $this->content_manager->grab_file('http://content62.eol.org/content/2013/06/25/16/25942_orig.jpg', 'image');
        $cache_path = ContentManager::cache_path($file);
        $sizes = getimagesize(CONTENT_LOCAL_PATH . $cache_path .'_580_360.jpg');
        // this will max out the width
        $this->assertTrue($sizes[0] == 580);
        $this->assertTrue($sizes[1] == 233);
        self::delete_content(CONTENT_LOCAL_PATH . $cache_path);

        $file = $this->content_manager->grab_file('http://content62.eol.org/content/2013/06/25/16/25942_orig.jpg', 'image', array('rotation' => 90));
        $cache_path = ContentManager::cache_path($file);
        $sizes = getimagesize(CONTENT_LOCAL_PATH . $cache_path .'_580_360.jpg');
        // now it will max out the height
        $this->assertTrue($sizes[0] == 145);
        $this->assertTrue($sizes[1] == 360);
        self::delete_content(CONTENT_LOCAL_PATH . $cache_path);
    }

    function testCustomLargeSize()
    {
        $file = $this->content_manager->grab_file('http://content62.eol.org/content/2013/06/25/16/25942_orig.jpg', 'image');
        $cache_path = ContentManager::cache_path($file);
        $sizes = getimagesize(CONTENT_LOCAL_PATH . $cache_path .'_580_360.jpg');
        // this will max out the width
        $this->assertTrue($sizes[0] == 580);
        $this->assertTrue($sizes[1] == 233);
        self::delete_content(CONTENT_LOCAL_PATH . $cache_path);

        $file = $this->content_manager->grab_file('http://content62.eol.org/content/2013/06/25/16/25942_orig.jpg', 'image', array('large_image_dimensions' => array(300, 300)));
        $cache_path = ContentManager::cache_path($file);
        $sizes = getimagesize(CONTENT_LOCAL_PATH . $cache_path .'_580_360.jpg');
        // now it will max out the height
        $this->assertTrue($sizes[0] == 300);
        $this->assertTrue($sizes[1] == 121);
        self::delete_content(CONTENT_LOCAL_PATH . $cache_path);
    }

    private static function delete_content($prefix)
    {
        if(!$prefix || $prefix == '/') return;
        foreach(glob($prefix. '*') as $filename) unlink($filename);
    }
}

?>