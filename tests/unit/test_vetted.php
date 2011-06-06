<?php
namespace php_active_record;

class test_vetted extends SimpletestUnitBase
{
    function testRowsWithIdOfZero()
    {
        // truncate tables and get rid of the auto_inc on vetted
        $GLOBALS['db_connection']->update("ALTER TABLE vetted MODIFY id INT NOT NULL");
        $GLOBALS['db_connection']->insert("INSERT INTO vetted (id, label) VALUES (0, 'unknown')");
        
        $this->assertTrue(Vetted::unknown()->id == 0, 'Should recognize rows with ID=0');
        $this->assertTrue(Vetted::unknown()->id == 0, 'Should recognize rows with ID=0 again');
        
        // need to set it back to auto increment
        $GLOBALS['db_connection']->update("ALTER TABLE vetted MODIFY id INT NOT NULL auto_increment");
    }
}

?>