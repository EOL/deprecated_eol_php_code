<?php

class SchemaDocument
{
    private $FILE;
    public  $filename;
    
    function __construct($filename, $fopen_flags = "w+")
    {
        $this->filename = $filename;
        $this->FILE = fopen($this->filename, $fopen_flags);
        if ($this->FILE === false)
        {
            echo "Could not open file $filename\n";
            debug ( "Could not open file $filename");
            flush();
            debug("Could not open file $filename");
            return false;
        }
        fwrite($this->FILE, self::xml_header());
    }
    
    public function save_taxon_xml($t)
    {
        if($this->FILE === false)
        {
            echo "Could not write to file $this->filename as it is not open\n";
            flush();
            debug("Could not write to file $this->filename as it is not open");
            return false;
        }
        fwrite($this->FILE, $t->__toXML());
    }
    
    function __destruct()
    {
        if ($this->FILE === false)
        {
            echo "Could not close file $this->filename\n";
            flush();
        } 
        fwrite($this->FILE, self::xml_footer());
        fclose($this->FILE);
    }
    
    public static function print_taxon_xml($taxa)
    {
        header('Content-type: text/xml');
        echo self::get_taxon_xml($taxa);
    }
    
    public static function xml_header()
    {
        $xml = "<?xml version='1.0' encoding='utf-8' ?>\n";
        $xml .= "<response\n";
        $xml .= "  xmlns='http://www.eol.org/transfer/content/0.3'\n";
        $xml .= "  xmlns:xsd='http://www.w3.org/2001/XMLSchema'\n";
        $xml .= "  xmlns:dc='http://purl.org/dc/elements/1.1/'\n";
        $xml .= "  xmlns:dcterms='http://purl.org/dc/terms/'\n";
        $xml .= "  xmlns:geo='http://www.w3.org/2003/01/geo/wgs84_pos#'\n";
        $xml .= "  xmlns:dwc='http://rs.tdwg.org/dwc/dwcore/'\n";
        $xml .= "  xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance'\n";
        $xml .= "  xsi:schemaLocation='http://www.eol.org/transfer/content/0.3 http://services.eol.org/schema/content_0_3.xsd'>\n";
        return $xml;
    }
    
    public static function xml_footer()
    {
        $xml = "</response>\n";
        return $xml;
    }
    
    public static function get_taxon_xml($taxa, $FILE = null)
    {
        if($FILE) fwrite($FILE, self::xml_header());
        else $xml = self::xml_header();
        
        foreach($taxa as $t)
        {
            if($FILE) fwrite($FILE, $t->__toXML());
            else $xml .= $t->__toXML();
        }
        
        if($FILE) fwrite($FILE, self::xml_footer());
        else $xml .= self::xml_footer();
        
        if($FILE) return true;
        return $xml;
    }
}

?>