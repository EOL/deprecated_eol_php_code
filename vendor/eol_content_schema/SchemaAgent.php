<?php

class SchemaAgent
{
    public $fullName;
    public $homepage;
    public $logoURL;
    public $role;
    
    public function __construct($parameters)
    {
        $this->fullName = @$parameters["fullName"];
        $this->homepage = @$parameters["homepage"];
        $this->logoURL = @$parameters["logoURL"];
        $this->role = @$parameters["role"];
    }
    
    public function __toString()
    {
        $string = "<u>Agent:</u><blockquote>\n";
        $string .= "fullName: ".$this->fullName."<br>\n";
        $string .= "homepage: ".$this->homepage."<br>\n";
        $string .= "logoURL: ".$this->logoURL."<br>\n";
        $string .= "role: ".$this->role;
        $string .= "</blockquote>\n";
        return $string;
    }
    
    public function __toXML()
    {
        $xml = "<agent";
        if($this->homepage) $xml .= " homepage=\"".htmlspecialchars($this->homepage)."\"";
        if($this->logoURL) $xml .= " logoURL=\"".htmlspecialchars($this->logoURL)."\"";
        if($this->role) $xml .= " role=\"".htmlspecialchars($this->role)."\"";
        $xml .= ">".htmlspecialchars($this->fullName)."</agent>\n";
        return $xml;
    }
}

?>
