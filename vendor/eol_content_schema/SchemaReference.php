<?php

class SchemaReference
{
    public $fullReference;
    public $referenceIdentifiers;

    public function __construct($parameters)
    {
        $this->fullReference = @$parameters["fullReference"];
        $this->referenceIdentifiers = @$parameters["referenceIdentifiers"];
        
        if(!$this->referenceIdentifiers) $this->referenceIdentifiers = array();
    }
    
    public function __toString()
    {
        $string = "<u>Reference:</u><blockquote>\n";
        $string .= "fullReference: ".$this->fullReference."<br>\n";
        foreach($this->referenceIdentifiers as $i)
        {
            $string .= $i->label.": ".$i->value."<br>\n";
        }
        $string .= "</blockquote>\n";
        return $string;
    }
    
    public function __toXML()
    {
        $xml = "<reference";
        foreach($this->referenceIdentifiers as $i)
        {
            $xml .= " ".$i->label."=\"".htmlspecialchars($i->value)."\"";
        }
        $xml .= ">".htmlspecialchars($this->fullReference)."</reference>\n";
        return $xml;
    }
}

?>