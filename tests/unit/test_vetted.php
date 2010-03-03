<?php

class test_vetted extends SimpletestUnitBase
{
    public $load_fixtures = true;
    
    function testRowsWithIdOfZero()
    {
        // truncate tables and get rid of the auto_inc on vetted
        $GLOBALS['db_connection']->update("ALTER TABLE vetted MODIFY id INT NOT NULL");
        $GLOBALS['db_connection']->truncate_tables('test');
        $GLOBALS['db_connection']->insert("INSERT INTO vetted (id, label) VALUES (0, 'unknown')");
        
        $this->assertTrue(Vetted::insert('unknown') == 0, 'Should recognize rows with ID=0');
        $this->assertTrue(Vetted::insert('unknown') == 0, 'Should recognize rows with ID=0 again');
        
        // go back to auto_inc so as to not effect other tests
        //$GLOBALS['db_connection']->update("ALTER TABLE vetted MODIFY id INT NOT NULL auto_increment");
        
        $this->assertTrue(Vetted::insert('unknown') == 0, 'Should recognize rows with ID=0 even with auto increment set');
    }
}

?>