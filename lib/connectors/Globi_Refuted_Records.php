<?php
namespace php_active_record;
class Globi_Refuted_Records
{
    function __construct()
    {
        $this->columns = array('identifier', 'argumentTypeId', 'argumentTypeName', 'argumentReasonID', 'argumentReasonName', 'interactionTypeId', 'interactionTypeName', 'referenceCitation', 
                               'sourceCitation', 'sourceArchiveURI', 'sourceTaxonId', 'sourceTaxonName', 'sourceTaxonRank', 'sourceTaxonKingdomName', 'targetTaxonId', 'targetTaxonName', 
                               'targetTaxonRank', 'targetTaxonKingdomName');
        
    }
    public function write_refuted_report($rec, $rep_name)
    {
        // print_r($rec);
        // exit("\n[$rep_name]\n");
    }
}
?>