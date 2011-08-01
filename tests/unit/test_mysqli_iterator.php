<?php
namespace php_active_record;

require_library('MysqliResultIterator');
require_library('MysqliResultFileIterator');

class test_mysqli_iterator extends SimpletestUnitBase
{
    function testMysqliResultIterator()
    {
        $result = $GLOBALS['db_connection']->query("SELECT * FROM names");
        $this->assertTrue($result, 'We should get a result');
        $this->assertTrue($result->num_rows == 0, 'But it should be empty');
        
        $names_to_add = array();
        $names_to_add[] = "Aus bus";
        $names_to_add[] = "   Cud dus  ";
        $names_to_add[] = "abc";
        $names_to_add[] = "DEF";
        $names_to_add[] = " GHI";
        
        foreach($names_to_add as $name)
        {
            $GLOBALS['db_connection']->insert("INSERT IGNORE INTO names (string) VALUES ('$name')");
        }
        
        $result = $GLOBALS['db_connection']->query("SELECT * FROM names");
        $this->assertTrue($result->num_rows == count($names_to_add), 'Should get as many rows as names added');
        
        foreach(new MysqliResultIterator($result) as $row_num => $row)
        {
            $this->assertTrue($row['string'] == $names_to_add[$row_num-1], 'Names should be the same as what was inserted');
        }
        
        foreach($GLOBALS['db_connection']->iterate("SELECT * FROM names") as $row_num => $row)
        {
            $this->assertTrue($row['string'] == $names_to_add[$row_num-1], 'Names should be the same as what was inserted');
        }
        
        foreach($GLOBALS['db_connection']->iterate_file("SELECT string FROM names") as $row_num => $row)
        {
            $this->assertTrue($row[0] == $names_to_add[$row_num], 'Names should be the same as what was inserted');
        }
        
    }
}

?>