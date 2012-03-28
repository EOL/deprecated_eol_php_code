<?php
namespace php_active_record;
require_vendor('eol_content_schema_v2');
require_library('ArchiveDataIngester');
require_library('ContentArchiveValidator');

class test_archive_validator extends SimpletestUnitBase
{
    // function setUp()
    // {
    //     parent::setUp();
    //     $this->archive_directory = DOC_ROOT . "tmp/test_archive_validator/";
    //     // delete the contents of the created archive
    //     if(!file_exists($this->archive_directory)) mkdir($this->archive_directory);
    //     $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->archive_directory));
    // }
    // 
    // function tearDown()
    // {
    //     $files_in_archive = read_dir($this->archive_directory);
    //     foreach($files_in_archive as $file)
    //     {
    //         if(substr($file, 0, 1) == '.') continue;
    //         unlink($this->archive_directory . $file);
    //     }
    //     rmdir($this->archive_directory);
    //     unset($this->archive_builder);
    //     parent::tearDown();
    // }
    // 
    // function reset()
    // {
    //     // delete existing files
    //     $files_in_archive = read_dir($this->archive_directory);
    //     foreach($files_in_archive as $file)
    //     {
    //         if(substr($file, 0, 1) == '.') continue;
    //         unlink($this->archive_directory . $file);
    //     }
    //     $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->archive_directory));
    // }
    // 
    // function testValidateTaxonComplete()
    // {
    //     $t = new \eol_schema\Taxon();
    //     $t->taxonID = "12345";
    //     $t->scientificName = "Aus bus";
    //     $t->taxonRank = "species";
    //     $t->taxonomicStatus = "accepted";
    //     $this->archive_builder->write_object_to_file($t);
    //     $this->archive_builder->finalize();
    //     list($errors, $warnings) = $this->validate();
    //     $this->assertFalse($errors);
    //     $this->assertFalse($warnings);
    // }
    // 
    // function testValidateTaxonID()
    // {
    //     $t = new \eol_schema\Taxon();
    //     $t->scientificName = "Aus bus";
    //     $this->archive_builder->write_object_to_file($t);
    //     $this->archive_builder->finalize();
    //     list($errors, $warnings) = $this->validate();
    //     $this->assertTrue($errors, 'There should be errors');
    //     $this->assertTrue($errors[0]->uri == 'http://rs.tdwg.org/dwc/terms/taxonID');
    //     $this->assertTrue($errors[0]->message == 'Taxa must have identifiers');
    // }
    // 
    // function testValidateTaxonScientificName()
    // {
    //     $t = new \eol_schema\Taxon();
    //     $t->taxonID = "12345";
    //     $this->archive_builder->write_object_to_file($t);
    //     $this->archive_builder->finalize();
    //     list($errors, $warnings) = $this->validate();
    //     $this->assertFalse($errors);
    //     $this->assertTrue($warnings, 'There should be warnings');
    //     $this->assertTrue(count($warnings) == 2, 'There should be 2 warnings');
    //     $this->assertTrue($warnings[0]->uri == 'http://rs.tdwg.org/dwc/terms/scientificName');
    //     $this->assertTrue($warnings[0]->message == 'Taxa should have scientificNames');
    //     $this->assertTrue($warnings[1]->message == 'Taxa should contain a scientificName or minimally a kingdom, phylum, class, order, family or genus');
    // }
    // 
    // function testValidateTaxonAnyName()
    // {
    //     $t = new \eol_schema\Taxon();
    //     $t->taxonID = "12345";
    //     $t->scientificName = "Aus bus";
    //     $this->archive_builder->write_object_to_file($t);
    //     $this->archive_builder->finalize();
    //     list($errors, $warnings) = $this->validate();
    //     $this->assertFalse($errors);
    //     $this->assertFalse($warnings);
    //     $this->reset();
    //     
    //     $t = new \eol_schema\Taxon();
    //     $t->taxonID = "12345";
    //     $t->genus = "Aus";
    //     $this->archive_builder->write_object_to_file($t);
    //     $this->archive_builder->finalize();
    //     list($errors, $warnings) = $this->validate();
    //     $this->assertFalse($errors);
    //     $this->assertTrue($warnings, 'There should be warnings');
    //     $this->assertTrue(count($warnings) == 1, 'There should be only one warning');
    //     $this->assertTrue($warnings[0]->uri == 'http://rs.tdwg.org/dwc/terms/scientificName');
    //     $this->assertTrue($warnings[0]->message == 'Taxa should have scientificNames');
    //     $this->reset();
    //     
    //     $t = new \eol_schema\Taxon();
    //     $t->taxonID = "12345";
    //     $t->family = "Aus";
    //     $this->archive_builder->write_object_to_file($t);
    //     $this->archive_builder->finalize();
    //     list($errors, $warnings) = $this->validate();
    //     $this->assertFalse($errors);
    //     $this->assertTrue($warnings, 'There should be warnings');
    //     $this->assertTrue(count($warnings) == 1, 'There should be only one warning');
    //     $this->assertTrue($warnings[0]->uri == 'http://rs.tdwg.org/dwc/terms/scientificName');
    //     $this->assertTrue($warnings[0]->message == 'Taxa should have scientificNames');
    //     $this->reset();
    //     
    //     $t = new \eol_schema\Taxon();
    //     $t->taxonID = "12345";
    //     $t->order = "Aus";
    //     $this->archive_builder->write_object_to_file($t);
    //     $this->archive_builder->finalize();
    //     list($errors, $warnings) = $this->validate();
    //     $this->assertFalse($errors);
    //     $this->assertTrue($warnings, 'There should be warnings');
    //     $this->assertTrue(count($warnings) == 1, 'There should be only one warning');
    //     $this->assertTrue($warnings[0]->uri == 'http://rs.tdwg.org/dwc/terms/scientificName');
    //     $this->assertTrue($warnings[0]->message == 'Taxa should have scientificNames');
    //     $this->reset();
    //     
    //     $t = new \eol_schema\Taxon();
    //     $t->taxonID = "12345";
    //     $t->class = "Aus";
    //     $this->archive_builder->write_object_to_file($t);
    //     $this->archive_builder->finalize();
    //     list($errors, $warnings) = $this->validate();
    //     $this->assertFalse($errors);
    //     $this->assertTrue($warnings, 'There should be warnings');
    //     $this->assertTrue(count($warnings) == 1, 'There should be only one warning');
    //     $this->assertTrue($warnings[0]->uri == 'http://rs.tdwg.org/dwc/terms/scientificName');
    //     $this->assertTrue($warnings[0]->message == 'Taxa should have scientificNames');
    //     $this->reset();
    //     
    //     $t = new \eol_schema\Taxon();
    //     $t->taxonID = "12345";
    //     $t->phylum = "Aus";
    //     $this->archive_builder->write_object_to_file($t);
    //     $this->archive_builder->finalize();
    //     list($errors, $warnings) = $this->validate();
    //     $this->assertFalse($errors);
    //     $this->assertTrue($warnings, 'There should be warnings');
    //     $this->assertTrue(count($warnings) == 1, 'There should be only one warning');
    //     $this->assertTrue($warnings[0]->uri == 'http://rs.tdwg.org/dwc/terms/scientificName');
    //     $this->assertTrue($warnings[0]->message == 'Taxa should have scientificNames');
    //     $this->reset();
    //     
    //     $t = new \eol_schema\Taxon();
    //     $t->taxonID = "12345";
    //     $t->kingdom = "Aus";
    //     $this->archive_builder->write_object_to_file($t);
    //     $this->archive_builder->finalize();
    //     list($errors, $warnings) = $this->validate();
    //     $this->assertFalse($errors);
    //     $this->assertTrue($warnings, 'There should be warnings');
    //     $this->assertTrue(count($warnings) == 1, 'There should be only one warning');
    //     $this->assertTrue($warnings[0]->uri == 'http://rs.tdwg.org/dwc/terms/scientificName');
    //     $this->assertTrue($warnings[0]->message == 'Taxa should have scientificNames');
    //     $this->reset();
    // }
    // 
    // function testValidateTaxonRank()
    // {
    //     $t = new \eol_schema\Taxon();
    //     $t->taxonID = "12345";
    //     $t->scientificName = "Aus bus";
    //     $t->taxonRank = "nonsense";
    //     $this->archive_builder->write_object_to_file($t);
    //     $this->archive_builder->finalize();
    //     list($errors, $warnings) = $this->validate();
    //     $this->assertFalse($errors);
    //     $this->assertTrue($warnings, 'There should be warnings');
    //     $this->assertTrue(count($warnings) == 1, 'There should be only one warning');
    //     $this->assertTrue($warnings[0]->uri == 'http://rs.tdwg.org/dwc/terms/taxonRank');
    //     $this->assertTrue($warnings[0]->message == 'Taxa should have a valid rank');
    // }
    // 
    // function testValidateTaxonStatus()
    // {
    //     $t = new \eol_schema\Taxon();
    //     $t->taxonID = "12345";
    //     $t->scientificName = "Aus bus";
    //     $t->taxonomicStatus = "nonsense";
    //     $this->archive_builder->write_object_to_file($t);
    //     $this->archive_builder->finalize();
    //     list($errors, $warnings) = $this->validate();
    //     $this->assertFalse($errors);
    //     $this->assertTrue($warnings, 'There should be warnings');
    //     $this->assertTrue(count($warnings) == 1, 'There should be only one warning');
    //     $this->assertTrue($warnings[0]->uri == 'http://rs.tdwg.org/dwc/terms/taxonomicStatus');
    //     $this->assertTrue($warnings[0]->message == 'Taxa should have a valid taxonomicStatus');
    // }
    // 
    // function testValidateMediaID()
    // {
    //     $mr = new \eol_schema\MediaResource();
    //     $mr->title = 'not much here';
    //     $this->archive_builder->write_object_to_file($mr);
    //     $this->archive_builder->finalize();
    //     list($errors, $warnings) = $this->validate();
    //     $this->assertTrue($errors, 'There should be errors');
    //     $this->assertTrue($errors[0]->uri == 'http://purl.org/dc/terms/identifier');
    //     $this->assertTrue($errors[0]->message == 'Media must have identifiers');
    //     $this->assertTrue($errors[1]->uri == 'http://purl.org/dc/terms/type');
    //     $this->assertTrue($errors[1]->message == 'DataType must be present');
    // }
    // 
    // function testValidateImagesNeedUrls()
    // {
    //     $mr = new \eol_schema\MediaResource();
    //     $mr->identifier = "12345";
    //     $mr->type = 'http://purl.org/dc/dcmitype/StillImage';
    //     $this->archive_builder->write_object_to_file($mr);
    //     $this->archive_builder->finalize();
    //     list($errors, $warnings) = $this->validate();
    //     $this->assertTrue($errors, 'There should be errors');
    //     $this->assertTrue($errors[0]->message == 'Images must have an accessURI');
    // }
    // 
    // 
    // function validate()
    // {
    //     $archive = new ContentArchiveReader(null, $this->archive_directory);
    //     $validator = new ContentArchiveValidator($archive);
    //     $validator->get_validation_errors();
    //     return array($validator->errors(), $validator->warnings());
    // }
}

?>