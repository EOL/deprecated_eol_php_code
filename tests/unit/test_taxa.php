<?php

class test_taxa extends SimpletestUnitBase
{
    function testFindAndCreateByID()
    {
        $this->load_fixtures();
        $taxon = new Taxon($this->fixtures->taxa->Tetragnatha_guatemalensis->id);
        $this->assertTrue($taxon->id, $this->fixtures->taxa->Tetragnatha_guatemalensis->id, "Should be able to find this taxon by id");
    }
    
    function testCreateByArray()
    {
        $this->load_fixtures();
        $taxon = new Taxon(get_object_vars($this->fixtures->taxa->Tetragnatha_guatemalensis));
        $this->assertTrue($taxon->id, $this->fixtures->taxa->Tetragnatha_guatemalensis->id, "Should be able to find taxon by parameters");
    }
    
    function testInsertByObject()
    {
        $parameters = array("scientific_name" => "Aus buds Linnasue");
        
        $id = Taxon::insert_object_into(Functions::mock_object("Taxon", $parameters), "taxa");
        $this->assertTrue($id!=0);
    }
}

?>