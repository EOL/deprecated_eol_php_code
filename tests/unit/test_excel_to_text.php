<?php
namespace php_active_record;
require_library('ExcelToText');

class test_excel_to_text extends SimpletestUnitBase
{
    function testSomething()
    {
        $xml_converter = new ExcelToText(DOC_ROOT .'/tests/fixtures/files/new_schema.xlsx');
        $xml_converter->convert_to_new_schema_archive();
    }
}

?>