<?php

require_once(DOC_ROOT . 'vendor/simpletest/autorun.php');
require_once(DOC_ROOT . 'vendor/simpletest/web_tester.php');

class SimpletestWebBase extends WebTestCase
{
    function setUp()
    {
        $called_class_name = get_class($this);
        echo "WebTest => ".$called_class_name."\n"; flush();
        
        $GLOBALS['db_connection']->truncate_tables('test');
        if(isset($this->load_fixtures)) $this->load_fixtures();
    }
    
    function tearDown()
    {
        unset($this->fixtures);
        //shell_exec("rm -fdr ".DOC_ROOT."temp/*");
    }
    
    function load_fixtures()
    {
        $this->fixtures = load_fixtures('test');
    }
}

?>