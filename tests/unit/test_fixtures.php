<?php

class test_fixtures extends SimpletestUnitBase
{
    public $load_fixtures = true;
    
    function testHaveDataTypes()
    {
        $result = $GLOBALS['db_connection']->query("SELECT * FROM data_types");
        $this->assertTrue($result && $result->num_rows!=0, "Should have resources");
    }
    
    function testHasedResourceFixture()
    {
        $this->assertEqual($this->fixtures->data_types->sound->schema_value, "http://purl.org/dc/dcmitype/Sound", "Should have fixture data in object");
    }
    
    function testFind()
    {
        $data_type_id = DataType::insert($this->fixtures->data_types->sound->schema_value);
        $this->assertTrue($data_type_id, $this->fixtures->data_types->sound->id, "Should be able to find this data type by id");
    }
}

?>