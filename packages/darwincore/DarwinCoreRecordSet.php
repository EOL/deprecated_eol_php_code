<?php
namespace php_active_record;

class DarwinCoreRecordSet
{
    public static function print_taxon_xml($taxa)
    {
        header('Content-type: text/xml');
        
        echo self::get_taxon_xml($taxa);
    }
    
    public static function xml_header()
    {
        $xml = "<?xml version='1.0' encoding='utf-8' ?>\n";
        $xml .= "<dwr:DarwinRecordSet\n";
        $xml .= "  xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance'\n";
        $xml .= "  xsi:schemaLocation='http://rs.tdwg.org/dwc/dwcrecord/  http://rs.tdwg.org/dwc/xsd/tdwg_dwc_classes.xsd'\n";
        $xml .= "  xmlns:dcterms='http://purl.org/dc/terms/'\n";
        $xml .= "  xmlns:dwc='http://rs.tdwg.org/dwc/terms/'\n";
        $xml .= "  xmlns:dwr='http://rs.tdwg.org/dwc/dwcrecord/'>\n";
        return $xml;
    }
    
    public static function xml_footer()
    {
        $xml = "</dwr:DarwinRecordSet>\n";
        return $xml;
    }
    
    public static function get_taxon_xml($taxa)
    {
        $xml = self::xml_header();
        foreach($taxa as $t)
        {
            $xml .= $t->__toXML();
        }
        $xml .= self::xml_footer();
        return $xml;
    }
}

?>