<?php
namespace php_active_record;
require_library('connectors/AvibaseAPI');
class test_connector_avibase_api extends SimpletestUnitBase
{
    function testAvibaseAPI()
    {
        $taxonomy = "ioc";
        print "\n taxonomy: [$taxonomy]";
        $avibase = new AvibaseAPI();
        $taxa = $avibase->prepare_data($taxonomy, true);
        $all_taxa = array();
        foreach($taxa as $key => $value)
        {
            $taxon_record["taxon"] = array( "sciname" => $key, 
                                            "family"  => $value["family"], 
                                            "kingdom" => $avibase->ancestry["kingdom"],
                                            "phylum"  => $avibase->ancestry["phylum"],
                                            "class"   => $avibase->ancestry["class"],
                                            "order"   => $avibase->family_list[$taxonomy][$value["family"]],
                                            "id"      => $value["id"]);
            $taxon_record["common_names"] = array();
            $taxon_record["references"] = array();
            $taxon_record["synonyms"] = array();
            $taxon_record["dataobjects"] = array();
            $arr = $avibase->get_avibase_taxa($taxon_record);
            $taxa = $arr[0];
            $this->assertTrue(is_array($taxa), 'Taxa should be an array');
            $taxon = $taxa[0];
            $this->assertIsA($taxon, 'SchemaTaxon', 'Response should be an array of SchemaTaxon');
            break;
        }
    }
}
?>