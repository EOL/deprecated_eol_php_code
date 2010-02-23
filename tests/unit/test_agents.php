<?php

class test_agents extends SimpletestUnitBase
{
    function testCreateByMock()
    {
        $mock = Functions::mock_object("Agent", array( "full_name" => "Jane Smith", "display_name" => "J. Smith"));
        $agent_id = Agent::insert($mock);
        $agent = new Agent($agent_id);
        
        $this->assertTrue($agent->id, "Should have created a new agent");
        $this->assertEqual($agent->full_name, "Jane Smith", "Should have proper full_name");
        $this->assertEqual($agent->display_name, "J. Smith", "Should have proper display_name");
    }
}

?>