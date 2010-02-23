<?php

require_once(LOCAL_ROOT . '/classes/modules/simpletest/autorun.php');

class SimpletestUnitBase extends UnitTestCase
{
    function setUp()
    {
        $this->mysqli =& $GLOBALS['mysqli_connection'];
        
        if(isset($this->load_fixtures))
        {
            $this->mysqli->truncate_tables('test');
            $this->fixtures = Functions::load_fixtures('test');
        }
    }
    
    function tearDown()
    {
        if(isset($this->load_fixtures)) unset($this->fixtures);
        //shell_exec("rm -fdr ".DOC_ROOT."temp/*");
        
        $called_class_name = get_class($this);
        echo "UnitTest => ".$called_class_name."\n";
        flush();
    }
}

?>