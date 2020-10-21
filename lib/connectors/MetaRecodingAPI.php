<?php
namespace php_active_record;
/* connector: [called from DwCA_Utility.php, which is called from resource_utility.php for DATA-1863] */
class MetaRecodingAPI
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
        $this->func->initialize_terms_remapping(60*60*24); //param is $expire_seconds. 0 means expire now.
        /* END DATA-1841 terms remapping */
        
        $tables = $info['harvester']->tables;
        
        /* task 1: individualCount */
        if(in_array($this->resource_id, array('692_meta_recoded'))) self::task_1($tables);
        
        /* task 2: eventDate */
        if(in_array($this->resource_id, array('692_meta_recoded'))) self::task_2($tables);
        
    }
    private function task_2($tables)
    {   /*
        http://rs.tdwg.org/dwc/terms/eventDate - the more awkward moving method which will apply to the rest of the cases; 
        from a column in occurrences, to a new column in MoF, with the occurrence record being applied to all MoF records for that occurrence. 
        The uri for the meta file for the new column: http://rs.tdwg.org/dwc/terms/measurementDeterminedDate
        */
        
    }
    private function task_1($tables)
    {   /*  http://rs.tdwg.org/dwc/terms/individualCount - probably the easiest to move; from its column in occurrences 
            to a "measurementOfTaxon=FALSE record in the MoF file, 
            with measurementType=http://eol.org/schema/terms/SampleSize
        */
        self::process_occurrence($tables['http://rs.tdwg.org/dwc/terms/occurrence'][0], 'task_1_info'); 
            /* generates $this->oID_individualCount[$occurrenceID] = $individualCount */
        self::process_measurementorfact($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0], 'task_1_info');
            /* Loops MoF build info -> $this->oID_mID_mOfTaxon[oID][mID][mOfTaxon] = '' */
        // print_r($this->oID_individualCount); print_r($this->oID_mID_mOfTaxon); exit;
        self::process_measurementorfact($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0], 'write_task_1');
        self::process_occurrence($tables['http://rs.tdwg.org/dwc/terms/occurrence'][0], 'write_task_1'); 

        // self::organize_MoF_mOfTaxon_false_create_if_needed();
    }
    /* not used
    private function organize_MoF_mOfTaxon_false_create_if_needed($occurrenceID)
    {   
        $this->oID_individualCount
        Array(
            [12e1aea54c7d8dc661f84043155a5cde_692] => 743
        )
        $this->oID_mID_mOfTaxon
        Array(
            [12e1aea54c7d8dc661f84043155a5cde_692] => Array(
                    [701b0a2f26dc29fb010578363b0b29ef_692] => Array([true] => )
                    [aa9b7b5ad7a0de7f2cf1a5a1e0795353_692] => Array([true] => )
                    [db61bc0d012b92f0025972e0a96f526d_692] => Array([true] => )
                    [eec24afe1ca94f9a60f3d4db85b557cb_692] => Array([true] => )
                )
        )
        print_r($this->oID_individualCount[$occurrenceID]);
        print_r($this->oID_mID_mOfTaxon[$occurrenceID]); //exit;
        child record in MoF:
            - doesn't have: occurrenceID | measurementOfTaxon
            - has parentMeasurementID
            - has also a unique measurementID, as expected.
        minimum cols on a child record in MoF
            - measurementID
            - measurementType
            - measurementValue
            - parentMeasurementID
    } */
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
            $rec = array_map('trim', $rec);
            /*Array(
                [http://rs.tdwg.org/dwc/terms/measurementID] => 701b0a2f26dc29fb010578363b0b29ef_692
                [http://rs.tdwg.org/dwc/terms/occurrenceID] => 12e1aea54c7d8dc661f84043155a5cde_692
                [http://eol.org/schema/measurementOfTaxon] => true
                [http://rs.tdwg.org/dwc/terms/measurementType] => http://rs.tdwg.org/dwc/terms/decimalLatitude
                [http://rs.tdwg.org/dwc/terms/measurementValue] => -14.8272
                [http://rs.tdwg.org/dwc/terms/measurementUnit] => http://purl.obolibrary.org/obo/UO_0000185
                [http://eol.org/schema/terms/statisticalMethod] => http://semanticscience.org/resource/SIO_001113
            )*/
            $measurementID = $rec['http://rs.tdwg.org/dwc/terms/measurementID'];
            $occurrenceID = $rec['http://rs.tdwg.org/dwc/terms/occurrenceID'];
            $measurementOfTaxon = $rec['http://eol.org/schema/measurementOfTaxon'];
            if($occurrenceID != '12e1aea54c7d8dc661f84043155a5cde_692') continue;
            //===========================================================================================================================================================
            if($what == 'task_1_info') {
                /* Loops MoF build info -> $this->oID_mID_mOfTaxon[oID][mID][mOfTaxon] = '' */
                if(isset($this->oID_individualCount[$occurrenceID])) {
                    $this->oID_mID_mOfTaxon[$occurrenceID][$measurementID][$measurementOfTaxon] = '';
                }
            }
            //===========================================================================================================================================================
            if($what == 'write_task_1') {
                // /* task_2
                if($eventDate = @$this->oID_eventDate[$occurrenceID]) { //task_2
                    $rec['http://rs.tdwg.org/dwc/terms/measurementDeterminedDate'] = $eventDate;
                }
                // */
                $m = new \eol_schema\MeasurementOrFact_specific();
                $uris = array_keys($rec);
                foreach($uris as $uri) {
                    $field = pathinfo($uri, PATHINFO_BASENAME);
                    $m->$field = $rec[$uri];
                }
                $this->archive_builder->write_object_to_file($m);
                
                /*
                child record in MoF:
                    - doesn't have: occurrenceID | measurementOfTaxon
                    - has parentMeasurementID
                    - has also a unique measurementID, as expected.
                minimum cols on a child record in MoF
                    - measurementID
                    - measurementType
                    - measurementValue
                    - parentMeasurementID
                */
                // /* task_1
                if($individualCount = @$this->oID_individualCount[$m->occurrenceID]) { //create a new row (child row)
                    $m2 = new \eol_schema\MeasurementOrFact_specific();
                    $rek = array();
                    $rek['http://rs.tdwg.org/dwc/terms/measurementID'] = md5("$m->measurementID|$individualCount|SampleSize");
                    $rek['http://rs.tdwg.org/dwc/terms/measurementType'] = 'http://eol.org/schema/terms/SampleSize';
                    $rek['http://rs.tdwg.org/dwc/terms/measurementValue'] = $individualCount;
                    $rek['http://eol.org/schema/parentMeasurementID'] = $m->measurementID;
                    $uris = array_keys($rek);
                    foreach($uris as $uri) {
                        $field = pathinfo($uri, PATHINFO_BASENAME);
                        $m2->$field = $rek[$uri];
                    }
                    $this->archive_builder->write_object_to_file($m2);
                }
                // */
            }
            //===========================================================================================================================================================
            if($what == 'write') {
                $m = new \eol_schema\MeasurementOrFact_specific();
                $uris = array_keys($rec);
                foreach($uris as $uri) {
                    $field = pathinfo($uri, PATHINFO_BASENAME);
                    $m->$field = $rec[$uri];
                }
                /* START DATA-1841 terms remapping */
                $m = $this->func->given_m_update_mType_mValue($m);
                // echo "\nLocal: ".count($this->func->remapped_terms)."\n"; //just testing
                /* END DATA-1841 terms remapping */
                $this->archive_builder->write_object_to_file($m);
            }
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
            $rec = array_map('trim', $rec);
            // print_r($rec); exit("\ndebug...\n");
            /*Array(
                [http://rs.tdwg.org/dwc/terms/occurrenceID] => 12e1aea54c7d8dc661f84043155a5cde_692
                [http://rs.tdwg.org/dwc/terms/taxonID] => 831047
                [http://rs.tdwg.org/dwc/terms/individualCount] => 743
                [http://rs.tdwg.org/dwc/terms/eventDate] => 1968-08-11 11:00:00+00 to 1974-01-05 11:00:00+00
                [http://rs.tdwg.org/dwc/terms/occurrenceRemarks] => 
            )*/
            $occurrenceID = $rec['http://rs.tdwg.org/dwc/terms/occurrenceID'];
            if($occurrenceID != '12e1aea54c7d8dc661f84043155a5cde_692') continue;
            //===========================================================================================================================================================
            if($what == 'task_1_info') {
                if($val = $rec['http://rs.tdwg.org/dwc/terms/individualCount']) $this->oID_individualCount[$occurrenceID] = $val;   //task_1
                if($val = $rec['http://rs.tdwg.org/dwc/terms/eventDate']) $this->oID_eventDate[$occurrenceID] = $val;               //task_2
            }
            //===========================================================================================================================================================
            elseif($what = 'write_task_1') {
                if(isset($rec['http://rs.tdwg.org/dwc/terms/individualCount'])) unset($rec['http://rs.tdwg.org/dwc/terms/individualCount']); //task_1
                if(isset($rec['http://rs.tdwg.org/dwc/terms/eventDate']))       unset($rec['http://rs.tdwg.org/dwc/terms/eventDate']);       //task_2
                // print_r($rec); exit;
                $uris = array_keys($rec);
                $o = new \eol_schema\Occurrence_specific();
                foreach($uris as $uri) {
                    $field = pathinfo($uri, PATHINFO_BASENAME);
                    $o->$field = $rec[$uri];
                }
                $this->archive_builder->write_object_to_file($o);
            }
            //===========================================================================================================================================================
            elseif($what = 'write') {
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
    }
    /* not used, from copied template
    private function create_taxon($rec)
    {
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID  = $rec["Symbol"];
        $taxon->scientificName  = $rec["Scientific Name with Author"];
        $taxon->taxonomicStatus = 'valid';
        $taxon->family  = $rec["Family"];
        $taxon->source = $rec['source_url'];
        // $taxon->taxonRank       = '';
        // $taxon->taxonRemarks    = '';
        // $taxon->rightsHolder    = '';
        if(!isset($this->taxon_ids[$taxon->taxonID])) {
            $this->taxon_ids[$taxon->taxonID] = '';
            $this->archive_builder->write_object_to_file($taxon);
        }
    }
    private function create_vernacular($rec)
    {   if($comname = $rec['National Common Name']) {
            $v = new \eol_schema\VernacularName();
            $v->taxonID         = $rec["Symbol"];
            $v->vernacularName  = $comname;
            $v->language        = 'en';
            $this->archive_builder->write_object_to_file($v);
        }
    }
    */
    /*================================================================= ENDS HERE ======================================================================*/
}
?>
