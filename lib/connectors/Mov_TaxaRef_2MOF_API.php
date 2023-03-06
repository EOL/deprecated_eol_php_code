<?php
namespace php_active_record;
/* connector: [called from DwCA_Utility.php, which is called from clients:] 
1st client: mov_TaxaRef_toMOF.php (Brazilian Flora)
2nd client: xxx.php
*/
class Mov_TaxaRef_2MOF_API
{
    function __construct($archive_builder, $resource_id)
    {
        $this->resource_id = $resource_id;
        $this->archive_builder = $archive_builder;
        
        // $this->download_options = array('cache' => 1, 'resource_id' => $resource_id, 'expire_seconds' => 60*60*24*30*4, 'download_wait_time' => 1000000, 'timeout' => 10800, 'download_attempts' => 1, 'delay_in_minutes' => 1);
        // $this->download_options['expire_seconds'] = false; //comment after first harvest
    }
    /*================================================================= STARTS HERE ======================================================================*/
    function start($info)
    {   //exit("\nchecking...\n");
        /* START DATA-1841 terms remapping */
        /* not needed yet so far
        require_library('connectors/TraitGeneric');
        $this->func = new TraitGeneric($this->resource_id, $this->archive_builder);
        $this->func->initialize_terms_remapping(60*60*24); //param is $expire_seconds. 0 means expire now.
        */
        /* END DATA-1841 terms remapping */
        
        $tables = $info['harvester']->tables;
        self::process_taxon($tables['http://rs.tdwg.org/dwc/terms/taxon'][0], 'info');
        self::process_taxon($tables['http://rs.tdwg.org/dwc/terms/taxon'][0], 'write_taxa');
        
        self::process_occurrence($tables['http://rs.tdwg.org/dwc/terms/occurrence'][0], 'info');
        self::process_measurementorfact($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0], 'write_MoF');
    }
    private function initialize_mapping()
    {   $mappings = Functions::get_eol_defined_uris(false, true);     //1st param: false means will use 1day cache | 2nd param: opposite direction is true
        echo "\n".count($mappings). " - default URIs from EOL registry.";
        $this->uris = Functions::additional_mappings($mappings); //add more mappings used in the past
        // print_r($this->uris);
    }
    private function process_taxon($meta, $what)
    {   //print_r($meta);
        echo "\nprocess_taxon...[$what]\n"; $i = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % 100000) == 0) echo "\n".number_format($i);
            if($meta->ignore_header_lines && $i == 1) continue;
            if(!$row) continue;
            // $row = Functions::conv_to_utf8($row); //possibly to fix special chars. but from copied template
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta->fields as $field) {
                if(!$field['term']) continue;
                $rec[$field['term']] = $tmp[$k];
                $k++;
            } //print_r($rec); exit;
            /*Array(
                [http://rs.tdwg.org/dwc/terms/taxonID] => 12
                [http://rs.tdwg.org/ac/terms/furtherInformationURL] => http://reflora.jbrj.gov.br/reflora/listaBrasil/FichaPublicaTaxonUC/FichaPublicaTaxonUC.do?id=FB12
                [http://eol.org/schema/reference/referenceID] => 
                [http://rs.tdwg.org/dwc/terms/acceptedNameUsageID] => 
                [http://rs.tdwg.org/dwc/terms/parentNameUsageID] => 120181
                [http://rs.tdwg.org/dwc/terms/scientificName] => Agaricales
                [http://rs.tdwg.org/dwc/terms/namePublishedIn] => 
                [http://rs.tdwg.org/dwc/terms/kingdom] => Fungi
                [http://rs.tdwg.org/dwc/terms/phylum] => Basidiomycota
                [http://rs.tdwg.org/dwc/terms/class] => 
                [http://rs.tdwg.org/dwc/terms/order] => Agaricales
                [http://rs.tdwg.org/dwc/terms/family] => 
                [http://rs.tdwg.org/dwc/terms/genus] => 
                [http://rs.tdwg.org/dwc/terms/taxonRank] => order
                [http://rs.tdwg.org/dwc/terms/scientificNameAuthorship] => 
                [http://rs.tdwg.org/dwc/terms/taxonomicStatus] => accepted
                [http://purl.org/dc/terms/modified] => 2018-08-10 11:58:06.954
            )*/
            //===========================================================================================================================================================
            $taxonID = $rec['http://rs.tdwg.org/dwc/terms/taxonID'];
            $referenceID = $rec['http://eol.org/schema/reference/referenceID'];

            if($what == 'info') {
                $this->taxa_ref_info[$taxonID] = $referenceID;
            } //end what == 'info'
            //===========================================================================================================================================================
            if($what == 'write_taxa') {
                //-----------------------------------------------------------
                /* start write */
                $o = new \eol_schema\Taxon();
                $uris = array_keys($rec);
                foreach($uris as $uri) {
                    $field = pathinfo($uri, PATHINFO_BASENAME);
                    if($field == 'referenceID') continue;
                    $o->$field = $rec[$uri];
                }
                $this->archive_builder->write_object_to_file($o);
            } //end what == 'write_taxa'
            //===========================================================================================================================================================

            // if($i >= 10) break; //debug only
        }
    }
    private function process_occurrence($meta, $what)
    {   //print_r($meta);
        echo "\nprocess_occurrence...[$what]\n"; $i = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % 100000) == 0) echo "\n".number_format($i);
            if($meta->ignore_header_lines && $i == 1) continue;
            if(!$row) continue;
            // $row = Functions::conv_to_utf8($row); //possibly to fix special chars. but from copied template
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta->fields as $field) {
                if(!$field['term']) continue;
                $rec[$field['term']] = $tmp[$k];
                $k++;
            }
            // print_r($rec); exit("\ndebug...\n");
            /*Array(
                [http://rs.tdwg.org/dwc/terms/occurrenceID] => ec671627b9284aab2a7eac28e1f2d5c7_BF
                [http://rs.tdwg.org/dwc/terms/taxonID] => 121159
            )*/
            
            $taxonID      = $rec['http://rs.tdwg.org/dwc/terms/taxonID'];
            $occurrenceID = $rec['http://rs.tdwg.org/dwc/terms/occurrenceID'];

            if($referenceID = @$this->taxa_ref_info[$taxonID]) {
                $this->occurrence_reference_info[$occurrenceID] = $referenceID;
            }        
        }
    }
    private function process_measurementorfact($meta, $what)
    {   //print_r($meta);
        echo "\nprocess_measurementorfact...[$what]\n"; $i = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % 100000) == 0) echo "\n".number_format($i);
            if($meta->ignore_header_lines && $i == 1) continue;
            if(!$row) continue;
            // $row = Functions::conv_to_utf8($row); //possibly to fix special chars. but from copied template
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta->fields as $field) {
                if(!$field['term']) continue;
                $rec[$field['term']] = $tmp[$k];
                $k++;
            } //print_r($rec); exit;
            /*Array(
                [http://rs.tdwg.org/dwc/terms/measurementID] => 544be436e28e56dc7f561a0f616089c4_BF
                [http://rs.tdwg.org/dwc/terms/occurrenceID] => ec671627b9284aab2a7eac28e1f2d5c7_BF
                [http://eol.org/schema/measurementOfTaxon] => true
                [http://rs.tdwg.org/dwc/terms/measurementType] => http://eol.org/schema/terms/NativeRange
                [http://rs.tdwg.org/dwc/terms/measurementValue] => https://www.geonames.org/3451133
                [http://rs.tdwg.org/dwc/terms/measurementRemarks] => NATIVA (BR-RS)
                [http://purl.org/dc/terms/source] => http://reflora.jbrj.gov.br/reflora/floradobrasil/FB121159
                [http://purl.org/dc/terms/bibliographicCitation] => Brazil Flora G (2019). Brazilian Flora 2020 project - Projeto Flora do Brasil 2020. Version 393.206. Instituto de Pesquisas Jardim Botanico do Rio de Janeiro. Checklist dataset https://doi.org/10.15468/1mtkaw accessed via GBIF.org on 2023-02-14
            )*/
            //===========================================================================================================================================================
            $occurrenceID = $rec['http://rs.tdwg.org/dwc/terms/occurrenceID'];
            
            if($referenceID = @$this->occurrence_reference_info[$occurrenceID]) {
                $rec['http://eol.org/schema/reference/referenceID'] = $referenceID;        
            }

            $o = new \eol_schema\MeasurementOrFact_specific();
            $uris = array_keys($rec);
            foreach($uris as $uri) {
                $field = pathinfo($uri, PATHINFO_BASENAME);
                $o->$field = $rec[$uri];
            }
            /* START DATA-1841 terms remapping */
            /* copied template
            $o = $this->func->given_m_update_mType_mValue($o);
            // echo "\nLocal: ".count($this->func->remapped_terms)."\n"; //just testing
            */
            /* END DATA-1841 terms remapping */

            // $o->measurementID = Functions::generate_measurementID($o, $this->resource_id); //copied template
            $this->archive_builder->write_object_to_file($o);
        }
    }
    /*================================================================= ENDS HERE ======================================================================*/
}
?>