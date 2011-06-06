<?php
namespace php_active_record;

class test_active_record_functions extends SimpletestUnitBase
{
    function testToCamelCase()
    {
        $this->assertEqual(to_camel_case('this_is_the_string'), 'ThisIsTheString', 'String should have the proper camel case');
        $this->assertEqual(to_camel_case('This_is_the_String'), 'ThisIsTheString', 'String should have the proper camel case');
        $this->assertEqual(to_camel_case('This_is_a_String'), 'ThisIsAString', 'String should have the proper camel case');
    }
    
    function testToUnderscore()
    {
        $this->assertEqual(to_underscore('ThisIsTheString'), 'this_is_the_string', 'String should have the proper camel case');
        $this->assertEqual(to_underscore('ThisIsAString'), 'this_is_a_string', 'String should have the proper camel case');
    }
    
    function testIsCamelCase()
    {
        $this->assertTrue(is_camel_case('ThisIsTheString'), 'Should validate as camel case');
        $this->assertTrue(is_camel_case('ThisIsAString'), 'Should validate as camel case');
        $this->assertTrue(is_camel_case('THISISaSTRING'), 'Should validate as camel case');
        
        $this->assertFalse(is_camel_case('THISIS aSTRING'), 'Should not validate as camel case');
        $this->assertFalse(is_camel_case('THIS-IS aSTRING'), 'Should not validate as camel case');
        $this->assertFalse(is_camel_case('This_is_a_String'), 'Should not validate as camel case');
    }
    
    function testIsUnderscore()
    {
        $this->assertTrue(is_underscore('this_is_the_string'), 'Should validate as underscore');
        $this->assertTrue(is_underscore('a_b_c_d'), 'Should validate as underscore');
        
        $this->assertFalse(is_underscore('this_fails_'), 'Should not validate as underscore');
        $this->assertFalse(is_underscore('_and_this'), 'Should not validate as underscore');
        $this->assertFalse(is_underscore('This_is_a_String'), 'Should not validate as underscore');
        $this->assertFalse(is_underscore('THISISaSTRING'), 'Should not validate as underscore');
    }
    
    function testToSingular()
    {
        $this->assertTrue(to_singular('ponies') == 'pony', 'Should singularize ies');
        $this->assertTrue(to_singular('potatoes') == 'potato', 'Should singularize oes');
        $this->assertTrue(to_singular('cards') == 'card', 'Should singularize s');
    }
    
    function testToPlural()
    {
        $this->assertTrue(to_plural('pony') == 'ponies', 'Should pluralize y');
        $this->assertTrue(to_plural('potato') == 'potatoes', 'Should pluralize o');
        $this->assertTrue(to_plural('card') == 'cards', 'Should singularize everything else');
    }
    
    function testTimer()
    {
        $start_time = start_timer();
        sleep(2);
        $end_time = time_elapsed();
        $this->assertTrue(abs($end_time - $start_time - 2) < 0.003, 'Timer should be accurate to .003');
    }
    
    function testTableFields()
    {
        $GLOBALS['db_connection']->insert('CREATE TABLE some_test_table (field_one INT, field_two INT, field_three VARCHAR(10))');
        $this->assertTrue(table_fields('some_test_table') == array('field_one', 'field_two', 'field_three'), 'Table fields should work');
        $GLOBALS['db_connection']->delete('DROP TABLE some_test_table');
    }
    
    function testRandomDigits()
    {
        $random = random_digits(7);
        $this->assertTrue(strlen($random) == 7, 'should get 7 random digits');
        $this->assertPattern("/^[0-9]{7}$/", $random, 'should get 7 random digits');
        $this->assertTrue($random != random_digits(7), 'should get a different 7 random digits');
        
        $random = random_digits(6, 7);
        $this->assertTrue(strlen($random) == 6, 'should get 6 random digits');
        
        // make sure the first digit is never less than 7 - 30 tries
        $pass_test = true;
        for($i=0; $i<30 ; $i++)
        {
            $random = random_digits(6, 7);
            if(substr($random, 0, 1) < 7)
            {
                $pass_test = false;
                break;
            }
        }
        $this->assertTrue($pass_test, 'the first digit should never be less than 7');
    }
    
    function testTempFilePath()
    {
        $tmp_path = temp_filepath();
        $this->assertPattern("/^".preg_quote(DOC_ROOT,"/") ."tmp\/tmp_[0-9]{5}\.file$/", $tmp_path, 'should return correct temp file path');
        
        $tmp_path = temp_filepath(true);
        $this->assertPattern("/^tmp\/tmp_[0-9]{5}\.file$/", $tmp_path, 'should return relative temp file path');
        
        $tmp_path = temp_filepath(true, 'jpg');
        $this->assertPattern("/^tmp\/tmp_[0-9]{5}\.jpg$/", $tmp_path, 'should return relative temp file path with extension');
        
    }
}

?>