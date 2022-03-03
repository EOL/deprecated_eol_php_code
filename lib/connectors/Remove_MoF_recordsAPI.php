<?php
namespace php_active_record;
/* connector: [called from DwCA_Utility.php, which is called from pbdb_more_adjustments.php for DATA-1814]
Tasks here:
https://eol-jira.bibalex.org/browse/DATA-1814?focusedCommentId=66687&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-66687
https://eol-jira.bibalex.org/browse/DATA-1814?focusedCommentId=66696&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-66696
https://eol-jira.bibalex.org/browse/DATA-1814?focusedCommentId=66697&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-66697
*/
class Remove_MoF_recordsAPI
{
    function __construct($archive_builder, $resource_id)
    {
        $this->resource_id = $resource_id;
        $this->archive_builder = $archive_builder;
        $this->download_options = array('cache' => 1, 'resource_id' => $resource_id, 'expire_seconds' => 60*60*24*30*1, 'download_wait_time' => 1000000, 'timeout' => 10800, 'download_attempts' => 1, 'delay_in_minutes' => 1);
        if($resource_id == '368_cleaned_MoF') { //PaleoDB aka PBDB
            $this->to_be_removed = "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/PaleoDB/PBDB_inferred_records_to_remove.tsv";
        }
        if($resource_id == 'another resource') {
        }
    }
    function start($info)
    {
        $this->to_remove = self::get_to_be_removed_list();
        $tables = $info['harvester']->tables;
        self::process_measurementorfact($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0], 'write MoF');
        self::process_occurrence($tables['http://rs.tdwg.org/dwc/terms/occurrence'][0], 'write Occurrence');
        // exit("\n-end here for now-\n");
    }
    private function initialize_mapping()
    {   $mappings = Functions::get_eol_defined_uris(false, true);     //1st param: false means will use 1day cache | 2nd param: opposite direction is true
        echo "\n".count($mappings). " - default URIs from EOL registry.";
        $this->uris = Functions::additional_mappings($mappings); //add more mappings used in the past
        // self::use_mapping_from_jen();
        // print_r($this->uris);
    }
    
    private function get_to_be_removed_list() //for PBDB
    {
        $tmp_file = Functions::save_remote_file_to_local($this->to_be_removed, $this->download_options); $i = 0;
        foreach(new FileIterator($tmp_file) as $line => $row) { $i++;
            $row = Functions::conv_to_utf8($row);
            if($i == 1) $fields = explode("\t", $row);
            else {
                if(!$row) continue;
                $tmp = explode("\t", $row);
                $rec = array(); $k = 0;
                foreach($fields as $field) {
                    $rec[$field] = $tmp[$k];
                    $k++;
                }
                $rec = array_map('trim', $rec);
                // print_r($rec); exit;
                /*Array(
                    [attribute] => http://purl.obolibrary.org/obo/RO_0002303
                    [value] => http://purl.obolibrary.org/obo/ENVO_00000447
                    [Inferred from] => Inferred from Vertebrata
                )*/
                $inferred_from = Functions::remove_whitespace(str_ireplace(array("Inferred from", "."), "", trim($rec['Inferred from'])));
                $str = $rec['attribute']." | ".$rec['value']." | ".$inferred_from;
                $final[$str] = '';
            }
        }
        unlink($tmp_file); //print_r($final); exit;
        return $final;
    }
    private function process_measurementorfact($meta, $task)
    {   //print_r($meta);
        echo "\nprocess_measurementorfact...$task\n"; $i = 0;
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
                [http://rs.tdwg.org/dwc/terms/measurementID] => 2a7071241876030d430ca5e2a48fbd36_368
                [http://rs.tdwg.org/dwc/terms/occurrenceID] => 73e24fc3724cf0b60ecdbf2c4eeed717_368
                [http://eol.org/schema/measurementOfTaxon] => true
                [http://rs.tdwg.org/dwc/terms/measurementType] => http://eol.org/schema/terms/ExtinctionStatus
                [http://rs.tdwg.org/dwc/terms/measurementValue] => http://eol.org/schema/terms/extant
                [http://rs.tdwg.org/dwc/terms/measurementUnit] => 
                [http://eol.org/schema/terms/statisticalMethod] => 
                [http://rs.tdwg.org/dwc/terms/measurementRemarks] => 
                [http://purl.org/dc/terms/source] => https://paleobiodb.org/classic/checkTaxonInfo?is_real_user=1&taxon_no=1
                [http://purl.org/dc/terms/bibliographicCitation] => The Paleobiology Database, https://paleobiodb.org
            )*/
            $measurementID = $rec['http://rs.tdwg.org/dwc/terms/measurementID'];
            $occurrenceID = $rec['http://rs.tdwg.org/dwc/terms/occurrenceID'];
            $measurementType = $rec['http://rs.tdwg.org/dwc/terms/measurementType'];
            $measurementValue = $rec['http://rs.tdwg.org/dwc/terms/measurementValue'];
            $measurementRemarks = $rec['http://rs.tdwg.org/dwc/terms/measurementRemarks'];
            //===========================================================================================================================================================
            if($task == 'write MoF') {
                $inferred_from = Functions::remove_whitespace(str_ireplace(array("Inferred from", "."), "", trim($measurementRemarks)));
                $str = $measurementType." | ".$measurementValue." | ".$inferred_from;
                if(isset($this->to_remove[$str])) {
                    // $this->delete_occurrence_id[$occurrenceID] = ''; --- wrong implementation; use $this->existing_occurrence_id instead
                    continue;
                }
                else { //write
                    $o = new \eol_schema\MeasurementOrFact_specific();
                    $uris = array_keys($rec);
                    foreach($uris as $uri) {
                        $field = pathinfo($uri, PATHINFO_BASENAME);
                        $o->$field = $rec[$uri];
                    }
                    $this->archive_builder->write_object_to_file($o);
                    $this->existing_occurrence_id[$occurrenceID] = '';
                    // if($i >= 10) break; //debug only
                }
                
                /* another adjustment --- this was moved to PaleoDBAPI_v2.php
                And here's a second mapping, for a different process:
                If "Inferred from" appears in measurementRemarks, regardless of the rest of the text, 
                AND the measurementValue is one of those below, 
                populate lifestage of the corresponding occurrence record with http://www.ebi.ac.uk/efo/EFO_0001272
                
                $mValues = array("http://www.wikidata.org/entity/Q1759860", "http://www.marinespecies.org/traits/Infaunal", "http://www.marinespecies.org/traits/Epifaunal", "http://eol.org/schema/terms/semiInfaunal", "http://eol.org/schema/terms/Attached", "http://www.wikidata.org/entity/Q640114", "http://eol.org/schema/terms/intermediateEpifaunal", "http://eol.org/schema/terms/lowEpifaunal", "http://eol.org/schema/terms/upperEpifaunal", "http://eol.org/schema/terms/shallowInfaunal");
                if(in_array($measurementValue, $mValues)) {
                    if(stripos($measurementRemarks, "Inferred from") !== false) $this->occurrence_id_lifeStage[$occurrenceID] = "http://www.ebi.ac.uk/efo/EFO_0001272";  //string is found
                }
                */
            }
            //===========================================================================================================================================================
        }
    }
    private function process_occurrence($meta, $task)
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
            // print_r($rec); exit("\ndebug...\n");
            /*Array(
                [http://rs.tdwg.org/dwc/terms/occurrenceID] => 73e24fc3724cf0b60ecdbf2c4eeed717_368
                [http://rs.tdwg.org/dwc/terms/taxonID] => 1
                [http://rs.tdwg.org/dwc/terms/lifeStage] => 
            )*/
            //===========================================================================================================================================================
            $occurrenceID = $rec['http://rs.tdwg.org/dwc/terms/occurrenceID'];
            //===========================================================================================================================================================
            
            if($task == "write Occurrence") {
                if(isset($this->existing_occurrence_id[$occurrenceID])) {
                    // /* another adjustment
                    if($val = @$this->occurrence_id_lifeStage[$occurrenceID]) $rec['http://rs.tdwg.org/dwc/terms/lifeStage'] = $val;
                    // */
                    
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
    }
}
?>