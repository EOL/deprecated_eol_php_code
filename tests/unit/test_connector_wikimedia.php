<?php
namespace php_active_record;
require_vendor('wikipedia');

class test_connector_wikimedia extends SimpletestUnitBase
{
    function testMediaOnPage()
    {
        $p = new \WikimediaPage('<xml/>');
        $p->text = "
        <gallery>
        File:Boletus vermiculosoides 78531.jpg|[[:Category:Boletus vermiculosoides|''Boletus vermiculosoides'']]
        </gallery>
        ==Related species==
        <gallery>
        Image:Boletus impolitus 2009 G3.jpg|''[[Hemileccinum impolitum]]'' (Syn. ''Boletus impolitus'')
        </gallery>
        <gallery>
        File:Boletus vermiculosus 348301 crop.jpg|[[:Category:Boletus vermiculosus|''Boletus vermiculosus'']]
        </gallery>";
        $media = $p->media_on_page();
        $this->assertTrue(count($media) == 2);
        $this->assertTrue(in_array('Boletus vermiculosoides 78531.jpg', $media));
        $this->assertTrue(in_array('Boletus vermiculosus 348301 crop.jpg', $media));
        $this->assertFalse(in_array('Image:Boletus impolitus 2009 G3.jpg', $media));
    }
}

?>