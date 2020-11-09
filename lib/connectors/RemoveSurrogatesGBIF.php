<?php
namespace php_active_record;
/* connector: [called from DwCA_Utility.php, which is called from gbif_classification_DATA1868.php from DATA-1868. */
class RemoveSurrogatesGBIF
{
    function __construct($resource_id, $archive_builder)
    {
        $this->resource_id = $resource_id;
        $this->archive_builder = $archive_builder;
        // $this->download_options = array('resource_id' => $resource_id, 'expire_seconds' => 60*60*24*30*3, 'download_wait_time' => 1000000, 'timeout' => 10800, 'download_attempts' => 1, 'delay_in_minutes' => 1);

    }
    /*================================================================= STARTS HERE ======================================================================*/
    private function get_children_of_taxa_group($taxon_ids)
    {
        require_library('connectors/PaleoDBAPI_v2');
        $func = new PaleoDBAPI_v2("");
        $dwca_file = CONTENT_RESOURCE_LOCAL_PATH . "gbif_classification_without_ancestry".".tar.gz"; //this is the input
        $descendant_taxon_ids = $func->get_descendants_given_parent_ids($dwca_file, $taxon_ids, 'gbif_classification_final');
        return $descendant_taxon_ids;
    }
    function remove_surrogates_from_GBIF($info)
    {
        $tables = $info['harvester']->tables;
        //----------------------------------------------------------------------------------------------
        /* 1st step: Get all taxonIDs of surrogates */
        $taxonIDs_of_surrogates = self::get_IDs_of_surrogates($tables); //e.g. 10549716, 9954965
        //----------------------------------------------------------------------------------------------
        $this->children_of_Aves = array();
        foreach($taxonIDs_of_surrogates as $surrogates) $this->children_of_Aves[$surrogates] = ''; //include the actual surrogates, of course!
        
        $children = self::get_children_of_taxa_group($taxonIDs_of_surrogates);
        foreach($children as $child) $this->children_of_Aves[$child] = '';
        unset($children);
        echo "\nChildren of surrogates: ".count($this->children_of_Aves)."\n";
        //----------------------------------------------------------------------------------------------
        self::process_generic_table($tables['http://rs.tdwg.org/dwc/terms/taxon'][0], 'taxon_write');
    }
    private function get_IDs_of_surrogates($tables)
    {
        return self::process_generic_table($tables['http://rs.tdwg.org/dwc/terms/taxon'][0], 'info_list');
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
            $rec= array_map('trim', $rec);
            // print_r($rec); exit;
            /*Array(
                [http://rs.tdwg.org/dwc/terms/taxonID] => 1162096
                [http://rs.tdwg.org/dwc/terms/acceptedNameUsageID] => 
                [http://rs.tdwg.org/dwc/terms/parentNameUsageID] => 1162079
                [http://rs.tdwg.org/dwc/terms/originalNameUsageID] => 
                [http://rs.tdwg.org/dwc/terms/scientificName] => Eopenthes deceptor Sharp, 1908
                [http://rs.tdwg.org/dwc/terms/nameAccordingTo] => 
                [http://rs.tdwg.org/dwc/terms/namePublishedIn] => 
                [http://rs.tdwg.org/dwc/terms/taxonRank] => species
                [http://rs.tdwg.org/dwc/terms/scientificNameAuthorship] => Sharp, 1908
                [http://rs.tdwg.org/dwc/terms/taxonomicStatus] => accepted
                [http://rs.tdwg.org/dwc/terms/nomenclaturalStatus] => 
                [http://rs.tdwg.org/dwc/terms/taxonRemarks] => 
                [http://rs.tdwg.org/dwc/terms/datasetID] => 7ddf754f-d193-4cc9-b351-99906754a03b
                [http://rs.gbif.org/terms/1.0/canonicalName] => Eopenthes deceptor
                [http://eol.org/schema/EOLid] => 1173736
            )*/
            
            if($what == 'info_list') {
                /* The scientificNames of the BOLD surrogates are all of the form:
                BOLD:ABC1234
                ranging from BOLD:AAA0001 to BOLD:ADV9516.

                The scientificNames of the UNITE surrogates are all of the form:
                SH1234567.08FU
                ranging from SH1502188.08FU to SH1659848.08FU
                */
                $sciname = $rec['http://rs.tdwg.org/dwc/terms/scientificName'];
                $taxonID = $rec['http://rs.tdwg.org/dwc/terms/taxonID'];
                if(substr($sciname, 0, 5) == 'BOLD:') {
                    $arr = explode(':', $sciname);
                    $part = $arr[1]; //e.g. AAA0001
                    if($part >= 'AAA0001' && $part <= 'ADV9516') $final[$taxonID] = '';
                }
                elseif(substr($sciname,0,2) == 'SH' && substr($sciname, -5) == '.08FU') {
                    if($sciname >= 'SH1502188.08FU' && $sciname <= 'SH1659848.08FU') $final[$taxonID] = '';
                }
            }
            elseif($what == 'taxon_write') {
                if(isset($this->children_of_Aves[$rec['http://rs.tdwg.org/dwc/terms/taxonID']])) continue;
                if(isset($this->children_of_Aves[$rec['http://rs.tdwg.org/dwc/terms/parentNameUsageID']])) continue;
                $o = new \eol_schema\Taxon();
                $uris = array_keys($rec);
                foreach($uris as $uri) {
                    $field = pathinfo($uri, PATHINFO_BASENAME);
                    $o->$field = $rec[$uri];
                }
                $this->archive_builder->write_object_to_file($o);
            }
            /* from copied template
            elseif($what == 'vernacular') {
                if(isset($this->children_of_Aves[$rec['http://rs.tdwg.org/dwc/terms/taxonID']])) continue;
                $o = new \eol_schema\VernacularName();
            }
            elseif($what == 'occurrence') {
                if(isset($this->children_of_Aves[$rec['http://rs.tdwg.org/dwc/terms/taxonID']])) {
                    $this->remove_occurrence_id[$rec['http://rs.tdwg.org/dwc/terms/occurrenceID']] = '';
                    continue;
                }
                if(isset($this->Ostracoda_remove_occurrence_id[$rec['http://rs.tdwg.org/dwc/terms/occurrenceID']])) continue;
                $o = new \eol_schema\Occurrence();
            }
            elseif($what == 'MoF') {
                if(isset($this->remove_occurrence_id[$rec['http://rs.tdwg.org/dwc/terms/occurrenceID']])) continue;
                if(isset($this->Ostracoda_remove_occurrence_id[$rec['http://rs.tdwg.org/dwc/terms/occurrenceID']])) continue;
                $o = new \eol_schema\MeasurementOrFact_specific();
            }
            */
            else exit("\nInvestigate [$what]\n");
            
            // if($i >= 10) break; //debug only
        }
        if($what == 'info_list') return array_keys($final);
    }
    /*================================================================= ENDS HERE ======================================================================*/
}
?>
