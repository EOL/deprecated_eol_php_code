<?php

class SchemaSubject
{
    public $label;
    
    public function __construct($parameters)
    {
        $this->label = @$parameters["label"];
    }
    
    public function __toString()
    {
        $string = "<u>Subject:</u><blockquote>\n";
        $string .= "label: ".$this->label;
        $string .= "</blockquote>\n";
        return $string;
    }
    
    public function __toXML()
    {
        $xml = "<subject>".htmlspecialchars($this->label)."</subject>\n";
        return $xml;
    }
}

?>