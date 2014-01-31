<?php

class SchemaAudience
{
    public $label;
    
    public function __construct($parameters)
    {
        $this->label = @$parameters["label"];
    }
    
    public function __toString()
    {
        $string = "<u>Audience:</u><blockquote>\n";
        $string .= "label: ".$this->label;
        $string .= "</blockquote>\n";
        return $string;
    }
    
    public function __toXML()
    {
        $xml = "<audience>".htmlspecialchars($this->label)."</audience>\n";
        return $xml;
    }
}

?>