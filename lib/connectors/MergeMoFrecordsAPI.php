<?php
namespace php_active_record;
/* connector: [called from DwCA_Utility.php, which is called from 368_merge_two_MoF_into_one.php for DATA-1831]
Task here: https://eol-jira.bibalex.org/browse/DATA-1831?focusedCommentId=65098&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-65098
*/
class MergeMoFrecordsAPI
{
    function __construct($archive_builder, $resource_id)
    {
        $this->resource_id = $resource_id;
        $this->archive_builder = $archive_builder;
        // $this->download_options = array('cache' => 1, 'resource_id' => $resource_id, 'expire_seconds' => 60*60*24*30*4, 'download_wait_time' => 1000000, 'timeout' => 10800, 'download_attempts' => 1, 'delay_in_minutes' => 1);
        
        if($resource_id == '368_merged_MoF') {
            /* For any given occurrence, if there are (at least) two records for measurementType=http://www.wikidata.org/entity/Q1053008,
            with measurementValues https://www.wikidata.org/entity/Q59099 AND 
                                   http://www.wikidata.org/entity/Q81875
            (herbivore and carnivore)
            please replace them with a single record. */
            $this->sought['mtype'] = "http://www.wikidata.org/entity/Q1053008"; //trophic level
            $this->sought['mvalues'] = array("https://www.wikidata.org/entity/Q59099", "http://www.wikidata.org/entity/Q81875"); //herbivore and carnivore
            $this->sought['merged_value'] = "https://www.wikidata.org/entity/Q164509"; //omnivore
        }
        if($resource_id == 'another resource') {
        }
    }
    function start($info)
    {   
        print_r($this->sought);
        // require_library('connectors/TraitGeneric');
        // $this->func = new TraitGeneric($this->resource_id, $this->archive_builder);
        /* START DATA-1841 terms remapping */
        // $this->func->initialize_terms_remapping(60*60*24); //param is $expire_seconds. 0 means expire now.
        /* END DATA-1841 terms remapping */
        
        $tables = $info['harvester']->tables;
        self::process_measurementorfact($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0], 'search cases in MoF');
        // print_r($this->cases); exit;
        self::parse_cases(); //exit;
        self::process_measurementorfact($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0], 'gen mIDs info list');
        // print_r($this->measurementIDs);
        echo "\nmeasurementIDs: ".count($this->measurementIDs)."\n";
        self::process_measurementorfact($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0], 'write MoF');
        self::add_merged_MoF_records();
        
        // self::process_occurrence($tables['http://rs.tdwg.org/dwc/terms/occurrence'][0]); //this is 
        
        // exit("\n-end here for now-\n");
    }
    private function initialize_mapping()
    {   $mappings = Functions::get_eol_defined_uris(false, true);     //1st param: false means will use 1day cache | 2nd param: opposite direction is true
        echo "\n".count($mappings). " - default URIs from EOL registry.";
        $this->uris = Functions::additional_mappings($mappings); //add more mappings used in the past
        // self::use_mapping_from_jen();
        // print_r($this->uris);
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
            // if($i == 173) print_r($rec); //exit;
            /*Array(
                [http://rs.tdwg.org/dwc/terms/measurementID] => 6502dc891e5f0d73f5c918128eaf59b7_368
                [http://rs.tdwg.org/dwc/terms/occurrenceID] => 0c47b620c4623f4e023b413f975e3a1b_368
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
            //===========================================================================================================================================================
            if($task == 'search cases in MoF') {
                /* For any given occurrence, if there are (at least) two records for measurementType=http://www.wikidata.org/entity/Q1053008,
                with measurementValues https://www.wikidata.org/entity/Q59099 AND http://www.wikidata.org/entity/Q81875*/
                if($measurementType == (string) $this->sought['mtype'] && in_array($measurementValue, $this->sought['mvalues'])) {
                    $this->cases[$occurrenceID][$measurementID] = '';
                }
            }
            //===========================================================================================================================================================
            elseif($task == 'gen mIDs info list') {
                if(isset($this->measurementIDs[$measurementID])) $this->measurementIDs[$measurementID] = $rec;
            }
            //===========================================================================================================================================================
            elseif($task == 'write MoF') {
                if(isset($this->measurementIDs[$measurementID])) continue;
                else { //write bulk of what remains
                    $o = new \eol_schema\MeasurementOrFact_specific();
                    $uris = array_keys($rec);
                    foreach($uris as $uri) {
                        $field = pathinfo($uri, PATHINFO_BASENAME);
                        $o->$field = $rec[$uri];
                    }
                    /* START DATA-1841 terms remapping */
                    // $o = $this->func->given_m_update_mType_mValue($o);
                    // echo "\nLocal: ".count($this->func->remapped_terms)."\n"; //just testing
                    /* END DATA-1841 terms remapping */

                    $this->archive_builder->write_object_to_file($o);
                    // if($i >= 10) break; //debug only
                }
            }
            //===========================================================================================================================================================
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
            // print_r($rec); exit("\ndebug...\n");
            /*Array(
                [http://rs.tdwg.org/dwc/terms/occurrenceID] => 0c47b620c4623f4e023b413f975e3a1b_368
                [http://rs.tdwg.org/dwc/terms/taxonID] => 1
                [http://rs.tdwg.org/dwc/terms/lifeStage] => 
            )*/
            //===========================================================================================================================================================
            $occurrenceID = $rec['http://rs.tdwg.org/dwc/terms/occurrenceID'];
            if(isset($this->delete_occurrence_id[$occurrenceID])) continue;
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
    private function parse_cases()
    {   //$this->cases[$occurrenceID][$measurementID]
        $i = 0;
        $measurementIDs = array();
        foreach($this->cases as $occurID => $mIDs) {
            if(count($mIDs) > 1) {
                $i++;
                // echo "\n$i. [$occurID]"; print_r($mIDs); //good debug
                $measurementIDs = array_merge($measurementIDs, $mIDs);
            }
        }
        /* 265. [724131aca0ce1711f148c60a5fcb44d5_368]Array
        (
            [b50131f97a1d491621866ff9c019d8ea_368] => 
            [bcb2bc3e159d4f8a2b65590d8fe635e4_368] => 
        )
        266. [d93391c4a534a691693ec00f615be56c_368]Array
        (
            [8444ad47e96cb08954a7ae6089354c1c_368] => 
            [e74ac13b1f907efb7ab7fc904ff845a3_368] => 
        )
        */
        // print_r($measurementIDs); //good debug
        echo "\ntotal cases: [$i]";
        echo "\ntotal measurementIDs: [".count($measurementIDs)."]\n";
        $this->measurementIDs = $measurementIDs;
    }
    private function add_merged_MoF_records()
    {
        $i = 0;
        foreach($this->cases as $occurID => $mIDs) {
            if(count($mIDs) > 1) {
                $i++;
                // echo "\n$i. [$occurID]"; print_r($mIDs); //good debug
                $IDs = array_keys($mIDs);
                $combined_remarks = self::get_combined_remarks($IDs);
                
                if($rec = $this->measurementIDs[$IDs[0]]) { //first of two mIDs. You can get any of the two.
                    /* [e74ac13b1f907efb7ab7fc904ff845a3_368] => Array
                            (
                                [http://rs.tdwg.org/dwc/terms/measurementID] => e74ac13b1f907efb7ab7fc904ff845a3_368
                                [http://rs.tdwg.org/dwc/terms/occurrenceID] => d93391c4a534a691693ec00f615be56c_368
                                [http://eol.org/schema/measurementOfTaxon] => true
                                [http://rs.tdwg.org/dwc/terms/measurementType] => http://www.wikidata.org/entity/Q1053008
                                [http://rs.tdwg.org/dwc/terms/measurementUnit] => 
                                [http://eol.org/schema/terms/statisticalMethod] => 
                                [http://purl.org/dc/terms/source] => https://paleobiodb.org/classic/checkTaxonInfo?is_real_user=1&taxon_no=424146
                                [http://purl.org/dc/terms/bibliographicCitation] => The Paleobiology Database, https://paleobiodb.org
                                Only these 2 will have a new value:
                                [http://rs.tdwg.org/dwc/terms/measurementValue] => http://www.wikidata.org/entity/Q81875
                                [http://rs.tdwg.org/dwc/terms/measurementRemarks] => Source value: carnivore - eats living or dead animals, either by predation or scavenging. Inferred from Ursidae.
                            )
                    */
                    //start writing:
                    $o = new \eol_schema\MeasurementOrFact_specific();
                    $uris = array_keys($rec);
                    foreach($uris as $uri) {
                        $field = pathinfo($uri, PATHINFO_BASENAME);
                        $o->$field = $rec[$uri];
                    }
                    $o->measurementRemarks = $combined_remarks;
                    $o->measurementValue = $this->sought['merged_value'];
                    $this->archive_builder->write_object_to_file($o);
                }
                else exit("\nUndefined mID B: [$mID]\n");
            }
        }
        /* 265. [724131aca0ce1711f148c60a5fcb44d5_368]Array
        (
            [b50131f97a1d491621866ff9c019d8ea_368] => 
            [bcb2bc3e159d4f8a2b65590d8fe635e4_368] => 
        )
        266. [d93391c4a534a691693ec00f615be56c_368]Array
        (
            [8444ad47e96cb08954a7ae6089354c1c_368] => 
            [e74ac13b1f907efb7ab7fc904ff845a3_368] => 
        )
        */
        echo "\ntotal cases: [$i]";
    }
    private function get_combined_remarks($mIDs)
    {
        $final = '';
        foreach($mIDs as $mID) {
            if($rec = $this->measurementIDs[$mID]) {
                $final .= " ".$rec['http://rs.tdwg.org/dwc/terms/measurementRemarks'];
            }
            else exit("\nUndefined mID A: [$mID]\n");
        }
        return trim($final);
    }
}
?>