<?php

class test_hierarchy_entries extends SimpletestUnitBase
{
    function testCreateFromMockObject()
    {
        $resource = Functions::mock_object("Resource", array("id" => 111));
        $hierarchy = Functions::mock_object("Hierarchy", array("id" => 222));
        
        $name_ids = array();
        $name_ids[] = 11;
        $name_ids[] = 22;
        $name_ids[] = 33;
        $name_ids[] = 44;
        
        $parent_hierarchy_entry = null;
        foreach($name_ids as $id)
        {
            $params = array();
            $params["name_id"] = $id;
            $params["hierarchy_id"] = $hierarchy->id;
            if($parent_hierarchy_entry) $params["parent_id"] = $parent_hierarchy_entry->id;
            $params["identifier"] = 'identifier';
            
            $mock_hierarchy_entry = Functions::mock_object("HierarchyEntry", $params);
            
            $hierarchy_entry = new HierarchyEntry(HierarchyEntry::insert($mock_hierarchy_entry));
            
            
            $this->assertTrue($hierarchy_entry->id > 0, "The hierarchy entry should have an id");
            $this->assertTrue($hierarchy_entry->name_id == $id, "The name_id should be set properly");
            if($parent_hierarchy_entry) $this->assertTrue($hierarchy_entry->parent_id == $parent_hierarchy_entry->id, "The parent_id should be set properly");
            
            $parent_hierarchy_entry = $hierarchy_entry;
        }
        
        
    }
}

?>