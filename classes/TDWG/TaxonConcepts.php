<?php

class TaxonConcept extends RDFDocumentElement
{
    function name()
    {
        return parent::get_resource("tc:hasName", "TaxonName");
    }
    
    function name_string()
    {
        return parent::get_literal("tc:nameString");
    }
}

?>