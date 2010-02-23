<?php

class test_names extends SimpletestUnitBase
{    
    function testNameFunctions()
    {
        $str = "Aus bus Smith (Linnaeus 1777)";
        $cf = "Aus bus";
        $cl = "aus bus smith ( linnaeus 1777 )";
        
        $canonical_form = Functions::canonical_form($str);
        $clean_name = Functions::clean_name($str);
        
        $this->assertTrue($canonical_form == $cf, "The canonical form should be right");
        $this->assertTrue($clean_name == $cl, "The clean name should be right");
    }
    
    function testInsertName()
    {
        $str = "Aus bus Smith (Linnaeus 1777)";
        
        $name_id = Name::insert($str);
        $this->assertTrue($name_id>0, "There should be a name_id");
        
        $name = new Name($name_id);
        $canonical_form = $name->canonical_form();
        $this->assertTrue($name->id>0, "Should be able to make a name object");
        $this->assertTrue($canonical_form->string=="Aus bus", "Name should have a canonical form");
    }
}

?>