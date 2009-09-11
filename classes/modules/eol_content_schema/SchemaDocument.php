<?php

class SchemaDocument extends MysqlBase
{
    public static function print_taxon_xml($taxa)
    {
        header('Content-type: text/xml');
        
        echo self::get_taxon_xml($taxa);
    }
    
    public static function get_taxon_xml($taxa)
    {
        $xml = "<?xml version='1.0' encoding='utf-8' ?>\n";
        $xml .= "<response\n";
        $xml .= "  xmlns='http://www.eol.org/transfer/content/0.2'\n";
        $xml .= "  xmlns:xsd='http://www.w3.org/2001/XMLSchema'\n";
        $xml .= "  xmlns:dc='http://purl.org/dc/elements/1.1/'\n";
        $xml .= "  xmlns:dcterms='http://purl.org/dc/terms/'\n";
        $xml .= "  xmlns:geo='http://www.w3.org/2003/01/geo/wgs84_pos#'\n";
        $xml .= "  xmlns:dwc='http://rs.tdwg.org/dwc/dwcore/'\n";
        $xml .= "  xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance'\n";
        $xml .= "  xsi:schemaLocation='http://www.eol.org/transfer/content/0.2 http://services.eol.org/schema/content_0_2.xsd'>\n";
        
        foreach($taxa as $t)
        {
            $xml .= $t->__toXML();
            //echo $t;
        }
        
        $xml .= "</response>";
        
        return $xml;
    }
}

?>