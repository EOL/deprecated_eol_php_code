<?php

class TaxonName extends RDFDocumentElement
{
    function name_complete()
    {
        return parent::get_literal("tn:nameComplete");
    }
    
    function name_string()
    {
        return parent::get_literal("tn:nameComplete");
    }
}

?>