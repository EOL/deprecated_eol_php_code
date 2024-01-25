<?php
namespace php_active_record;
/* connector: [called from DwCA_Utility.php, which is called from resource_utility.php for DATA-1863] */
class MetaRecodingAPI
{
    function __construct($archive_builder, $resource_id)
    {
        $this->resource_id = $resource_id;
        $this->archive_builder = $archive_builder;
        /* Hmmm not used...
        $this->download_options = array('cache' => 1, 'resource_id' => $resource_id, 'expire_seconds' => 60*60*24*30*4, 'download_wait_time' => 1000000, 'timeout' => 10800, 'download_attempts' => 1, 'delay_in_minutes' => 1);
        // $this->download_options['expire_seconds'] = false; //comment after first harvest
        */
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
        if(in_array($this->resource_id, array('692_meta_recoded', 'griis_meta_recoded', 'natdb_meta_recoded_1'))) self::task_123($tables);
        if(in_array($this->resource_id, array('726_meta_recoded'))) self::task_individualCount_as_child_in_MoF($tables);
        
        /* task 2: eventDate
        http://rs.tdwg.org/dwc/terms/eventDate - the more awkward moving method which will apply to the rest of the cases; 
        from a column in occurrences, to a new column in MoF, with the occurrence record being applied to all MoF records for that occurrence. 
        The uri for the meta file for the new column: http://rs.tdwg.org/dwc/terms/measurementDeterminedDate
        DONE: from a column in occurrences, to a new column in MoF measurementDeterminedDate
        DONE_2: where eventDate is in MoF with measurementOfTaxon = false with mType = eventDate
        transferred to MoF column measurementDeterminedDate, applied to all MoF records for that occurrence.
        e.g. Harvard Museum of Comparative Zoology [OpenData|https://opendata.eol.org/dataset/harvard-museum-of-comparative-zoology/resource/c70577a3-7ba7-472f-b3de-bf3043beebfd]
        */
        if(in_array($this->resource_id, array('201_meta_recoded', 'cotr_meta_recoded'))) self::task_eventDate_as_row_in_MoF($tables);
        
        // /* task is to move MoF cols as MoF child rows
        if(in_array($this->resource_id, array('cotr_meta_recoded_final'))) {
            self::task_move_MoF_cols_2_MoF_children($tables);
        }
        // */
        
        /* task 3: occurrenceRemarks
        http://rs.tdwg.org/dwc/terms/occurrenceRemarks - same sort of move, to a MoF column with uri http://rs.tdwg.org/dwc/terms/measurementRemarks
        */

        if(in_array($this->resource_id, array('770_meta_recoded', 'natdb_meta_recoded', 'copepods_meta_recoded',
                                              '42_meta_recoded', 'cotr_meta_recoded_1', '727_meta_recoded',
                                              '707_meta_recoded', 'test3_meta_recoded', '26_meta_recoded',
                                              'try_dbase_2024_meta_recoded'))) self::task_67($tables);
        /* http://rs.tdwg.org/dwc/terms/lifeStage - from a column in MoF (or possibly a child record?), this should move to a column in occurrences
           http://rs.tdwg.org/dwc/terms/sex - from a column in MoF (or possibly a child record?), this should move to a column in occurrences
        DONE2: if lifeStage or sex is a child row in MoF. Implemented in WoRMS (26).
        */
        
        if(in_array($this->resource_id, array('test_meta_recoded', 'test2_meta_recoded', '26_meta_recoded_1'))) self::task_45($tables);
        /* http://rs.tdwg.org/dwc/terms/measurementUnit - from wherever it is (child record?), this should move to a column in MoF
           http://eol.org/schema/terms/statisticalMethod - from wherever it is (child record?), this should move to a column in MoF
        DONE2: if measurementUnit or statisticalMethod is a child row in MoF. Implemented in WoRMS (26).
        DONE: if mUnit and sMethod is a column in occurrence -> moved to a column in MoF
        */
        
        // /* start Unrecognized_fields tasks -----------------------------------------------------
        if(in_array($this->resource_id, array('col_meta_recoded'))) {
            self::process_taxon($tables['http://rs.tdwg.org/dwc/terms/taxon'][0], 'carry_over');
            self::task_CCP2Agents($tables); //task_200: contributor, creator, publisher from Document to Agents
        }
        //CCP and probably missing measurementID
        if(in_array($this->resource_id, array('Cicadellinae_meta_recoded', 'Deltocephalinae_meta_recoded', 'Appeltans_et_al_meta_recoded', 
            '168_meta_recoded', '200_meta_recoded', 'Braconids_meta_recoded'))) {
            self::task_CCP2Agents($tables); //task_200: contributor, creator, publisher from Document to Agents
            self::task_carryOverMoF($tables);
        }
        
        if(in_array($this->resource_id, array('168_meta_recoded'))) { //DATA-1878
            self::process_taxon($tables['http://rs.tdwg.org/dwc/terms/taxon'][0], 'carry_over');
        }
        
        //CCP only
        if(in_array($this->resource_id, array('678_meta_recoded', 'ECSEML_meta_recoded', 'fwater_marine_image_bank_meta_recoded'))) {
            self::task_CCP2Agents($tables); //task_200: contributor, creator, publisher from Document to Agents
        }
        //occurrence2MoF
        if(in_array($this->resource_id, array('Carrano_2006_meta_recoded', 'plant_growth_form_meta_recoded'))) {
            self::task_move_col_in_occurrence_to_MoF_row_with_MeasurementOfTaxon_false($tables);
        }
        //CCP and occurrence2MoF
        if(in_array($this->resource_id, array('circa_meta_recoded', '201_meta_recoded_2'))) {
            self::task_CCP2Agents($tables); //task_200: contributor, creator, publisher from Document to Agents
            self::task_move_col_in_occurrence_to_MoF_row_with_MeasurementOfTaxon_false($tables);
        }
        // ----------------------------------------------------- */
        if(isset($this->debug)) print_r($this->debug);
    }
    private function task_CCP2Agents($tables) //task_200: contributor, creator, publisher from Document to Agents
    {
        self::process_document($tables['http://eol.org/schema/media/document'][0], 'move_CCP_to_Agents'); //CCP is contributor creator publisher
    }
    private function task_carryOverMoF($tables) //carryover MoF, but add measurementID if missing
    {
        if($val = @$tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0]) self::process_measurementorfact($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0], 'add_missing_measurementID');
    }
    private function task_individualCount_as_child_in_MoF($tables) //replace mType to 'http://eol.org/schema/terms/SampleSize'
    {
        self::process_measurementorfact($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0], 'write_task_indivCount');
    }
    private function task_eventDate_as_row_in_MoF($tables)
    {
        self::process_measurementorfact($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0], 'task_eventDate_info');
        self::process_measurementorfact($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0], 'write_task_eventDate');
    }

    private function task_move_MoF_cols_2_MoF_children($tables)
    {
        self::process_measurementorfact($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0], 'move_cols_2child_rows');
    }
    
    private function task_move_col_in_occurrence_to_MoF_row_with_MeasurementOfTaxon_false($tables) //for DATA-1875: recoding unrecognized fields
    {
        /* old 
        self::process_occurrence($tables['http://rs.tdwg.org/dwc/terms/occurrence'][0], 'task_occurrence2MoF'); // generates $this->{$fld_name}[$occurrenceID]
        self::write_occurrence2MoF();
        */
        
        // /* new: the new row in MoF will be now child records. With the correct implementation of being child records:
        self::process_measurementorfact($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0], 'task_occurrence2MoF');
            // generates $this->oID_with_True_mOfTaxon_mID[$occurrenceID] = $measurementID
        self::process_occurrence($tables['http://rs.tdwg.org/dwc/terms/occurrence'][0], 'task_occurrence2MoF');
            // generates $this->{$fld_name}[$occurrenceID]
        self::write_occurrence2MoF();
        // */
    }
    private function task_45($tables)
    {
        if(in_array($this->resource_id, array('test_meta_recoded'))) {
            // DONE: if mUnit and sMethod is a column in occurrence -> moved to a column in MoF
            self::process_occurrence($tables['http://rs.tdwg.org/dwc/terms/occurrence'][0], 'task_45_info_write');
            self::process_measurementorfact($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0], 'write_task_45_1');
            unset($this->oID_measurementUnit);     //task_4
            unset($this->oID_statisticalMethod);   //task_5
        }
        elseif(in_array($this->resource_id, array('test2_meta_recoded', '26_meta_recoded_1'))) {
            // TODO: no implementation yet if measurementUnit or statisticalMethod is a child row in MoF
            self::process_measurementorfact($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0], 'task_45_info_2');
            self::process_measurementorfact($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0], 'write_task_45_2');
        }
    }
    private function task_67($tables)
    {   
        if(in_array($this->resource_id, array('test3_meta_recoded', '26_meta_recoded'))) {
            // /* lifeStage & sex as row child in MoF, move to column in occurrence
            self::process_measurementorfact($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0], 'task_67_info_2_pre'); //gen $this->mID_oID
            self::process_measurementorfact($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0], 'task_67_info_2');
            self::process_measurementorfact($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0], 'write_task_67_2');
            self::process_occurrence($tables['http://rs.tdwg.org/dwc/terms/occurrence'][0], 'write_task_67_2');
            unset($this->mID_oID);
            unset($this->oID_lifeStage);
            unset($this->oID_sex);
            // print_r($this->debug); //exit("\n---------------------\n");
            // */
        }
        else { //the rest
            // /* lifeStage & sex, column in MoF, move to column in occurrence
            self::process_measurementorfact($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0], 'task_67_info_1');
            self::process_occurrence($tables['http://rs.tdwg.org/dwc/terms/occurrence'][0], 'write_task_67_1');
            self::process_measurementorfact($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0], 'write_task_67_1');
            unset($this->oID_lifeStage);   //task_6
            unset($this->oID_sex);         //task_7
            // print_r($this->debug); //exit("\n---------------------\n");
            // */
        }
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
            // print_r($meta->fields);
            foreach($meta->fields as $field) {
                if(!$field['term']) continue;
                $rec[$field['term']] = $tmp[$k]; //put "@" as @$tmp[$k] during development
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
            )
            Array( for task_move_MoF_cols_2_MoF_children
                [http://rs.tdwg.org/dwc/terms/measurementID] => 843cbe9ac346937d30067251e55e1e3f_cotr
                [http://rs.tdwg.org/dwc/terms/occurrenceID] => 25
                [http://eol.org/schema/measurementOfTaxon] => true
                [http://eol.org/schema/parentMeasurementID] => 
                [http://rs.tdwg.org/dwc/terms/measurementType] => http://eol.org/schema/terms/Present
                [http://rs.tdwg.org/dwc/terms/measurementValue] => http://www.marineregions.org/mrgid/1903
                [http://rs.tdwg.org/dwc/terms/measurementUnit] => 
                [http://eol.org/schema/terms/statisticalMethod] => 
                [http://rs.tdwg.org/dwc/terms/measurementMethod] => Derived from range map (expert_opinion)
                [http://rs.tdwg.org/dwc/terms/measurementRemarks] => 
                [http://purl.org/dc/terms/source] => https://coraltraits.org/species/968
                [http://purl.org/dc/terms/bibliographicCitation] => Madin, Joshua (2016): Coral Trait Database 1.1.1. figshare. Dataset. https://doi.org/10.6084/m9.figshare.2067414.v1
                [http://eol.org/schema/reference/referenceID] => 40; 48
                [http://semanticscience.org/resource/SIO_000770] => 
                [http://purl.obolibrary.org/obo/STATO_0000035] => 
                [http://purl.obolibrary.org/obo/OBI_0000235] => 
                [http://semanticscience.org/resource/SIO_000769] => 
                [http://purl.obolibrary.org/obo/STATO_0000231] => 
                [http://rs.tdwg.org/dwc/terms/measurementDeterminedDate] => 
            )*/
            $measurementID = @$rec['http://rs.tdwg.org/dwc/terms/measurementID'];
            $occurrenceID = $rec['http://rs.tdwg.org/dwc/terms/occurrenceID'];
            $measurementOfTaxon = $rec['http://eol.org/schema/measurementOfTaxon'];
            $measurementType = $rec['http://rs.tdwg.org/dwc/terms/measurementType'];
            $measurementValue = $rec['http://rs.tdwg.org/dwc/terms/measurementValue'];
            $parentMeasurementID = @$rec['http://eol.org/schema/parentMeasurementID'];
            $measurementRemarks = @$rec['http://rs.tdwg.org/dwc/terms/measurementRemarks'];
            
            // /* manual correction of not-needed value. e.g. 'Unit' for measurementUnit.
            if($mUnit = @$rec['http://rs.tdwg.org/dwc/terms/measurementUnit']) {
                if($mUnit == 'Unit') $rec['http://rs.tdwg.org/dwc/terms/measurementUnit'] = '';
            }
            // */
            
            // if($occurrenceID != '12e1aea54c7d8dc661f84043155a5cde_692') continue; //debug only
            // if($occurrenceID != 'b33cb50b7899db1686454eb60113ca25_692') continue; //debug only - has both eventDate and occurrenceRemarks


            //===========================================================================================================================================================
            if($what == 'task_occurrence2MoF') { // New: as of Sep 28, 2022
                if($measurementOfTaxon == 'true') {
                    $this->oID_with_True_mOfTaxon_mID[$occurrenceID] = $measurementID;
                }
                continue;
            }
            //===========================================================================================================================================================
            if($what == 'move_cols_2child_rows') {
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
                $child_flds = array('http://semanticscience.org/resource/SIO_000770', 'http://purl.obolibrary.org/obo/STATO_0000035', 
                    'http://purl.obolibrary.org/obo/OBI_0000235', 'http://semanticscience.org/resource/SIO_000769', 
                    'http://purl.obolibrary.org/obo/STATO_0000231');
                foreach($child_flds as $mType) {
                    if($mValue = @$rec[$mType]) { //create a new row (child row)
                        $m2 = new \eol_schema\MeasurementOrFact_specific();
                        $rek = array();
                        $rek['http://rs.tdwg.org/dwc/terms/measurementID'] = md5("$mType|$mValue|$measurementID");
                        $rek['http://rs.tdwg.org/dwc/terms/measurementType'] = $mType;
                        $rek['http://rs.tdwg.org/dwc/terms/measurementValue'] = $mValue;
                        $rek['http://eol.org/schema/parentMeasurementID'] = $measurementID;
                        $uris = array_keys($rek);
                        foreach($uris as $uri) {
                            $field = pathinfo($uri, PATHINFO_BASENAME);
                            $m2->$field = $rek[$uri];
                        }
                        $this->archive_builder->write_object_to_file($m2);
                    }
                }
                unset($rec['http://semanticscience.org/resource/SIO_000770']);
                unset($rec['http://purl.obolibrary.org/obo/STATO_0000035']);
                unset($rec['http://purl.obolibrary.org/obo/OBI_0000235']);
                unset($rec['http://semanticscience.org/resource/SIO_000769']);
                unset($rec['http://purl.obolibrary.org/obo/STATO_0000231']);
                $m = self::write_MoF_rec($rec);
            }

            // /*
            if($what == 'task_45_info_2') {
                // /* statisticalMethod
                if($parentMeasurementID && $measurementType == 'http://eol.org/schema/terms/statisticalMethod') { //via parentMeasurementID
                    $this->mID_sMethod[$parentMeasurementID] = $measurementValue;
                }
                elseif($measurementOfTaxon != 'true' && $measurementType == 'http://eol.org/schema/terms/statisticalMethod') { //via mOfTaxon not 'true'
                    $this->oID_sMethod[$occurrenceID] = $measurementValue;
                }
                // */
                // /* measurementUnit
                if($parentMeasurementID && $measurementType == 'http://rs.tdwg.org/dwc/terms/measurementUnit') { //via parentMeasurementID
                    $this->mID_mUnit[$parentMeasurementID] = $measurementValue;
                }
                elseif($measurementOfTaxon != 'true' && $measurementType == 'http://rs.tdwg.org/dwc/terms/measurementUnit') { //via mOfTaxon not 'true'
                    $this->oID_mUnit[$occurrenceID] = $measurementValue;
                }
                // */
            }
            if($what == 'write_task_45_2') {
                // /* statisticalMethod
                if($val = @$this->mID_sMethod[$measurementID]) $rec['http://eol.org/schema/terms/statisticalMethod'] = $val;
                if(@$this->oID_sMethod[$occurrenceID] && $measurementOfTaxon == 'true') $rec['http://eol.org/schema/terms/statisticalMethod'] = $this->oID_sMethod[$occurrenceID];
                // */
                // /* measurementUnit
                if($val = @$this->mID_mUnit[$measurementID]) $rec['http://rs.tdwg.org/dwc/terms/measurementUnit'] = $val;
                if(@$this->oID_mUnit[$occurrenceID] && $measurementOfTaxon == 'true') $rec['http://rs.tdwg.org/dwc/terms/measurementUnit'] = $this->oID_mUnit[$occurrenceID];
                // */
                
                //statisticalMethod
                if($parentMeasurementID && $measurementType == 'http://eol.org/schema/terms/statisticalMethod') continue; //via parentMeasurementID
                elseif($measurementOfTaxon != 'true' && $measurementType == 'http://eol.org/schema/terms/statisticalMethod') continue; //via mOfTaxon not 'true'
                //measurementUnit
                if($parentMeasurementID && $measurementType == 'http://rs.tdwg.org/dwc/terms/measurementUnit') continue; //via parentMeasurementID
                elseif($measurementOfTaxon != 'true' && $measurementType == 'http://rs.tdwg.org/dwc/terms/measurementUnit') continue; //via mOfTaxon not 'true'

                self::write_MoF_rec($rec);
            }
            // */
            
            if($what == 'task_123_info') {
                /* Loops MoF build info -> $this->oID_mID_mOfTaxon[oID][mID][mOfTaxon] = '' */
                if(isset($this->oID_individualCount[$occurrenceID])) {
                    $this->oID_mID_mOfTaxon[$occurrenceID][$measurementID][$measurementOfTaxon] = '';
                }
            }
            if($what == 'task_eventDate_info') {
                if($measurementOfTaxon != 'true' && $measurementType == 'http://rs.tdwg.org/dwc/terms/eventDate') {
                    if($occurrenceID) {
                        $this->oID_eventDate[$occurrenceID] = $measurementValue;
                        $this->delete_mIDs[$measurementID] = '';
                    }
                    elseif($parentMeasurementID) {
                        $this->parentID_eventDate[$parentMeasurementID] = $measurementValue;
                        $this->delete_mIDs[$measurementID] = '';
                    }
                    else exit("\nFigure out the link...\n");
                }
            }
            elseif($what == 'write_task_eventDate') {
                if(isset($this->delete_mIDs[$measurementID])) continue;

                //when occurrenceID is used e.g. 201
                if($eventDate = @$this->oID_eventDate[$occurrenceID]) {
                    $rec['http://rs.tdwg.org/dwc/terms/measurementDeterminedDate'] = $eventDate;
                }
                
                //when parentmeasurementID is used e.g. cotr.tar.gz
                if($eventDate = @$this->parentID_eventDate[$parentMeasurementID]) {
                    $rec['http://rs.tdwg.org/dwc/terms/measurementDeterminedDate'] = $eventDate;
                }
                
                self::write_MoF_rec($rec);
            }

            if($what == 'write_task_indivCount') {
                if($parentMeasurementID && $measurementType == 'http://rs.tdwg.org/dwc/terms/individualCount') {
                    $rec['http://rs.tdwg.org/dwc/terms/measurementType'] = 'http://eol.org/schema/terms/SampleSize';
                }
                self::write_MoF_rec($rec);
            }
            
            if($what == 'add_missing_measurementID') self::write_MoF_rec($rec);
            
            if($what == 'task_67_info_1') { //lifeStage | sex
                if($val = @$rec['http://rs.tdwg.org/dwc/terms/lifeStage']) $this->oID_lifeStage[$occurrenceID] = $val;   //task_6
                if($val = @$rec['http://rs.tdwg.org/dwc/terms/sex'])       $this->oID_sex[$occurrenceID] = $val;         //task_7
                $this->debug['contents']['M lifeStage'][@$rec['http://rs.tdwg.org/dwc/terms/lifeStage']] = '';
            }
            // /*
            if($what == 'task_67_info_2_pre') { //for lifeStage | sex as row chile in MoF
                $this->mID_oID[$measurementID] = $occurrenceID;
            }
            if($what == 'task_67_info_2') { //lifeStage | sex
                // /* lifeStage
                if($parentMeasurementID && $measurementType == 'http://rs.tdwg.org/dwc/terms/lifeStage') { //via parentMeasurementID
                    $occur_id = @$this->mID_oID[$parentMeasurementID];
                    $this->oID_lifeStage[$occur_id] = $measurementValue;
                }
                elseif($measurementOfTaxon != 'true' && $measurementType == 'http://rs.tdwg.org/dwc/terms/lifeStage') { //via mOfTaxon not 'true'
                    $this->oID_lifeStage[$occurrenceID] = $measurementValue;
                }
                // */
                // /* sex
                if($parentMeasurementID && $measurementType == 'http://rs.tdwg.org/dwc/terms/sex') { //via parentMeasurementID
                    $occur_id = @$this->mID_oID[$parentMeasurementID];
                    $this->oID_sex[$occur_id] = $measurementValue;
                }
                elseif($measurementOfTaxon != 'true' && $measurementType == 'http://rs.tdwg.org/dwc/terms/sex') { //via mOfTaxon not 'true'
                    $this->oID_sex[$occurrenceID] = $measurementValue;
                }
                // */
            }
            // */
            if($what == 'write_task_67_2') { //lifeStage | sex
                // /* lifeStage
                if($parentMeasurementID && $measurementType == 'http://rs.tdwg.org/dwc/terms/lifeStage') continue; //via parentMeasurementID
                elseif($measurementOfTaxon != 'true' && $measurementType == 'http://rs.tdwg.org/dwc/terms/lifeStage') continue; //via mOfTaxon not 'true'
                // */
                // /* sex
                if($parentMeasurementID && $measurementType == 'http://rs.tdwg.org/dwc/terms/sex') continue; //via parentMeasurementID
                elseif($measurementOfTaxon != 'true' && $measurementType == 'http://rs.tdwg.org/dwc/terms/sex') continue; //via mOfTaxon not 'true'
                // */
                self::write_MoF_rec($rec);
            }
            
            if($what == 'write_task_45_1') {
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
            if($what == 'write_task_67_1') {
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
                    if($measurementRemarks) $tmp = $measurementRemarks.". ".$occurrenceRemarks.".";
                    else                    $tmp = $occurrenceRemarks;
                    $tmp = Functions::remove_whitespace($tmp);
                    $tmp = trim(str_replace("..", ".", $tmp));
                    $rec['http://rs.tdwg.org/dwc/terms/measurementRemarks'] = $tmp;
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
        // /* Per Jen: https://eol-jira.bibalex.org/browse/DATA-1863?focusedCommentId=65399&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-65399
        // - MeasurementOfTaxon should be blank for child records.
        // - MeasurementOfTaxon should be 'false' if to represent additional metadata.
        /*
        if($rec['http://eol.org/schema/measurementOfTaxon'] == '') {
            if(@$rec['http://eol.org/schema/parentMeasurementID']) {} //means a child record
            else $rec['http://eol.org/schema/measurementOfTaxon'] = 'false'; --- wrong directive, thus commented
        }
        */
        if(@$rec['http://eol.org/schema/parentMeasurementID']) $rec['http://eol.org/schema/measurementOfTaxon'] = ''; //means a child record
        // */
        
        $m = new \eol_schema\MeasurementOrFact_specific();
        $uris = array_keys($rec);
        foreach($uris as $uri) {
            $field = pathinfo($uri, PATHINFO_BASENAME);
            $m->$field = $rec[$uri];
        }

        // /* add measurementID if missing --- New Jan 14, 2021
        if(!isset($m->measurementID)) {
            $m->measurementID = Functions::generate_measurementID($m, $this->resource_id); //3rd param is optional. If blank then it will consider all properties of the extension
        }
        // */

        // /* New Jan 14, 2021 --- measurementValue should not be blank
        if(!$m->measurementValue) return $m;
        // */
        
        /* 1st client is WoRMS: https://eol-jira.bibalex.org/browse/DATA-1827?focusedCommentId=66426&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-66426
        if(in_array($this->resource_id, array("26_meta_recoded_1", "26_meta_recoded"))) {
            $m->measurementID = Functions::generate_measurementID($m, $this->resource_id);
        }
        */

        if(!isset($this->measurementIDs[$m->measurementID])) {
            $this->measurementIDs[$m->measurementID] = '';
            $this->archive_builder->write_object_to_file($m);
        }
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
            
            // /* manual correction of not-needed value. e.g. 'Unit' for measurementUnit.
            if($mUnit = @$rec['http://rs.tdwg.org/dwc/terms/measurementUnit']) {
                if($mUnit == 'Unit') $rec['http://rs.tdwg.org/dwc/terms/measurementUnit'] = '';
            }
            // */
            
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
            elseif($what == 'write_task_67_1' || $what == 'write_task_67_2') {
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
            elseif($what == 'task_occurrence2MoF') { //exit("\n111\n");
                /*OCCURRENCES
                Recode as MoF records with MeasurementOfTaxon=false:
                */
                $fields = array('http://rs.tdwg.org/dwc/terms/basisOfRecord', 'http://rs.tdwg.org/dwc/terms/catalogNumber',
                                'http://rs.tdwg.org/dwc/terms/collectionCode', 'http://rs.tdwg.org/dwc/terms/countryCode',
                                'http://rs.tdwg.org/dwc/terms/institutionCode');
                // print_r($rec); exit;
                foreach($fields as $fld) {
                    if(isset($rec[$fld])) {
                        // print_r(pathinfo($fld)); exit("\n222\n");
                        $fld_name = "oID_".pathinfo($fld, PATHINFO_BASENAME); //exit("\n$fld_name\n");
                        if($val = @$rec[$fld]) {
                            $this->{$fld_name}[$occurrenceID] = $val;
                            // print_r($this->$fld_name);
                        }
                        unset($rec[$fld]);
                    }
                }
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
    private function write_occurrence2MoF()
    {   $fields = array('http://rs.tdwg.org/dwc/terms/basisOfRecord', 'http://rs.tdwg.org/dwc/terms/catalogNumber',
                        'http://rs.tdwg.org/dwc/terms/collectionCode', 'http://rs.tdwg.org/dwc/terms/countryCode',
                        'http://rs.tdwg.org/dwc/terms/institutionCode');
        foreach($fields as $fld) {
            $fld_name = "oID_".pathinfo($fld, PATHINFO_BASENAME); //e.g. 'oID_catalogNumber'
            // echo "\n$fld_name\n"; print_r($this->{$fld_name});
            if(!isset($this->{$fld_name})) continue;
            foreach($this->{$fld_name} as $oID => $val) {
                $m2 = new \eol_schema\MeasurementOrFact();
                $rek = array();
                /* old --- I can't believe I'm still using measurementOfTaxon false here...
                $rek['http://rs.tdwg.org/dwc/terms/measurementID'] = md5("$oID|$fld|$val");
                $rek['http://rs.tdwg.org/dwc/terms/occurrenceID'] = $oID;
                $rek['http://rs.tdwg.org/dwc/terms/measurementType'] = 'http://rs.tdwg.org/dwc/terms/'.pathinfo($fld, PATHINFO_BASENAME);
                $rek['http://rs.tdwg.org/dwc/terms/measurementValue'] = $val;
                $rek['http://eol.org/schema/measurementOfTaxon'] = 'false'; --- fix this, investigate first though...
                */
                
                // /* new --- no more measurementOfTaxon == 'false'
                $rek['http://rs.tdwg.org/dwc/terms/measurementID'] = md5("$oID|$fld|$val");
                $rek['http://rs.tdwg.org/dwc/terms/measurementType'] = 'http://rs.tdwg.org/dwc/terms/'.pathinfo($fld, PATHINFO_BASENAME);
                $rek['http://rs.tdwg.org/dwc/terms/measurementValue'] = $val;
                if($parentMeasurementID = @$this->oID_with_True_mOfTaxon_mID[$oID]) {
                    $rek['http://eol.org/schema/parentMeasurementID'] = $parentMeasurementID;
                }
                else {
                    print_r($rek);
                    exit("\nCannot proceed, parentMeasurementID not found.\n");
                }
                // */
                
                $uris = array_keys($rek);
                foreach($uris as $uri) {
                    $field = pathinfo($uri, PATHINFO_BASENAME);
                    $m2->$field = $rek[$uri];
                }
                $this->archive_builder->write_object_to_file($m2);
            }
        }
    }
    private function write_occurrence($rec)
    {
        self::log_unmapped_string_if_needed($rec, 'occurrence');
        
        $uris = array_keys($rec);
        $o = new \eol_schema\Occurrence_specific();
        foreach($uris as $uri) {
            $field = pathinfo($uri, PATHINFO_BASENAME);
            $o->$field = $rec[$uri];
        }
        $this->archive_builder->write_object_to_file($o);
    }
    private function log_unmapped_string_if_needed($rec, $what)
    {
        if($what == 'occurrence') {
            if($val = @$rec['http://rs.tdwg.org/dwc/terms/lifeStage']) {
                if(substr($val,0,4) != 'http') $this->debug['no uri']['lifeStage'][$val] = '';
            }
            if($val = @$rec['http://rs.tdwg.org/dwc/terms/sex']) {
                if(substr($val,0,4) != 'http') $this->debug['no uri']['sex'][$val] = '';
            }
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
    private function process_document($meta, $what)
    {   //print_r($meta);
        echo "\nprocess_document...[$what]\n"; $i = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % 100000) == 0) echo "\n".number_format($i);
            if($meta->ignore_header_lines && $i == 1) continue;
            if(!$row) continue;
            // $row = Functions::conv_to_utf8($row); //possibly to fix special chars. but from copied template
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            
            /*
            echo "\n".count($meta->fields)." -- ".count($tmp)."\n";
            if(count($meta->fields) != count($tmp)) {
                print_r($meta->fields);
                print_r($tmp);
                exit;
            }
            */

            if(count($meta->fields) != count($tmp)) continue; //maybe harsh but needed
            
            foreach($meta->fields as $field) {
                $term = $field['term'];
                if(!$term) continue;
                if(stripos($term, "#") !== false) $term = self::get_proper_field($term); //string is found
                $rec[$term] = $tmp[$k];
                $k++;
            }
            $rec = array_map('trim', $rec);
            // print_r($rec); exit("\ndebug...\n");
            /*Array(
                [http://purl.org/dc/terms/identifier] => 0f2e3e3b8fd91fcb3f542f8760fd4172
                [http://rs.tdwg.org/dwc/terms/taxonID] => 474
                [http://purl.org/dc/terms/type] => http://purl.org/dc/dcmitype/Text
                [http://purl.org/dc/terms/format] => text/html
                [http://iptc.org/std/Iptc4xmpExt/1.0/xmlns/CVterm] => http://rs.tdwg.org/ontology/voc/SPMInfoItems#Distribution
                [http://purl.org/dc/terms/description] => <b>Distribution</b>:   Neotropical.
                [http://rs.tdwg.org/ac/terms/furtherInformationURL] => http://takiya.speciesfile.org/taxahelp.asp?hc=474&key=Proconia&lng=En
                [http://purl.org/dc/terms/language] => En
                [http://ns.adobe.com/xap/1.0/rights/UsageTerms] => http://creativecommons.org/licenses/by/3.0/
                [http://ns.adobe.com/xap/1.0/rights/Owner] => D. M. Takiya & D. Dmitriev
                [http://purl.org/dc/terms/creator] => D. M. Takiya & D. Dmitriev
            )*/
            
            /* ---------- START customization ---------- */
            if($this->resource_id == '168_meta_recoded') { //per Jen: https://eol-jira.bibalex.org/browse/DATA-1878?focusedCommentId=65579&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-65579
                $identifier = $rec['http://purl.org/dc/terms/identifier'];
                if(strlen($identifier) > 32) $rec['http://purl.org/dc/terms/identifier'] = md5($identifier);
            }
            /* ---------- END customization ---------- */
            
            if($what == 'move_CCP_to_Agents') {
                $agent_ids = self::add_agents($rec);
                if(isset($rec['http://purl.org/dc/terms/contributor'])) unset($rec['http://purl.org/dc/terms/contributor']);
                if(isset($rec['http://purl.org/dc/terms/creator'])) unset($rec['http://purl.org/dc/terms/creator']);
                if(isset($rec['http://purl.org/dc/terms/publisher'])) unset($rec['http://purl.org/dc/terms/publisher']);
                if(isset($rec['http://eol.org/schema/media/thumbnailURL'])) unset($rec['http://eol.org/schema/media/thumbnailURL']);
                $uris = array_keys($rec);
                
                // print_r($uris);
                // print_r(pathinfo("http://www.w3.org/2003/01/geo/wgs84_pos#lat"));
                // exit;
                
                $o = new \eol_schema\MediaResource();
                foreach($uris as $uri) {
                    $field = pathinfo($uri, PATHINFO_BASENAME);
                    if(stripos($field, "#") !== false) $field = self::get_proper_field($field); //string is found
                    $o->$field = $rec[$uri];
                }
                if($agent_ids) $o->agentID = implode("; ", $agent_ids);
                // /* customized
                if($this->resource_id == '678_meta_recoded') { //no license
                    if(!$o->UsageTerms) $o->UsageTerms = 'http://creativecommons.org/licenses/by-nc-sa/3.0/';
                }
                if($this->resource_id == 'ECSEML_meta_recoded') { //with duplicate identifier
                    if(!isset($this->object_ids[$o->identifier])) {
                        $this->object_ids[$o->identifier] = '';
                        $this->archive_builder->write_object_to_file($o);
                    }
                    else {
                        $o->identifier = md5($o->accessURI);
                        if(!isset($this->object_ids[$o->identifier])) {
                            $this->object_ids[$o->identifier] = '';
                            $this->archive_builder->write_object_to_file($o);
                        }
                        else exit("\nDuplicate accessURI\n");
                    }
                    continue;
                }
                // */
                $this->archive_builder->write_object_to_file($o);
            }
        } //end foreach()
    }
    private function get_proper_field($field)
    {   /* e.g. with "#"
        [23] => http://www.w3.org/2003/01/geo/wgs84_pos#lat
        [24] => http://www.w3.org/2003/01/geo/wgs84_pos#long
        [25] => http://www.w3.org/2003/01/geo/wgs84_pos#alt
        */
        $arr = explode('#', $field);
        return trim($arr[1]);
    }
    private function add_agents($rec)
    {   
        $flds = array('http://purl.org/dc/terms/contributor', 'http://purl.org/dc/terms/creator', 'http://purl.org/dc/terms/publisher');
        $agent_ids = array();
        foreach($flds as $index) {
            if($name = @$rec[$index]) {
                if(!$name) continue;
                $r = new \eol_schema\Agent();
                $r->term_name       = $name;
                $r->agentRole       = pathinfo($index, PATHINFO_BASENAME);
                $r->identifier      = md5("$r->term_name|$r->agentRole");
                // $r->term_homepage   = '';
                $agent_ids[] = $r->identifier;
                if(!isset($this->agent_ids[$r->identifier])) {
                   $this->agent_ids[$r->identifier] = '';
                   $this->archive_builder->write_object_to_file($r);
                }
            }
        }
        return $agent_ids;
    }
    private function process_taxon($meta, $what)
    {   //print_r($meta);
        echo "\nprocess_measurementorfact...[$what]\n"; $i = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % 100000) == 0) echo "\n".number_format($i);
            if($meta->ignore_header_lines && $i == 1) continue;
            if(!$row) continue;
            // $row = Functions::conv_to_utf8($row); //possibly to fix special chars. but from copied template
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            // print_r($meta->fields);
            foreach($meta->fields as $field) {
                if(!$field['term']) continue;
                $rec[$field['term']] = $tmp[$k]; //put "@" as @$tmp[$k] during development
                $k++;
            } //print_r($rec); exit;
            $rec = array_map('trim', $rec);
            if($what == 'carry_over') {
                // /* start write DwCA
                
                /* ---------- START customization ---------- */
                if($this->resource_id == '168_meta_recoded') { //DATA-1878
                    unset($rec['http://rs.tdwg.org/dwc/terms/acceptedNameUsage']);
                    unset($rec['http://rs.tdwg.org/dwc/terms/infraspecificEpithet']);
                }
                /* ---------- END customization ---------- */

                $uris = array_keys($rec);
                $o = new \eol_schema\Taxon();
                foreach($uris as $uri) {
                    $field = pathinfo($uri, PATHINFO_BASENAME);
                    $o->$field = $rec[$uri];
                }
                $this->archive_builder->write_object_to_file($o);
                // */
            }
        }
    }
}
?>