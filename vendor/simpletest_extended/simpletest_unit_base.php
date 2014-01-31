<?php
namespace php_active_record;

require_once(DOC_ROOT . 'vendor/simpletest/autorun.php');

class SimpletestUnitBase extends \UnitTestCase
{
    function setUp()
    {
        $called_class_name = get_class($this);
        static $last_called_class = "";
        if($last_called_class != $called_class_name)
        {
            echo "UnitTest => $called_class_name\n";
            $last_called_class = $called_class_name;
        }
        
        if($test_name = get_simpletest_name()) echo "   $test_name\n";
        else echo "   UNKNOWN TEST\n";
        flush();
        
        // restart reinializes the connection - so if memcached was turned on a connection will be made
        // but more importantly if it was turned off the connection will be closed and the cache flushed
        Cache::restart();
        Cache::flush();
        
        $GLOBALS['db_connection']->truncate_tables('test');
        if(isset($this->load_fixtures)) $this->load_fixtures();
    }
    
    function tearDown()
    {
        unset($this->fixtures);
    }
    
    function load_fixtures()
    {
        $this->fixtures = load_fixtures('test');
    }
}

?>