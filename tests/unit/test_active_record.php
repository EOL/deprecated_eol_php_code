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
}

?>