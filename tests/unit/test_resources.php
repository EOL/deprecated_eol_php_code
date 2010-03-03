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
        $attr = array(  'accesspoint_url'       => WEB_ROOT . 'tests/fixtures/files/test_resource.xml',
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
        
        copy(DOC_ROOT . "tests/fixtures/files/test_resource.xml", $resource->resource_file_path());
        
        $this->assertTrue(count(HarvestEvent::all()) == 0, 'There shouldnt be any events to begin with');
        $this->assertTrue(count(DataObject::all()) == 0, 'There shouldnt be any data objects to begin with');
        $this->assertTrue(count(Taxon::all()) == 0, 'There shouldnt be any taxa to begin with');
        
        // harvest the resource
        $resource->harvest();
        
        // make sure we have harvest events
        $events = HarvestEvent::all();
        $this->assertTrue(count($events) == 1, 'There should be an event after harvesting');
        $this->assertTrue($events[0]->resource_id == $resource->id, 'It should belong to the resource');
        $this->assertTrue($events[0]->began_at != null, 'It should have a begin date');
        $this->assertTrue($events[0]->completed_at != null, 'It should have a completed date');
        $this->assertTrue($events[0]->published_at == null, 'It should not have a published date');
        
        // make sure we have data objects
        $objects = DataObject::all();
        $this->assertTrue(count($objects) > 0, 'There should be objects after harvesting');
        $this->assertTrue($objects[0]->published == 0, 'Objects should not be published');
        $this->assertTrue($objects[0]->visibility_id == Visibility::insert('preview'), 'Objects should be in preview mode');
        $this->assertTrue($objects[0]->vetted_id == Vetted::insert('unknown'), 'Objects should be in vetted "unknown"');
        $this->assertTrue($objects[0]->data_rating == 2.5, 'Objects should have the default rating');
        
        // make sure we have taxa
        $taxa = Taxon::all();
        $this->assertTrue(count($taxa) > 0, 'There should be taxa after harvesting');
        
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
                //$taxon_parameters["identifier"] = Functions::import_decode($t_dc->identifier);
                //$taxon_parameters["source_url"] = Functions::import_decode($t_dc->source);
                $taxon_parameters["taxon_kingdom"] = Functions::import_decode($t_dwc->Kingdom);
                $taxon_parameters["taxon_phylum"] = Functions::import_decode($t_dwc->Phylum);
                $taxon_parameters["taxon_class"] = Functions::import_decode($t_dwc->Class);
                $taxon_parameters["taxon_order"] = Functions::import_decode($t_dwc->Order);
                $taxon_parameters["taxon_family"] = Functions::import_decode($t_dwc->Family);
                //$taxon_parameters["taxon_genus"] = Functions::import_decode($t_dwc->Genus);
                $taxon_parameters["scientific_name"] = Functions::import_decode($t_dwc->ScientificName);
                $taxon_parameters["name_id"] = Name::insert(Functions::import_decode($t_dwc->ScientificName));
                //$taxon_parameters["taxon_created_at"] = trim($t_dcterms->created);
                //$taxon_parameters["taxon_modified_at"] = trim($t_dcterms->modified);
                
                foreach($taxon_parameters as $key => $value)
                {
                    $test_value = $taxa[$i]->$key;
                    // dates have a default value in the DB
                    if($test_value == "0000-00-00 00:00:00") $test_value = "";
                    $this->assertTrue($value == $test_value, "Taxon ($i) $key should be correct");
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
        
        unlink(CONTENT_RESOURCE_LOCAL_PATH . $resource->id .".xml");
    }
}

?>