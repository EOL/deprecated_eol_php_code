<?php
namespace php_active_record;

class test_biodiversity_gem extends SimpletestUnitBase
{
    function testGemIsInstalled()
    {
        $gem_is_installed = shell_exec('which parserver');
        $this->assertTrue($gem_is_installed, "


The biodiversity gem should be installed for Ranked Canonical Forms to be properly
generated (in lib/RubyNameParserClient.php). If you haven't done so already, please
try running `sudo gem install biodiversity --version '=1.0.10'`. It is OK to not
install the gem on development machines, but it is necessary for production machines.


        ");
    }

    function testCanonicalForms()
    {
        require_library('RubyNameParserClient');
        $parser = new RubyNameParserClient();
        $canonical_forms = array(
            array('Aus bus var. cus Linnaeus',  'Aus bus var. cus'),
            array('Aus bus var. cus',           'Aus bus var. cus'),
            array('Aus bus cus Linnaeus',       'Aus bus cus'),
            array('Aus bus cus',                'Aus bus cus')
        );
        foreach($canonical_forms as $data)
        {
            $this->assertEqual($parser->lookup_string($data[0]), $data[1]);
        }
    }

}

?>
