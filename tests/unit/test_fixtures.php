<?php

class test_fixtures extends SimpletestUnitBase
{
    public $load_fixtures = true;
    
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
    
    function testFindAndCreateByID()
    {
        $taxon = new Taxon($this->fixtures->taxa->Tetragnatha_guatemalensis->id);
        $this->assertTrue($taxon->id, $this->fixtures->taxa->Tetragnatha_guatemalensis->id, "Should be able to find this taxon by id");
    }
    
    function testCreateByArray()
    {
        $taxon = new Taxon(get_object_vars($this->fixtures->taxa->Tetragnatha_guatemalensis));
        $this->assertTrue($taxon->id, $this->fixtures->taxa->Tetragnatha_guatemalensis->id, "Should be able to find taxon by parameters");
    }
}

?>