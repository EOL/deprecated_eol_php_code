<?php

include_once("TestBase.php");


class test_hierarchies extends TestBase
{
    function testCreateFromMockObject()
    {
        $agent_id = "111";
        $description = "Description of Hierarchy";
        $params = array();
        $params["agent_id"] = $agent_id;
        $params["description"] = $description;
        $hierarchy_mock = Functions::mock_object("Hierarchy", $params);
        $hierarchy = new Hierarchy(Hierarchy::insert($hierarchy_mock));
        
        $this->assertTrue($hierarchy->id > 0, "Hierarchy should have an id");
        $this->assertTrue($hierarchy->agent_id == $agent_id, "Hierarchy should have the proper agent_id");
        $this->assertTrue($hierarchy->description == $description, "Hierarchy should have proper description");
    }
}

?>