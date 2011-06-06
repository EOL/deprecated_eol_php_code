<?php
namespace php_active_record;

class test_darwincore_module extends SimpletestUnitBase
{    
    function testNameFunctions()
    {
        $taxon = new DarwinCoreTaxon(array('scientificName' => 'Aus bus Linnaeus 1776', 'junk' => 'nonsense'));
        $this->assertTrue($taxon->scientificName == 'Aus bus Linnaeus 1776', 'Elements in DWC should be set');
        $this->assertFalse(isset($taxon->junk), 'Elements not in DWC shouldnt be set');
    }
}

?>