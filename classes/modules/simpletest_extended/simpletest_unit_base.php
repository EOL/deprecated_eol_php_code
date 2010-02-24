<?php

require_once(LOCAL_ROOT . '/classes/modules/simpletest/autorun.php');

class SimpletestUnitBase extends UnitTestCase
{
    function setUp()
    {
        $called_class_name = get_class($this);
        echo "UnitTest => ".$called_class_name."\n"; flush();
        
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