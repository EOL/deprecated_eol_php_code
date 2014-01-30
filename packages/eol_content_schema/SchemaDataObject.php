<?php

class SchemaDataObject
{
    public $identifier;
    public $dataType;
    public $mimeType;
    public $created;
    public $modified;
    public $title;
    public $language;
    public $license;
    public $rights;
    public $rightsHolder;
    public $bibliographicCitation;
    public $source;
    public $description;
    public $mediaURL;
    public $thumbnailURL;
    public $location;
    public $agents;
    public $audiences;
    public $subjects;
    public $references;
    public $point;
    public $oldDBToc;
    
    public function __construct($parameters)
    {
        $this->identifier = @$parameters["identifier"];
        $this->dataType = @$parameters["dataType"];
        $this->mimeType = @$parameters["mimeType"];
        $this->created = @$parameters["created"];
        $this->modified = @$parameters["modified"];
        $this->title = @$parameters["title"];
        $this->language = @$parameters["language"];
        $this->license = @$parameters["license"];
        $this->rights = @$parameters["rights"];
        $this->rightsHolder = @$parameters["rightsHolder"];
        $this->bibliographicCitation = @$parameters["bibliographicCitation"];
        $this->source = @$parameters["source"];
        $this->description = @$parameters["description"];
        $this->mediaURL = @$parameters["mediaURL"];
        $this->thumbnailURL = @$parameters["thumbnailURL"];
        $this->location = @$parameters["location"];
        $this->agents = @$parameters["agents"];
        $this->audiences = @$parameters["audiences"];
        $this->subjects = @$parameters["subjects"];
        $this->references = @$parameters["references"];
        $this->point = @$parameters["point"];
        $this->additionalInformation = @$parameters["additionalInformation"];
        
        $this->description = str_replace("<img/>","",$this->description);
        $this->description = preg_replace("/<a>(.*?)<\/a>/","\\1",$this->description);
    }
    
    public function __toString()
    {
        $string = "<u>DataObject:</u><blockquote>\n";
        $string .= "identifier: ".$this->identifier."<br>\n";
        $string .= "dataType: ".$this->dataType."<br>\n";
        $string .= "mimeType: ".$this->mimeType."<br>\n";
        if(is_array($this->agents)) foreach($this->agents as $a) $string .= $a->__toString();
        $string .= "created: ".$this->created."<br>\n";
        $string .= "modified: ".$this->modified."<br>\n";
        $string .= "title: ".$this->title."<br>\n";
        $string .= "language: ".$this->language."<br>\n";
        $string .= "license: ".$this->license."<br>\n";
        $string .= "rights: ".$this->rights."<br>\n";
        $string .= "rightsHolder: ".$this->rightsHolder."<br>\n";
        $string .= "bibliographicCitation: ".$this->bibliographicCitation."<br>\n";
        $string .= "source: ".$this->source."<br>\n";
        if(is_array($this->subjects)) foreach($this->subjects as $s) $string .= $s->__toString();
        $string .= "description: ".$this->description."<br>\n";
        $string .= "mediaURL: ".$this->mediaURL."<br>\n";
        $string .= "thumbnailURL: ".$this->thumbnailURL."<br>\n";
        $string .= "location: ".$this->location."<br>\n";
        $string .= "additionalInformation: ".$this->additionalInformation."<br>\n";
        if(get_class($this->point)=="SchemaPoint") $string .= $this->point->__toString();
        if(is_array($this->references)) foreach($this->references as $r) $string .= $r->__toString();
        $string .= "</blockquote>\n";
        return $string;
    }
    
    public function __toXML()
    {
        $xml = "<dataObject>\n";
        if($this->identifier) $xml .= "  <dc:identifier>".htmlspecialchars($this->identifier)."</dc:identifier>\n";
        $xml .= "  <dataType>".htmlspecialchars($this->dataType)."</dataType>\n";
        if($this->mimeType) $xml .= "  <mimeType>".htmlspecialchars($this->mimeType)."</mimeType>\n";
        if(is_array($this->agents)) foreach($this->agents as $a) $xml .= $a->__toXML();
        if($this->created) $xml .= "  <dcterms:created>".htmlspecialchars($this->created)."</dcterms:created>\n";
        if($this->modified) $xml .= "  <dcterms:modified>".htmlspecialchars($this->modified)."</dcterms:modified>\n";
        if($this->title) $xml .= "  <dc:title>".htmlspecialchars($this->title)."</dc:title>\n";
        if($this->language) $xml .= "  <dc:language>".htmlspecialchars($this->language)."</dc:language>\n";
        if($this->license) $xml .= "  <license>".htmlspecialchars($this->license)."</license>\n";
        if($this->rights) $xml .= "  <dc:rights>".htmlspecialchars($this->rights)."</dc:rights>\n";
        if($this->rightsHolder) $xml .= "  <dcterms:rightsHolder>".htmlspecialchars($this->rightsHolder)."</dcterms:rightsHolder>\n";
        if($this->bibliographicCitation) $xml .= "  <dcterms:bibliographicCitation>".htmlspecialchars($this->bibliographicCitation)."</dcterms:bibliographicCitation>\n";
        if(is_array($this->audiences)) foreach($this->audiences as $a) $xml .= $a->__toXML();
        if($this->source) $xml .= "  <dc:source>".htmlspecialchars($this->source)."</dc:source>\n";
        if(is_array($this->subjects)) foreach($this->subjects as $s) $xml .= $s->__toXML();
        if($this->description) $xml .= "  <dc:description>".htmlspecialchars($this->description)."</dc:description>\n";
        if($this->mediaURL) $xml .= "  <mediaURL>".htmlspecialchars($this->mediaURL)."</mediaURL>\n";
        if($this->thumbnailURL) $xml .= "  <thumbnailURL>".htmlspecialchars($this->thumbnailURL)."</thumbnailURL>\n";
        if($this->location) $xml .= "  <location>".htmlspecialchars($this->location)."</location>\n";
        if($this->point && get_class($this->point)=="SchemaPoint") $xml .= $this->point->__toXML();
        if(is_array($this->references)) foreach($this->references as $r) $xml .= $r->__toXML();
        if($this->additionalInformation) $xml .= "  <additionalInformation>". $this->additionalInformation ."</additionalInformation>\n";
        $xml .= "</dataObject>\n";
        return $xml;
    }
}

?>
