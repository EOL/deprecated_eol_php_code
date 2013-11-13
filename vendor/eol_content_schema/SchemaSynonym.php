<?php

class SchemaSynonym
{
    public $synonym;
    public $relationship;
    
    public function __construct($parameters)
    {
        $this->synonym = @$parameters["synonym"];
        $this->relationship = @$parameters["relationship"];
    }
    
    public function __toString()
    {
        $string = "<u>Synonym:</u><blockquote>\n";
        $string .= "synonym: ".$this->synonym."<br>\n";
        $string .= "relationship: ".$this->relationship;
        $string .= "</blockquote>\n";
        return $string;
    }
    
    public function __toXML()
    {
        $xml = "<synonym";
        if($this->relationship) $xml .= " relationship=\"".htmlspecialchars($this->relationship)."\"";
        $xml .= ">".htmlspecialchars($this->synonym)."</synonym>\n";
        return $xml;
    }
}

?>