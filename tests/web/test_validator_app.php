<?php
namespace php_active_record;

class test_validator_app extends SimpletestWebBase
{
    function testIndexPage()
    {
        $this->get(WEB_ROOT . 'applications/validator/index.php?ENV_NAME=test');
        $this->assertText('XML File Validator');
        $this->assertText('XML File URL');
        $this->assertText('XML File Upload');
    }
    
    function testWithURL()
    {
        $this->get(WEB_ROOT . 'applications/validator/index.php?ENV_NAME=test');
        $this->setField('file_url', WEB_ROOT .'tests/fixtures/files/test_resource.xml');
        $this->setConnectionTimeout(60);
        $this->clickSubmit('Submit');
        $this->assertText('http://services.eol.org/schema/content_0_3.xsd');
        $this->assertText('This file is valid according to its defined XSD');
        //$this->assertText('but there were EOL internal validation warnings');
    }
    
    function testWithUpload()
    {
        // SimpleTest does not handle file uploads as of current version 1.0.1
    }
}

?>