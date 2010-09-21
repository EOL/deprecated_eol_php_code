<?php

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
        $this->assertPattern('/<rank>Species<\/rank>/', 'Search should find the right rank');
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
        $this->assertPattern('/<Name scientific=\'true\' ref=\'n1\'>Aus bus Linnaeus<\/Name>/', 'Should return the name in the TaxonConcep');
    }
    
    function testDetailsMissingID()
    {
        $this->prepare_test_data();
        
        $this->get(WEB_ROOT . 'applications/lifedesk/service.php?function=details_tcs&id=123451245&ENV_NAME=test');
        $this->assertPattern('/<\/DataSet>/', 'Should return an empty response');
    }
    
    
    function prepare_test_data()
    {
        $hierarchy_id = Hierarchy::insert(array('id' => 529, 'label' => 'Test Hierarchy'));
        $name_id = Name::insert('Aus bus Linnaeus');
        $rank_id = Rank::insert('species');
        HierarchyEntry::insert(array('hierarchy_id' => $hierarchy_id, 'name_id' => $name_id, 'rank_id' => $rank_id));
    }
}

?>