<?php

class SchemaTaxon
{
    public $identifier;
    public $source;
    public $kingdom;
    public $phylum;
    public $class;
    public $order;
    public $family;
    public $scientificName;
    public $created;
    public $modified;
    public $commonNames;
    public $synonyms;
    public $references;
    public $dataObjects;
    
    public function __construct($parameters)
    {
        $this->identifier = @$parameters["identifier"];
        $this->source = @$parameters["source"];
        $this->kingdom = @$parameters["kingdom"];
        $this->phylum = @$parameters["phylum"];
        $this->class = @$parameters["class"];
        $this->order = @$parameters["order"];
        $this->family = @$parameters["family"];
        $this->genus = @$parameters["genus"];
        $this->scientificName = @$parameters["scientificName"];
        $this->created = @$parameters["created"];
        $this->modified = @$parameters["modified"];
        $this->rank = @$parameters["rank"];
        $this->commonNames = @$parameters["commonNames"];
        $this->synonyms = @$parameters["synonyms"];
        $this->references = @$parameters["references"];
        $this->dataObjects = @$parameters["dataObjects"];
    }
    
    public function __toString()
    {
        $string = "<u>Taxon:</u><blockquote>\n";
        $string .= "identifier: ".$this->identifier."<br>\n";
        $string .= "source: ".$this->source."<br>\n";
        $string .= "kingdom: ".$this->kingdom."<br>\n";
        $string .= "phylum: ".$this->phylum."<br>\n";
        $string .= "class: ".$this->class."<br>\n";
        $string .= "order: ".$this->order."<br>\n";
        $string .= "family: ".$this->family."<br>\n";
        $string .= "genus: ".$this->genus."<br>\n";
        $string .= "scientificName: ".$this->scientificName."<br>\n";
        $string .= "created: ".$this->created."<br>\n";
        $string .= "modified: ".$this->modified."<br>\n";
        $string .= "rank: ".$this->rank."<br>\n";
        if(is_array($this->commonNames)) foreach($this->commonNames as $c) $string .= $c->__toString();
        if(is_array($this->synonyms)) foreach($this->synonyms as $s) $string .= $s->__toString();
        if(is_array($this->references)) foreach($this->references as $r) $string .= $r->__toString();
        if(is_array($this->dataObjects)) foreach($this->dataObjects as $d) $string .= $d->__toString();
        $string .= "</blockquote>\n";
        return $string;
    }
    
    public function __toXML()
    {
        $xml = "<taxon>\n";
        $xml .= "  <dc:identifier>".htmlspecialchars($this->identifier)."</dc:identifier>\n";
        if($this->source) $xml .= "  <dc:source>".htmlspecialchars($this->source)."</dc:source>\n";
        if($this->kingdom) $xml .= "  <dwc:Kingdom>".htmlspecialchars($this->kingdom)."</dwc:Kingdom>\n";
        if($this->phylum) $xml .= "  <dwc:Phylum>".htmlspecialchars($this->phylum)."</dwc:Phylum>\n";
        if($this->class) $xml .= "  <dwc:Class>".htmlspecialchars($this->class)."</dwc:Class>\n";
        if($this->order) $xml .= "  <dwc:Order>".htmlspecialchars($this->order)."</dwc:Order>\n";
        if($this->family) $xml .= "  <dwc:Family>".htmlspecialchars($this->family)."</dwc:Family>\n";
        if($this->genus) $xml .= "  <dwc:Genus>".htmlspecialchars($this->genus)."</dwc:Genus>\n";
        $xml .= "  <dwc:ScientificName>".htmlspecialchars($this->scientificName)."</dwc:ScientificName>\n";
        if($this->rank) $xml .= "  <rank>".htmlspecialchars($this->rank)."</rank>\n";
        if(is_array($this->commonNames)) foreach($this->commonNames as $c) $xml .= $c->__toXML();
        if(is_array($this->synonyms)) foreach($this->synonyms as $s) $xml .= $s->__toXML();
        if($this->created) $xml .= "  <dcterms:created>".htmlspecialchars($this->created)."</dcterms:created>\n";
        if($this->modified) $xml .= "  <dcterms:modified>".htmlspecialchars($this->modified)."</dcterms:modified>\n";
        if(is_array($this->references)) foreach($this->references as $r) $xml .= $r->__toXML();
        if(is_array($this->dataObjects)) foreach($this->dataObjects as $d) $xml .= $d->__toXML();
        $xml .= "</taxon>\n";
        return $xml;
    }
}

?>