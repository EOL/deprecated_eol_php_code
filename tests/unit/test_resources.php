<?php

class test_resources extends SimpletestUnitBase
{
    function testHarvesting()
    {
        // create the test resource
        $agent_id = Agent::insert(array('full_name' => 'Test Content Partner'));
        $agent = new Agent($agent_id);
        
        // create the content partner
        $content_partner_id = ContentPartner::insert(array('agent_id' => $agent_id, 'auto_publish' => 0, 'vetted' => 0));
        
        // create the resource
        $attr = array(  'accesspoint_url'       => LOCAL_WEB_ROOT . '/fixtures/files/test_resource.xml',
                        'service_type_id'       => ServiceType::insert('EOL Transfer Schema'),
                        'refresh_period_hours'  => 1,
                        'auto_publish'          => 0,
                        'vetted'                => 0,
                        'resource_status_id'    => ResourceStatus::insert('Validated'));
        $resource_id = Resource::insert($attr);
        $agent->add_resouce($resource_id, 'Data Supplier');
        $resource = new Resource($resource_id);
        
        $this->assertTrue(isset($resource->id), 'Resource should have an ID');
        $this->assertTrue($resource->id >= 1, 'Resource ID should be 1 or more');
        $this->assertIsA($resource->content_partner(), 'ContentPartner', 'Resource should have a content partner');
        $this->assertTrue($resource->content_partner()->id == $content_partner_id, 'ContentPartner', 'Resource should have the right content partner');
        
        copy(LOCAL_ROOT."/fixtures/files/test_resource.xml", CONTENT_RESOURCE_LOCAL_PATH."/".$resource->id.".xml");
        
        $this->assertTrue(count(HarvestEvent::all()) == 0, 'There shouldnt be any events to begin with');
        $this->assertTrue(count(DataObject::all()) == 0, 'There shouldnt be any data objects to begin with');
        $this->assertTrue(count(Taxon::all()) == 0, 'There shouldnt be any taxa to begin with');
        
        // harvest the resource
        $resource->harvest();
        
        $events = HarvestEvent::all();
        $this->assertTrue(count($events) == 1, 'There should be an event after harvesting');
        $this->assertTrue($events[0]->resource_id == $resource->id, 'It should belong to the resource');
        $this->assertTrue($events[0]->began_at != null, 'It should have a begin date');
        $this->assertTrue($events[0]->completed_at != null, 'It should have a completed date');
        $this->assertTrue($events[0]->published_at == null, 'It should not have a published date');
        
        $objects = DataObject::all();
        $this->assertTrue(count($objects) > 0, 'There should be objects after harvesting');
        $this->assertTrue($objects[0]->published == 0, 'Objects should not be published');
        $this->assertTrue($objects[0]->visibility_id == Visibility::insert('preview'), 'Objects should be in preview mode');
        $this->assertTrue($objects[0]->vetted_id == Vetted::insert('unknown'), 'Objects should be in vetted "unknown"');
        $this->assertTrue($objects[0]->data_rating == 2.5, 'Objects should have the default rating');
        
        
        // $resource_xml = Functions::get_hashed_response(CONTENT_RESOURCE_LOCAL_PATH . $new_file_path);
        // foreach($resource_xml->taxon-> as $taxon)
        // {
        //     Functions::import_decode
        // }
        
        unlink(CONTENT_RESOURCE_LOCAL_PATH . $resource->id .".xml");
    }
}

?>