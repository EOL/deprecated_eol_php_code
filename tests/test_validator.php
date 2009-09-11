<?php

include_once("TestBase.php");

class test_validator extends TestBase
{
    function testProperValidation()
    {
        $validator = new SchemaValidator();
        $response = $validator->validate(LOCAL_ROOT."/fixtures/files/resource.xml");
        $this->assertTrue($response, "There should be a response");
        $this->assertNotA($response, "array", "Response should not be an array");
    }
    
    function testImroperValidation()
    {
        $doc = simplexml_load_file(LOCAL_ROOT."/fixtures/files/resource.xml");
        $taxon = $doc->addChild('taxon');
        $taxon->addChild('identifier', '12345', 'http://purl.org/dc/elements/1.1/');
        $taxon->addChild('scientificname', 'some name', 'http://rs.tdwg.org/dwc/dwcore/');
        //this should be capitalized to be valid
        
        $FILE = fopen(LOCAL_ROOT."/temp/resource.xml", "w+");
        fwrite($FILE, $doc->asXML());
        fclose($FILE);
        
        $validator = new SchemaValidator();
        $response = $validator->validate(LOCAL_ROOT."/temp/resource.xml");
        $this->assertTrue($response, "There should be a response");
        $this->assertIsA($response, "array", "Response should be an array of errors");
    }
}

?>