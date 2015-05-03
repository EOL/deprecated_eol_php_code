<?php
namespace php_active_record;

class test_validator extends SimpletestUnitBase
{
    function testProperValidation()
    {
        $response = SchemaValidator::validate(DOC_ROOT . "tests/fixtures/files/test_resource.xml");
        $this->assertTrue($response, "There should be a response");
        $this->assertNotA($response, "array", "Response should not be an array");
    }
    
    function testImroperValidation()
    {
        $doc = simplexml_load_file(DOC_ROOT . "tests/fixtures/files/test_resource.xml");
        $taxon = $doc->addChild('taxon');
        $taxon->addChild('identifier', '12345', 'http://purl.org/dc/elements/1.1/');
        $taxon->addChild('scientificname', 'some name', 'http://rs.tdwg.org/dwc/dwcore/');
        //this should be capitalized to be valid
        
        if(!($FILE = fopen(DOC_ROOT . "temp/resource.xml", "w+")))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " . DOC_ROOT . "temp/resource.xml");
          return;
        }
        fwrite($FILE, $doc->asXML());
        fclose($FILE);
        
        $response = SchemaValidator::validate(DOC_ROOT . "temp/resource.xml");
        $this->assertTrue($response, "There should be a response");
        $this->assertIsA($response, "array", "Response should be an array of errors");
        
        unlink(DOC_ROOT . "temp/resource.xml");
    }
}

?>