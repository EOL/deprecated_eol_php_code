<?php
namespace php_active_record;

class test_resources extends SimpletestUnitBase
{
    function testHarvesting()
    {
        $solr_api = new SolrAPI(SOLR_SERVER, 'hierarchy_entries');
        $solr_api->delete_all_documents();
        $solr_api = new SolrAPI(SOLR_SERVER, 'site_search');
        $solr_api->delete_all_documents();
        $solr_api = new SolrAPI(SOLR_SERVER, 'data_objects');
        $solr_api->delete_all_documents();
        $solr_api = new SolrAPI(SOLR_SERVER, 'collection_items');
        $solr_api->delete_all_documents();
        $solr_api = new SolrAPI(SOLR_SERVER, 'hierarchy_entry_relationship');
        $solr_api->delete_all_documents();
        
        
        
        $toc = TableOfContent::find_or_create_by_translated_label('Overview');
        $ii = InfoItem::find_or_create_by_translated_label('DiagnosticDescription', array('schema_value' => 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#DiagnosticDescription', 'toc_id' => $toc->id));
        $resource = self::create_resource();
        
        $this->assertTrue(count(HarvestEvent::all()) == 0, 'There shouldnt be any events to begin with');
        $this->assertTrue(count(DataObject::all()) == 0, 'There shouldnt be any data objects to begin with');
        $this->assertTrue(count(HierarchyEntry::all()) == 0, 'There shouldnt be any hierarchy entries to begin with');
        $this->assertTrue(count(TaxonConceptName::all()) == 0, 'There shouldnt be any taxon concept names to begin with');
        $this->assertTrue(count(HarvestProcessLog::all()) == 0, 'There shouldnt be any harvest logs');
        
        $result = $GLOBALS['db_connection']->query("SELECT 1 FROM top_images LIMIT 1");
        $this->assertTrue($result->num_rows == 0, 'shouldnt be any top images');
        
        // harvest the resource and run all the denormalized tasks to test them
        passthru(PHP_BIN_PATH.DOC_ROOT."rake_tasks/harvest_requested.php -id $resource->id ENV_NAME=test");
        self::harvest($resource);
        
        // $result = $GLOBALS['db_connection']->query("SELECT 1 FROM top_images LIMIT 1");
        // $this->assertTrue($result->num_rows > 0, 'should be top images after harvesting and before denormalizing');
        // $result = $GLOBALS['db_connection']->query("SELECT 1 FROM top_concept_images LIMIT 1");
        // $this->assertTrue($result->num_rows > 0, 'should be top concept images after harvesting and before denormalizing');
        
        passthru(PHP_BIN_PATH.DOC_ROOT."rake_tasks/denormalize_tables.php ENV_NAME=test");
        
        $this->check_content_after_harvesting($resource);
        
        self::harvest($resource);
        
        @unlink(CONTENT_RESOURCE_LOCAL_PATH . $resource->id .".xml");
    }
    
    function testResourceDefaults()
    {
        $resource = self::create_resource(array('language_id' => 0));
        self::harvest($resource);
        $last_object = DataObject::last();
        $this->assertTrue($last_object->language_id == 0, 'Languages by default should be null');
        
        $resource = self::create_resource(array('language_id' => Language::default_language()->id));
        self::harvest($resource);
        $last_object = DataObject::last();
        $this->assertTrue($last_object->language_id == Language::default_language()->id, 'Languages by default should be null');
    }
    
    function testIUCNDataType()
    {
        $resource = self::create_resource(array('title' => 'IUCN Red List'));
        self::harvest($resource);
        $last_object = DataObject::last();
        $this->assertTrue($last_object->data_type_id == DataType::iucn()->id, 'IUCN should get a special data type');
    }
    
    function testWikipediaInfoItem()
    {
        $resource = self::create_resource(array('title' => 'Wikipedia'));
        self::harvest($resource);
        $last_object = DataObject::last();
        $ii = InfoItem::find_or_create_by_schema_value('http://www.eol.org/voc/table_of_contents#Wikipedia');
        $do_iis = $last_object->info_items;
        $this->assertTrue($ii->id == $do_iis[0]->id, 'Wikipedia should get a special TOC');
    }
    
    function testBOLDInfoItem()
    {
        $resource = self::create_resource(array('title' => 'BOLD Systems Resource'));
        self::harvest($resource);
        $last_object = DataObject::last();
        $ii = InfoItem::find_or_create_by_schema_value('http://www.eol.org/voc/table_of_contents#Barcode');
        $do_iis = $last_object->info_items;
        $this->assertTrue($ii->id == $do_iis[0]->id, 'BOLD should get a special TOC');
    }
    
    function testUnvettedUnpublished()
    {
        $resource = self::create_resource(array('vetted' => false, 'auto_publish' => false));
        self::harvest($resource);
        $last_object = DataObject::last();
        $this->assertTrue($last_object->published == 0, 'Should not get published');
        $this->assertTrue($last_object->best_vetted() == Vetted::unknown(), 'Should not be vetted');
    }
    
    // function testResourceWithDWCA()
    // {
    //     $resource = self::create_resource(array('vetted' => false, 'auto_publish' => false, 'dwc_archive_url' => DOC_ROOT . 'tests/fixtures/files/dwca.tar.gz'));
    //     $this->assertTrue($resource->hierarchy_id == 0, 'Should not start with hierarchy');
    //     $this->assertTrue($resource->dwc_hierarchy_id == 0, 'Should not start with a DWC hierarchy');
    //     
    //     self::harvest($resource);
    //     
    //     $this->assertTrue($resource->hierarchy_id != 0, 'Should have a hierarchy');
    //     $this->assertTrue($resource->dwc_hierarchy_id != 0, 'Should have a DWC hierarchy');
    //     
    //     $first_dwc_hierarchy_id = $resource->dwc_hierarchy_id;
    //     $first_dwc_hierarchy = Hierarchy::find($first_dwc_hierarchy_id);
    //     $this->assertTrue($first_dwc_hierarchy->id, 'Should have a DWC hierarchy');
    //     
    //     self::harvest($resource);
    //     
    //     $first_dwc_hierarchy = Hierarchy::find($first_dwc_hierarchy_id);
    //     $this->assertFalse(isset($first_dwc_hierarchy->id), 'First DWC hierarchy should be gone');
    // }
    
    function testSetAutoPublish()
    {
        $resource = self::create_resource(array('title' => 'BOLD Systems Resource', 'auto_publish' => false));
        $this->assertTrue($resource->auto_publish == 0);
        
        $resource->set_autopublish(true);
        $resource = Resource::find($resource->id);
        $this->assertTrue($resource->auto_publish == 1);
    }
    
    function testPublishing()
    {
        $resource = self::create_resource(array('title' => 'BOLD Systems Resource', 'auto_publish' => false));
        self::harvest($resource);
        $last_object = DataObject::last();
        $this->assertTrue($last_object->published == 0);
        $this->assertTrue($last_object->best_visibility() == Visibility::preview());
        
        // checking to make sure changed source_urls create new entries
        $first_entry_no_source = HierarchyEntry::find(HierarchyEntry::find_last_by_identifier('98h34jgbksfbg'));
        $this->assertTrue($first_entry_no_source->source_url == '');
        $this->assertTrue($first_entry_no_source->visibility_id == Visibility::preview()->id);
        
        $first_entry_with_source = HierarchyEntry::find(HierarchyEntry::find_last_by_identifier('http://mushroomobserver.org/name/show_name/14971'));
        $this->assertTrue($first_entry_with_source->source_url == 'http://mushroomobserver.org/name/show_name/14971');
        $this->assertTrue($first_entry_with_source->published == 0);
        $this->assertTrue($first_entry_with_source->visibility_id == Visibility::preview()->id);
        
        // changing the URL AND copying the new file to ../resources/$ID.xml
        $resource->set_accesspoint_url(DOC_ROOT . 'tests/fixtures/files/test_resource_reharvest.xml');
        copy($resource->accesspoint_url, $resource->resource_file_path());
        
        self::harvest($resource);
        $last_object = DataObject::last();
        $this->assertTrue($last_object->published == 0);
        $this->assertTrue($last_object->best_visibility() == Visibility::preview());
        
        // checking to make sure changed source_urls create new entries
        $second_entry_no_source = HierarchyEntry::find(HierarchyEntry::find_last_by_identifier('98h34jgbksfbg'));
        $this->assertTrue($second_entry_no_source->source_url == 'http://www.example.com/taxa/98h34jgbksfbg');
        // when a source URL is added to a record with no source - the record is updated
        $this->assertTrue($second_entry_no_source->id == $first_entry_no_source->id);
        
        $second_entry_with_source = HierarchyEntry::find(HierarchyEntry::find_last_by_identifier('http://mushroomobserver.org/name/show_name/14971'));
        $this->assertTrue($second_entry_with_source->source_url == 'http://mushroomobserver.org/');
        // ... but we do create a new record if the source existed before and is changed
        $this->assertTrue($second_entry_with_source->id != $first_entry_with_source->id);
        $this->assertTrue($second_entry_with_source->guid == $first_entry_with_source->guid);
        
        $resource->set_autopublish(true);
        $resource = Resource::find($resource->id);
        self::harvest($resource);
        $last_object = DataObject::last();
        $this->assertTrue($last_object->published == 1);
        $this->assertTrue($last_object->best_visibility() == Visibility::visible());
        
        self::harvest($resource);
        $last_object = DataObject::last();
        $this->assertTrue($last_object->published == 1);
        $this->assertTrue($last_object->best_visibility() == Visibility::visible());
        
        // making sure entries that are orphaned are not in preview mode
        $last_entry_no_source = HierarchyEntry::find(HierarchyEntry::find_last_by_identifier('98h34jgbksfbg'));
        $this->assertTrue($last_entry_no_source->published == 1);
        $this->assertTrue($last_entry_no_source->visibility_id == Visibility::visible()->id);
        
        $last_entry_with_source = HierarchyEntry::find(HierarchyEntry::find_last_by_identifier('http://mushroomobserver.org/name/show_name/14971'));
        $this->assertTrue($last_entry_with_source->published == 1);
        $this->assertTrue($last_entry_with_source->visibility_id == Visibility::visible()->id);
        
        // refresh the original no-source object (the one that is orphaned)
        $original_entry = HierarchyEntry::find($first_entry_with_source->id);
        $this->assertTrue($original_entry->visibility_id == Visibility::invisible()->id);
        $this->assertTrue($original_entry->published == 0);
    }
    
    
    
    
    
    
    
    
    
    
    
    
    function check_content_after_harvesting($resource)
    {
        $result = $GLOBALS['db_connection']->query("SELECT 1 FROM hierarchy_entries LIMIT 1");
        $this->assertTrue(HierarchyEntry::first(), 'should be hierarchy_entries after harvesting');
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
        $result = $GLOBALS['db_connection']->query("SELECT 1 FROM taxon_concept_names LIMIT 1");
        $this->assertTrue($result->num_rows > 0, 'should be taxon_concept_names after harvesting');
        $result = $GLOBALS['db_connection']->query("SELECT 1 FROM data_objects_taxon_concepts LIMIT 1");
        $this->assertTrue($result->num_rows > 0, 'should be data_objects_taxon_concepts after harvesting');
        $result = $GLOBALS['db_connection']->query("SELECT 1 FROM data_objects_table_of_contents LIMIT 1");
        $this->assertTrue($result->num_rows > 0, 'should be data_objects_table_of_contents after harvesting');
        $result = $GLOBALS['db_connection']->query("SELECT 1 FROM data_objects_info_items LIMIT 1");
        $this->assertTrue($result->num_rows > 0, 'should be data_objects_info_items after harvesting');
        // $result = $GLOBALS['db_connection']->query("SELECT 1 FROM random_hierarchy_images LIMIT 1");
        // $this->assertTrue($result->num_rows > 0, 'should be random_hierarchy_images after harvesting');
        $result = $GLOBALS['db_connection']->query("SELECT 1 FROM harvest_process_logs LIMIT 1");
        $this->assertTrue($result->num_rows > 0, 'should be harvest_process_logs after harvesting');
        
        // make sure we have harvest events
        $events = HarvestEvent::all();
        $this->assertTrue(count($events) == 1, 'There should be an event after harvesting');
        $this->assertTrue($events[0]->resource_id == $resource->id, 'It should belong to the resource');
        $this->assertTrue($events[0]->publish == 1, 'It should be published');
        $this->assertTrue($events[0]->published_at, 'It should be published');
        $this->assertTrue($events[0]->began_at != null, 'It should have a begin date');
        $this->assertTrue($events[0]->completed_at != null, 'It should have a completed date');
        $this->assertTrue($events[0]->published_at != null, 'It should have a published date');
        
        // make sure we have data objects
        $objects = DataObject::all();
        $this->assertTrue(count($objects) > 0, 'There should be objects after harvesting');
        $this->assertTrue($objects[0]->published == 1, 'Objects should be published');
        $this->assertTrue($objects[0]->data_rating == 2.5, 'Objects should have the default rating');
        
        // make sure we have hierarchy entries
        $all_entries = HierarchyEntry::all();
        $this->assertTrue(count($all_entries) > 0, 'There should be hierarchy entries after harvesting');
        
        // Reading the resource XML and checking every field
        $reader = new \XMLReader();
        $reader->open($resource->resource_file_path());
        $i = 0;
        $j = 0;
        while(@$reader->read())
        {
            if($reader->nodeType == \XMLReader::ELEMENT && $reader->name == "taxon")
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
                $taxon_parameters["name_id"] = Name::find_or_create_by_string(Functions::import_decode($t_dwc->ScientificName))->id;
                
                $hierarchy_entry = null;
                $result = $GLOBALS['db_connection']->query("SELECT id FROM hierarchy_entries WHERE name_id=".$taxon_parameters["name_id"]." AND identifier='". $GLOBALS['db_connection']->escape($taxon_parameters["identifier"]) ."'");
                if($result && $row=$result->fetch_assoc())
                {
                    $hierarchy_entry = HierarchyEntry::find($row['id']);
                }
                $this->assertTrue($hierarchy_entry != null, 'should be able to find the entry for this taxon');
                
                $references = array();
                foreach($t->reference as $r)
                {
                    $references[] = Functions::import_decode((string) $r, 0, 0);
                }
                $taxa_refs = $hierarchy_entry->references;
                $this->assertTrue(count($references) == count($taxa_refs), 'references should be the same');
                foreach($hierarchy_entry->references as $ref)
                {
                    $this->assertTrue(in_array($ref->full_reference, $references), 'reference bodies should be the same');
                }
                
                foreach($t->dataObject as $d)
                {
                    $d_dc = $d->children("http://purl.org/dc/elements/1.1/");
                    $d_dcterms = $d->children("http://purl.org/dc/terms/");
                    $d_geo = $d->children("http://www.w3.org/2003/01/geo/wgs84_pos#");
                    
                    if($d->dataType == 'http://purl.org/dc/dcmitype/MovingImage') $d->dataType = 'YouTube';
                    $data_object_parameters = array();
                    $data_object_parameters["identifier"] = Functions::import_decode($d_dc->identifier);
                    $data_object_parameters["data_type_id"] = DataType::find_or_create_by_schema_value(Functions::import_decode($d->dataType))->id;
                    $data_object_parameters["mime_type_id"] = @MimeType::find_or_create_by_translated_label(Functions::import_decode($d->mimeType))->id ?: 0;
                    $data_object_parameters["object_created_at"] = Functions::import_decode($d_dcterms->created);
                    $data_object_parameters["object_modified_at"] = Functions::import_decode($d_dcterms->modified);
                    $data_object_parameters["object_title"] = Functions::import_decode($d_dc->title, 0, 0);
                    $data_object_parameters["language_id"] = @Language::find_or_create_for_parser(Functions::import_decode($d_dc->language))->id ?: 0;
                    $data_object_parameters["license_id"] = License::find_or_create_for_parser(Functions::import_decode($d->license))->id;
                    $data_object_parameters["rights_statement"] = Functions::import_decode($d_dc->rights, 0, 0);
                    $data_object_parameters["rights_holder"] = Functions::import_decode($d_dcterms->rightsHolder, 0, 0);
                    $data_object_parameters["bibliographic_citation"] = Functions::import_decode($d_dcterms->bibliographicCitation, 0, 0);
                    $data_object_parameters["source_url"] = Functions::import_decode($d_dc->source);
                    $data_object_parameters["description"] = Functions::import_decode($d_dc->description, 0, 0);
                    $data_object_parameters["object_url"] = Functions::import_decode($d->mediaURL);
                    $data_object_parameters["thumbnail_url"] = Functions::import_decode($d->thumbnailURL);
                    $data_object_parameters["location"] = Functions::import_decode($d->location, 0, 0);
                    
                    $this->assertTrue($objects[$j]->published == 1, "DataObject ($j) should be published");
                    $this->assertTrue($objects[$j]->best_vetted() == Vetted::trusted(), "DataObject ($j) should be vetted");
                    foreach($data_object_parameters as $key => $value)
                    {
                        $test_value = $objects[$j]->$key;
                        // dates have a default value in the DB
                        if($test_value == "0000-00-00 00:00:00") $test_value = "";
                        if($value == null) $value = 0;
                        $this->assertTrue($value == $test_value, "DataObject ($j) $key should be correct ($value != $test_value)");
                    }
                    $j++;
                }
                $i++;
            }
        }
    }
    
    
    
    
    
    
    private static function harvest($resource)
    {
        // set to Harvest Requested - this will only change the status id
        passthru(PHP_BIN_PATH . DOC_ROOT ."rake_tasks/harvest_requested.php -id $resource->id ENV_NAME=test");
        
        // now harvest the resource
        $resource->harvest(false);
        passthru(PHP_BIN_PATH . DOC_ROOT ."rake_tasks/publish_resources.php ENV_NAME=test");
        passthru(PHP_BIN_PATH . DOC_ROOT ."rake_tasks/table_of_contents.php ENV_NAME=test");
        Cache::flush();
    }
    
    private static function create_resource($args = array())
    {
        if(!isset($args['auto_publish'])) $args['auto_publish'] = 1;
        if(!isset($args['vetted'])) $args['vetted'] = 1;
        if(!isset($args['title'])) $args['title'] = 'Test Resource';
        if(!isset($args['file_path'])) $args['file_path'] = DOC_ROOT . 'tests/fixtures/files/test_resource.xml';
        if(!isset($args['dwc_archive_url'])) $args['dwc_archive_url'] = '';
        if(!isset($args['language_id'])) $args['language_id'] = 0;
        
        // create the test resource
        $agent = Agent::find_or_create(array('full_name' => 'Test Content Partner'));
        $user = User::find_or_create(array('display_name' => 'Test Content Partner', 'agent_id' => $agent->id));
        
        // create the content partner
        $content_partner = ContentPartner::find_or_create(array('user_id' => $user->id));
        
        // create the resource
        $attr = array(  'content_partner_id'    => $content_partner->id,
                        'accesspoint_url'       => $args['file_path'],
                        'service_type'          => ServiceType::find_or_create_by_translated_label('EOL Transfer Schema'),
                        'refresh_period_hours'  => 1,
                        'auto_publish'          => $args['auto_publish'],
                        'vetted'                => $args['vetted'],
                        'title'                 => $args['title'],
                        'dwc_archive_url'       => $args['dwc_archive_url'],
                        'language_id'           => $args['language_id'],
                        'resource_status'       => ResourceStatus::validated());
        $resource = Resource::find_or_create($attr);
        
        copy($resource->accesspoint_url, $resource->resource_file_path());
        return $resource;
    }
    
    
}

?>