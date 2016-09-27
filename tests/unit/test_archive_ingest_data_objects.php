<?php
namespace php_active_record;

class test_archive_ingest_data_objects extends SimpletestUnitBase
{
    function setUp()
    {
        parent::setUp();
        recursive_rmdir_contents(DOC_ROOT . "vendor/eol_content_schema_v2/extension_cache/");
        $this->archive_directory = CONTENT_RESOURCE_LOCAL_PATH . "/1/";
        if(!file_exists($this->archive_directory)) mkdir($this->archive_directory);
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->archive_directory));
        
        $this->resource = self::create_resource();
        $this->taxon1 = new \eol_schema\Taxon();
        $this->taxon1->taxonID = "123456";
        $this->taxon1->kingdom = "Animalia";
        $this->taxon1->phylum = "Chordata";
        $this->taxon1->class = "Mammalia";
        $this->taxon1->order = "Carnivora";
        $this->taxon1->family = "Ursidae";
        $this->taxon1->genus = "Ursus";
        $this->taxon1->scientificName = "Ursus maritimus Phipps, 1774";
        $this->taxon1->taxonRank = "species";
        $this->taxon1->furtherInformationURL = "http://some.url/polar_bear";
        $this->archive_builder->write_object_to_file($this->taxon1);
        $this->taxon2 = new \eol_schema\Taxon();
        $this->taxon2->taxonID = "987654";
        $this->taxon2->kingdom = "Animalia";
        $this->taxon2->phylum = "Chordata";
        $this->taxon2->class = "Actinopterygii";
        $this->taxon2->order = "Salmoniformes";
        $this->taxon2->family = "Salmonidae";
        $this->taxon2->genus = "Salmo";
        $this->taxon2->scientificName = "Salmo salar Linnaeus, 1758";
        $this->taxon2->taxonRank = "species";
        $this->taxon2->furtherInformationURL = "http://some.url/salmon";
        $this->archive_builder->write_object_to_file($this->taxon2);
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
    
    function testImportTextWithAllFields()
    {
        $m = new \eol_schema\MediaResource();
        $m->taxonID = $this->taxon1->taxonID;
        $m->identifier = "text1";
        $m->type = "http://purl.org/dc/dcmitype/Text";
        $m->format = "text/html";
        $m->CVterm = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Biology";
        $m->title = "Text Title";
        $m->description = "Text Description";
        $m->furtherInformationURL = "http://example.com/text1";
        $m->derivedFrom = "derived from something";
        $m->CreateDate = "2012-04-24 00:00:00";
        $m->modified = "2012-04-25 00:00:00";
        $m->language = "en";
        $m->Rating = "1.2";
        $m->audience = "Children";
        $m->UsageTerms = "http://creativecommons.org/licenses/by-nc-sa/3.0/";
        $m->rights = "these are the rights";
        $m->Owner = "Someone";
        $m->bibliographicCitation = "a citation";
        $m->creator = "a creator";
        $m->publisher = "a publisher";
        $m->contributor = "a contributor";
        $m->LocationCreated = "created someplace";
        $m->spatial = "collected someplace";
        $m->lat = "10";
        $m->long = "20";
        $m->alt = "30";
        $this->archive_builder->write_object_to_file($m);
        $this->archive_builder->finalize();
        self::harvest($this->resource);
        
        $data_object = DataObject::find_by_identifier($m->identifier);
        $this->assertEqual($data_object->identifier, $m->identifier);
        $this->assertEqual($data_object->data_type->schema_value, $m->type);
        $this->assertEqual($data_object->mime_type->translation->label, $m->format);
        $this->assertEqual($data_object->info_items[0]->schema_value, $m->CVterm);
        $this->assertEqual($data_object->object_title, $m->title);
        $this->assertEqual($data_object->description, $m->description);
        $this->assertEqual($data_object->source_url, $m->furtherInformationURL);
        $this->assertEqual($data_object->derived_from, $m->derivedFrom);
        $this->assertEqual($data_object->object_created_at, $m->CreateDate);
        $this->assertEqual($data_object->object_modified_at, $m->modified);
        $this->assertEqual($data_object->language->iso_639_1, $m->language);
        $this->assertEqual($data_object->data_rating, $m->Rating);
        $this->assertEqual($data_object->audiences[0]->translation->label, $m->audience);
        $this->assertEqual($data_object->license->source_url, $m->UsageTerms);
        $this->assertEqual($data_object->rights_statement, $m->rights);
        $this->assertEqual($data_object->rights_holder, $m->Owner);
        $this->assertEqual($data_object->bibliographic_citation, $m->bibliographicCitation);
        $this->assertEqual($data_object->location, $m->LocationCreated);
        $this->assertEqual($data_object->spatial_location, $m->spatial);
        $this->assertEqual($data_object->latitude, $m->lat);
        $this->assertEqual($data_object->longitude, $m->long);
        $this->assertEqual($data_object->altitude, $m->alt);
        
        $ado = $data_object->agents_data_objects;
        $this->assertEqual(count($ado), 3);
        $this->assertEqual($ado[0]->agent->full_name, $m->creator);
        $this->assertEqual($ado[0]->agent_role->translation->label, 'Creator');
        $this->assertEqual($ado[1]->agent->full_name, $m->publisher);
        $this->assertEqual($ado[1]->agent_role->translation->label, 'Publisher');
        $this->assertEqual($ado[2]->agent->full_name, $m->contributor);
        $this->assertEqual($ado[2]->agent_role->translation->label, 'Contributor');
        
        $this->assertEqual(count($data_object->data_objects_hierarchy_entries), 1);
        $this->assertEqual(count($data_object->data_objects_taxon_concepts), 1);
        $this->assertEqual($data_object->data_objects_hierarchy_entries[0]->hierarchy_entry->taxon_concept_id,
            $data_object->data_objects_taxon_concepts[0]->taxon_concept->id);
        $this->assertEqual($data_object->data_objects_hierarchy_entries[0]->hierarchy_entry->name->string, $this->taxon1->scientificName);
    }
    
    function testImportImageWithAllFields()
    {
        $m = new \eol_schema\MediaResource();
        $m->taxonID = $this->taxon1->taxonID;
        $m->identifier = "image1";
        $m->type = "http://purl.org/dc/dcmitype/StillImage";
        $m->subtype = "Map";
        $m->format = "image/jpeg";
        $m->title = "Ada aurantiaca x Odontoglossum constrictum";
        $m->description = "Monteporzio Catone 2012 - Nardotto e Capello";
        $m->accessURI = "http://farm9.staticflickr.com/8156/6966019350_37b495aafd.jpg";
        $m->thumbnailURL = "http://farm9.staticflickr.com/8156/6966019350_37b495aafd_s.jpg";
        $m->furtherInformationURL = "http://www.flickr.com/photos/81918877@N00/6966019350/in/pool-806927@N20/";
        $m->derivedFrom = "derived from something";
        $m->CreateDate = "2012-04-24 00:00:00";
        $m->modified = "2012-04-25 00:00:00";
        $m->language = "en";
        $m->Rating = "1.2";
        $m->audience = "Children";
        $m->UsageTerms = "http://creativecommons.org/licenses/by-nc-sa/3.0/";
        $m->rights = "these are the rights";
        $m->Owner = "Someone";
        $m->bibliographicCitation = "a citation";
        $m->creator = "a creator";
        $m->publisher = "a publisher";
        $m->contributor = "a contributor";
        $m->LocationCreated = "created someplace";
        $m->spatial = "collected someplace";
        $m->lat = "10";
        $m->long = "20";
        $m->alt = "30";
        $this->archive_builder->write_object_to_file($m);
        $this->archive_builder->finalize();
        self::harvest($this->resource);
        
        $data_object = DataObject::find_by_identifier($m->identifier);
        $this->assertEqual($data_object->identifier, $m->identifier);
        $this->assertEqual($data_object->data_type->schema_value, $m->type);
        $this->assertEqual($data_object->data_subtype->schema_value, $m->subtype);
        $this->assertEqual($data_object->mime_type->translation->label, $m->format);
        $this->assertEqual($data_object->object_title, $m->title);
        $this->assertEqual($data_object->description, $m->description);
        $this->assertEqual($data_object->object_url, $m->accessURI);
        $this->assertEqual($data_object->thumbnail_url, $m->thumbnailURL);
        $this->assertEqual($data_object->source_url, $m->furtherInformationURL);
        $this->assertEqual($data_object->derived_from, $m->derivedFrom);
        $this->assertEqual($data_object->object_created_at, $m->CreateDate);
        $this->assertEqual($data_object->object_modified_at, $m->modified);
        $this->assertEqual($data_object->language->iso_639_1, $m->language);
        $this->assertEqual($data_object->data_rating, $m->Rating);
        $this->assertEqual($data_object->audiences[0]->translation->label, $m->audience);
        $this->assertEqual($data_object->license->source_url, $m->UsageTerms);
        $this->assertEqual($data_object->rights_statement, $m->rights);
        $this->assertEqual($data_object->rights_holder, $m->Owner);
        $this->assertEqual($data_object->bibliographic_citation, $m->bibliographicCitation);
        $this->assertEqual($data_object->location, $m->LocationCreated);
        $this->assertEqual($data_object->spatial_location, $m->spatial);
        $this->assertEqual($data_object->latitude, $m->lat);
        $this->assertEqual($data_object->longitude, $m->long);
        $this->assertEqual($data_object->altitude, $m->alt);
        
        $ado = $data_object->agents_data_objects;
        $this->assertEqual(count($ado), 3);
        $this->assertEqual($ado[0]->agent->full_name, $m->creator);
        $this->assertEqual($ado[0]->agent_role->translation->label, 'Creator');
        $this->assertEqual($ado[1]->agent->full_name, $m->publisher);
        $this->assertEqual($ado[1]->agent_role->translation->label, 'Publisher');
        $this->assertEqual($ado[2]->agent->full_name, $m->contributor);
        $this->assertEqual($ado[2]->agent_role->translation->label, 'Contributor');
        
        $this->assertEqual(count($data_object->data_objects_hierarchy_entries), 1);
        $this->assertEqual(count($data_object->data_objects_taxon_concepts), 1);
        $this->assertEqual($data_object->data_objects_hierarchy_entries[0]->hierarchy_entry->taxon_concept_id,
            $data_object->data_objects_taxon_concepts[0]->taxon_concept->id);
        $this->assertEqual($data_object->data_objects_hierarchy_entries[0]->hierarchy_entry->name->string, $this->taxon1->scientificName);
        
    }
    
    function testImportObjectReferencesByID()
    {
        $m = new \eol_schema\MediaResource();
        $m->taxonID = $this->taxon1->taxonID;
        $m->identifier = "text1";
        $m->type = "http://purl.org/dc/dcmitype/Text";
        $m->CVterm = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Biology";
        $m->description = "Text Description";
        $m->UsageTerms = "http://creativecommons.org/licenses/by-nc-sa/3.0/";
        $m->referenceID = "11;22,33";
        $this->archive_builder->write_object_to_file($m);
        $r = new \eol_schema\Reference();
        $r->identifier = "11";
        $r->full_reference = "This is another sample reference";
        $this->archive_builder->write_object_to_file($r);
        $r = new \eol_schema\Reference();
        $r->identifier = "22";
        $r->title = "Some title";
        $r->pages = "101-150";
        $r->pageStart = "101";
        $r->pageEnd = "150";
        $r->volume = "v1";
        $r->edition = "September";
        $r->publisher = "Some publisher";
        $r->authorList = "Helm, Danko";
        $r->editorList = "Robertson, Manuel";
        $r->language = "fr";
        $r->uri = "http://some.uri";
        $r->doi = "10.1000/182";
        $this->archive_builder->write_object_to_file($r);
        $r = new \eol_schema\Reference();
        $r->identifier = "33";
        $r->full_reference = "Third reference";
        $this->archive_builder->write_object_to_file($r);
        $this->archive_builder->finalize();
        self::harvest($this->resource);
        
        $data_object = DataObject::find_by_identifier($m->identifier);
        // check the object references
        $references = $data_object->references;
        $this->assertEqual(count($references), 3);
        $this->assertEqual($references[0]->full_reference, "This is another sample reference");
        $this->assertEqual($references[0]->provider_mangaed_id, "11");
        $this->assertEqual($references[1]->provider_mangaed_id, "22");
        $this->assertEqual($references[1]->title, "Some title");
        $this->assertEqual($references[1]->pages, "101-150");
        $this->assertEqual($references[1]->page_start, "101");
        $this->assertEqual($references[1]->page_end, "150");
        $this->assertEqual($references[1]->volume, "v1");
        $this->assertEqual($references[1]->edition, "September");
        $this->assertEqual($references[1]->publisher, "Some publisher");
        $this->assertEqual($references[1]->authors, "Helm, Danko");
        $this->assertEqual($references[1]->editors, "Robertson, Manuel");
        $this->assertEqual($references[1]->language->id, Language::find_or_create_for_parser('fr')->id);
        $this->assertEqual(count($references[1]->ref_identifiers), 2);
        $this->assertEqual($references[1]->ref_identifiers[0]->ref_identifier_type->label, 'uri');
        $this->assertEqual($references[1]->ref_identifiers[0]->identifier, 'http://some.uri');
        $this->assertEqual($references[1]->ref_identifiers[1]->ref_identifier_type->label, 'doi');
        $this->assertEqual($references[1]->ref_identifiers[1]->identifier, '10.1000/182');
        $this->assertEqual($references[2]->full_reference, "Third reference");
        $this->assertEqual($references[2]->provider_mangaed_id, "33");
    }
    
    function testImportObjectMultipleAgents()
    {
        $m = new \eol_schema\MediaResource();
        $m->taxonID = $this->taxon1->taxonID;
        $m->identifier = "text1";
        $m->type = "http://purl.org/dc/dcmitype/Text";
        $m->CVterm = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Biology";
        $m->description = "Text Description";
        $m->UsageTerms = "http://creativecommons.org/licenses/by-nc-sa/3.0/";
        $m->creator = "a creator; another creator; and another";
        $m->publisher = "a publisher; another publisher";
        $m->contributor = "a contributor; another contributor";
        $this->archive_builder->write_object_to_file($m);
        $this->archive_builder->finalize();
        self::harvest($this->resource);
        
        $data_object = DataObject::find_by_identifier($m->identifier);
        $ado = $data_object->agents_data_objects;
        $this->assertEqual(count($ado), 7);
        $creators = explode(";", $m->creator);
        $this->assertEqual($ado[0]->agent->full_name, trim($creators[0]));
        $this->assertEqual($ado[0]->agent_role->translation->label, 'Creator');
        $this->assertEqual($ado[1]->agent->full_name, trim($creators[1]));
        $this->assertEqual($ado[1]->agent_role->translation->label, 'Creator');
        $this->assertEqual($ado[2]->agent->full_name, trim($creators[2]));
        $this->assertEqual($ado[2]->agent_role->translation->label, 'Creator');
        
        $publishers = explode(";", $m->publisher);
        $this->assertEqual($ado[3]->agent->full_name, trim($publishers[0]));
        $this->assertEqual($ado[3]->agent_role->translation->label, 'Publisher');
        $this->assertEqual($ado[4]->agent->full_name, trim($publishers[1]));
        $this->assertEqual($ado[4]->agent_role->translation->label, 'Publisher');
        
        $contributors = explode(";", $m->contributor);
        $this->assertEqual($ado[5]->agent->full_name, trim($contributors[0]));
        $this->assertEqual($ado[5]->agent_role->translation->label, 'Contributor');
        $this->assertEqual($ado[6]->agent->full_name, trim($contributors[1]));
        $this->assertEqual($ado[6]->agent_role->translation->label, 'Contributor');
    }
    
    function testImportObjectMultipleAgentIDs()
    {
        $m = new \eol_schema\MediaResource();
        $m->taxonID = $this->taxon1->taxonID;
        $m->identifier = "text1";
        $m->type = "http://purl.org/dc/dcmitype/Text";
        $m->CVterm = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Biology";
        $m->description = "Text Description";
        $m->UsageTerms = "http://creativecommons.org/licenses/by-nc-sa/3.0/";
        $m->creator = "a creator; another creator; and another";
        $m->agentID = "11;22";
        $this->archive_builder->write_object_to_file($m);
        $a1 = new \eol_schema\Agent();
        $a1->identifier = "11";
        $a1->term_name = "Full name";
        $a1->term_firstName = "First";
        $a1->term_familyName = "Last";
        $a1->agentRole = "Some new agent role";
        $a1->term_mbox = "someone@example.com";
        $a1->term_homepage = "http://example.com";
        $a1->term_logo = "http://www.akamai.com/graphics/misc/rss.jpg";
        $a1->term_currentProject = "MyProject";
        $a1->organization = "MyOrganization";
        $a1->term_accountName = "MyAccountName";
        $a1->term_openid = "something";
        $this->archive_builder->write_object_to_file($a1);
        $a2 = new \eol_schema\Agent();
        $a2->identifier = "22";
        $a2->term_name = "Full name 2";
        $a2->term_firstName = "First 2";
        $a2->term_familyName = "Last 2";
        $a2->term_mbox = "someone2@example.com";
        $a2->term_homepage = "http://example2.com";
        $a2->term_logo = "http://www.akamai.com/graphics/misc/rss.jpg";
        $a2->term_currentProject = "MyProject 2";
        $a2->organization = "MyOrganization 2";
        $a2->term_accountName = "MyAccountName 2";
        $a2->term_openid = "something 2";
        $this->archive_builder->write_object_to_file($a2);
        $this->archive_builder->finalize();
        self::harvest($this->resource);
        
        $data_object = DataObject::find_by_identifier($m->identifier);
        $ado = $data_object->agents_data_objects;
        $this->assertEqual(count($ado), 5);
        $creators = explode(";", $m->creator);
        $this->assertEqual($ado[0]->agent->full_name, trim($creators[0]));
        $this->assertEqual($ado[0]->agent_role->translation->label, 'Creator');
        $this->assertEqual($ado[1]->agent->full_name, trim($creators[1]));
        $this->assertEqual($ado[1]->agent_role->translation->label, 'Creator');
        $this->assertEqual($ado[2]->agent->full_name, trim($creators[2]));
        $this->assertEqual($ado[2]->agent_role->translation->label, 'Creator');
        $this->assertEqual($ado[3]->agent->full_name, $a1->term_name);
        $this->assertEqual($ado[3]->agent->given_name, $a1->term_firstName);
        $this->assertEqual($ado[3]->agent->family_name, $a1->term_familyName);
        $this->assertEqual($ado[3]->agent->email, $a1->term_mbox);
        $this->assertEqual($ado[3]->agent->homepage, $a1->term_homepage);
        $this->assertEqual($ado[3]->agent->logo_url, $a1->term_logo);
        $this->assertTrue($ado[3]->agent->logo_cache_url > '201200000000000');
        $this->assertEqual($ado[3]->agent->project, $a1->term_currentProject);
        $this->assertEqual($ado[3]->agent->organization, $a1->organization);
        $this->assertEqual($ado[3]->agent->account_name, $a1->term_accountName);
        $this->assertEqual($ado[3]->agent->openid, $a1->term_openid);
        $this->assertEqual($ado[3]->agent_role->translation->label, $a1->agentRole);
        $this->assertEqual($ado[4]->agent->full_name, $a2->term_name);
        $this->assertEqual($ado[4]->agent->given_name, $a2->term_firstName);
        $this->assertEqual($ado[4]->agent->family_name, $a2->term_familyName);
        $this->assertEqual($ado[4]->agent->email, $a2->term_mbox);
        $this->assertEqual($ado[4]->agent->homepage, $a2->term_homepage);
        $this->assertEqual($ado[4]->agent->logo_url, $a2->term_logo);
        $this->assertTrue($ado[4]->agent->logo_cache_url > '201200000000000');
        $this->assertTrue($ado[4]->agent->logo_cache_url != $ado[3]->agent->logo_cache_url);
        $this->assertEqual($ado[4]->agent->project, $a2->term_currentProject);
        $this->assertEqual($ado[4]->agent->organization, $a2->organization);
        $this->assertEqual($ado[4]->agent->account_name, $a2->term_accountName);
        $this->assertEqual($ado[4]->agent->openid, $a2->term_openid);
        $this->assertEqual($ado[4]->agent_role, NULL);
    }
    
    function testImportObjectMultipleTaxa()
    {
        $m = new \eol_schema\MediaResource();
        $m->taxonID = $this->taxon1->taxonID ."; ". $this->taxon2->taxonID;
        $m->identifier = "text1";
        $m->type = "http://purl.org/dc/dcmitype/Text";
        $m->CVterm = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Biology";
        $m->description = "Text Description";
        $m->UsageTerms = "http://creativecommons.org/licenses/by-nc-sa/3.0/";
        $m->creator = "a creator; another creator; and another";
        $m->publisher = "a publisher; another publisher";
        $m->contributor = "a contributor; another contributor";
        $this->archive_builder->write_object_to_file($m);
        $this->archive_builder->finalize();
        self::harvest($this->resource);
        
        $data_object = DataObject::find_by_identifier($m->identifier);
        $this->assertEqual(count($data_object->data_objects_hierarchy_entries), 2);
        $this->assertEqual(count($data_object->data_objects_taxon_concepts), 2);
        $this->assertEqual($data_object->data_objects_hierarchy_entries[0]->hierarchy_entry->taxon_concept_id,
            $data_object->data_objects_taxon_concepts[0]->taxon_concept->id);
        $this->assertEqual($data_object->data_objects_hierarchy_entries[1]->hierarchy_entry->taxon_concept_id,
            $data_object->data_objects_taxon_concepts[1]->taxon_concept->id);
        $this->assertEqual($data_object->data_objects_hierarchy_entries[0]->hierarchy_entry->name->string, $this->taxon1->scientificName);
        $this->assertEqual($data_object->data_objects_hierarchy_entries[1]->hierarchy_entry->name->string, $this->taxon2->scientificName);
    }
    
    function testImportDifferentLicenseURI()
    {
        $m = new \eol_schema\MediaResource();
        $m->taxonID = $this->taxon1->taxonID;
        $m->identifier = "text1";
        $m->type = "http://purl.org/dc/dcmitype/Text";
        $m->CVterm = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Biology";
        $m->description = "Text Description";
        $m->license = "http://creativecommons.org/licenses/by-nc-sa/3.0/";
        $this->archive_builder->write_object_to_file($m);
        $this->archive_builder->finalize();
        self::harvest($this->resource);
        
        $data_object = DataObject::find_by_identifier($m->identifier);
        $this->assertEqual($data_object->license->source_url, $m->license);
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
