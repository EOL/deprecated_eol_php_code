<?php
namespace php_active_record;
/* connector: [called from DwCA_Utility.php, which is called from clients:] 
1st client: treatmentbank_adjust.php
2nd client: xxx.php
*/
class DwCA_Rem_Taxa_Adjust_MoF_API
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
    {   
        require_library('connectors/TraitGeneric'); 
        $this->func = new TraitGeneric($this->resource_id, $this->archive_builder);
        /* START DATA-1841 terms remapping */
        $this->func->initialize_terms_remapping(60*60*24); //param is $expire_seconds. 0 means expire now.
        /* END DATA-1841 terms remapping */
        
        $tables = $info['harvester']->tables;
        self::process_taxon($tables['http://rs.tdwg.org/dwc/terms/taxon'][0], 'info');
        self::process_taxon($tables['http://rs.tdwg.org/dwc/terms/taxon'][0], 'write_taxa');
        
        self::process_occurrence($tables['http://rs.tdwg.org/dwc/terms/occurrence'][0], 'write_occurrence');
        self::process_measurementorfact($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0], 'write_MoF');
    }
    private function initialize_mapping()
    {   $mappings = Functions::get_eol_defined_uris(false, true);     //1st param: false means will use 1day cache | 2nd param: opposite direction is true
        echo "\n".count($mappings). " - default URIs from EOL registry.";
        $this->uris = Functions::additional_mappings($mappings); //add more mappings used in the past
        // self::use_mapping_from_jen();
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
                [http://rs.tdwg.org/dwc/terms/taxonID] => DB5AFC3EC73E5732C0D8E33CFDC6FD63.taxon
                [http://rs.tdwg.org/dwc/terms/acceptedNameUsageID] => 
                [http://rs.tdwg.org/dwc/terms/parentNameUsageID] => 
                [http://rs.tdwg.org/dwc/terms/originalNameUsageID] => 
                [http://rs.tdwg.org/dwc/terms/scientificName] => Laemosaccus rileyi Hespenheide 2019
                [http://rs.tdwg.org/dwc/terms/namePublishedIn] => 
                [http://rs.tdwg.org/dwc/terms/kingdom] => Animalia
                [http://rs.tdwg.org/dwc/terms/phylum] => Arthropoda
                [http://rs.tdwg.org/dwc/terms/class] => Insecta
                [http://rs.tdwg.org/dwc/terms/order] => Coleoptera
                [http://rs.tdwg.org/dwc/terms/family] => Curculionidae
                [http://rs.tdwg.org/dwc/terms/genus] => Laemosaccus
                [http://rs.tdwg.org/dwc/terms/taxonRank] => species
                [http://rs.tdwg.org/dwc/terms/scientificNameAuthorship] => Hespenheide 2019
                [http://rs.tdwg.org/dwc/terms/taxonomicStatus] => 
                [http://rs.tdwg.org/dwc/terms/nomenclaturalStatus] => 
                [http://purl.org/dc/terms/references] => http://treatment.plazi.org/id/DB5AFC3EC73E5732C0D8E33CFDC6FD63
                [http://rs.gbif.org/terms/1.0/canonicalName] => Laemosaccus rileyi
            )*/
            //===========================================================================================================================================================
            $taxonID = $rec['http://rs.tdwg.org/dwc/terms/taxonID'];
            $phylum = $rec['http://rs.tdwg.org/dwc/terms/phylum'];
            $class = $rec['http://rs.tdwg.org/dwc/terms/class'];
            $order = $rec['http://rs.tdwg.org/dwc/terms/order'];
            $kingdom = $rec['http://rs.tdwg.org/dwc/terms/kingdom'];

            if($what == 'info') {
                /* Too confusing for me- please remove the whole record:
                    class=Gastropoda, where phylum=Arthropoda
                    class=Amphibia, where phylum=Arthropoda
                    order=Anura, where phylum=Arthropoda
                    class=Gastropoda, where phylum=Chordata
                    class=Hexanauplia, where phylum=Chordata
                    class=Insecta, where phylum=Chordata
                */
                if($class == 'Gastropoda'   && $phylum == 'Arthropoda') $this->taxonIDs_to_delete[$taxonID] = '';
                if($class == 'Amphibia'     && $phylum == 'Arthropoda') $this->taxonIDs_to_delete[$taxonID] = '';
                if($order == 'Anura'        && $phylum == 'Arthropoda') $this->taxonIDs_to_delete[$taxonID] = '';
                if($class == 'Gastropoda'   && $phylum == 'Chordata') $this->taxonIDs_to_delete[$taxonID] = '';
                if($class == 'Hexanauplia'  && $phylum == 'Chordata') $this->taxonIDs_to_delete[$taxonID] = '';
                if($class == 'Insecta'      && $phylum == 'Chordata') $this->taxonIDs_to_delete[$taxonID] = '';

                // /* new: Nov 21, 2023:
                if($scientificName = @$rec["http://rs.tdwg.org/dwc/terms/scientificName"]) {
                    if(!Functions::valid_sciname_for_traits($scientificName)) $this->taxonIDs_to_delete[$taxonID] = '';
                }
                // */

            } //end what == 'info'
            //===========================================================================================================================================================
            if($what == 'write_taxa') {
                if(isset($this->taxonIDs_to_delete[$taxonID])) continue;
                $acceptedNameUsageID = $rec['http://rs.tdwg.org/dwc/terms/acceptedNameUsageID'];
                $parentNameUsageID   = $rec['http://rs.tdwg.org/dwc/terms/parentNameUsageID'];
                $originalNameUsageID = $rec['http://rs.tdwg.org/dwc/terms/originalNameUsageID'];
                if(isset($this->taxonIDs_to_delete[$acceptedNameUsageID])) $rec['http://rs.tdwg.org/dwc/terms/acceptedNameUsageID'] = '';
                if(isset($this->taxonIDs_to_delete[$parentNameUsageID])) $rec['http://rs.tdwg.org/dwc/terms/parentNameUsageID'] = '';
                if(isset($this->taxonIDs_to_delete[$originalNameUsageID])) $rec['http://rs.tdwg.org/dwc/terms/originalNameUsageID'] = '';

                //-----------------------------------------------------------
                /* They use "Animalia" quite often instead of "Metazoa" in their ancestry data. Could we have a global replace for that? */
                if($kingdom == 'Metazoa') $rec['http://rs.tdwg.org/dwc/terms/kingdom'] = 'Animalia';
                //-----------------------------------------------------------
                /* surgery within the ancestry data, preserving the record:
                    order=Passeriformes, where phylum=Arthropoda => remove Passeriformes
                    class=Aves, where phylum=Arthropoda => remove Aves
                    class=Actinopterygii, where phylum=Arthropoda => remove Actinopterygii
                */
                if($order == 'Passeriformes'    && $phylum == 'Arthropoda') $rec['http://rs.tdwg.org/dwc/terms/order'] = ''; //remove Passeriformes
                if($class == 'Aves'             && $phylum == 'Arthropoda') $rec['http://rs.tdwg.org/dwc/terms/class'] = ''; //remove Aves
                if($class == 'Actinopterygii'   && $phylum == 'Arthropoda') $rec['http://rs.tdwg.org/dwc/terms/class'] = ''; //remove Actinopterygii
                //-----------------------------------------------------------
                /* Some other nonstandard terms that end up in the ancestry data in this resource, to be removed wherever found:
                    ORDO
                    FAMILIA
                    Not
                    null
                */
                $fields = array('http://rs.tdwg.org/dwc/terms/kingdom', 'http://rs.tdwg.org/dwc/terms/phylum', 'http://rs.tdwg.org/dwc/terms/class',
                                'http://rs.tdwg.org/dwc/terms/order', 'http://rs.tdwg.org/dwc/terms/family', 'http://rs.tdwg.org/dwc/terms/genus');
                $strings = array('ORDO', 'FAMILIA', 'Not', 'null');
                foreach($fields as $field) {
                    $val = $rec[$field];
                    if(in_array($val, $strings)) $rec[$field] = '';
                }
                //-----------------------------------------------------------
                /* start write */
                $o = new \eol_schema\Taxon();
                $uris = array_keys($rec);
                foreach($uris as $uri) {
                    $field = pathinfo($uri, PATHINFO_BASENAME);
                    $o->$field = $rec[$uri];
                }
                $this->archive_builder->write_object_to_file($o);
            } //end what == 'write_taxa'
            //===========================================================================================================================================================

            // if($i >= 10) break; //debug only
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
            /**/
            //===========================================================================================================================================================
            $occurrenceID = $rec['http://rs.tdwg.org/dwc/terms/occurrenceID'];
            if(isset($this->occurrenceIDs_to_delete[$occurrenceID])) continue;
            
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

            // /* Nov 15, 2023: "linn" -> http://purl.obolibrary.org/obo/ENVO_00000040 
            // Any output from this source string should be discarded. It's a common form of the author string "Linnaeus"
            // https://eol-jira.bibalex.org/browse/DATA-1896?focusedCommentId=67722&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-67722
            // http://purl.obolibrary.org/obo/ENVO_00000040	source text: "linn"
            if($o->measurementValue == "http://purl.obolibrary.org/obo/ENVO_00000040" || @$o->measurementRemarks == 'source text: "linn"') continue;
            // */
    
            /* END DATA-1841 terms remapping */
            // $o->measurementID = Functions::generate_measurementID($o, $this->resource_id); //copied template
            $this->archive_builder->write_object_to_file($o);
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
            /**/
            
            $taxonID      = $rec['http://rs.tdwg.org/dwc/terms/taxonID'];
            $occurrenceID = $rec['http://rs.tdwg.org/dwc/terms/occurrenceID'];
            if(isset($this->taxonIDs_to_delete[$taxonID])) {
                $this->occurrenceIDs_to_delete[$occurrenceID] = '';
                continue;
            }
            
            
            $uris = array_keys($rec);
            // $uris = array('http://rs.tdwg.org/dwc/terms/occurrenceID', 'http://rs.tdwg.org/dwc/terms/taxonID'); //copied template
            $o = new \eol_schema\Occurrence_specific();
            foreach($uris as $uri) {
                $field = pathinfo($uri, PATHINFO_BASENAME);
                $o->$field = $rec[$uri];
            }
            $this->archive_builder->write_object_to_file($o);
            // if($i >= 10) break; //debug only
        }
    }
    /*================================================================= ENDS HERE ======================================================================*/
}
?>