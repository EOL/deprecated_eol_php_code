<?php

include_once("TestBase.php");


class test_fixtures extends TestBase
{
    function testHaveResources()
    {
        $result = $this->mysqli->query("SELECT * FROM resources");
        $this->assertTrue($result && $result->num_rows!=0, "Should have resources");
    }
    
    function testHasedResourceFixture()
    {
        $this->assertEqual($this->fixtures->resources->canadian_arachnologist->title, "Canadian Arachnologist", "Should have resource title");
    }
    
    function testHaveTaxa()
    {
        $result = $this->mysqli->query("SELECT * FROM taxa");
        $this->assertTrue($result && $result->num_rows!=0, "Should have taxa");
    }
}

?>