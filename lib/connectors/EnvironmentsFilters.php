<?php
namespace php_active_record;
/* connector: [called from DwCA_Utility.php, which is called from environments_2_eol.php for DATA-1851] */
class EnvironmentsFilters
{
    function __construct($archive_builder, $resource_id)
    {
        $this->resource_id = $resource_id;
        $this->archive_builder = $archive_builder;
        $this->download_options = array('cache' => 1, 'resource_id' => $resource_id, 'expire_seconds' => 60*60*24*30*4, 'download_wait_time' => 1000000, 'timeout' => 10800, 'download_attempts' => 1, 'delay_in_minutes' => 1);
        // $this->download_options['expire_seconds'] = false; //comment after first harvest
    }
    /*================================================================= STARTS HERE ======================================================================*/
    function start($info)
    {   
        require_library('connectors/TraitGeneric'); 
        $this->func = new TraitGeneric($this->resource_id, $this->archive_builder);
        /* START DATA-1841 terms remapping */
        // $this->func->initialize_terms_remapping(60*60*24); //param is $expire_seconds. 0 means expire now.
        /* END DATA-1841 terms remapping */
        
        $tables = $info['harvester']->tables;
        // self::process_measurementorfact($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0]);
        self::process_occurrence($tables['http://rs.tdwg.org/dwc/terms/occurrence'][0]);
        unset($this->occurrenceID_bodyPart);
        
        // self::initialize_mapping(); //for location string mappings
    }
    private function initialize_mapping()
    {   $mappings = Functions::get_eol_defined_uris(false, true);     //1st param: false means will use 1day cache | 2nd param: opposite direction is true
        echo "\n".count($mappings). " - default URIs from EOL registry.";
        $this->uris = Functions::additional_mappings($mappings); //add more mappings used in the past
        // self::use_mapping_from_jen();
        // print_r($this->uris);
    }
    private function process_measurementorfact($meta)
    {   //print_r($meta);
        echo "\nprocess_measurementorfact...\n"; $i = 0;
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
            print_r($rec); exit;
            /*Array(
                [http://rs.tdwg.org/dwc/terms/measurementID] => 7b840a5f6b1b9f1ced978a184b75befb_21_ENV
                [http://rs.tdwg.org/dwc/terms/occurrenceID] => 4441c21d2cedc23a347b337b1813f2c4_21_ENV
                [http://eol.org/schema/measurementOfTaxon] => true
                [http://rs.tdwg.org/dwc/terms/measurementType] => http://purl.obolibrary.org/obo/RO_0002303
                [http://rs.tdwg.org/dwc/terms/measurementValue] => http://purl.obolibrary.org/obo/ENVO_00000300
                [http://rs.tdwg.org/dwc/terms/measurementRemarks] => source text: "shrubs"
                [http://purl.org/dc/terms/source] => http://amphibiaweb.org/cgi/amphib_query?where-genus=Osteocephalus&where-species=buckleyi&account=amphibiaweb
                [http://purl.org/dc/terms/contributor] => Albertina P. Lima (author). William E. Magnusson (author). Marcelo Menin (author). Luciana K. Erdtmann (author). Domingos J. Rodrigues (author). Claudia Keller (author). Walter Hödl (author).
                [http://eol.org/schema/reference/referenceID] => 
            )*/
            //===========================================================================================================================================================
            //===========================================================================================================================================================
            $o = new \eol_schema\MeasurementOrFact_specific();
            $uris = array_keys($rec);
            foreach($uris as $uri) {
                $field = pathinfo($uri, PATHINFO_BASENAME);
                $o->$field = $rec[$uri];
            }
            
            /* START DATA-1841 terms remapping */
            $o = $this->func->given_m_update_mType_mValue($o);
            // echo "\nLocal: ".count($this->func->remapped_terms)."\n"; //just testing
            /* END DATA-1841 terms remapping */
            
            $this->archive_builder->write_object_to_file($o);
            // if($i >= 10) break; //debug only
        }
    }
    private function process_occurrence($meta)
    {   //print_r($meta);
        echo "\nprocess_occurrence...\n"; $i = 0;
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
            print_r($rec); exit("\ndebug...\n");
            /*Array(
                [http://rs.tdwg.org/dwc/terms/occurrenceID] => 4441c21d2cedc23a347b337b1813f2c4_21_ENV
                [http://rs.tdwg.org/dwc/terms/taxonID] => 1005
            )*/
            //===========================================================================================================================================================
            //===========================================================================================================================================================
            $uris = array_keys($rec);
            $o = new \eol_schema\Occurrence_specific();
            foreach($uris as $uri) {
                $field = pathinfo($uri, PATHINFO_BASENAME);
                $o->$field = $rec[$uri];
            }
            $this->archive_builder->write_object_to_file($o);
            // if($i >= 10) break; //debug only
        }
    }
}
?>