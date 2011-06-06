<?php
namespace php_active_record;

class test_browser extends SimpletestWebBase
{
    function testBrowser()
    {
        $hierarchy = Hierarchy::create(array('label' => 'Test Hierarchy'));
        $this->get(WEB_ROOT . 'applications/taxonomic_browsers/browser.php?ENV_NAME=test');
        $this->assertPattern('/Test Hierarchy/', 'Taxonomic browser should default to showing a hierarchy listing');
        $this->assertPattern("/hierarchy_id=$hierarchy->id/", 'Taxonomic browser should link to the hierarchy page');
    }
}

?>