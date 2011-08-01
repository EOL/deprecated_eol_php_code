<?php
namespace php_active_record;

class test_hierarchy_entries extends SimpletestUnitBase
{
    function testInsert()
    {
        $params = array('hierarchy_id'  => 12345,
                        'identifier'    => 'abcd1234',
                        'name_id'       => Name::find_or_create_by_string('Homo sapiens')->id,
                        'parent_id'     => Rank::find_or_create_by_translated_label('species')->id,
                        'source_url'    => 'http://www.example.org/abcd1234');
        $hierarchy_entry = HierarchyEntry::find_or_create($params);
        $this->assertTrue($hierarchy_entry->id == 1, "The hierarchy entry should have an id");
        
        foreach($params as $p => $value)
        {
            $this->assertTrue($hierarchy_entry->$p == $value, "attributes should be correct");
        }
        
        $this->assertTrue($hierarchy_entry->name->string == 'Homo sapiens', "name string sould be correct");
    }
    
    function testDuplicateName()
    {
        $name_id = Name::find_or_create_by_string('Chordata')->id;
        $params = array('hierarchy_id'  => 12345,
                        'identifier'    => 'abcd1234',
                        'name_id'       => $name_id,
                        'parent_id'     => 0,
                        'source_url'    => 'http://www.example.org/abcd1234');
        $hierarchy_entry = HierarchyEntry::find_or_create($params);
        $this->assertTrue($hierarchy_entry->id == 1, "The hierarchy entry should have an id");
        
        $name_id = Name::find_or_create_by_string('Chordata')->id;
        $params = array('hierarchy_id'  => 98,
                        'identifier'    => 'abcd1234',
                        'name_id'       => $name_id,
                        'parent_id'     => 0,
                        'source_url'    => 'http://www.example.org/abcd1234');
        $hierarchy_entry = HierarchyEntry::find_or_create($params);
        $this->assertTrue($hierarchy_entry->id == 2, "The hierarchy entry should have an id");
    }
    
    function testCreateFromArray()
    {
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
            $params["hierarchy_id"] = 222;
            if($parent_hierarchy_entry) $params["parent_id"] = $parent_hierarchy_entry->id;
            $params["identifier"] = 'identifier';
            
            $hierarchy_entry = HierarchyEntry::find_or_create($params);
            
            
            $this->assertTrue($hierarchy_entry->id > 0, "The hierarchy entry should have an id");
            $this->assertTrue($hierarchy_entry->name_id == $id, "The name_id should be set properly");
            if($parent_hierarchy_entry) $this->assertTrue($hierarchy_entry->parent_id == $parent_hierarchy_entry->id, "The parent_id should be set properly");
            
            $parent_hierarchy_entry = $hierarchy_entry;
        }
        
        
    }
}

?>