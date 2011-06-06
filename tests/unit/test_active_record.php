<?php
namespace php_active_record;

class test_active_record extends SimpletestUnitBase
{
    function testFindOrCreateWithNullValue()
    {
        $this->assertEqual(MimeType::find_or_create_by_label(NULL), NULL);
    }
    
    function testFindOrCreateWithArray()
    {
        $parameters = array("full_name" => "Jane Smith", "display_name" => "J. Smith");
        $agent = Agent::find_or_create($parameters);
        $this->assertTrue($agent->id, "Should have created a new agent");
        $this->assertEqual($agent->full_name, "Jane Smith", "Should have proper full_name");
        $this->assertEqual($agent->display_name, "J. Smith", "Should have proper display_name");
        
        $parameters = array( "agent_id" => 111, "description" => "Description of Hierarchy");
        $hierarchy = Hierarchy::find_or_create($parameters);
        $this->assertTrue($hierarchy->id > 0, "Hierarchy should have an id");
        $this->assertTrue($hierarchy->agent_id == 111, "Hierarchy should have the proper agent_id");
        $this->assertTrue($hierarchy->description == "Description of Hierarchy", "Hierarchy should have proper description");
    }
}

?>