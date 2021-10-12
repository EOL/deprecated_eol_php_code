<?php
namespace php_active_record;
/* connector: called from DwCA_Utility.php, which is called from filter_term_group_by_taxa.php
from: https://eol-jira.bibalex.org/browse/DATA-1870?focusedCommentId=65425&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-65425
*/
class FilterTermGroupByTaxa
{
    function __construct($archive_builder, $resource_id, $params)
    {
        $this->resource_id = $resource_id;
        $this->archive_builder = $archive_builder;
        $this->params = $params;
        // $this->download_options = array('resource_id' => $resource_id, 'expire_seconds' => 60*60*24*30*3, 'download_wait_time' => 1000000, 'timeout' => 10800, 'download_attempts' => 1, 'delay_in_minutes' => 1);
        $this->report_utility_ON = true; // false means no report utility will be generated. True eats more memory.
        $str = str_replace(', ', "_", $this->params['taxonIDs']);
        $this->report_file = CONTENT_RESOURCE_LOCAL_PATH . "/reports/FTG_" . $this->params['target'] . "_" . $str . ".txt";
    }
    /*================================================================= STARTS HERE ======================================================================*/
    function start($info)
    {
        self::initialize();
        // print_r($this->params); exit("\n100\n");
        /*Array(
            [source] => 617_ENV
            [target] => wikipedia_en_traits_FTG
            [taxonIDs] => Q1390, Q1357, Q10908
        )
        e.g. taxonIDs is insects, spiders, amphibians */
        //----------------------------------------------------------------------------------------------
        $this->children_of_TaxaGroup = array();
        $taxonIDs = explode(',', $this->params['taxonIDs']);
        $taxonIDs = array_map('trim', $taxonIDs);
        // print_r($taxonIDs); exit;
        $children = self::get_children_of_TaxaGroup($taxonIDs); //e.g. $taxonIDs is insects = Q1390 | spiders = Q1357 | amphibians = Q10908
        foreach($children as $child) $this->children_of_TaxaGroup[$child] = '';
        unset($children);
        // print_r($this->children_of_TaxaGroup);
        // echo "\nChildren of IDs: ".count($this->children_of_TaxaGroup)."\n"; exit;
        //----------------------------------------------------------------------------------------------
        
        $tables = $info['harvester']->tables;
        if($this->report_utility_ON) {
            self::process_generic_table_for_TaxaGroup($tables['http://rs.tdwg.org/dwc/terms/taxon'][0], 'taxon');
        }
        self::process_generic_table_for_TaxaGroup($tables['http://rs.tdwg.org/dwc/terms/occurrence'][0], 'occurrence');
        self::process_generic_table_for_TaxaGroup($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0], 'MoF');
        
        // self::process_generic_table($tables['http://rs.tdwg.org/dwc/terms/taxon'][0], 'taxon');
        // self::process_generic_table($tables['http://rs.gbif.org/terms/1.0/vernacularname'][0], 'vernacular');
        self::process_generic_table($tables['http://rs.tdwg.org/dwc/terms/occurrence'][0], 'occurrence');
        self::process_generic_table($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0], 'MoF');
    }
    private function initialize()
    {
        if($this->report_utility_ON) {
            $handle = fopen($this->report_file, "w");
            $fields = array("taxonID", "scientificName", "mType", "mValue", "mRemarks", "descendants_of");
            fwrite($handle, implode("\t", $fields) . "\n");
            fclose($handle);
        }

        require_library('connectors/Functions_Pensoft');
        require_library('connectors/Pensoft2EOLAPI');
        $param['resource_id'] = 'nothing';
        $this->pensoft = new Pensoft2EOLAPI($param);
        $this->descendants_of_saline_water = $this->pensoft->get_descendants_of_habitat_group($this->params['habitat_filter']); //e.g. param "saline water"
        /* e.g. Q1390, Q1357, and Q10908. i.e. no saltwater insects, spiders, or amphibians. */
        // print_r($this->descendants_of_saline_water); exit;("\n");
        /*Array(
            [t.uri] => 
            [http://purl.obolibrary.org/obo/ENVO_00002010] => 
            [http://purl.obolibrary.org/obo/ENVO_00002227] => 
            [http://purl.obolibrary.org/obo/ENVO_01000307] => 
            [http://purl.obolibrary.org/obo/ENVO_00002019] => 
        */
    }
    private function get_children_of_TaxaGroup($taxon_ids)
    {
        require_library('connectors/PaleoDBAPI_v2');
        $func = new PaleoDBAPI_v2("");
        $dwca_file = CONTENT_RESOURCE_LOCAL_PATH . $this->params['source'] . ".tar.gz"; //617_ENV.tar.gz
        $descendant_taxon_ids = $func->get_descendants_given_parent_ids($dwca_file, $taxon_ids);
        return $descendant_taxon_ids;
    }
    private function process_generic_table_for_TaxaGroup($meta, $what)
    {   //print_r($meta);
        echo "\nprocess $what...TaxaGroup\n"; $i = 0;
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
            if($what == 'taxon') { //only when $this->report_utility_ON is true
                $taxonID = $rec['http://rs.tdwg.org/dwc/terms/taxonID'];
                $this->taxonID_info[$taxonID] = $rec['http://rs.tdwg.org/dwc/terms/scientificName'];
            }
            if($what == 'occurrence') {
                $occurrenceID = $rec['http://rs.tdwg.org/dwc/terms/occurrenceID'];
                $taxonID = $rec['http://rs.tdwg.org/dwc/terms/taxonID'];
                if(isset($this->children_of_TaxaGroup[$taxonID])) {
                    $this->occurrence_id_TaxaGroup[$occurrenceID] = '';
                    if($this->report_utility_ON) {
                        $this->occurrenceID_taxonID_info[$occurrenceID] = $taxonID;
                    }
                }
            }
            elseif($what == 'MoF') {
                $occurrenceID = $rec['http://rs.tdwg.org/dwc/terms/occurrenceID'];
                $mValue = $rec['http://rs.tdwg.org/dwc/terms/measurementValue'];
                $mType = $rec['http://rs.tdwg.org/dwc/terms/measurementType'];
                if(isset($this->occurrence_id_TaxaGroup[$occurrenceID])) {
                    /* copied template from RemoveAvesChildrenAPI.php
                    per: https://eol-jira.bibalex.org/browse/DATA-1831?focusedCommentId=64595&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-64595
                    For all descendants of TaxaGroup, (FurtherInformationURL=https://paleobiodb.org/classic/checkTaxonInfo?is_real_user=1&taxon_no=22826)
                    please remove all records with measurementType= http://purl.obolibrary.org/obo/RO_0002303
                    if($rec['http://rs.tdwg.org/dwc/terms/measurementType'] == 'http://purl.obolibrary.org/obo/RO_0002303') {
                        $this->TaxaGroup_remove_occurrence_id[$rec['http://rs.tdwg.org/dwc/terms/occurrenceID']] = '';
                    }
                    */
                    /* per: https://eol-jira.bibalex.org/browse/DATA-1870?focusedCommentId=65425&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-65425
                    I'd like to filter out the "descendants_of_salt_water" terms for taxa that are descendants of these taxon IDs in the wikipedia traits resource:
                    (I may think of more; I'll add them here)
                    Q1390, Q1357, and Q10908
                    i.e. no saltwater insects, spiders, or amphibians.
                    */
                    if($mType == 'http://purl.obolibrary.org/obo/RO_0002303') { //habitat
                        if(isset($this->descendants_of_saline_water[$mValue])) {
                            $this->TaxaGroup_remove_occurrence_id[$occurrenceID] = '';
                            // print_r($rec);
                            if($this->report_utility_ON) self::write_report($rec); //utility only
                        }
                    }
                }
            }
        }
    }
    private function process_generic_table($meta, $what)
    {   //print_r($meta);
        echo "\nprocess $what...\n"; $i = 0;
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
            $occurrenceID = $rec['http://rs.tdwg.org/dwc/terms/occurrenceID'];
            
            /* copied template from RemoveAvesChildrenAPI.php
            if($what == 'taxon') {
                if(isset($this->children_of_Aves[$rec['http://rs.tdwg.org/dwc/terms/taxonID']])) continue;
                if(isset($this->children_of_Aves[$rec['http://rs.tdwg.org/dwc/terms/parentNameUsageID']])) continue;
                $o = new \eol_schema\Taxon();
            }
            elseif($what == 'vernacular') {
                if(isset($this->children_of_Aves[$rec['http://rs.tdwg.org/dwc/terms/taxonID']])) continue;
                $o = new \eol_schema\VernacularName();
            }
            elseif($what == 'occurrence') {
                if(isset($this->children_of_Aves[$rec['http://rs.tdwg.org/dwc/terms/taxonID']])) {
                    $this->remove_occurrence_id[$rec['http://rs.tdwg.org/dwc/terms/occurrenceID']] = '';
                    continue;
                }
                if(isset($this->TaxaGroup_remove_occurrence_id[$rec['http://rs.tdwg.org/dwc/terms/occurrenceID']])) continue;
                $o = new \eol_schema\Occurrence();
            }
            elseif($what == 'MoF') {
                if(isset($this->remove_occurrence_id[$rec['http://rs.tdwg.org/dwc/terms/occurrenceID']])) continue;
                if(isset($this->TaxaGroup_remove_occurrence_id[$rec['http://rs.tdwg.org/dwc/terms/occurrenceID']])) continue;
                $o = new \eol_schema\MeasurementOrFact_specific();
            }
            else exit("\nInvestigate [$what]\n");
            */
            
            if($this->params['target'] == 'wikipedia_en_traits_FTG') {
                if($what == 'occurrence') {
                    if(isset($this->TaxaGroup_remove_occurrence_id[$occurrenceID])) continue;
                    $o = new \eol_schema\Occurrence();
                }
                elseif($what == 'MoF') {
                    if(isset($this->TaxaGroup_remove_occurrence_id[$occurrenceID])) continue;
                    $o = new \eol_schema\MeasurementOrFact_specific();
                }
                else exit("\nInvestigate [$what]\n");
            }
            
            $uris = array_keys($rec);
            foreach($uris as $uri) {
                $field = pathinfo($uri, PATHINFO_BASENAME);
                $o->$field = $rec[$uri];
            }
            $this->archive_builder->write_object_to_file($o);
            // if($i >= 10) break; //debug only
        }
    }
    private function write_report($rec)
    {   //print_r($this->params); exit;
        /*Array(
            [source] => 617_ENV
            [target] => wikipedia_en_traits_FTG
            [taxonIDs] => Q1390, Q1357, Q10908
        )*/
        // print_r($rec); exit("\nabc\n");
        /*Array(
            [http://rs.tdwg.org/dwc/terms/measurementID] => 9cb6f33250b98e12397e60297242f627_617_ENV
            [http://rs.tdwg.org/dwc/terms/occurrenceID] => d6a07a6127dfc7309024d5eb641f3874_617_ENV
            [http://eol.org/schema/measurementOfTaxon] => true
            [http://rs.tdwg.org/dwc/terms/measurementType] => http://purl.obolibrary.org/obo/RO_0002303
            [http://rs.tdwg.org/dwc/terms/measurementValue] => http://purl.obolibrary.org/obo/ENVO_00000316
            [http://rs.tdwg.org/dwc/terms/measurementRemarks] => source text: "intertidal zone"
            [http://purl.org/dc/terms/source] => http://en.wikipedia.org/w/index.php?title=Desidae&oldid=963995687
        )*/
        $occurrenceID = $rec['http://rs.tdwg.org/dwc/terms/occurrenceID'];
        $taxonID = $this->occurrenceID_taxonID_info[$occurrenceID];
        $sciname = $this->taxonID_info[$taxonID];
        // print_r($rec); exit("\n[$taxonID] [$sciname]\n"); //[Q10038] [Desidae]
        $mType = $rec['http://rs.tdwg.org/dwc/terms/measurementType'];
        $mValue = $rec['http://rs.tdwg.org/dwc/terms/measurementValue'];
        $mRemarks = $rec['http://rs.tdwg.org/dwc/terms/measurementRemarks'];
        self::write_to_text(array($taxonID, $sciname, $mType, $mValue, $mRemarks, $this->params['taxonIDs']));
    }
    private function write_to_text($arr)
    {
        $handle = fopen($this->report_file, "a");
        fwrite($handle, implode("\t", $arr) . "\n");
        fclose($handle);
    }
}
?>