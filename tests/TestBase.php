<?php

require_once(SIMPLE_TEST."autorun.php");

class TestBase extends UnitTestCase
{
    public $mysqli;
    
    function setUp()
    {
        $this->mysqli =& $GLOBALS['mysqli_connection'];
        //shell_exec("rm -fdr ".LOCAL_ROOT."temp/*");
        
        $this->fixtures = Functions::load_fixtures("test");
    }
    
    function tearDown()
    {
        $this->mysqli->truncate_tables("test");
        
        unset($this->fixtures);
        //shell_exec("rm -fdr ".LOCAL_ROOT."temp/*");
        echo get_class($this)."...\n";
        flush();
    }
}

?>