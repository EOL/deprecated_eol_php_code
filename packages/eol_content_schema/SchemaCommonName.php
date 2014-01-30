<?php

class SchemaCommonName
{
    public $name;
    public $language;
    
    public function __construct($parameters)
    {
        $this->name = @$parameters["name"];
        $this->language = @$parameters["language"];
    }
    
    public function __toString()
    {
        $string = "<u>CommonName:</u><blockquote>\n";
        $string .= "name: ".$this->name."<br>\n";
        $string .= "language: ".$this->language;
        $string .= "</blockquote>\n";
        return $string;
    }
    
    public function __toXML()
    {
        $xml = "<commonName";
        if($this->language) $xml .= " xml:lang=\"".htmlspecialchars($this->language)."\"";
        $xml .= ">".htmlspecialchars($this->name)."</commonName>\n";
        return $xml;
    }
}

?>