<?php
namespace php_active_record;

class test_content_archive_parser extends SimpletestUnitBase
{    
    function testParse()
    {
        $harvester = new ContentArchiveReader(NULL, DOC_ROOT . "tests/fixtures/files/eol_schema2");
        $harvester->process_row_type("http://rs.tdwg.org/dwc/terms/Taxon", 'php_active_record\\something_cool');
        // $harvester->process_row_type("http://www.eol.org/schema/transfer#MediaResource", 'php_active_record\\something_else');
    }
}

function something_cool($row)
{
    return $row;
}


?>