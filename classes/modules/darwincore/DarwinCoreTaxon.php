<?php

class DarwinCoreTaxon
{
    static $taxonID;
    static $parentNameUsageID;
    static $scientificName;
    static $taxonRank;
    static $taxonomicStatus;
    static $nomenclaturalCode;
    static $nomenclaturalStatus;
    static $vernacularNames;
    
    public function __construct($parameters)
    {
        $this->taxonID = @$parameters["taxonID"];
        $this->parentNameUsageID = @$parameters["parentNameUsageID"];
        $this->scientificName = @$parameters["scientificName"];
        $this->taxonRank = @$parameters["taxonRank"];
        $this->taxonomicStatus = @$parameters["taxonomicStatus"];
        $this->nomenclaturalCode = @$parameters["nomenclaturalCode"];
        $this->nomenclaturalStatus = @$parameters["nomenclaturalStatus"];
        
        $this->vernacularNames = @$parameters["vernacularNames"];
    }
    
    public function __toXML()
    {
        $xml =  "<dwc:Taxon>\n";
        if($this->taxonID) $xml .= "  <dwc:taxonID>".htmlspecialchars($this->taxonID)."</dwc:taxonID>\n";
        if($this->parentNameUsageID) $xml .= "  <dwc:parentNameUsageID>".htmlspecialchars($this->parentNameUsageID)."</dwc:parentNameUsageID>\n";
        if($this->scientificName) $xml .= "  <dwc:scientificName>".htmlspecialchars($this->scientificName)."</dwc:scientificName>\n";
        if($this->taxonRank) $xml .= "  <dwc:taxonRank>".htmlspecialchars($this->taxonRank)."</dwc:taxonRank>\n";
        if($this->taxonomicStatus) $xml .= "  <dwc:taxonomicStatus>".htmlspecialchars($this->taxonomicStatus)."</dwc:taxonomicStatus>\n";
        if($this->nomenclaturalCode) $xml .= "  <dwc:nomenclaturalCode>".htmlspecialchars($this->nomenclaturalCode)."</dwc:nomenclaturalCode>\n";
        if($this->nomenclaturalStatus) $xml .= "  <dwc:nomenclaturalStatus>".htmlspecialchars($this->nomenclaturalStatus)."</dwc:nomenclaturalStatus>\n";
        
        // vernacular names can be
        // array("name1", "name2")
        // array("lang" => "name") or
        // array("lang" => array("name1", "name2"))
        if($this->vernacularNames)
        {
            foreach($this->vernacularNames as $lang => $vern)
            {
                if(is_array($vern))
                {
                    foreach($vern as $k => $v)
                    {
                        if(is_string($lang)) $xml .= "  <dwc:vernacularName xml:lang='".htmlspecialchars($lang)."'>".htmlspecialchars($v)."</dwc:vernacularName>\n";
                        else $xml .= "  <dwc:vernacularName>".htmlspecialchars($v)."</dwc:vernacularName>\n";
                    }
                }
                elseif(is_string($lang)) $xml .= "  <dwc:vernacularName xml:lang='".htmlspecialchars($lang)."'>".htmlspecialchars($vern)."</dwc:vernacularName>\n";
                else $xml .= "  <dwc:vernacularName>".htmlspecialchars($vern)."</dwc:vernacularName>\n";
            }
        }
        
        $xml .= "</dwc:Taxon>\n";
        return $xml;
    }
}

?>
