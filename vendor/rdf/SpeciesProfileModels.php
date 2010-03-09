<?php

class SpeciesProfileModel extends RDFDocumentElement
{
    function taxon_concepts()
    {
        return parent::get_resources("spm:aboutTaxon", "TaxonConcept");
    }
    
    function info_items()
    {
        return parent::get_resources("spm:hasInformation", "InfoItem");
    }
}

?>