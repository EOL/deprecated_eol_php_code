<?php
namespace php_active_record;

class test_lifedesk_service extends SimpletestWebBase
{
    function testNoData()
    {
        $this->get(WEB_ROOT . 'applications/lifedesk/service.php?ENV_NAME=test');
        $this->assertPattern('/'. preg_quote('<?xml version="1.0" encoding="UTF-8"?>', '/') .'/', 'LifeDeskAPI should return XML');
        $this->assertPattern('/'. preg_quote('<results xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">', '/') .'/', 'LifeDeskAPI should have a results element');
        $this->assertPattern('/<\/results>/', 'LifeDeskAPI should have a close results element');
    }
    
    function testSearch()
    {
        $this->prepare_test_data();
        $this->get(WEB_ROOT . 'applications/lifedesk/service.php?function=search&search=Aus bus&ENV_NAME=test');
        $this->assertPattern('/<title>Test Hierarchy<\/title>/', 'Search should find the right hierarchy');
        $this->assertPattern('/<name>Aus bus Linnaeus<\/name>/', 'Search should find the right name');
        $this->assertPattern('/<rank>species<\/rank>/', 'Search should find the right rank');
        $this->assertPattern('/<number_of_children>0<\/number_of_children>/', 'Search should find the right number of children');
    }
    
    function testDetailsTCS()
    {
        $this->prepare_test_data();
        
        $this->get(WEB_ROOT . 'applications/lifedesk/service.php?function=details_tcs&id=1&ENV_NAME=test');
        $this->assertPattern('/<TaxonName id=\'n1\' nomenclaturalCode=\'Zoological\'>/', 'Should return a TaxonName');
        $this->assertPattern('/<Simple>Aus bus Linnaeus<\/Simple>/', 'Should return the full name');
        $this->assertPattern('/<Simple>Aus bus<\/Simple>/', 'Should return the canonical name');
        $this->assertPattern('/<Rank code=\'sp\'>Species<\/Rank>/', 'Should return the canonical name');
        $this->assertPattern('/<Name scientific=\'true\' ref=\'n1\'>Aus bus Linnaeus<\/Name>/', 'Should return the name in the TaxonConcept');
    }
    
    function testDetailsMissingID()
    {
        $this->prepare_test_data();
        
        $this->get(WEB_ROOT . 'applications/lifedesk/service.php?function=details_tcs&id=123451245&ENV_NAME=test');
        $this->assertPattern('/<\/DataSet>/', 'Should return an empty response');
    }
    
    
    function prepare_test_data()
    {
        $hierarchy = Hierarchy::find_or_create(array('label' => 'Test Hierarchy', 'browsable' => 1));
        $name = Name::find_or_create_by_string('Aus bus Linnaeus');
        $rank = Rank::find_or_create_by_translated_label('species');
        HierarchyEntry::find_or_create(array('hierarchy' => $hierarchy, 'name' => $name, 'rank' => $rank));
    }
}

?>