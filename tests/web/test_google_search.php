<?php
namespace php_active_record;

class test_google_search extends SimpletestWebBase
{
    function testSearching()
    {
        @unlink(DOC_ROOT . "temp/google_output.txt");
        $this->get(WEB_ROOT . 'applications/google/search.php?ENV_NAME=test');
        $this->setField('nameList', 'Homo sapiens');
        $this->clickSubmit('Submit');
        $this->assertText('Homo sapiens');
        $this->assertTrue(file_exists(DOC_ROOT . "temp/google_output.txt"), 'Results file should exist');
        
        $contents = file_get_contents(DOC_ROOT . "temp/google_output.txt");
        $this->assertTrue(preg_match("/^Homo\+sapiens\thttp:/", $contents), 'Results file should contain links');
        @unlink(DOC_ROOT . "temp/google_output.txt");
    }
}

?>