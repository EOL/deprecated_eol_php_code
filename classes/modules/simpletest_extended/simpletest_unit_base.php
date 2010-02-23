<?php

require_once(LOCAL_ROOT . '/classes/modules/simpletest/autorun.php');

class SimpletestUnitBase extends UnitTestCase
{
    function setUp()
    {
        $this->mysqli =& $GLOBALS['mysqli_connection'];
        $this->fixtures = Functions::load_fixtures('test');
    }
    
    function tearDown()
    {
        $this->mysqli->truncate_tables('test');
        
        unset($this->fixtures);
        //shell_exec("rm -fdr ".DOC_ROOT."temp/*");
        
        $called_class_name = get_class($this);
        echo "UnitTest => ".$called_class_name."\n";
        flush();
    }
}

?>