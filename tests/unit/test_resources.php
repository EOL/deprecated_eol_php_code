<?php

class test_resources extends SimpletestUnitBase
{
    function testHarvesting()
    {
        $this->load_fixtures();
        $resource = self::create_resource();
        
        $this->assertTrue(count(HarvestEvent::all()) == 0, 'There shouldnt be any events to begin with');
        $this->assertTrue(count(DataObject::all()) == 0, 'There shouldnt be any data objects to begin with');
        $this->assertTrue(count(HierarchyEntry::all()) == 0, 'There shouldnt be any hierarchy entries to begin with');
        
        $result = $GLOBALS['db_connection']->query("SELECT 1 FROM top_images LIMIT 1");
        $this->assertTrue($result->num_rows == 0, 'shouldnt be any top images');
        
        // harvest the resource and run all the denormalized tasks to test them
        shell_exec(PHP_BIN_PATH.DOC_ROOT."rake_tasks/harvest_resources_cron_task.php ENV_NAME=test");
        
        $this->check_content_after_harvesting($resource);
        
        self::harvest($resource);
        
        @unlink(CONTENT_RESOURCE_LOCAL_PATH . $resource->id .".xml");
    }
    
    function testIUCNDataType()
    {
        $resource = self::create_resource(array('title' => 'IUCN Red List'));
        self::harvest($resource);
        $last_object = DataObject::last();
        $this->assertTrue($last_object->data_type_id == DataType::insert('IUCN'), 'IUCN should get a special data type');
    }
    
    function testWikipediaInfoItem()
    {
        $resource = self::create_resource(array('title' => 'Wikipedia'));
        self::harvest($resource);
        $last_object = DataObject::last();
        $ii = InfoItem::insert('http://www.eol.org/voc/table_of_contents#Wikipedia');
        $do_iis = $last_object->info_items();
        $this->assertTrue($ii == $do_iis[0]->id, 'Wikipedia should get a special TOC');
    }
    
    function testBOLDInfoItem()
    {
        $resource = self::create_resource(array('title' => 'BOLD Systems Resource'));
        self::harvest($resource);
        $last_object = DataObject::last();
        $ii = InfoItem::insert('http://www.eol.org/voc/table_of_contents#Barcode');
        $do_iis = $last_object->info_items();
        $this->assertTrue($ii == $do_iis[0]->id, 'BOLD should get a special TOC');
    }
    
    function testUnvettedUnpublished()
    {
        $resource = self::create_resource(array('vetted' => false, 'auto_publish' => false));
        self::harvest($resource);
        $last_object = DataObject::last();
        $this->assertTrue($last_object->published == 0, 'Should not get published');
        $this->assertTrue($last_object->vetted_id == Vetted::insert('unknown'), 'Should not be vetted');
    }
    
    function testSetAutoPublish()
    {
        $resource = self::create_resource(array('title' => 'BOLD Systems Resource', 'auto_publish' => false));
        $this->assertTrue($resource->auto_publish == 0);
        
        $resource->set_autopublish(true);
        $resource = new Resource($resource->id);
        $this->assertTrue($resource->auto_publish == 1);
    }
    
    function testPublishing()
    {
        $resource = self::create_resource(array('title' => 'BOLD Systems Resource', 'auto_publish' => false));
        self::harvest($resource);
        $last_object = DataObject::last();
        $this->assertTrue($last_object->published == 0);
        $this->assertTrue($last_object->visibility_id == Visibility::insert('preview'));
        
        self::harvest($resource);
        $last_object = DataObject::last();
        $this->assertTrue($last_object->published == 0);
        $this->assertTrue($last_object->visibility_id == Visibility::insert('preview'));
        
        $resource->set_autopublish(true);
        $resource = new Resource($resource->id);
        self::harvest($resource);
        $last_object = DataObject::last();
        $this->assertTrue($last_object->published == 1);
        $this->assertTrue($last_object->visibility_id == Visibility::insert('visible'));
        
        self::harvest($resource);
        $last_object = DataObject::last();
        $this->assertTrue($last_object->published == 1);
        $this->assertTrue($last_object->visibility_id == Visibility::insert('visible'));
    }
    
    
    
    
    
    
    
    
    
    
    
    
    function check_content_after_harvesting($resource)
    {
        $result = $GLOBALS['db_connection']->query("SELECT 1 FROM hierarchy_entries LIMIT 1");
        $this->assertTrue($result->num_rows > 0, 'should be hierarchy_entries after harvesting');
        $result = $GLOBALS['db_connection']->query("SELECT 1 FROM refs LIMIT 1");
        $this->assertTrue($result->num_rows > 0, 'should be refs after harvesting');
        $result = $GLOBALS['db_connection']->query("SELECT 1 FROM hierarchy_entries_refs LIMIT 1");
        $this->assertTrue($result->num_rows > 0, 'should be hierarchy_entries_refs after harvesting');
        $result = $GLOBALS['db_connection']->query("SELECT 1 FROM data_objects_hierarchy_entries LIMIT 1");
        $this->assertTrue($result->num_rows > 0, 'should be data_objects_hierarchy_entries after harvesting');
        $result = $GLOBALS['db_connection']->query("SELECT 1 FROM synonyms LIMIT 1");
        $this->assertTrue($result->num_rows > 0, 'should be synonyms after harvesting');
        $result = $GLOBALS['db_connection']->query("SELECT 1 FROM top_images LIMIT 1");
        $this->assertTrue($result->num_rows > 0, 'should be top_images after harvesting');
        $result = $GLOBALS['db_connection']->query("SELECT 1 FROM top_concept_images LIMIT 1");
        $this->assertTrue($result->num_rows > 0, 'should be top_concept_images after harvesting');
        $result = $GLOBALS['db_connection']->query("SELECT 1 FROM hierarchies_content LIMIT 1");
        $this->assertTrue($result->num_rows > 0, 'should be hierarchies_content after harvesting');
        $result = $GLOBALS['db_connection']->query("SELECT 1 FROM taxon_concept_content LIMIT 1");
        $this->assertTrue($result->num_rows > 0, 'should be taxon_concept_content after harvesting');
        $result = $GLOBALS['db_connection']->query("SELECT 1 FROM taxon_concept_names LIMIT 1");
        $this->assertTrue($result->num_rows > 0, 'should be taxon_concept_names after harvesting');
        $result = $GLOBALS['db_connection']->query("SELECT 1 FROM feed_data_objects LIMIT 1");
        $this->assertTrue($result->num_rows > 0, 'should be feed_data_objects after harvesting');
        $result = $GLOBALS['db_connection']->query("SELECT 1 FROM data_objects_taxon_concepts LIMIT 1");
        $this->assertTrue($result->num_rows > 0, 'should be data_objects_taxon_concepts after harvesting');
        $result = $GLOBALS['db_connection']->query("SELECT 1 FROM data_types_taxon_concepts LIMIT 1");
        $this->assertTrue($result->num_rows > 0, 'should be data_types_taxon_concepts after harvesting');
        $result = $GLOBALS['db_connection']->query("SELECT 1 FROM data_objects_table_of_contents LIMIT 1");
        $this->assertTrue($result->num_rows > 0, 'should be data_objects_table_of_contents after harvesting');
        $result = $GLOBALS['db_connection']->query("SELECT 1 FROM random_hierarchy_images LIMIT 1");
        $this->assertTrue($result->num_rows > 0, 'should be random_hierarchy_images after harvesting');
        
        // make sure we have harvest events
        $events = HarvestEvent::all();
        $this->assertTrue(count($events) == 1, 'There should be an event after harvesting');
        $this->assertTrue($events[0]->resource_id == $resource->id, 'It should belong to the resource');
        $this->assertTrue($events[0]->began_at != null, 'It should have a begin date');
        $this->assertTrue($events[0]->completed_at != null, 'It should have a completed date');
        $this->assertTrue($events[0]->published_at != null, 'It should have a published date');
        
        // make sure we have data objects
        $objects = DataObject::all();
        $this->assertTrue(count($objects) > 0, 'There should be objects after harvesting');
        $this->assertTrue($objects[0]->published == 1, 'Objects should be published');
        $this->assertTrue($objects[0]->visibility_id == Visibility::insert('visible'), 'Objects should be visible');
        $this->assertTrue($objects[0]->vetted_id == Vetted::insert('trusted'), 'Objects should be in vetted "trusted"');
        $this->assertTrue($objects[0]->data_rating == 2.5, 'Objects should have the default rating');
        
        // make sure we have hierarchy entries
        $all_entries = HierarchyEntry::all();
        $this->assertTrue(count($all_entries) > 0, 'There should be hierarchy entries after harvesting');
        
        // Reading the resource XML and checking every field
        $reader = new XMLReader();
        $reader->open($resource->resource_file_path());
        $i = 0;
        $j = 0;
        while(@$reader->read())
        {
            if($reader->nodeType == XMLReader::ELEMENT && $reader->name == "taxon")
            {
                $taxon_xml = $reader->readOuterXML();
                $t = simplexml_load_string($taxon_xml, null, LIBXML_NOCDATA);
                
                $t_dc = $t->children("http://purl.org/dc/elements/1.1/");
                $t_dcterms = $t->children("http://purl.org/dc/terms/");
                $t_dwc = $t->children("http://rs.tdwg.org/dwc/dwcore/");
                
                $taxon_parameters = array();
                $taxon_parameters["identifier"] = Functions::import_decode($t_dc->identifier);
                $taxon_parameters["source_url"] = Functions::import_decode($t_dc->source);
                // $taxon_parameters["taxon_kingdom"] = Functions::import_decode($t_dwc->Kingdom);
                // $taxon_parameters["taxon_phylum"] = Functions::import_decode($t_dwc->Phylum);
                // $taxon_parameters["taxon_class"] = Functions::import_decode($t_dwc->Class);
                // $taxon_parameters["taxon_order"] = Functions::import_decode($t_dwc->Order);
                // $taxon_parameters["taxon_family"] = Functions::import_decode($t_dwc->Family);
                // $taxon_parameters["taxon_genus"] = Functions::import_decode($t_dwc->Genus);
                $taxon_parameters["scientific_name"] = Functions::import_decode($t_dwc->ScientificName);
                $taxon_parameters["name_id"] = Name::insert(Functions::import_decode($t_dwc->ScientificName));
                
                $hierarchy_entry = null;
                $result = $GLOBALS['db_connection']->query("SELECT id FROM hierarchy_entries WHERE name_id=".$taxon_parameters["name_id"]." AND identifier='". $GLOBALS['db_connection']->escape($taxon_parameters["identifier"]) ."'");
                if($result && $row=$result->fetch_assoc())
                {
                    $hierarchy_entry = new HierarchyEntry($row['id']);
                }
                $this->assertTrue($hierarchy_entry != null, 'should be able to find the entry for this taxon');
                
                $references = array();
                foreach($t->reference as $r)
                {
                    $references[] = Functions::import_decode((string) $r);
                }
                $taxa_refs = $hierarchy_entry->references();
                $this->assertTrue(count($references) == count($taxa_refs), 'references should be the same');
                foreach($hierarchy_entry->references() as $ref)
                {
                    $this->assertTrue(in_array($ref->full_reference, $references), 'references should be the same');
                }
                
                foreach($t->dataObject as $d)
                {
                    $d_dc = $d->children("http://purl.org/dc/elements/1.1/");
                    $d_dcterms = $d->children("http://purl.org/dc/terms/");
                    $d_geo = $d->children("http://www.w3.org/2003/01/geo/wgs84_pos#");
                    
                    $data_object_parameters = array();
                    //$data_object_parameters["identifier"] = Functions::import_decode($d_dc->identifier);
                    $data_object_parameters["data_type_id"] = DataType::insert(Functions::import_decode($d->dataType));
                    $data_object_parameters["mime_type_id"] = MimeType::insert(Functions::import_decode($d->mimeType));
                    $data_object_parameters["object_created_at"] = Functions::import_decode($d_dcterms->created);
                    $data_object_parameters["object_modified_at"] = Functions::import_decode($d_dcterms->modified);
                    $data_object_parameters["object_title"] = Functions::import_decode($d_dc->title, 0, 0);
                    $data_object_parameters["language_id"] = Language::insert(Functions::import_decode($d_dc->language));
                    $data_object_parameters["license_id"] = License::insert(Functions::import_decode($d->license));
                    $data_object_parameters["rights_statement"] = Functions::import_decode($d_dc->rights, 0, 0);
                    $data_object_parameters["rights_holder"] = Functions::import_decode($d_dcterms->rightsHolder, 0, 0);
                    $data_object_parameters["bibliographic_citation"] = Functions::import_decode($d_dcterms->bibliographicCitation, 0, 0);
                    $data_object_parameters["source_url"] = Functions::import_decode($d_dc->source);
                    $data_object_parameters["description"] = Functions::import_decode($d_dc->description, 0, 0);
                    $data_object_parameters["object_url"] = Functions::import_decode($d->mediaURL);
                    $data_object_parameters["thumbnail_url"] = Functions::import_decode($d->thumbnailURL);
                    $data_object_parameters["location"] = Functions::import_decode($d->location, 0, 0);
                    
                    $this->assertTrue($objects[$j]->published == 1, "DataObject ($j) should be published");
                    $this->assertTrue($objects[$j]->vetted_id == Vetted::insert('trusted'), "DataObject ($j) should be vetted");                    
                    foreach($data_object_parameters as $key => $value)
                    {
                        $test_value = $objects[$j]->$key;
                        // dates have a default value in the DB
                        if($test_value == "0000-00-00 00:00:00") $test_value = "";
                        if($value == null) $value = 0;
                        $this->assertTrue($value == $test_value, "DataObject ($j) $key should be correct");
                    }
                    $j++;
                }
                $i++;
            }
        }
    }
    
    
    
    
    
    
    private static function harvest($resource)
    {
        // set to force harvest and harvest again
        shell_exec(PHP_BIN_PATH . DOC_ROOT ."rake_tasks/force_harvest.php -id $resource->id ENV_NAME=test");
        $resource->harvest(false);
        shell_exec(PHP_BIN_PATH . DOC_ROOT ."rake_tasks/publish_resources.php ENV_NAME=test");
        shell_exec(PHP_BIN_PATH . DOC_ROOT ."rake_tasks/table_of_contents.php ENV_NAME=test");
    }
    
    private static function create_resource($args = array())
    {
        if(!isset($args['auto_publish'])) $args['auto_publish'] = 1;
        if(!isset($args['vetted'])) $args['vetted'] = 1;
        if(!isset($args['title'])) $args['title'] = 'Test Resource';
        if(!isset($args['file_path'])) $args['file_path'] = DOC_ROOT . 'tests/fixtures/files/test_resource.xml';
        if(!isset($args['dwc_archive_url'])) $args['dwc_archive_url'] = '';
        
        // create the test resource
        $agent_id = Agent::insert(array('full_name' => 'Test Content Partner'));
        $agent = new Agent($agent_id);
        
        // create the content partner
        $content_partner_id = ContentPartner::insert(array('id' => 101010101, 'agent_id' => $agent_id, 'auto_publish' => $args['auto_publish'], 'vetted' => $args['vetted']));
        
        // create the resource
        $attr = array(  'accesspoint_url'       => $args['file_path'],
                        'service_type_id'       => ServiceType::insert('EOL Transfer Schema'),
                        'refresh_period_hours'  => 1,
                        'auto_publish'          => $args['auto_publish'],
                        'vetted'                => $args['vetted'],
                        'title'                 => $args['title'],
                        'dwc_archive_url'       => $args['dwc_archive_url'],
                        'resource_status_id'    => ResourceStatus::insert('Validated'));
        $resource_id = Resource::insert($attr);
        $agent->add_resouce($resource_id, 'Data Supplier');
        $resource = new Resource($resource_id);
        
        copy($resource->accesspoint_url, $resource->resource_file_path());
        return $resource;
    }
    
    
}

?>