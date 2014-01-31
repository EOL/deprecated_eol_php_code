<?php
namespace php_active_record;

class test_archive_validator extends SimpletestUnitBase
{
    function setUp()
    {
        parent::setUp();
        recursive_rmdir_contents(DOC_ROOT . "vendor/eol_content_schema_v2/extension_cache/");
        $this->archive_directory = DOC_ROOT . "tmp/test_archive_validator/";
        // delete the contents of the created archive
        if(!file_exists($this->archive_directory)) mkdir($this->archive_directory);
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->archive_directory));
    }
    
    function tearDown()
    {
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
    
    function reset()
    {
        // delete existing files
        $files_in_archive = read_dir($this->archive_directory);
        foreach($files_in_archive as $file)
        {
            if(substr($file, 0, 1) == '.') continue;
            unlink($this->archive_directory . $file);
        }
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->archive_directory));
    }
    
    function testValidateTaxonComplete()
    {
        $t = new \eol_schema\Taxon();
        $t->taxonID = "12345";
        $t->scientificName = "Aus bus";
        $t->taxonRank = "species";
        $t->taxonomicStatus = "accepted";
        $this->archive_builder->write_object_to_file($t);
        $this->archive_builder->finalize();
        list($errors, $warnings) = $this->validate();
        $this->assertFalse($errors);
        $this->assertFalse($warnings);
    }
    
    function testValidateTaxonID()
    {
        $t = new \eol_schema\Taxon();
        $t->scientificName = "Aus bus";
        $this->archive_builder->write_object_to_file($t);
        $this->archive_builder->finalize();
        list($errors, $warnings) = $this->validate();
        $this->assertTrue($errors, 'There should be errors');
        $this->assertTrue($errors[0]->file == 'taxon.tab');
        $this->assertTrue($errors[0]->message == 'Taxa must have identifiers');
        $this->reset();
        
        // identifier used in the place of TaxonID is OK. We'll interpret it as TaxonID
        $t = new \eol_schema\Taxon();
        $t->identifier = "12345";
        $t->scientificName = "Aus bus";
        $this->archive_builder->write_object_to_file($t);
        $this->archive_builder->finalize();
        list($errors, $warnings) = $this->validate();
        $this->assertFalse($errors);
        $this->assertFalse($warnings);
    }
    
    function testValidateTaxonScientificName()
    {
        $t = new \eol_schema\Taxon();
        $t->taxonID = "12345";
        $this->archive_builder->write_object_to_file($t);
        $this->archive_builder->finalize();
        list($errors, $warnings) = $this->validate();
        $this->assertFalse($errors);
        $this->assertTrue($warnings, 'There should be warnings');
        $this->assertTrue($warnings[0]->uri == 'http://rs.tdwg.org/dwc/terms/scientificName');
        $this->assertTrue($warnings[0]->message == 'Taxa should have scientificNames');
    }
    
    function testValidateTaxonAnyName()
    {
        $t = new \eol_schema\Taxon();
        $t->taxonID = "12345";
        $t->scientificName = "Aus bus";
        $this->archive_builder->write_object_to_file($t);
        $this->archive_builder->finalize();
        list($errors, $warnings) = $this->validate();
        $this->assertFalse($errors);
        $this->assertFalse($warnings);
        $this->reset();
        
        $t = new \eol_schema\Taxon();
        $t->taxonID = "12345";
        $t->genus = "Aus";
        $this->archive_builder->write_object_to_file($t);
        $this->archive_builder->finalize();
        list($errors, $warnings) = $this->validate();
        $this->assertFalse($errors);
        $this->assertTrue($warnings, 'There should be warnings');
        $this->assertTrue(count($warnings) == 1, 'There should be only one warning');
        $this->assertTrue($warnings[0]->uri == 'http://rs.tdwg.org/dwc/terms/scientificName');
        $this->assertTrue($warnings[0]->message == 'Taxa should have scientificNames');
        $this->reset();
        
        $t = new \eol_schema\Taxon();
        $t->taxonID = "12345";
        $t->family = "Aus";
        $this->archive_builder->write_object_to_file($t);
        $this->archive_builder->finalize();
        list($errors, $warnings) = $this->validate();
        $this->assertFalse($errors);
        $this->assertTrue($warnings, 'There should be warnings');
        $this->assertTrue(count($warnings) == 1, 'There should be only one warning');
        $this->assertTrue($warnings[0]->uri == 'http://rs.tdwg.org/dwc/terms/scientificName');
        $this->assertTrue($warnings[0]->message == 'Taxa should have scientificNames');
        $this->reset();
        
        $t = new \eol_schema\Taxon();
        $t->taxonID = "12345";
        $t->order = "Aus";
        $this->archive_builder->write_object_to_file($t);
        $this->archive_builder->finalize();
        list($errors, $warnings) = $this->validate();
        $this->assertFalse($errors);
        $this->assertTrue($warnings, 'There should be warnings');
        $this->assertTrue(count($warnings) == 1, 'There should be only one warning');
        $this->assertTrue($warnings[0]->uri == 'http://rs.tdwg.org/dwc/terms/scientificName');
        $this->assertTrue($warnings[0]->message == 'Taxa should have scientificNames');
        $this->reset();
        
        $t = new \eol_schema\Taxon();
        $t->taxonID = "12345";
        $t->class = "Aus";
        $this->archive_builder->write_object_to_file($t);
        $this->archive_builder->finalize();
        list($errors, $warnings) = $this->validate();
        $this->assertFalse($errors);
        $this->assertTrue($warnings, 'There should be warnings');
        $this->assertTrue(count($warnings) == 1, 'There should be only one warning');
        $this->assertTrue($warnings[0]->uri == 'http://rs.tdwg.org/dwc/terms/scientificName');
        $this->assertTrue($warnings[0]->message == 'Taxa should have scientificNames');
        $this->reset();
        
        $t = new \eol_schema\Taxon();
        $t->taxonID = "12345";
        $t->phylum = "Aus";
        $this->archive_builder->write_object_to_file($t);
        $this->archive_builder->finalize();
        list($errors, $warnings) = $this->validate();
        $this->assertFalse($errors);
        $this->assertTrue($warnings, 'There should be warnings');
        $this->assertTrue(count($warnings) == 1, 'There should be only one warning');
        $this->assertTrue($warnings[0]->uri == 'http://rs.tdwg.org/dwc/terms/scientificName');
        $this->assertTrue($warnings[0]->message == 'Taxa should have scientificNames');
        $this->reset();
        
        $t = new \eol_schema\Taxon();
        $t->taxonID = "12345";
        $t->kingdom = "Aus";
        $this->archive_builder->write_object_to_file($t);
        $this->archive_builder->finalize();
        list($errors, $warnings) = $this->validate();
        $this->assertFalse($errors);
        $this->assertTrue($warnings, 'There should be warnings');
        $this->assertTrue(count($warnings) == 1, 'There should be only one warning');
        $this->assertTrue($warnings[0]->uri == 'http://rs.tdwg.org/dwc/terms/scientificName');
        $this->assertTrue($warnings[0]->message == 'Taxa should have scientificNames');
        $this->reset();
    }
    
    function testValidateTaxonRank()
    {
        $t = new \eol_schema\Taxon();
        $t->taxonID = "12345";
        $t->scientificName = "Aus bus";
        $t->taxonRank = "nonsense";
        $this->archive_builder->write_object_to_file($t);
        $this->archive_builder->finalize();
        list($errors, $warnings) = $this->validate();
        $this->assertFalse($errors);
        $this->assertTrue($warnings, 'There should be warnings');
        $this->assertTrue(count($warnings) == 1, 'There should be only one warning');
        $this->assertTrue($warnings[0]->uri == 'http://rs.tdwg.org/dwc/terms/taxonRank');
        $this->assertTrue($warnings[0]->message == 'Unrecognized taxon rank');
    }
    
    function testValidateTaxonStatus()
    {
        $t = new \eol_schema\Taxon();
        $t->taxonID = "12345";
        $t->scientificName = "Aus bus";
        $t->taxonomicStatus = "nonsense";
        $this->archive_builder->write_object_to_file($t);
        $this->archive_builder->finalize();
        list($errors, $warnings) = $this->validate();
        $this->assertFalse($errors);
        $this->assertTrue($warnings, 'There should be warnings');
        $this->assertTrue(count($warnings) == 1, 'There should be only one warning');
        $this->assertTrue($warnings[0]->uri == 'http://rs.tdwg.org/dwc/terms/taxonomicStatus');
        $this->assertTrue($warnings[0]->message == 'Unrecognized taxonomicStatus');
    }
    
    function testValidateMediaID()
    {
        $mr = new \eol_schema\MediaResource();
        $mr->title = 'not much here';
        $this->archive_builder->write_object_to_file($mr);
        $mr = new \eol_schema\MediaResource();
        $mr->identifier = 'someid';
        $mr->title = 'not much here';
        $this->archive_builder->write_object_to_file($mr);
        $this->archive_builder->finalize();
        list($errors, $warnings) = $this->validate();
        $this->assertTrue($errors, 'There should be errors');
        $this->assertTrue($errors[0]->uri == 'http://purl.org/dc/terms/identifier');
        $this->assertTrue($errors[0]->message == 'Media must have identifiers');
        $this->assertTrue($errors[1]->uri == 'http://rs.tdwg.org/dwc/terms/taxonID');
        $this->assertTrue($errors[1]->message == 'Media must have taxonIDs');
        $this->assertTrue($errors[2]->uri == 'http://purl.org/dc/terms/type');
        $this->assertTrue($errors[2]->message == 'DataType must be present');
    }
    
    function testValidateImagesNeedUrls()
    {
        $mr = new \eol_schema\MediaResource();
        $mr->identifier = "12345";
        $mr->type = 'http://purl.org/dc/dcmitype/StillImage';
        $mr->UsageTerms = 'http://creativecommons.org/licenses/by/3.0/';
        $this->archive_builder->write_object_to_file($mr);
        $this->archive_builder->finalize();
        list($errors, $warnings) = $this->validate();
        $this->assertTrue($errors, 'There should be errors');
        $this->assertTrue($errors[0]->message == 'Media must have taxonIDs');
        $this->assertTrue($errors[1]->message == 'Multimedia must have accessURIs');
    }
    
    function testValidateLicense()
    {
        $mr = new \eol_schema\MediaResource();
        $mr->identifier = "12345";
        $mr->taxonID = "99999";
        $mr->type = 'http://purl.org/dc/dcmitype/Text';
        $mr->description = "This is the text";
        $mr->CVterm = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Description";
        $mr->UsageTerms = 'http://creativecommons.org/licenses/by/3.0/';
        $this->archive_builder->write_object_to_file($mr);
        $this->archive_builder->finalize();
        list($errors, $warnings) = $this->validate();
        $this->assertFalse($errors);
        $this->reset();
        
        $mr->UsageTerms = 'not applicable';
        $this->archive_builder->write_object_to_file($mr);
        $this->archive_builder->finalize();
        list($errors, $warnings) = $this->validate();
        $this->assertFalse($errors);
        $this->reset();
        
        $mr->UsageTerms = 'no known copyright restrictions';
        $this->archive_builder->write_object_to_file($mr);
        $this->archive_builder->finalize();
        list($errors, $warnings) = $this->validate();
        $this->assertFalse($errors);
        $this->reset();
        
        $mr->UsageTerms = 'http://www.flickr.com/commons/usage/';
        $this->archive_builder->write_object_to_file($mr);
        $this->archive_builder->finalize();
        list($errors, $warnings) = $this->validate();
        $this->assertFalse($errors);
        $this->reset();
        
        $mr->UsageTerms = 'http://creativecommons.org/publicdomain/zero/1.0/';
        $this->archive_builder->write_object_to_file($mr);
        $this->archive_builder->finalize();
        list($errors, $warnings) = $this->validate();
        $this->assertFalse($errors);
        $this->reset();
        
        $mr->UsageTerms = NULL;
        $mr->license = 'http://creativecommons.org/publicdomain/zero/1.0/';
        $this->archive_builder->write_object_to_file($mr);
        $this->archive_builder->finalize();
        list($errors, $warnings) = $this->validate();
        $this->assertFalse($errors);
        $this->reset();
        
        $mr->license = 'http://creativecommons.org/licences/by/3.0/';
        $this->archive_builder->write_object_to_file($mr);
        $this->archive_builder->finalize();
        list($errors, $warnings) = $this->validate();
        $this->assertFalse($errors);
        $this->reset();
        
        $mr->license = NULL;
        $mr->UsageTerms = 'nonsense';
        $this->archive_builder->write_object_to_file($mr);
        $this->archive_builder->finalize();
        list($errors, $warnings) = $this->validate();
        $this->assertTrue($errors, 'There should be errors');
        $this->assertTrue($errors[0]->message == 'Invalid license');
    }
    
    function testValidateReferenceID()
    {
        $r = new \eol_schema\Reference();
        $r->title = 'not much here';
        $this->archive_builder->write_object_to_file($r);
        $this->archive_builder->finalize();
        list($errors, $warnings) = $this->validate();
        $this->assertTrue($errors, 'There should be errors');
        $this->assertTrue($errors[0]->message == 'References must have identifiers');
        $this->reset();
        
        $r = new \eol_schema\Reference();
        $r->identifier = '1234';
        $r->title = 'more here';
        $this->archive_builder->write_object_to_file($r);
        $this->archive_builder->finalize();
        list($errors, $warnings) = $this->validate();
        $this->assertFalse($errors);
    }
    
    function testValidateReferencesNeedTitles()
    {
        $r = new \eol_schema\Reference();
        $r->identifier = '123';
        $r->volume = 3;
        $this->archive_builder->write_object_to_file($r);
        $this->archive_builder->finalize();
        list($errors, $warnings) = $this->validate();
        $this->assertTrue($errors, 'There should be errors');
        $this->assertTrue($errors[0]->message == 'References must minimally contain a full_reference or title');
        $this->reset();
        
        $r = new \eol_schema\Reference();
        $r->identifier = '123';
        $r->volume = 3;
        $r->full_reference = 'this is where its at';
        $this->archive_builder->write_object_to_file($r);
        $this->archive_builder->finalize();
        list($errors, $warnings) = $this->validate();
        $this->assertFalse($errors);
        $this->reset();
        
        $r = new \eol_schema\Reference();
        $r->identifier = '123';
        $r->volume = 3;
        $r->title = 'this is where its at';
        $this->archive_builder->write_object_to_file($r);
        $this->archive_builder->finalize();
        list($errors, $warnings) = $this->validate();
        $this->assertFalse($errors);
        $this->reset();
        
        $r = new \eol_schema\Reference();
        $r->identifier = '123';
        $r->volume = 3;
        $r->primaryTitle = 'this is where its at';
        $this->archive_builder->write_object_to_file($r);
        $this->archive_builder->finalize();
        list($errors, $warnings) = $this->validate();
        $this->assertFalse($errors);
    }
    
    function testValidateAgentID()
    {
        $a = new \eol_schema\Agent();
        $a->term_name = 'Thomas Jefferson';
        $this->archive_builder->write_object_to_file($a);
        $this->archive_builder->finalize();
        list($errors, $warnings) = $this->validate();
        $this->assertTrue($errors, 'There should be errors');
        $this->assertTrue($errors[0]->message == 'Agents must have identifiers');
        $this->reset();
        
        $a = new \eol_schema\Agent();
        $a->identifier = '1234';
        $a->term_name = 'Thomas Jefferson';
        $this->archive_builder->write_object_to_file($a);
        $this->archive_builder->finalize();
        list($errors, $warnings) = $this->validate();
        $this->assertFalse($errors);
    }
    
    function testValidateAgentLogoURL()
    {
        $a = new \eol_schema\Agent();
        $a->identifier = '1234';
        $a->term_name = 'Thomas Jefferson';
        $a->term_logo = 'not a url';
        $this->archive_builder->write_object_to_file($a);
        $this->archive_builder->finalize();
        list($errors, $warnings) = $this->validate();
        $this->assertTrue($errors, 'There should be errors');
        $this->assertTrue($errors[0]->message == 'Invalid URL');
        $this->reset();
        
        $a = new \eol_schema\Agent();
        $a->identifier = '1234';
        $a->term_name = 'Thomas Jefferson';
        $a->term_logo = 'http://something';
        $this->archive_builder->write_object_to_file($a);
        $this->archive_builder->finalize();
        list($errors, $warnings) = $this->validate();
        $this->assertTrue($errors, 'There should be errors');
        $this->assertTrue($errors[0]->message == 'Invalid URL');
        $this->reset();
        
        $a = new \eol_schema\Agent();
        $a->identifier = '1234';
        $a->term_name = 'Thomas Jefferson';
        $a->term_logo = 'http://some.url';
        $this->archive_builder->write_object_to_file($a);
        $this->archive_builder->finalize();
        list($errors, $warnings) = $this->validate();
        $this->assertFalse($errors);
    }
    
    function testValidateAgentInvalidRole()
    {
        $a = new \eol_schema\Agent();
        $a->identifier = '1234';
        $a->term_name = 'Thomas Jefferson';
        $a->agentRole = 'president';
        $this->archive_builder->write_object_to_file($a);
        $this->archive_builder->finalize();
        list($errors, $warnings) = $this->validate();
        $this->assertTrue($warnings, 'There should be warnings');
        $this->assertTrue($warnings[0]->message == 'Unrecognized agent role');
        $this->reset();
        
        $a = new \eol_schema\Agent();
        $a->identifier = '1234';
        $a->term_name = 'Thomas Jefferson';
        $a->agentRole = 'author';
        $this->archive_builder->write_object_to_file($a);
        $this->archive_builder->finalize();
        list($errors, $warnings) = $this->validate();
        $this->assertFalse($warnings);
        $this->reset();
    }
    
    function testValidateAgentsNeedNames()
    {
        $a = new \eol_schema\Agent();
        $a->identifier = '123';
        $a->agentRole = 'photographer';
        $this->archive_builder->write_object_to_file($a);
        $this->archive_builder->finalize();
        list($errors, $warnings) = $this->validate();
        $this->assertTrue($errors, 'There should be errors');
        $this->assertTrue($errors[0]->message == 'Agents must minimally contain a term_name, term_firstName or term_familyName');
        $this->reset();
        
        $a = new \eol_schema\Agent();
        $a->identifier = '123';
        $a->term_name = 'Thomas Jefferson';
        $a->agentRole = 'photographer';
        $this->archive_builder->write_object_to_file($a);
        $this->archive_builder->finalize();
        list($errors, $warnings) = $this->validate();
        $this->assertFalse($errors);
        $this->reset();
        
        $a = new \eol_schema\Agent();
        $a->identifier = '123';
        $a->term_firstName = 'Thomas';
        $a->agentRole = 'photographer';
        $this->archive_builder->write_object_to_file($a);
        $this->archive_builder->finalize();
        list($errors, $warnings) = $this->validate();
        $this->assertFalse($errors);
        $this->reset();
        
        $a = new \eol_schema\Agent();
        $a->identifier = '123';
        $a->term_familyName = 'Jefferson';
        $a->agentRole = 'photographer';
        $this->archive_builder->write_object_to_file($a);
        $this->archive_builder->finalize();
        list($errors, $warnings) = $this->validate();
        $this->assertFalse($errors);
    }
    
    function testValidateVernacularNameTaxonID()
    {
        $v = new \eol_schema\VernacularName();
        $v->vernacularName = 'bluefish';
        $this->archive_builder->write_object_to_file($v);
        $this->archive_builder->finalize();
        list($errors, $warnings) = $this->validate();
        $this->assertTrue($errors, 'There should be errors');
        $this->assertTrue($errors[0]->message == 'Vernacular names must have taxonIDs');
        $this->reset();
        
        $v = new \eol_schema\VernacularName();
        $v->taxonID = '123';
        $v->vernacularName = 'bluefish';
        $this->archive_builder->write_object_to_file($v);
        $this->archive_builder->finalize();
        list($errors, $warnings) = $this->validate();
        $this->assertFalse($errors);
    }
    
    function testValidateVernacularNameLanguage()
    {
        $v = new \eol_schema\VernacularName();
        $v->taxonID = '123';
        $v->vernacularName = 'bluefish';
        $v->language = 'English';
        $this->archive_builder->write_object_to_file($v);
        $this->archive_builder->finalize();
        list($errors, $warnings) = $this->validate();
        $this->assertTrue($warnings, 'There should be warnings');
        $this->assertTrue($warnings[0]->message == 'Vernacular name languages should use standardized ISO 639 language codes');
        $this->reset();
        
        $v = new \eol_schema\VernacularName();
        $v->taxonID = '123';
        $v->vernacularName = 'bluefish';
        $v->language = 'eng-american';
        $this->archive_builder->write_object_to_file($v);
        $this->archive_builder->finalize();
        list($errors, $warnings) = $this->validate();
        $this->assertTrue($warnings, 'There should be warnings');
        $this->assertTrue($warnings[0]->message == 'Vernacular name languages should use standardized ISO 639 language codes');
        $this->reset();
        
        $v = new \eol_schema\VernacularName();
        $v->taxonID = '123';
        $v->vernacularName = 'bluefish';
        $v->language = 'en';
        $this->archive_builder->write_object_to_file($v);
        $this->archive_builder->finalize();
        list($errors, $warnings) = $this->validate();
        $this->assertFalse($warnings);
        $this->reset();
        
        $v = new \eol_schema\VernacularName();
        $v->taxonID = '123';
        $v->vernacularName = 'bluefish';
        $v->language = 'eng';
        $this->archive_builder->write_object_to_file($v);
        $this->archive_builder->finalize();
        list($errors, $warnings) = $this->validate();
        $this->assertFalse($warnings);
        $this->reset();
        
        $v = new \eol_schema\VernacularName();
        $v->taxonID = '123';
        $v->vernacularName = 'bluefish';
        $v->language = 'en-us';
        $this->archive_builder->write_object_to_file($v);
        $this->archive_builder->finalize();
        list($errors, $warnings) = $this->validate();
        $this->assertFalse($warnings);
    }
    
    private function validate()
    {
        $archive = new ContentArchiveReader(null, $this->archive_directory);
        $validator = new ContentArchiveValidator($archive);
        $validator->get_validation_errors();
        return array($validator->display_errors(), $validator->display_warnings());
    }
}

?>