<?php
namespace php_active_record;

class test_names extends SimpletestUnitBase
{    
    function testNameFunctions()
    {
        $str = "Aus bus Smith (Linnaeus 1777)";
        $cf = "Aus bus";
        $cl = "aus bus smith ( linnaeus 1777 )";
        
        $canonical_form = Functions::canonical_form($str);
        $clean_name = Functions::clean_name($str);
        
        $this->assertTrue($canonical_form == $cf, "The canonical form should be right");
        $this->assertTrue($clean_name == $cl, "The clean name should be right");
        $this->assertTrue(Functions::canonical_form('Homo sapiens cultiv sapiens del Leary') == 'Homo sapiens sapiens', 'Should have correct canonical form');
    }
    
    function testInsertNameWithoutCaching()
    {
        $GLOBALS['no_cache']['names'] = true;
        $str = "Aus bus Smith (Linnaeus 1777)";
        
        $name = Name::find_or_create_by_string($str);
        $this->assertTrue($name->id > 0, "There should be a name_id");
        $this->assertTrue($name->id > 0, "Should be able to make a name object");
        $this->assertTrue($name->canonical_form->string == "Aus bus", "Name should have a canonical form");
    }
    
    function testInsertNameWithCaching()
    {
        $GLOBALS["ENV_ENABLE_CACHING_SAVED"] = $GLOBALS["ENV_ENABLE_CACHING"];
        $GLOBALS["ENV_ENABLE_CACHING"] = true;
        $GLOBALS['no_cache']['names'] = false;
        
        $str = "Aus bus Smith (Linnaeus 1777)";
        
        $name = Name::find_or_create_by_string($str);
        $this->assertTrue($name->id > 0, "There should be a name_id");
        $this->assertTrue($name->canonical_form->string == "Aus bus", "Name should have a canonical form");
        
        $GLOBALS['no_cache']['names'] = true;
        $GLOBALS["ENV_ENABLE_CACHING"] = $GLOBALS["ENV_ENABLE_CACHING_SAVED"];
        unset($GLOBALS["ENV_ENABLE_CACHING_SAVED"]);
    }
    
    function testIsSurrogate()
    {
        $surrogates = array('Deferribacters incertae sedis',
                            'Amanita sp. 1 HKAS 38419',
                            'Amanita cf. muscaria MFC-14',
                            'Incertae sedis{51. 1. }',
                            'Amanita cf. pantherina HKAS 26746',
                            'Lactobacullus genera incertae sedis',
                            'Incertae sedis{25. 15}',
                            'uncultured Leucocoprinus',
                            'Morchella esculenta ß ovalis Wallr.',
                            'Yersinia pestis G1670',
                            'Yersinia pestis Nepal516',
                            'Yersinia pestis biovar Orientalis str. PEXU2',
                            'Yersinia pestis FV-1',
                            'Yersinia pestis KIM D27',
                            'Artemisia vulgaris (type 1)',
                            'Helicobacter pylori 120',
                            'Helicobacter pylori HPKX_1039_AG0C1',
                            'Helicobacter pylori 74B',
                            'Helicobacter pylori 245',
                            'Infectious bursal disease virus',
                            'JC virus',
                            'Doritis pulcherrima hybrid',
                            'Doritis pulcherrima cultivar',
                            'Heuchera sanguinea X Tiarella cordifolia',
                            'Heuchera sanguinea x Tiarella cordifolia',
                            'Heuchera sanguinea × Tiarella cordifolia',
                            'Herpes simplexvirus',
                            'Herpes simplex strain',
                            'Oryza sativa Japonica Group',
                            'Asteraceae environmental sample',
                            'Polychaeta group',
                            'Drosophila cf. polychaeta SM-2007',
                            'Helicobacter pylori NCTC 11637',
                            'Coccidioides posadasii RMSCC 1040',
                            'Coccidioides posadasii RMSCC 2133',
                            'Coccidioides posadasii CPA 0001',
                            'Coccidioides posadasii str. Silveira',
                            'Arctiidae_unassigned',
                            'haloarchaeon TP100');
        foreach($surrogates as $surrogate)
        {
            $this->assertTrue(Name::is_surrogate($surrogate), "These names should be surrogates");
        }
        
        $valid_names = array('Aus bus',
                             'Aus bus Linnaeus',
                             'Aus bus Linnaeus 1983',
                             'Aus bus var. cus Linnaeus 1777',
                             'Aus bus var. cus (Linnaeus 1785)',
                             'Aus bus var. cus Linnaeus,1934',
                             'Aus bus var. cus (Linnaeus,1934)',
                             'Aus bus var. cus Linnaeus 1766-7',
                             'Something 7-maculata');
        foreach($valid_names as $valid_name)
        {
            $this->assertFalse(Name::is_surrogate($valid_name), "These names should not be surrogates");
        }
    }
}

?>