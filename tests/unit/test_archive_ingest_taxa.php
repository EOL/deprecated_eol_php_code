<?php
namespace php_active_record;

class test_archive_ingest_taxa extends SimpletestUnitBase
{
    function setUp()
    {
        parent::setUp();
        recursive_rmdir_contents(DOC_ROOT . "vendor/eol_content_schema_v2/extension_cache/");
        $this->archive_directory = CONTENT_RESOURCE_LOCAL_PATH . "/1/";
        if(!file_exists($this->archive_directory)) mkdir($this->archive_directory);
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->archive_directory));
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
    
    function testImportTaxonAdjacency()
    {
        $resource = self::create_resource();
        $t = new \eol_schema\Taxon();
        $t->taxonID = "123456";
        $t->kingdom = "Animalia";
        $t->phylum = "Chordata";
        $t->class = "Mammalia";
        $t->order = "Carnivora";
        $t->family = "Ursidae";
        $t->genus = "Ursus";
        $t->scientificName = "Ursus maritimus Phipps, 1774";
        $t->taxonRank = "species";
        $t->furtherInformationURL = "http://some.url";
        $t->taxonRemarks = "\"This is a string\" with 'various' (special!) *characters\nin it\tto make sure we can $ harvest #complex strings'";
        $this->archive_builder->write_object_to_file($t);
        $this->archive_builder->finalize();
        self::harvest($resource);
        
        // verify the basics
        $species = HierarchyEntry::find_by_identifier($t->taxonID);
        $this->assertEqual($species->name->string, $t->scientificName);
        $this->assertEqual($species->rank, Rank::find_or_create_by_translated_label('species'));
        $this->assertEqual($species->source_url, $t->furtherInformationURL);
        $this->assertEqual($species->hierarchy_id, $resource->hierarchy_id);
        $this->assertEqual($species->taxon_remarks, $t->taxonRemarks);
        
        // now check the parents
        $genus = $species->parent();
        $this->assertEqual($genus->name->string, $t->genus);
        $this->assertEqual($genus->rank, Rank::find_or_create_by_translated_label('genus'));
        $family = $genus->parent();
        $this->assertEqual($family->name->string, $t->family);
        $this->assertEqual($family->rank, Rank::find_or_create_by_translated_label('family'));
        $order = $family->parent();
        $this->assertEqual($order->name->string, $t->order);
        $this->assertEqual($order->rank, Rank::find_or_create_by_translated_label('order'));
        $class = $order->parent();
        $this->assertEqual($class->name->string, $t->class);
        $this->assertEqual($class->rank, Rank::find_or_create_by_translated_label('class'));
        $phylum = $class->parent();
        $this->assertEqual($phylum->name->string, $t->phylum);
        $this->assertEqual($phylum->rank, Rank::find_or_create_by_translated_label('phylum'));
        $kingdom = $phylum->parent();
        $this->assertEqual($kingdom->name->string, $t->kingdom);
        $this->assertEqual($kingdom->rank, Rank::find_or_create_by_translated_label('kingdom'));
    }
    
    function testImportTaxonParentChild()
    {
        $resource = self::create_resource();
        $t = new \eol_schema\Taxon();
        $t->taxonID = "111";
        $t->scientificName = "Animalia";
        $t->taxonRank = "kingdom";
        $this->archive_builder->write_object_to_file($t);
        $t->taxonID = "222";
        $t->scientificName = "Chordata";
        $t->taxonRank = "phylum";
        $t->parentNameUsageID = "111";
        $this->archive_builder->write_object_to_file($t);
        $t->taxonID = "333";
        $t->scientificName = "Mammalia";
        $t->taxonRank = "class";
        $t->parentNameUsageID = "222";
        $this->archive_builder->write_object_to_file($t);
        $t->taxonID = "444";
        $t->scientificName = "Carnivora";
        $t->taxonRank = "order";
        $t->parentNameUsageID = "333";
        $this->archive_builder->write_object_to_file($t);
        $t->taxonID = "555";
        $t->scientificName = "Ursidae";
        $t->taxonRank = "family";
        $t->parentNameUsageID = "444";
        $this->archive_builder->write_object_to_file($t);
        $t->taxonID = "666";
        $t->scientificName = "Ursus";
        $t->taxonRank = "genus";
        $t->parentNameUsageID = "555";
        $t->taxonRemarks = "\"This is a string\" with 'various' (special!) *characters\nin it\tto make sure we can $ harvest #complex strings'";
        $this->archive_builder->write_object_to_file($t);
        $t->taxonID = "777";
        $t->scientificName = "Ursus maritimus Phipps, 1774";
        $t->taxonRank = "species";
        $t->parentNameUsageID = "666";
        $t->taxonRemarks = NULL;
        $this->archive_builder->write_object_to_file($t);
        $this->archive_builder->finalize();
        self::harvest($resource);
        
        // check the species
        $species = HierarchyEntry::find_by_identifier(777);
        $this->assertEqual($species->name->string, 'Ursus maritimus Phipps, 1774');
        $this->assertEqual($species->rank, Rank::find_or_create_by_translated_label('species'));
        $this->assertEqual($species->taxon_remarks, NULL);
        
        // now check the parents
        $genus = $species->parent();
        $this->assertEqual($genus->name->string, 'Ursus');
        $this->assertEqual($genus->rank, Rank::find_or_create_by_translated_label('genus'));
        $this->assertEqual($genus->identifier, 666);
        $this->assertEqual($genus->taxon_remarks, "\"This is a string\" with 'various' (special!) *characters\nin it\tto make sure we can $ harvest #complex strings'");
        $family = $genus->parent();
        $this->assertEqual($family->name->string, 'Ursidae');
        $this->assertEqual($family->rank, Rank::find_or_create_by_translated_label('family'));
        $this->assertEqual($family->identifier, 555);
        $order = $family->parent();
        $this->assertEqual($order->name->string, 'Carnivora');
        $this->assertEqual($order->rank, Rank::find_or_create_by_translated_label('order'));
        $this->assertEqual($order->identifier, 444);
        $class = $order->parent();
        $this->assertEqual($class->name->string, 'Mammalia');
        $this->assertEqual($class->rank, Rank::find_or_create_by_translated_label('class'));
        $this->assertEqual($class->identifier, 333);
        $phylum = $class->parent();
        $this->assertEqual($phylum->name->string, 'Chordata');
        $this->assertEqual($phylum->rank, Rank::find_or_create_by_translated_label('phylum'));
        $this->assertEqual($phylum->identifier, 222);
        $kingdom = $phylum->parent();
        $this->assertEqual($kingdom->name->string, 'Animalia');
        $this->assertEqual($kingdom->rank, Rank::find_or_create_by_translated_label('kingdom'));
        $this->assertEqual($kingdom->identifier, 111);
    }
    
    function testImportTaxonSynonyms()
    {
        $resource = self::create_resource();
        
        $t = new \eol_schema\Taxon();
        $t->taxonID = "777";
        $t->scientificName = "Ursus maritimus Phipps, 1774";
        $t->taxonRank = "species";
        $this->archive_builder->write_object_to_file($t);
        $t->taxonID = "777_canonical";
        $t->scientificName = "Ursus maritimus";
        $t->parentNameUsageID = null;
        $t->acceptedNameUsageID = "777";
        $t->taxonomicStatus = "canonical form";
        $this->archive_builder->write_object_to_file($t);
        $t->taxonID = "777_synonym";
        $t->scientificName = "Ursus maritimus Syn";
        $t->parentNameUsageID = null;
        $t->acceptedNameUsageID = "777";
        $t->taxonomicStatus = null;
        $t->taxonRemarks = "\"This is a string\" with 'various' (special!) *characters\nin it\tto make sure we can $ harvest #complex strings'";
        $this->archive_builder->write_object_to_file($t);
        $this->archive_builder->finalize();
        self::harvest($resource);
        
        // check the species
        $species = HierarchyEntry::find_by_identifier(777);
        $synonyms = $species->synonyms;
        $this->assertEqual(count($synonyms), 2);
        $this->assertEqual($synonyms[0]->name->string, "Ursus maritimus");
        $this->assertEqual($synonyms[0]->synonym_relation, SynonymRelation::find_or_create_by_translated_label('canonical form'));
        $this->assertEqual($synonyms[1]->name->string, "Ursus maritimus Syn");
        // synonym should be the default status
        $this->assertEqual($synonyms[1]->synonym_relation, SynonymRelation::find_or_create_by_translated_label('synonym'));
    }
    
    function testImportTaxonReferencesByString()
    {
        $resource = self::create_resource();
        
        $t = new \eol_schema\Taxon();
        $t->taxonID = "777";
        $t->scientificName = "Ursus maritimus Phipps, 1774";
        $t->taxonRank = "species";
        $t->namePublishedIn = "This is a sample reference||And another one too";
        $this->archive_builder->write_object_to_file($t);
        $this->archive_builder->finalize();
        self::harvest($resource);
        
        // check the species
        $species = HierarchyEntry::find_by_identifier(777);
        $references = $species->references;
        $this->assertEqual(count($references), 2);
        $this->assertEqual($references[0]->full_reference, "This is a sample reference");
        $this->assertEqual($references[1]->full_reference, "And another one too");
    }
    
    function testImportTaxonReferencesByID()
    {
        $resource = self::create_resource();
        
        $t = new \eol_schema\Taxon();
        $t->taxonID = "777";
        $t->scientificName = "Ursus maritimus Phipps, 1774";
        $t->taxonRank = "species";
        $t->referenceID = "11;22,33";
        $this->archive_builder->write_object_to_file($t);
        $r1 = new \eol_schema\Reference();
        $r1->identifier = "11";
        $r1->full_reference = "This is another sample reference";
        $this->archive_builder->write_object_to_file($r1);
        $r2 = new \eol_schema\Reference();
        $r2->identifier = "22";
        $r2->title = "Some title";
        $r2->pages = "101-150";
        $r2->pageStart = "101";
        $r2->pageEnd = "150";
        $r2->volume = "v1";
        $r2->edition = "September";
        $r2->publisher = "Some publisher";
        $r2->authorList = "Helm, Danko";
        $r2->editorList = "Robertson, Manuel";
        $r2->language = "fr";
        $r2->uri = "http://some.uri";
        $r2->doi = "10.1000/182";
        $this->archive_builder->write_object_to_file($r2);
        $r3 = new \eol_schema\Reference();
        $r3->identifier = "33";
        $r3->full_reference = "Third reference";
        $this->archive_builder->write_object_to_file($r3);
        $this->archive_builder->finalize();
        self::harvest($resource);
        
        // check the species
        $species = HierarchyEntry::find_by_identifier(777);
        $references = $species->references;
        $this->assertEqual(count($references), 3);
        $this->assertEqual($references[0]->provider_mangaed_id, $r1->identifier);
        $this->assertEqual($references[0]->full_reference, $r1->full_reference);
        $this->assertEqual($references[1]->provider_mangaed_id, $r2->identifier);
        $this->assertEqual($references[1]->title, $r2->title);
        $this->assertEqual($references[1]->pages, $r2->pages);
        $this->assertEqual($references[1]->page_start, $r2->pageStart);
        $this->assertEqual($references[1]->page_end, $r2->pageEnd);
        $this->assertEqual($references[1]->volume, $r2->volume);
        $this->assertEqual($references[1]->edition, $r2->edition);
        $this->assertEqual($references[1]->publisher, $r2->publisher);
        $this->assertEqual($references[1]->authors, $r2->authorList);
        $this->assertEqual($references[1]->editors, $r2->editorList);
        $this->assertEqual($references[1]->language->translation->label, $r2->language);
        $this->assertEqual(count($references[1]->ref_identifiers), 2);
        $this->assertEqual($references[1]->ref_identifiers[0]->ref_identifier_type->label, 'uri');
        $this->assertEqual($references[1]->ref_identifiers[0]->identifier, $r2->uri);
        $this->assertEqual($references[1]->ref_identifiers[1]->ref_identifier_type->label, 'doi');
        $this->assertEqual($references[1]->ref_identifiers[1]->identifier, $r2->doi);
        $this->assertEqual($references[2]->provider_mangaed_id, $r3->identifier);
        $this->assertEqual($references[2]->full_reference, $r3->full_reference);
    }
    
    function testImportVernacularNames()
    {
        $resource = self::create_resource();
        $t = new \eol_schema\Taxon();
        $t->taxonID = "111";
        $t->scientificName = "Animalia";
        $t->taxonRank = "kingdom";
        $this->archive_builder->write_object_to_file($t);
        $v1 = new \eol_schema\VernacularName();
        $v1->taxonID = "111";
        $v1->vernacularName = "Animals";
        $v1->language = "en";
        $this->archive_builder->write_object_to_file($v1);
        $v2 = new \eol_schema\VernacularName();
        $v2->taxonID = "111";
        $v2->vernacularName = "Animaux";
        $v2->language = "fr";
        $this->archive_builder->write_object_to_file($v2);
        $v3 = new \eol_schema\VernacularName();
        $v3->taxonID = "111";
        $v3->vernacularName = "בעלי חיים";
        $this->archive_builder->write_object_to_file($v3);
        $this->archive_builder->finalize();
        self::harvest($resource);
        
        $entry = HierarchyEntry::find_by_identifier($t->taxonID);
        $this->assertEqual(count($entry->synonyms), 3);
        $this->assertEqual($entry->synonyms[0]->name->string, $v1->vernacularName);
        $this->assertEqual($entry->synonyms[0]->language->iso_639_1, $v1->language);
        $this->assertEqual($entry->synonyms[1]->name->string, $v2->vernacularName);
        $this->assertEqual($entry->synonyms[1]->language->translation->label, $v2->language);
        $this->assertEqual($entry->synonyms[2]->name->string, $v3->vernacularName);
        $this->assertEqual($entry->synonyms[2]->language, NULL);
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