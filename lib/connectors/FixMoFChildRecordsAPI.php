<?php
namespace php_active_record;
/* connector: [called from DwCA_Utility.php, which is called from resource_utility.php] */
class FixMoFChildRecordsAPI
{
    function __construct($archive_builder, $resource_id)
    {
        $this->resource_id = $resource_id;
        $this->archive_builder = $archive_builder;
    }
    /*================================================================= STARTS HERE ======================================================================*/
    function start($info)
    {   /* from copied template - was never used here
        require_library('connectors/TraitGeneric'); 
        $this->func = new TraitGeneric($this->resource_id, $this->archive_builder);
        // START DATA-1841 terms remapping
        $this->func->initialize_terms_remapping(60*60*24); //param is $expire_seconds. 0 means expire now.
        // END DATA-1841 terms remapping
        */
        
        $tables = $info['harvester']->tables;
        // if(in_array($this->resource_id, array('some resource id'))) {
        if(true) {
            self::fix_child_records_if_needed($tables);
        }
        
        if(isset($this->debug)) print_r($this->debug);
    }
    private function fix_child_records_if_needed($tables)
    {
        self::process_measurementorfact($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0], 'build_info_list');
            // generates $this->oID_with_True_mOfTaxon_mID[$occurrenceID] = $measurementID
        self::process_measurementorfact($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0], 'check_all_rows');
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
            // print_r($meta->fields);
            foreach($meta->fields as $field) {
                if(!$field['term']) continue;
                $rec[$field['term']] = $tmp[$k]; //put "@" as @$tmp[$k] during development
                $k++;
            } //print_r($rec); exit;
            $rec = array_map('trim', $rec);
            /*Array(
                [http://rs.tdwg.org/dwc/terms/measurementID] => M1
                [http://rs.tdwg.org/dwc/terms/occurrenceID] => O1
                [http://eol.org/schema/measurementOfTaxon] => true
                [http://eol.org/schema/associationID] => 
                [http://eol.org/schema/parentMeasurementID] => 
                [http://rs.tdwg.org/dwc/terms/measurementType] => http://purl.obolibrary.org/obo/FLOPO_0900032
                [http://rs.tdwg.org/dwc/terms/measurementValue] => http://purl.obolibrary.org/obo/FLOPO_0900033
                [http://rs.tdwg.org/dwc/terms/measurementUnit] => 
                [http://rs.tdwg.org/dwc/terms/measurementAccuracy] => 
                [http://eol.org/schema/terms/statisticalMethod] => 
                [http://rs.tdwg.org/dwc/terms/measurementDeterminedDate] => 
                [http://rs.tdwg.org/dwc/terms/measurementDeterminedBy] => 
                [http://rs.tdwg.org/dwc/terms/measurementMethod] => 
                [http://rs.tdwg.org/dwc/terms/measurementRemarks] => Source term: Arborescente.
                [http://purl.org/dc/terms/source] => http://collections.mnh.si.edu/search/botany/?irn=10176375
                [http://purl.org/dc/terms/bibliographicCitation] => Smithsonian Institution, National Museum of Narutal History, Department of Botany. Data for specimen 3281978. http://collections.mnh.si.edu/search/botany/
                [http://purl.org/dc/terms/contributor] => 
                [http://eol.org/schema/reference/referenceID] => 
            )
            Array(
                [http://rs.tdwg.org/dwc/terms/measurementID] => 9f6f9646366ed321e182227cc96ecdcf
                [http://rs.tdwg.org/dwc/terms/occurrenceID] => O5026
                [http://eol.org/schema/measurementOfTaxon] => false
                [http://eol.org/schema/associationID] => 
                [http://eol.org/schema/parentMeasurementID] => 
                [http://rs.tdwg.org/dwc/terms/measurementType] => http://rs.tdwg.org/dwc/terms/catalogNumber
                [http://rs.tdwg.org/dwc/terms/measurementValue] => 1958370
                [http://rs.tdwg.org/dwc/terms/measurementUnit] => 
                [http://rs.tdwg.org/dwc/terms/measurementAccuracy] => 
                [http://eol.org/schema/terms/statisticalMethod] => 
                [http://rs.tdwg.org/dwc/terms/measurementDeterminedDate] => 
                [http://rs.tdwg.org/dwc/terms/measurementDeterminedBy] => 
                [http://rs.tdwg.org/dwc/terms/measurementMethod] => 
                [http://rs.tdwg.org/dwc/terms/measurementRemarks] => 
                [http://purl.org/dc/terms/source] => 
                [http://purl.org/dc/terms/bibliographicCitation] => 
                [http://purl.org/dc/terms/contributor] => 
                [http://eol.org/schema/reference/referenceID] => 
            ) */
            $measurementID = @$rec['http://rs.tdwg.org/dwc/terms/measurementID'];
            $occurrenceID = $rec['http://rs.tdwg.org/dwc/terms/occurrenceID'];
            $measurementOfTaxon = $rec['http://eol.org/schema/measurementOfTaxon'];
            $measurementType = $rec['http://rs.tdwg.org/dwc/terms/measurementType'];
            $measurementValue = $rec['http://rs.tdwg.org/dwc/terms/measurementValue'];
            $parentMeasurementID = @$rec['http://eol.org/schema/parentMeasurementID'];
            
            //===========================================================================================================================================================
            if($what == 'build_info_list') {
                if($measurementOfTaxon == 'true') {
                    $this->oID_with_True_mOfTaxon_mID[$occurrenceID] = $measurementID;
                }
                continue;
            }
            //===========================================================================================================================================================
            if($what == 'check_all_rows') {
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
                if($measurementOfTaxon == 'true' && $parentMeasurementID != ''){
                    print_r($rec); exit("\nInvestigate rec: mOfTaxon true, parentID not blank\n");
                }
                
                if($measurementOfTaxon != 'true') { // criteria to fix child record
                    if($parentMeasurementID = @$this->oID_with_True_mOfTaxon_mID[$occurrenceID]) {
                        $m2 = new \eol_schema\MeasurementOrFact_specific();
                        $rek = array();
                        $rek['http://rs.tdwg.org/dwc/terms/measurementID'] = md5("$measurementType|$measurementValue|$measurementID|$parentMeasurementID");
                        $rek['http://rs.tdwg.org/dwc/terms/measurementType'] = $measurementType;
                        $rek['http://rs.tdwg.org/dwc/terms/measurementValue'] = $measurementValue;
                        $rek['http://eol.org/schema/parentMeasurementID'] = $parentMeasurementID;
                        $uris = array_keys($rek);
                        foreach($uris as $uri) {
                            $field = pathinfo($uri, PATHINFO_BASENAME);
                            $m2->$field = $rek[$uri];
                        }
                        $this->archive_builder->write_object_to_file($m2);
                        @$this->debug['fxMoFchild']++;
                    }
                    else {
                        print_r($rec);
                        exit("\nparentID not found.\n");
                    }
                }
                else $m = self::write_MoF_rec($rec); // rest of the un-changed carry-over MoF records
            }
            //===========================================================================================================================================================
            // if($i >= 10) break; //debug only
        }
    }
    private function write_MoF_rec($rec)
    {
        if(@$rec['http://eol.org/schema/parentMeasurementID']) $rec['http://eol.org/schema/measurementOfTaxon'] = ''; //means a child record
        
        $m = new \eol_schema\MeasurementOrFact_specific();
        $uris = array_keys($rec);
        foreach($uris as $uri) {
            $field = pathinfo($uri, PATHINFO_BASENAME);
            $m->$field = $rec[$uri];
        }

        // /* add measurementID if missing
        if(!isset($m->measurementID)) {
            $m->measurementID = Functions::generate_measurementID($m, $this->resource_id); //3rd param is optional. If blank then it will consider all properties of the extension
        }
        // */

        // /* measurementValue should not be blank
        if(!$m->measurementValue) return $m;
        // */
        
        if(!isset($this->measurementIDs[$m->measurementID])) {
            $this->measurementIDs[$m->measurementID] = '';
            $this->archive_builder->write_object_to_file($m);
        }
        return $m;
    }
    /*================================================================= ENDS HERE ======================================================================*/
}
?>