<?php
namespace php_active_record;

require_library('XLSParser');

class test_specialist_project_converter extends SimpletestUnitBase
{
    function testSpecialistProjectConversionToXML()
    {
        $file = DOC_ROOT . "tests/fixtures/files/eol_specialist_project.xls";
        $parser = new XLSParser();
        $arr = $parser->convert_sheet_to_array($file);          
        $xml_response = $parser->create_specialist_project_xml($arr);        
        $xml = simplexml_load_string($xml_response, null, LIBXML_NOCDATA);
        $this->assertTrue(count($xml->taxon) == 6, 'There should be 6 taxa in result XML');        
    }
}
?>