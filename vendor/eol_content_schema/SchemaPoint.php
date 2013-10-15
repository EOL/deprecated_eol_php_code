<?php

class SchemaPoint
{
    public $latitude;
    public $longitude;
    public $altitude;
    
    public function __construct($parameters)
    {
        $this->latitude = @$parameters["latitude"];
        $this->longitude = @$parameters["longitude"];
        $this->altitude = @$parameters["altitude"];
    }
    
    public function __toString()
    {
        $string = "<u>Point:</u><blockquote>\n";
        $string .= "latitude: ".$this->latitude."<br>\n";
        $string .= "longitude: ".$this->longitude."<br>\n";
        $string .= "altitude: ".$this->altitude;
        $string .= "</blockquote>\n";
        return $string;
    }
    
    public function __toXML()
    {
        $xml = "<geo:Point>\n";
        if($this->latitude) $xml .= "  <geo:lat>".htmlspecialchars($this->latitude)."</geo:lat>\n";
        if($this->longitude) $xml .= "  <geo:long>".htmlspecialchars($this->longitude)."</geo:long>\n";
        if($this->altitude) $xml .= "  <geo:alt>".htmlspecialchars($this->altitude)."</geo:alt>\n";
        $xml .= "</geo:Point>\n";
        return $xml;
    }
}

?>