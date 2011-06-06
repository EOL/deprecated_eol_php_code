<?php
namespace php_active_record;

class test_schema_example extends SimpletestWebBase
{
    function testSchemaExampleCode()
    {
        $this->get(WEB_ROOT . 'applications/schema/schema_creation_example.php');
        $this->assertPattern('/<dwc:ScientificName>Ursus maritimus Phipps, 1774<\/dwc:ScientificName>/', 'Should see the taxon name');
        $this->assertPattern('/<dc:title>Polar Bear {Ursus maritimus}<\/dc:title>/', 'Should see the text title');
        $this->assertPattern('/<agent role="author">Leary, P<\/agent>/', 'Should see the agent name');
    }
}

?>