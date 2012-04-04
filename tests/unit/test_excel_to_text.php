<?php
namespace php_active_record;
require_library('ExcelToText');

class test_excel_to_text extends SimpletestUnitBase
{
    function testSomething()
    {
        ExcelToText::worksheet_to_file(DOC_ROOT .'/tests/fixtures/files/new_schema_spreadsheet.xlsx', DOC_ROOT .'/temp/xyz.out');
    }
}

?>