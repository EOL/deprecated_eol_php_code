<?php
namespace php_active_record;

class test_archive_ingest_reharvest extends SimpletestUnitBase
{
    function setUp()
    {
        parent::setUp();
        recursive_rmdir_contents(DOC_ROOT . "vendor/eol_content_schema_v2/extension_cache/");
        $this->archive_directory = CONTENT_RESOURCE_LOCAL_PATH . "/1/";
        if(!file_exists($this->archive_directory)) mkdir($this->archive_directory);
        $this->resource = self::create_resource();
        $this->prepare_data();
    }
    
    function tearDown()
    {
        // delete the contents of the created archive
        $files_in_archive = read_dir($this->archive_directory);
        foreach($files_in_archive as $file)
        {
            if(substr($file, 0, 1) == '.') continue;
            unlink($this->archive_directory . $file);
        }
        rmdir($this->archive_directory);
        unset($this->archive_builder);
        parent::tearDown();
    }
    
    function testReharvestTaxonReferences()
    {
        // no need to have media for this test
        $this->media = array();
        $this->taxa[0]->referenceID = "11";
        $this->taxa[1]->referenceID = "11";
        $this->build_resource();
        self::harvest($this->resource);
        $entry = HierarchyEntry::find_by_identifier($this->taxa[0]->taxonID);
        $refs = $entry->published_references();
        $this->assertEqual(count($refs), 1);
        $this->assertEqual($refs[0]->provider_mangaed_id, 11);
        $original_entry_id = $entry->id;
        
        $this->taxa[0]->referenceID = "11;22";
        $this->taxa[1]->referenceID = "";
        $this->build_resource();
        self::harvest($this->resource);
        $entry = HierarchyEntry::find_by_identifier($this->taxa[0]->taxonID);
        $refs = $entry->published_references();
        $this->assertEqual(count($refs), 2);
        $this->assertEqual($refs[0]->provider_mangaed_id, 11);
        $this->assertEqual($refs[1]->provider_mangaed_id, 22);
        $this->assertEqual($entry->id, $original_entry_id);
        
        $this->taxa[0]->referenceID = "33";
        $this->build_resource();
        self::harvest($this->resource);
        $entry = HierarchyEntry::find_by_identifier($this->taxa[0]->taxonID);
        $refs = $entry->published_references();
        $this->assertEqual(count($refs), 1);
        $this->assertEqual($refs[0]->provider_mangaed_id, 33);
        $this->assertEqual($entry->id, $original_entry_id);
    }
    
    function testReharvestTaxonVernacularNames()
    {
        // no need to have media for this test
        $this->media = array();
        $this->build_resource();
        self::harvest($this->resource);
        $entry = HierarchyEntry::find_by_identifier($this->taxa[0]->taxonID);
        $this->assertEqual(count($entry->synonyms), 2);
        $this->assertEqual($entry->synonyms[0]->name->string, $this->vernacular_names[0]->vernacularName);
        $this->assertEqual($entry->synonyms[1]->name->string, $this->vernacular_names[1]->vernacularName);
        $original_entry_id = $entry->id;
        
        $vernacular = new \eol_schema\VernacularName();
        $vernacular->taxonID = $this->taxa[0]->taxonID;
        $vernacular->vernacularName = "New Common Name";
        $vernacular->language = "en";
        $this->vernacular_names[] = $vernacular;
        $this->build_resource();
        self::harvest($this->resource);
        $entry = HierarchyEntry::find_by_identifier($this->taxa[0]->taxonID);
        $this->assertEqual(count($entry->synonyms), 3);
        $this->assertEqual($entry->synonyms[0]->name->string, $this->vernacular_names[0]->vernacularName);
        $this->assertEqual($entry->synonyms[1]->name->string, $this->vernacular_names[1]->vernacularName);
        $this->assertEqual($entry->synonyms[2]->name->string, $vernacular->vernacularName);
        $this->assertEqual($entry->id, $original_entry_id);
        
        unset($this->vernacular_names[0]);
        unset($this->vernacular_names[1]);
        $this->build_resource();
        self::harvest($this->resource);
        $entry = HierarchyEntry::find_by_identifier($this->taxa[0]->taxonID);
        $this->assertEqual(count($entry->synonyms), 1);
        $this->assertEqual($entry->synonyms[0]->name->string, $vernacular->vernacularName);
        $this->assertEqual($entry->id, $original_entry_id);
        
        $this->vernacular_names = array();
        $this->build_resource();
        self::harvest($this->resource);
        $entry = HierarchyEntry::find_by_identifier($this->taxa[0]->taxonID);
        $this->assertEqual(count($entry->synonyms), 0);
    }
    
    function testReharvestDataObjectReferences()
    {
        // no need to have anything more than the first text object for this test
        $this->media = array($this->media[0]);
        $this->media[0]->referenceID = "11";
        $this->build_resource();
        self::harvest($this->resource);
        $object = DataObject::find_by_identifier($this->media[0]->identifier);
        $refs = $object->published_references();
        $this->assertEqual(count($refs), 1);
        $this->assertEqual($refs[0]->provider_mangaed_id, 11);
        $original_object_id = $object->id;
        
        $this->media[0]->referenceID = "11;22";
        $this->build_resource();
        self::harvest($this->resource);
        $object = DataObject::find_by_identifier($this->media[0]->identifier);
        $refs = $object->published_references();
        $this->assertEqual(count($refs), 2);
        $this->assertEqual($refs[0]->provider_mangaed_id, 11);
        $this->assertEqual($refs[1]->provider_mangaed_id, 22);
        $this->assertEqual($object->id, $original_object_id);
        
        $this->media[0]->referenceID = "33";
        $this->build_resource();
        self::harvest($this->resource);
        $object = DataObject::find_by_identifier($this->media[0]->identifier);
        $refs = $object->published_references();
        $this->assertEqual(count($refs), 1);
        $this->assertEqual($refs[0]->provider_mangaed_id, 33);
        $this->assertEqual($object->id, $original_object_id);
    }
    
    function testReharvestDataObjectAgents()
    {
        // no need to have anything more than the first text object for this test
        $this->media = array($this->media[0]);
        $this->media[0]->agentID = "";
        $this->media[0]->creator = "first creator; second creator";
        $this->media[0]->contributor = "";
        $this->media[0]->publisher = "";
        $this->build_resource();
        self::harvest($this->resource);
        $object = DataObject::find_by_identifier($this->media[0]->identifier);
        $ado = $object->agents_data_objects;
        $this->assertEqual(count($ado), 2);
        $this->assertEqual($ado[0]->agent->full_name, 'first creator');
        $this->assertEqual($ado[1]->agent->full_name, 'second creator');
        $original_object_id = $object->id;
        
        $this->media[0]->creator = "third creator; first creator; second creator";
        $this->build_resource();
        self::harvest($this->resource);
        $object = DataObject::find_by_identifier($this->media[0]->identifier);
        $ado = $object->agents_data_objects;
        $this->assertEqual(count($ado), 3);
        $this->assertEqual($ado[0]->agent->full_name, 'first creator');
        $this->assertEqual($ado[1]->agent->full_name, 'second creator');
        $this->assertEqual($ado[2]->agent->full_name, 'third creator');
        $this->assertEqual($object->id, $original_object_id);
        
        $this->media[0]->creator = "new creator";
        $this->build_resource();
        self::harvest($this->resource);
        $object = DataObject::find_by_identifier($this->media[0]->identifier);
        $ado = $object->agents_data_objects;
        $this->assertEqual(count($ado), 1);
        $this->assertEqual($ado[0]->agent->full_name, 'new creator');
        $this->assertEqual($object->id, $original_object_id);
    }
    
    function testReharvestDataObjectAgentIDs()
    {
        // no need to have anything more than the first text object for this test
        $this->media = array($this->media[0]);
        $this->media[0]->agentID = "11;22";
        $this->media[0]->creator = "";
        $this->media[0]->contributor = "";
        $this->media[0]->publisher = "";
        $this->build_resource();
        self::harvest($this->resource);
        $object = DataObject::find_by_identifier($this->media[0]->identifier);
        $ado = $object->agents_data_objects;
        $this->assertEqual(count($ado), 2);
        $this->assertEqual($ado[0]->agent->full_name, $this->agents[0]->term_name);
        $this->assertEqual($ado[1]->agent->full_name, $this->agents[1]->term_name);
        $original_object_id = $object->id;
        
        $this->media[0]->agentID = "33;11;22";
        $this->build_resource();
        self::harvest($this->resource);
        $object = DataObject::find_by_identifier($this->media[0]->identifier);
        $ado = $object->agents_data_objects;
        $this->assertEqual(count($ado), 3);
        $this->assertEqual($ado[0]->agent->full_name, $this->agents[0]->term_name);
        $this->assertEqual($ado[1]->agent->full_name, $this->agents[1]->term_name);
        $this->assertEqual($ado[2]->agent->full_name, $this->agents[2]->term_name);
        $this->assertEqual($object->id, $original_object_id);
        
        $this->media[0]->agentID = "22";
        $this->build_resource();
        self::harvest($this->resource);
        $object = DataObject::find_by_identifier($this->media[0]->identifier);
        $ado = $object->agents_data_objects;
        $this->assertEqual(count($ado), 1);
        $this->assertEqual($ado[0]->agent->full_name, $this->agents[1]->term_name);
        $this->assertEqual($object->id, $original_object_id);
    }
    
    function testReharvestTextDescription()
    {
        // no need to have anything more than the first text object for this test
        $this->media = array($this->media[0]);
        $this->media[0]->description = "the original description";
        $this->build_resource();
        self::harvest($this->resource);
        $object = array_pop(DataObject::find_all_by_identifier($this->media[0]->identifier));
        $this->assertEqual($object->description, $this->media[0]->description);
        $original_object_id = $object->id;
        
        $this->media[0]->description = "the first changed description";
        $this->build_resource();
        self::harvest($this->resource);
        $object = array_pop(DataObject::find_all_by_identifier($this->media[0]->identifier));
        $this->assertEqual($object->description, $this->media[0]->description);
        $this->assertNotEqual($object->id, $original_object_id);
        $second_object_id = $object->id;
        
        $this->build_resource();
        self::harvest($this->resource);
        $object = array_pop(DataObject::find_all_by_identifier($this->media[0]->identifier));
        $this->assertEqual($object->description, $this->media[0]->description);
        $this->assertEqual($object->id, $second_object_id);
    }
    
    function testReharvestImageURL()
    {
        // no need to have anything more than the first image object for this test
        $this->media = array($this->media[1]);
        $this->media[0]->accessURI = "http://farm9.staticflickr.com/8156/6966019350_37b495aafd.jpg";
        $this->build_resource();
        self::harvest($this->resource);
        $object = array_pop(DataObject::find_all_by_identifier($this->media[0]->identifier));
        $this->assertEqual($object->object_url, $this->media[0]->accessURI);
        $original_object = $object;
        
        $this->media[0]->accessURI = "http://upload.wikimedia.org/wikipedia/commons/thumb/6/69/Pandanus_utilis_fruit.JPG/220px-Pandanus_utilis_fruit.JPG";
        $this->build_resource();
        self::harvest($this->resource);
        $object = array_pop(DataObject::find_all_by_identifier($this->media[0]->identifier));
        $this->assertEqual($object->object_url, $this->media[0]->accessURI);
        $this->assertNotEqual($object->id, $original_object->id);
        $this->assertNotEqual($object->object_cache_url, $original_object->object_cache_url);
        $second_object = $object;
        
        $this->build_resource();
        self::harvest($this->resource);
        $object = array_pop(DataObject::find_all_by_identifier($this->media[0]->identifier));
        $this->assertEqual($object->object_url, $this->media[0]->accessURI);
        $this->assertEqual($object->id, $second_object->id);
        $this->assertEqual($object->object_cache_url, $second_object->object_cache_url);
    }
    
    function testDefaultLanguage()
    {
        $this->media = array($this->media[1]);
        $this->media[0]->UsageTerms = "http://creativecommons.org/licenses/by-nc-sa/3.0/";
        $this->build_resource();
        self::harvest($this->resource);
        $first_version = array_pop(DataObject::find_all_by_identifier($this->media[0]->identifier));
        $this->assertEqual($first_version->license->source_url, "http://creativecommons.org/licenses/by-nc-sa/3.0/");
        
        $this->media[0]->UsageTerms = NULL;
        $this->build_resource();
        self::harvest($this->resource);
        # essentially this version does not get inserted because there is no license, nor a default license on the resource
        $second_version = array_pop(DataObject::find_all_by_identifier($this->media[0]->identifier));
        $this->assertEqual($second_version->id, $first_version->id);
        
        $this->media[0]->UsageTerms = NULL;
        $this->resource->license_id = License::find_or_create_for_parser("http://creativecommons.org/licenses/by/3.0/")->id;
        $this->resource->save();
        $this->resource->refresh();
        $this->build_resource();
        self::harvest($this->resource);
        # essentially this version does not get inserted because there is no license, nor a default license on the resource
        $third_version = array_pop(DataObject::find_all_by_identifier($this->media[0]->identifier));
        $this->assertNotEqual($third_version->id, $first_version->id);
        $this->assertNotEqual($third_version->id, $second_version->id);
        $this->assertEqual($third_version->license->source_url, $this->resource->license->source_url);
    }
    
    
    
    
    
    function prepare_data()
    {
        $this->taxa = array();
        $this->references = array();
        $this->vernacular_names = array();
        $this->media = array();
        $this->agents = array();
        
        /*
            Taxon
        */
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID = "123456";
        $taxon->kingdom = "Animalia";
        $taxon->phylum = "Chordata";
        $taxon->class = "Mammalia";
        $taxon->order = "Carnivora";
        $taxon->family = "Ursidae";
        $taxon->genus = "Ursus";
        $taxon->scientificName = "Ursus maritimus Phipps, 1774";
        $taxon->taxonRank = "species";
        $taxon->furtherInformationURL = "http://some.url/polar_bear";
        $taxon->referenceID = "11;33";
        $this->taxa[] = $taxon;
        
        $vernacular = new \eol_schema\VernacularName();
        $vernacular->taxonID = $this->taxa[0]->taxonID;
        $vernacular->vernacularName = "Polar bear";
        $vernacular->language = "en";
        $this->vernacular_names[] = $vernacular;
        
        $vernacular = new \eol_schema\VernacularName();
        $vernacular->taxonID = $this->taxa[0]->taxonID;
        $vernacular->vernacularName = "Ours polaire";
        $vernacular->language = "fr";
        $this->vernacular_names[] = $vernacular;
        
        /*
            Taxon
        */
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID = "987654";
        $taxon->kingdom = "Animalia";
        $taxon->phylum = "Chordata";
        $taxon->class = "Actinopterygii";
        $taxon->order = "Salmoniformes";
        $taxon->family = "Salmonidae";
        $taxon->genus = "Salmo";
        $taxon->scientificName = "Salmo salar Linnaeus, 1758";
        $taxon->taxonRank = "species";
        $taxon->furtherInformationURL = "http://some.url/salmon";
        $taxon->referenceID = "22;33";
        $this->taxa[] = $taxon;
        
        $vernacular = new \eol_schema\VernacularName();
        $vernacular->taxonID = $this->taxa[1]->taxonID;
        $vernacular->vernacularName = "Atlantic salmon";
        $vernacular->language = "en";
        $this->vernacular_names[] = $vernacular;
        
        $vernacular = new \eol_schema\VernacularName();
        $vernacular->taxonID = $this->taxa[1]->taxonID;
        $vernacular->vernacularName = "Salmón del Atlántico";
        $vernacular->language = "es";
        $this->vernacular_names[] = $vernacular;
        
        /*
            References
        */
        $reference = new \eol_schema\Reference();
        $reference->identifier = "11";
        $reference->full_reference = "This is another sample reference";
        $this->references[] = $reference;
        
        $reference = new \eol_schema\Reference();
        $reference->identifier = "22";
        $reference->title = "Some title";
        $reference->pages = "101-150";
        $reference->pageStart = "101";
        $reference->pageEnd = "150";
        $reference->volume = "v1";
        $reference->edition = "September";
        $reference->publisher = "Some publisher";
        $reference->authorList = "Helm, Danko";
        $reference->editorList = "Robertson, Manuel";
        $reference->language = "fr";
        $reference->uri = "http://some.uri";
        $reference->doi = "10.1000/182";
        $this->references[] = $reference;
        
        $reference = new \eol_schema\Reference();
        $reference->identifier = "33";
        $reference->full_reference = "Third reference";
        $this->references[] = $reference;
        
        /*
            Text
        */
        $text = new \eol_schema\MediaResource();
        $text->taxonID = $this->taxa[0]->taxonID;
        $text->identifier = "text1";
        $text->type = "http://purl.org/dc/dcmitype/Text";
        $text->format = "text/html";
        $text->CVterm = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Biology";
        $text->title = "Text Title";
        $text->description = "Text Description";
        $text->furtherInformationURL = "http://example.com/text1";
        $text->derivedFrom = "derived from something";
        $text->CreateDate = "2012-04-24 00:00:00";
        $text->modified = "2012-04-25 00:00:00";
        $text->language = "en";
        $text->Rating = "1.2";
        $text->audience = "Children";
        $text->UsageTerms = "http://creativecommons.org/licenses/by-nc-sa/3.0/";
        $text->rights = "these are the rights";
        $text->Owner = "Someone";
        $text->bibliographicCitation = "a citation";
        $text->creator = "a creator";
        $text->publisher = "a publisher";
        $text->contributor = "a contributor";
        $text->LocationCreated = "created someplace";
        $text->spatial = "collected someplace";
        $text->lat = "10";
        $text->long = "20";
        $text->alt = "30";
        $this->media[] = $text;
        
        /*
            Image
        */
        $image = new \eol_schema\MediaResource();
        $image->taxonID = $this->taxa[1]->taxonID;
        $image->identifier = "image1";
        $image->type = "http://purl.org/dc/dcmitype/StillImage";
        $image->subtype = "Map";
        $image->format = "image/jpeg";
        $image->title = "Ada aurantiaca x Odontoglossum constrictum";
        $image->description = "Monteporzio Catone 2012 - Nardotto e Capello";
        $image->accessURI = "http://farm9.staticflickr.com/8156/6966019350_37b495aafd.jpg";
        $image->thumbnailURL = "http://farm9.staticflickr.com/8156/6966019350_37b495aafd_s.jpg";
        $image->furtherInformationURL = "http://www.flickr.com/photos/81918877@N00/6966019350/in/pool-806927@N20/";
        $image->derivedFrom = "derived from something";
        $image->CreateDate = "2012-04-24 00:00:00";
        $image->modified = "2012-04-25 00:00:00";
        $image->language = "en";
        $image->Rating = "1.2";
        $image->audience = "Children";
        $image->UsageTerms = "http://creativecommons.org/licenses/by-nc-sa/3.0/";
        $image->rights = "these are the rights";
        $image->Owner = "Someone";
        $image->bibliographicCitation = "a citation";
        $image->creator = "a creator";
        $image->publisher = "a publisher";
        $image->contributor = "a contributor";
        $image->LocationCreated = "created someplace";
        $image->spatial = "collected someplace";
        $image->lat = "10";
        $image->long = "20";
        $image->alt = "30";
        $this->media[] = $image;
        
        /*
            Agents
        */
        $agent = new \eol_schema\Agent();
        $agent->identifier = "11";
        $agent->term_name = "Full name";
        $agent->term_firstName = "First";
        $agent->term_familyName = "Last";
        $agent->agentRole = "Some new agent role";
        $agent->term_mbox = "someone@example.com";
        $agent->term_homepage = "http://example.com";
        $agent->term_currentProject = "MyProject";
        $agent->organization = "MyOrganization";
        $agent->term_accountName = "MyAccountName";
        $agent->term_openid = "something";
        $this->agents[] = $agent;
        
        $agent = new \eol_schema\Agent();
        $agent->identifier = "22";
        $agent->term_name = "Full name 2";
        $agent->term_firstName = "First 2";
        $agent->term_familyName = "Last 2";
        $agent->term_mbox = "someone2@example.com";
        $agent->term_homepage = "http://example2.com";
        $agent->term_currentProject = "MyProject 2";
        $agent->organization = "MyOrganization 2";
        $agent->term_accountName = "MyAccountName 2";
        $agent->term_openid = "something 2";
        $this->agents[] = $agent;
        
        $agent = new \eol_schema\Agent();
        $agent->identifier = "33";
        $agent->term_name = "Full name 3";
        $agent->term_firstName = "First 3";
        $agent->term_familyName = "Last 3";
        $agent->term_mbox = "someone3@example.com";
        $agent->term_homepage = "http://example3.com";
        $agent->term_currentProject = "MyProject 3";
        $agent->organization = "MyOrganization 3";
        $agent->term_accountName = "MyAccountName 3";
        $agent->term_openid = "something 3";
        $this->agents[] = $agent;
    }
    
    function build_resource()
    {
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->archive_directory));
        $content_to_add = array(
            $this->taxa,
            $this->references,
            $this->vernacular_names,
            $this->media,
            $this->agents);
        foreach($content_to_add as $records)
        {
            foreach($records as $r) $this->archive_builder->write_object_to_file($r);
        }
        $this->archive_builder->finalize();
    }
    
    private static function harvest($resource)
    {
        // set to Harvest Requested - this will only change the status id
        passthru(PHP_BIN_PATH . DOC_ROOT ."rake_tasks/harvest_requested.php -id $resource->id ENV_NAME=test");
        passthru(PHP_BIN_PATH . DOC_ROOT ."rake_tasks/harvest_resources_cron_task.php $resource->id --fast ENV_NAME=test");
        Cache::flush();
    }
    
    private static function create_resource($args = array())
    {
        if(!isset($args['auto_publish'])) $args['auto_publish'] = 1;
        if(!isset($args['vetted'])) $args['vetted'] = 1;
        if(!isset($args['title'])) $args['title'] = 'Test Resource';
        if(!isset($args['dwc_archive_url'])) $args['dwc_archive_url'] = '';
        
        // create the test resource
        $agent = Agent::find_or_create(array('full_name' => 'Test Content Partner'));
        $user = User::find_or_create(array('display_name' => 'Test Content Partner', 'agent_id' => $agent->id));
        
        // create the content partner
        $content_partner = ContentPartner::find_or_create(array('user_id' => $user->id));
        $hierarchy = Hierarchy::find_or_create(array('agent_id' => $agent->id, 'label' => 'Test Content Partner Hierarchy'));
        
        // create the resource
        $attr = array(  'content_partner_id'    => $content_partner->id,
                        'service_type'          => ServiceType::find_or_create_by_translated_label('EOL Transfer Schema'),
                        'refresh_period_hours'  => 1,
                        'auto_publish'          => $args['auto_publish'],
                        'vetted'                => $args['vetted'],
                        'title'                 => $args['title'],
                        'dwc_archive_url'       => $args['dwc_archive_url'],
                        'hierarchy_id'          => $hierarchy->id,
                        'resource_status'       => ResourceStatus::validated());
        $resource = Resource::find_or_create($attr);
        return $resource;
    }
}

?>