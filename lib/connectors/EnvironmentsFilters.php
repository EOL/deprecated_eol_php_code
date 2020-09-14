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
        $this->occurID_2delete = array();
        $this->debug = array();
    }
    /*================================================================= STARTS HERE ======================================================================*/
    function start($info)
    {   
        require_library('connectors/TraitGeneric'); 
        $this->func = new TraitGeneric($this->resource_id, $this->archive_builder);
        /* START DATA-1841 terms remapping */
        $this->func->initialize_terms_remapping(60*60*24); //param is $expire_seconds. 0 means expire now.
        /* END DATA-1841 terms remapping */
        
        /* 1st filter: https://eol-jira.bibalex.org/browse/DATA-1768?focusedCommentId=62965&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-62965 */
        self::borrow_data(); //from old lib
        // exit("\nexit muna\n");
        
        $tables = $info['harvester']->tables;
        /* Step 1: build info list: delete MoF with taxa and mValue combination from whitelist */
        $this->occurID_mValue = self::process_measurementorfact($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0], 'get occurID_mValue');
        $this->taxonID_occurIDs = self::process_occurrence($tables['http://rs.tdwg.org/dwc/terms/occurrence'][0], 'get taxonID_occurIDs');
        $taxonName_occurID_mValue = self::process_taxon($tables['http://rs.tdwg.org/dwc/terms/taxon'][0], 'get taxonName_occurID_mValue');
        self::delete_combination_of_taxonName_mValue($taxonName_occurID_mValue);
        
        // self::process_taxon($tables['http://rs.tdwg.org/dwc/terms/taxon'][0]);
        self::process_measurementorfact($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0], 'apply deletions');
        self::process_occurrence($tables['http://rs.tdwg.org/dwc/terms/occurrence'][0], 'apply deletions');
        // if($this->debug) print_r($this->debug);
        echo "\ncombination deleted MoF: No. of taxa with 1 or more MoF records removed: ".count(@$this->debug['combination deleted MoF'])."\n";
    }
    private function delete_combination_of_taxonName_mValue($taxonName_occurID_mValue)
    {   /* Array(
            [Abavorana nazgul] => Array(
                    [http://purl.obolibrary.org/obo/ENVO_00000081] => 01b0a0ad5a0a777ca05a8b86d8fa4cab_21_ENV
                    [http://purl.obolibrary.org/obo/ENVO_00000303] => 3da110d758df54f0ce8a3eb3eb7c3a9a_21_ENV
                    [http://purl.obolibrary.org/obo/ENVO_00000043] => 0bf0a13b48901a073eb3f42b23236a91_21_ENV
                    [http://purl.obolibrary.org/obo/ENVO_00000023] => 00b573e4b2f1a44ae6f7069d2de3ae76_21_ENV
                )
        )*/
        foreach($taxonName_occurID_mValue as $sciname => $mValues) {
            // echo "\n$sciname - "; print_r($mValues);
            foreach($mValues as $mValue => $occurID) {
                // /* new https://eol-jira.bibalex.org/browse/DATA-1768?focusedCommentId=62965&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-62965
                if(isset($this->excluded_eol_ids[$sciname]) && isset($this->excluded_terms[$mValue])) {
                    $this->occurID_2delete[$occurID] = '';
                    $this->debug['combination deleted MoF'][$sciname][$mValue] = ''; //good debug, but debug only
                    // exit("\nAt last a resource with a hit\n"); //debug only
                }
                // */
            }
        }
    }
    private function borrow_data()
    {
        require_library('connectors/EnvironmentsEOLDataConnector');
        $func = new EnvironmentsEOLDataConnector();
        $this->excluded_eol_ids = $func->get_excluded_eol_ids(false, 'scientific name'); //2nd param is sought field
        $this->excluded_terms = $func->get_excluded_terms();
        // echo "\n".count($this->excluded_eol_ids)."\n";
        // echo "\n".count($this->excluded_terms)."\n";
        // print_r($this->excluded_eol_ids); 
        // print_r($this->excluded_terms);
    }
    private function process_taxon($meta, $task)
    {   //print_r($meta);
        echo "\nprocess_taxon...($task)\n"; $i = 0;
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
                [http://rs.tdwg.org/dwc/terms/taxonID] => 8687
                [http://rs.tdwg.org/ac/terms/furtherInformationURL] => http://amphibiaweb.org/cgi/amphib_query?where-genus=Abavorana&where-species=nazgul&account=amphibiaweb
                [http://rs.tdwg.org/dwc/terms/scientificName] => Abavorana nazgul
                [http://rs.tdwg.org/dwc/terms/kingdom] => Animalia
                [http://rs.tdwg.org/dwc/terms/phylum] => Chordata
                [http://rs.tdwg.org/dwc/terms/class] => Amphibia
                [http://rs.tdwg.org/dwc/terms/order] => Anura
                [http://rs.tdwg.org/dwc/terms/family] => Ranidae
            )*/
            //===========================================================================================================================================================
            //===========================================================================================================================================================
            if($task == 'get taxonName_occurID_mValue') {
                // $this->occurID_mValue
                // $this->taxonID_occurIDs
                $taxonID = $rec['http://rs.tdwg.org/dwc/terms/taxonID'];
                $sciname = $rec['http://rs.tdwg.org/dwc/terms/scientificName'];
                if($occurIDs = @$this->taxonID_occurIDs[$taxonID]) {
                    // print_r($occurIDs); //exit("\n[$taxonID]\nexit 100\n");
                    $occurIDs = array_keys($occurIDs);
                    foreach($occurIDs as $occurID) $taxonName_occurID_mValue[$sciname][$this->occurID_mValue[$occurID]] = $occurID;
                    // print_r($taxonName_occurID_mValue); exit("\n[$taxonID]\nexit 100\n");
                    /* Array(
                        [Abavorana nazgul] => Array(
                                [http://purl.obolibrary.org/obo/ENVO_00000081] => 01b0a0ad5a0a777ca05a8b86d8fa4cab_21_ENV
                                [http://purl.obolibrary.org/obo/ENVO_00000303] => 3da110d758df54f0ce8a3eb3eb7c3a9a_21_ENV
                                [http://purl.obolibrary.org/obo/ENVO_00000043] => 0bf0a13b48901a073eb3f42b23236a91_21_ENV
                                [http://purl.obolibrary.org/obo/ENVO_00000023] => 00b573e4b2f1a44ae6f7069d2de3ae76_21_ENV
                            )
                    )*/
                }
            }
            elseif($task == 'xxx') {
                $uris = array_keys($rec);
                $o = new \eol_schema\Occurrence_specific();
                foreach($uris as $uri) {
                    $field = pathinfo($uri, PATHINFO_BASENAME);
                    $o->$field = $rec[$uri];
                }
                $this->archive_builder->write_object_to_file($o);
            }
            // if($i >= 10) break; //debug only
        }
        if($task == 'get taxonName_occurID_mValue') return $taxonName_occurID_mValue;
    }
    private function process_measurementorfact($meta, $task)
    {   //print_r($meta);
        echo "\nprocess_measurementorfact...($task)\n"; $i = 0;
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
            // print_r($rec); exit;
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
            if($task == 'get occurID_mValue') {
                $occurID_mValue[$rec['http://rs.tdwg.org/dwc/terms/occurrenceID']] = $rec['http://rs.tdwg.org/dwc/terms/measurementValue'];
            }
            elseif($task == 'apply deletions') {
                $occurID = $rec['http://rs.tdwg.org/dwc/terms/occurrenceID'];
                if(isset($this->occurID_2delete[$occurID])) continue;

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
            }
            // if($i >= 10) break; //debug only
        }
        if($task == 'get occurID_mValue') return $occurID_mValue;
    }
    private function process_occurrence($meta, $task)
    {   //print_r($meta);
        echo "\nprocess_occurrence...($task)\n"; $i = 0;
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
                [http://rs.tdwg.org/dwc/terms/occurrenceID] => 4441c21d2cedc23a347b337b1813f2c4_21_ENV
                [http://rs.tdwg.org/dwc/terms/taxonID] => 1005
            )*/
            //===========================================================================================================================================================
            //===========================================================================================================================================================
            if($task == 'get taxonID_occurIDs') {
                $taxonID_occurIDs[$rec['http://rs.tdwg.org/dwc/terms/taxonID']][$rec['http://rs.tdwg.org/dwc/terms/occurrenceID']] = '';
            }
            elseif($task == 'apply deletions') {
                $occurID = $rec['http://rs.tdwg.org/dwc/terms/occurrenceID'];
                if(isset($this->occurID_2delete[$occurID])) continue;
                
                $uris = array_keys($rec);
                $o = new \eol_schema\Occurrence_specific();
                foreach($uris as $uri) {
                    $field = pathinfo($uri, PATHINFO_BASENAME);
                    $o->$field = $rec[$uri];
                }
                $this->archive_builder->write_object_to_file($o);
            }
            // if($i >= 10) break; //debug only
        }
        if($task == 'get taxonID_occurIDs') return $taxonID_occurIDs;
    }
}
?>