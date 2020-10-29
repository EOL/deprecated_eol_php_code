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
        if(in_array($this->resource_id, array('692_meta_recoded'))) self::task_123($tables);
        
        /* task 2: eventDate
        http://rs.tdwg.org/dwc/terms/eventDate - the more awkward moving method which will apply to the rest of the cases; 
        from a column in occurrences, to a new column in MoF, with the occurrence record being applied to all MoF records for that occurrence. 
        The uri for the meta file for the new column: http://rs.tdwg.org/dwc/terms/measurementDeterminedDate
        DONE: from a column in occurrences, to a new column in MoF measurementDeterminedDate
        TODO: - a new variation of the eventDate: where eventDate is in MoF with measurementOfTaxon = false with mType = eventDate
        transferred to MoF column measurementDeterminedDate, applied to all MoF records for that occurrence.
        e.g. Harvard Museum of Comparative Zoology [OpenData|https://opendata.eol.org/dataset/harvard-museum-of-comparative-zoology/resource/c70577a3-7ba7-472f-b3de-bf3043beebfd]
        */
        if(in_array($this->resource_id, array('201_meta_recoded'))) self::task_eventDate_as_row_in_MoF($tables);
        
        /* task 3: occurrenceRemarks
        http://rs.tdwg.org/dwc/terms/occurrenceRemarks - same sort of move, to a MoF column with uri http://rs.tdwg.org/dwc/terms/measurementRemarks
        */

        if(in_array($this->resource_id, array('770_meta_recoded', 'natdb_meta_recoded', 'copepods_meta_recoded',
                                              '42_meta_recoded', 'cotr_meta_recoded'))) self::task_67($tables);
        /* http://rs.tdwg.org/dwc/terms/lifeStage - from a column in MoF (or possibly a child record?), this should move to a column in occurrences
           http://rs.tdwg.org/dwc/terms/sex - from a column in MoF (or possibly a child record?), this should move to a column in occurrences
        TODO: no implementation yet if lifeStage or sex is a child row in MoF.
        */
        
        if(in_array($this->resource_id, array('test_meta_recoded'))) self::task_45($tables);
        /* http://rs.tdwg.org/dwc/terms/measurementUnit - from wherever it is (child record?), this should move to a column in MoF
           http://eol.org/schema/terms/statisticalMethod - from wherever it is (child record?), this should move to a column in MoF
        TODO: no implementation yet if measurementUnit or statisticalMethod is a child row in MoF
        DONE: if mUnit and sMethod is a column in occurrence -> moved to a column in MoF
        */
    }
    private function task_eventDate_as_row_in_MoF($tables)
    {
        self::process_measurementorfact($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0], 'task_eventDate_info');
        self::process_measurementorfact($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0], 'write_task_eventDate');
    }
    private function task_45($tables)
    {
        self::process_occurrence($tables['http://rs.tdwg.org/dwc/terms/occurrence'][0], 'task_45_info_write');
        self::process_measurementorfact($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0], 'write_task_45');
        unset($this->oID_measurementUnit);     //task_4
        unset($this->oID_statisticalMethod);   //task_5
    }
    private function task_67($tables)
    {
        self::process_measurementorfact($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0], 'task_67_info');
        self::process_occurrence($tables['http://rs.tdwg.org/dwc/terms/occurrence'][0], 'write_task_67'); 
        self::process_measurementorfact($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0], 'write_task_67');
        unset($this->oID_lifeStage);   //task_6
        unset($this->oID_sex);         //task_7
        print_r($this->debug); //exit("\n---------------------\n");
    }
    private function task_123($tables)
    {   /*  http://rs.tdwg.org/dwc/terms/individualCount - probably the easiest to move; from its column in occurrences 
            to a "measurementOfTaxon=FALSE record in the MoF file, 
            with measurementType=http://eol.org/schema/terms/SampleSize
        */
        self::process_occurrence($tables['http://rs.tdwg.org/dwc/terms/occurrence'][0], 'task_123_info'); 
            /* generates:
            $this->oID_individualCount[$occurrenceID]   = $individualCount     //task_1
            $this->oID_eventDate[$occurrenceID]         = $eventDate           //task_2
            $this->oID_occurrenceRemarks[$occurrenceID] = $occurrenceRemarks   //task_3
            */
        self::process_measurementorfact($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0], 'task_123_info');
            /* Loops MoF build info -> $this->oID_mID_mOfTaxon[oID][mID][mOfTaxon] = '' */
        // print_r($this->oID_individualCount); print_r($this->oID_mID_mOfTaxon); exit;
        self::process_measurementorfact($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0], 'write_task_123');
        self::process_occurrence($tables['http://rs.tdwg.org/dwc/terms/occurrence'][0], 'write_task_123'); 

        unset($this->oID_individualCount);      //task_1
        unset($this->oID_eventDate);            //task_2
        unset($this->oID_occurrenceRemarks);    //task_3
        unset($this->oID_mID_mOfTaxon);

        // self::organize_MoF_mOfTaxon_false_create_if_needed(); //not used at the moment
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
            $measurementType = $rec['http://rs.tdwg.org/dwc/terms/measurementType'];
            $measurementValue = $rec['http://rs.tdwg.org/dwc/terms/measurementValue'];
            // if($occurrenceID != '12e1aea54c7d8dc661f84043155a5cde_692') continue; //debug only
            // if($occurrenceID != 'b33cb50b7899db1686454eb60113ca25_692') continue; //debug only - has both eventDate and occurrenceRemarks
            //===========================================================================================================================================================
            if($what == 'task_123_info') {
                /* Loops MoF build info -> $this->oID_mID_mOfTaxon[oID][mID][mOfTaxon] = '' */
                if(isset($this->oID_individualCount[$occurrenceID])) {
                    $this->oID_mID_mOfTaxon[$occurrenceID][$measurementID][$measurementOfTaxon] = '';
                }
            }
            if($what == 'task_eventDate_info') {
                if($measurementOfTaxon != 'true' && $measurementType == 'http://rs.tdwg.org/dwc/terms/eventDate') {
                    $this->oID_eventDate[$occurrenceID] = $measurementValue;
                    $this->delete_mIDs[$measurementID] = '';
                }
            }
            if($what == 'write_task_eventDate') {
                if(isset($this->delete_mIDs[$measurementID])) continue;
                if($eventDate = @$this->oID_eventDate[$occurrenceID]) $rec['http://rs.tdwg.org/dwc/terms/measurementDeterminedDate'] = $eventDate;
                self::write_MoF_rec($rec);
            }
            if($what == 'task_67_info') { //lifeStage | sex
                if($val = @$rec['http://rs.tdwg.org/dwc/terms/lifeStage']) $this->oID_lifeStage[$occurrenceID] = $val;   //task_6
                if($val = @$rec['http://rs.tdwg.org/dwc/terms/sex'])       $this->oID_sex[$occurrenceID] = $val;         //task_7
                $this->debug['contents']['M lifeStage'][@$rec['http://rs.tdwg.org/dwc/terms/lifeStage']] = '';
            }
            if($what == 'write_task_45') {
                if(!@$rec['http://eol.org/schema/parentMeasurementID']) { //not a child MoF
                    if($measurementUnit = @$this->oID_measurementUnit[$occurrenceID]) { //task_4
                        $rec['http://rs.tdwg.org/dwc/terms/measurementUnit'] = $measurementUnit;
                    }
                    if($statisticalMethod = @$this->oID_statisticalMethod[$occurrenceID]) { //task_5
                        $rec['http://eol.org/schema/terms/statisticalMethod'] = $statisticalMethod;
                    }
                }
                self::write_MoF_rec($rec);
            }
            if($what == 'write_task_67') {
                if(isset($rec['http://rs.tdwg.org/dwc/terms/lifeStage'])) unset($rec['http://rs.tdwg.org/dwc/terms/lifeStage']);    //task_6
                if(isset($rec['http://rs.tdwg.org/dwc/terms/sex']))       unset($rec['http://rs.tdwg.org/dwc/terms/sex']);          //task_7
                self::write_MoF_rec($rec);
            }
            //===========================================================================================================================================================
            if($what == 'write_task_123') { //MoF
                // /* task_2
                if($eventDate = @$this->oID_eventDate[$occurrenceID]) { //task_2
                    $rec['http://rs.tdwg.org/dwc/terms/measurementDeterminedDate'] = $eventDate;
                }
                // */
                // /* task_3
                if($occurrenceRemarks = @$this->oID_occurrenceRemarks[$occurrenceID]) { //task_2
                    $rec['http://rs.tdwg.org/dwc/terms/measurementRemarks'] = $occurrenceRemarks;
                }
                // */
                $m = self::write_MoF_rec($rec);
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
            /* not used at the moment...
            if($what == 'write') {
                $m = new \eol_schema\MeasurementOrFact_specific();
                $uris = array_keys($rec);
                foreach($uris as $uri) {
                    $field = pathinfo($uri, PATHINFO_BASENAME);
                    $m->$field = $rec[$uri];
                }
                // START DATA-1841 terms remapping
                $m = $this->func->given_m_update_mType_mValue($m);
                // echo "\nLocal: ".count($this->func->remapped_terms)."\n"; //just testing
                // END DATA-1841 terms remapping
                $this->archive_builder->write_object_to_file($m);
            } */
            //===========================================================================================================================================================
            // if($i >= 10) break; //debug only
        }
    }
    private function write_MoF_rec($rec)
    {
        $m = new \eol_schema\MeasurementOrFact_specific();
        $uris = array_keys($rec);
        foreach($uris as $uri) {
            $field = pathinfo($uri, PATHINFO_BASENAME);
            $m->$field = $rec[$uri];
        }
        $this->archive_builder->write_object_to_file($m);
        return $m;
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
            // if($occurrenceID != '12e1aea54c7d8dc661f84043155a5cde_692') continue; //debug only
            // if($occurrenceID != 'b33cb50b7899db1686454eb60113ca25_692') continue; //debug only - has both eventDate and occurrenceRemarks
            //===========================================================================================================================================================
            $this->debug['contents']['O lifeStage'][@$rec['http://rs.tdwg.org/dwc/terms/lifeStage']] = '';
            
            if($what == 'task_123_info') {
                if($val = @$rec['http://rs.tdwg.org/dwc/terms/individualCount']) $this->oID_individualCount[$occurrenceID] = $val;    //task_1
                if($val = @$rec['http://rs.tdwg.org/dwc/terms/eventDate']) $this->oID_eventDate[$occurrenceID] = $val;                //task_2
                if($val = @$rec['http://rs.tdwg.org/dwc/terms/occurrenceRemarks']) $this->oID_occurrenceRemarks[$occurrenceID] = $val;//task_3
            }
            elseif($what == 'task_45_info_write') {
                if($val = @$rec['http://rs.tdwg.org/dwc/terms/measurementUnit']) $this->oID_measurementUnit[$occurrenceID] = $val;    //task_4
                if($val = @$rec['http://eol.org/schema/terms/statisticalMethod']) $this->oID_statisticalMethod[$occurrenceID] = $val; //task_5
                //write
                if(isset($rec['http://rs.tdwg.org/dwc/terms/measurementUnit']))  unset($rec['http://rs.tdwg.org/dwc/terms/measurementUnit']);   //task_4
                if(isset($rec['http://eol.org/schema/terms/statisticalMethod'])) unset($rec['http://eol.org/schema/terms/statisticalMethod']);  //task_5
                self::write_occurrence($rec);
            }
            elseif($what == 'write_task_67') {
                if($val = @$this->oID_lifeStage[$occurrenceID]) {
                    if($val2 = @$rec['http://rs.tdwg.org/dwc/terms/lifeStage']) echo "\nmay laman [$val2] [$val]\n"; //stats only
                                                                $rec['http://rs.tdwg.org/dwc/terms/lifeStage'] = $val;  //task_6
                }
                if($val = @$this->oID_sex[$occurrenceID])       $rec['http://rs.tdwg.org/dwc/terms/sex'] = $val;        //task_7
                self::write_occurrence($rec);
            }
            //===========================================================================================================================================================
            elseif($what == 'write_task_123') {
                if(isset($rec['http://rs.tdwg.org/dwc/terms/individualCount']))   unset($rec['http://rs.tdwg.org/dwc/terms/individualCount']);  //task_1
                if(isset($rec['http://rs.tdwg.org/dwc/terms/eventDate']))         unset($rec['http://rs.tdwg.org/dwc/terms/eventDate']);        //task_2
                if(isset($rec['http://rs.tdwg.org/dwc/terms/occurrenceRemarks'])) unset($rec['http://rs.tdwg.org/dwc/terms/occurrenceRemarks']);//task_3
                // print_r($rec); exit;
                self::write_occurrence($rec);
            }
            //===========================================================================================================================================================
            /* not used atm.
            elseif($what == 'write') {
                $uris = array_keys($rec);
                $o = new \eol_schema\Occurrence_specific();
                foreach($uris as $uri) {
                    $field = pathinfo($uri, PATHINFO_BASENAME);
                    $o->$field = $rec[$uri];
                }
                $this->archive_builder->write_object_to_file($o);
            }
            */
            // if($i >= 10) break; //debug only
        }
    }
    private function write_occurrence($rec)
    {
        $uris = array_keys($rec);
        $o = new \eol_schema\Occurrence_specific();
        foreach($uris as $uri) {
            $field = pathinfo($uri, PATHINFO_BASENAME);
            $o->$field = $rec[$uri];
        }
        $this->archive_builder->write_object_to_file($o);
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
