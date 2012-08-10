<?php
namespace php_active_record;

class test_active_record extends SimpletestUnitBase
{
    function testFindOrCreateWithNullValue()
    {
        $this->assertEqual(MimeType::find_or_create_by_label(NULL), NULL);
    }
    
    function testFindOrCreateWithArray()
    {
        $parameters = array("given_name" => "Jane", "family_name" => "Smith");
        $user = User::find_or_create($parameters);
        $this->assertTrue($user->id, "Should have created a new user");
        $this->assertEqual($user->given_name, "Jane", "Should have proper given_name");
        $this->assertEqual($user->family_name, "Smith", "Should have proper family_name");
        
        $parameters = array( "agent_id" => 111, "description" => "Description of Hierarchy");
        $hierarchy = Hierarchy::find_or_create($parameters);
        $this->assertTrue($hierarchy->id > 0, "Hierarchy should have an id");
        $this->assertTrue($hierarchy->agent_id == 111, "Hierarchy should have the proper agent_id");
        $this->assertTrue($hierarchy->description == "Description of Hierarchy", "Hierarchy should have proper description");
    }
    
    function testFindTranslatedLanguage()
    {
        $en = Language::find_or_create_by_translated_label('English');
        $sp = Language::find_or_create_by_translated_label('Spanish', array('iso_639_1' => 'sp'));
        TranslatedLanguage::create(array('language_id' => $sp->id, 'original_language_id' => $en->id, 'label' => 'Anglais'));
    }
    
    function testFindWithEmptyStrings()
    {
        $params = array("provider_mangaed_id"       => 0,
                        "full_reference"            => 'This is the text of the reference',
                        "title"                     => '',
                        "authors"                   => '',
                        "publication_created_at"    => '0000-00-00 00:00:00',
                        "language_id"               => 0);
        $reference_first = Reference::find_or_create($params);
        $reference_second = Reference::find_or_create($params);
        $this->assertEqual($reference_first->id, $reference_second->id, "Should be able to lookup using empty strings");
    }
    
    function testFindWithNullValues()
    {
        $params = array("provider_mangaed_id"       => 0,
                        "full_reference"            => 'This is the text of the reference',
                        "title"                     => NULL,
                        "authors"                   => NULL,
                        "publication_created_at"    => '0000-00-00 00:00:00',
                        "language_id"               => 0);
        $reference_first = Reference::find_or_create($params);
        $reference_second = Reference::find_or_create($params);
        $this->assertEqual($reference_first->id, $reference_second->id, "Should be able to lookup using NULL values");
    }
}

?>