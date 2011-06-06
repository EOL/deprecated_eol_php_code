<?php
namespace php_active_record;

class test_functions extends SimpletestUnitBase
{
    function testIsUTF8()
    {
        $this->assertTrue(Functions::is_utf8('Simple'), 'String should be UTF8');
        $this->assertTrue(Functions::is_utf8('More("Complex") \'string\' 123'), 'String should be UTF8');
        $this->assertTrue(Functions::is_utf8('2038457alksjdbf(Q#*^&@)$(*&?><|}:"")'), 'String should be UTF8');
        $this->assertTrue(Functions::is_utf8('èñøÂÆÇº'), 'String should be UTF8');
        $this->assertTrue(Functions::is_utf8('Iñtërnâtiônàlizætiøn'), 'String should be UTF8');
        $this->assertTrue(Functions::is_utf8(' 〰‰〰〠〰〰ਠ†簠†††‱ㄠ〰㄰‰〰ㄊ†‽‱㄰ㄠㄱㄱ‰〱〠〰〱ਠ†㴠へ䑆㈱਼⽰牥㸊㱰㹔桥⁣潲牥捴⁕呆ⴱ'), 'String should be UTF8');
        $this->assertTrue(Functions::is_utf8('Çinekop balığı'), 'String should be UTF8');
        $this->assertTrue(Functions::is_utf8(' പുന്ജി'), 'String should be UTF8');
        
        $this->assertFalse(Functions::is_utf8("\xC2"), 'String should not be UTF8');
    }
    
    function testIsAscii()
    {
        $this->assertTrue(Functions::is_ascii('Simple'), 'String should be ASCII');
        $this->assertTrue(Functions::is_ascii('More("Complex") \'string\' 123'), 'String should be ASCII');
        $this->assertTrue(Functions::is_ascii('2038457alksjdbf(Q#*^&@)$(*&?><|}:"")'), 'String should be ASCII');
        
        $this->assertFalse(Functions::is_ascii('èñøÂÆÇº'), 'String should not be ASCII');
        $this->assertFalse(Functions::is_ascii('Iñtërnâtiônàlizætiøn'), 'String should not be ASCII');
        $this->assertFalse(Functions::is_ascii(' 〰‰〰〠〰〰ਠ†簠†††‱ㄠ〰㄰‰〰ㄊ†‽‱㄰ㄠㄱㄱ‰〱〠〰〱ਠ†㴠へ䑆㈱਼⽰牥㸊㱰㹔桥⁣潲牥捴⁕呆ⴱ'), 'String should not be ASCII');
        $this->assertFalse(Functions::is_ascii('Çinekop balığı'), 'String should not be ASCII');
        $this->assertFalse(Functions::is_ascii(' പുന്ജി'), 'String should not be ASCII');
        $this->assertFalse(Functions::is_ascii("\xC2"), 'String should not be ASCII');
    }
    
    function testCanonicalForm()
    {
        $this->assertTrue(Functions::canonical_form('Homo sapiens') == 'Homo sapiens', 'Canonical form should be correct');
        $this->assertTrue(Functions::canonical_form('HOMO sapiens') == 'HOMO sapiens', 'Canonical form should be correct');
        $this->assertTrue(Functions::canonical_form('Homo sapiens var sapiens') == 'Homo sapiens sapiens', 'Canonical form should be correct');
        $this->assertTrue(Functions::canonical_form('Homo sapiens var. sapiens') == 'Homo sapiens sapiens', 'Canonical form should be correct');
        $this->assertTrue(Functions::canonical_form('Homo sapiens Linn. var. sapiens Smith 2009') == 'Homo sapiens sapiens', 'Canonical form should be correct');
        $this->assertTrue(Functions::canonical_form('Homo sapiens Linn. var. sapiens ex von Smith 2009') == 'Homo sapiens sapiens', 'Canonical form should be correct');
        $this->assertTrue(Functions::canonical_form('X Homo sapiens Linn. var. sapiens ex von Smith 2009') == 'Homo sapiens sapiens', 'Canonical form should be correct');
        $this->assertTrue(Functions::canonical_form('x Homo sapiens Linn. var. sapiens ex von Smith 2009') == 'Homo sapiens sapiens', 'Canonical form should be correct');
    }
    
    function testItalicizedForm()
    {
        $this->assertTrue(Functions::italicized_form('Homo sapiens') == '<i>Homo sapiens</i>', 'Italicized form should be correct');
        $this->assertTrue(Functions::italicized_form('Homo sapiens Linn. 2009') == '<i>Homo sapiens</i> Linn. 2009', 'Italicized form should be correct');
    }
    
    function testGuid()
    {
        $guid = Functions::generate_guid();
        $this->assertTrue(strlen($guid) == 32, 'GUIDs should be 32 characters long');
        $this->assertPattern("/^[a-z0-9]+$/", $guid, 'GUIDs should be [a-z0-9]');
        
        $guid2 = Functions::generate_guid();
        $this->assertTrue($guid != $guid2, 'Should get a new GUID each time');
    }
    
    function testRemoveWhitespace()
    {
        $this->assertTrue(Functions::remove_whitespace('A         b') == 'A b', 'Should have removed whitespace');
        $this->assertTrue(Functions::remove_whitespace('A b') == 'A b', 'Should have removed whitespace');
        $this->assertTrue(Functions::remove_whitespace('A b ') == 'A b', 'Should have removed whitespace');
        $this->assertTrue(Functions::remove_whitespace('A      b   ') == 'A b', 'Should have removed whitespace');
    }
    
    function testImportDecode()
    {
        $this->assertTrue(Functions::import_decode('Búfal aquàtic', false, false) == 'Búfal aquàtic', 'Should get decoded properly');
        $this->assertTrue(Functions::import_decode('Búfal   aquàtic ', false, false) == 'Búfal   aquàtic', 'Should get decoded properly');
        $this->assertTrue(Functions::import_decode('Búfal   aquàtic ') == 'Búfal   aquàtic', 'Shouldnt remove whitespace by default');
        $this->assertTrue(Functions::import_decode('Búfal   aquàtic ', true, false) == 'Búfal aquàtic', 'Should get decoded properly');
        $this->assertTrue(Functions::import_decode("Búfal \x0A aquàtic", true, false) == 'Búfal &nbsp; aquàtic', 'Should get decoded properly');
        $this->assertTrue(Functions::import_decode("Búfal   aquàtic") == 'Búfal   aquàtic', 'Should get decoded properly');
        $this->assertTrue(Functions::import_decode('Búfal &lt;aquàtic&gt;', false, false) == 'Búfal &lt;aquàtic&gt;', 'Should get decoded properly');
        $this->assertTrue(Functions::import_decode('Búfal &lt;aquàtic&gt;', false, true) == 'Búfal <aquàtic>', 'Should get decoded properly');
        $this->assertTrue(Functions::import_decode('Búfal &lt;aquàtic&gt;') == 'Búfal <aquàtic>', 'Decode should be the default');
        $this->assertTrue(Functions::import_decode('Búfal &lt;aquàtic&gt;') == 'Búfal <aquàtic>', 'Decode should be the default');
        
        $this->assertTrue(Functions::import_decode('Búfal &nbsp; aquàtic', false, false) == 'Búfal &nbsp; aquàtic', 'Decode should be the default');
        $this->assertTrue(Functions::import_decode('Búfal &nbsp; aquàtic', false, true) == 'Búfal   aquàtic', 'Decode should be the default');
        $this->assertTrue(Functions::import_decode('Búfal &nbsp; aquàtic', true, true) == 'Búfal aquàtic', 'Decode should be the default');
    }

}

?>