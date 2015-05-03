<?php
namespace php_active_record;
require_library('connectors/EcomareAPI');

class test_connector_Ecomare_api extends SimpletestUnitBase
{
    function testEcomareAPI()
    {
        $str = "<?xml version='1.0' encoding='iso-8859-1'?>\n";
        $str .= "<subjects>\n";
        $str .= "  <subject_item>\n";
        $str .= "    <id>4171</id>\n";
        $str .= "    <title>Gewone zeehond</title>\n";
        $str .= "    <title_dui>Gemeiner Seehund</title_dui>\n";
        $str .= "    <title_eng>Harbour seal</title_eng>\n";
        $str .= "    <timestamp>1258467023</timestamp>\n";
        $str .= "    <name_latin>Phoca vitulina</name_latin>\n";
        $str .= "    <export_date>25/01/2013</export_date>\n";
        $str .= "  </subject_item>\n";
        $str .= "</subjects>\n";
        $temp_path = temp_filepath();
        if(!($OUT = fopen($temp_path, "w")))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .$temp_path);
          return;
        }
        fwrite($OUT, $str);
        fclose($OUT);
        $url = "http://dl.dropbox.com/u/7597512/Ecomare/encyclopedia_toc_small.xml";
        $url = $temp_path;
        $taxa = EcomareAPI::get_all_taxa($url);
        $this->assertTrue(is_array($taxa), 'Taxa should be an array');
        $taxon = $taxa[0];
        $this->assertIsA($taxon, 'SchemaTaxon', 'Response should be an array of SchemaTaxon');
        $dataObject = $taxon->dataObjects[0];
        $this->assertIsA($dataObject, 'SchemaDataObject', 'Taxon should have a data object');
        unlink($temp_path);
    }
}
?>