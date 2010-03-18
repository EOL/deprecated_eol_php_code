<?php

class test_validator_app extends SimpletestWebBase
{
    function testWithURL()
    {
        $this->get(WEB_ROOT . 'applications/validator/index.php?ENV_NAME=test');
        $this->setField('file_url', WEB_ROOT .'tests/fixtures/files/test_resource.xml');
        $this->clickSubmit('Submit');
        $this->assertText('http://services.eol.org/schema/content_0_2.xsd');
        $this->assertText('This file is valid according to its defined XSD');
        //$this->assertText('but there were EOL internal validation warnings');
    }
    
    function testWithUpload()
    {
        // SimpleTest does not handle file uploads as of current version 1.0.1
    }
}

?>