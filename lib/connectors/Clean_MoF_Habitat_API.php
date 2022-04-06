<?php
namespace php_active_record;
/* connector: [called from DwCA_Utility.php, which is called from rem_marine_terr_desc.php] */
class Clean_MoF_Habitat_API
{
    function __construct($archive_builder, $resource_id)
    {
        $this->resource_id = $resource_id;
        $this->archive_builder = $archive_builder;
        if(Functions::is_production()) {
        }
        else {
        }
        $this->debug = array();
    }
    /*================================================================= STARTS HERE ======================================================================*/
    function start($info)
    {
        /* START DATA-1841 terms remapping */
        require_library('connectors/TraitGeneric');
        $func = new TraitGeneric(false, false); //params are false and false bec. we just need to access 1 function.
        $this->remapped_terms = $func->initialize_terms_remapping(60*60*24);
        echo "\nremapped_terms local: ".count($this->remapped_terms)."\n";
        /* END DATA-1841 terms remapping */
        
        $tables = $info['harvester']->tables;
        $ret = self::get_all_phylum_in_DH();
        self::process_taxon($tables['http://rs.tdwg.org/dwc/terms/taxon'][0], $ret);
        self::process_occurrence($tables['http://rs.tdwg.org/dwc/terms/occurrence'][0], 'info'); //generates this->oID_taxonID_info
        self::process_measurementorfact_info($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0]); //to get $this->occurrence_id_2delete
        self::process_occurrence($tables['http://rs.tdwg.org/dwc/terms/occurrence'][0], 'write'); //this is to exclude taxonID = EOL:11584278 (undescribed)
        self::process_measurementorfact($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0]); //fix source links bec. of obsolete taxonIDs

        /* start customize --- copied template
        if($this->resource_id == 'xxx') self::process_reference($tables['http://eol.org/schema/reference/reference'][0]);
        */
    }

    /* copied template
    private function process_measurementorfact($meta)
    {   //print_r($meta);
        $i = 0;
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

            $measurementID = $rec['http://rs.tdwg.org/dwc/terms/measurementID'];
            $occurrenceID = $rec['http://rs.tdwg.org/dwc/terms/occurrenceID'];
            if(isset($this->measurement_id_2delete[$measurementID])) continue; //for deduplication
            if(isset($this->occurrence_id_2delete[$occurrenceID])) continue; //for deduplication

            $o = new \eol_schema\MeasurementOrFact();
            $uris = array_keys($rec);
            foreach($uris as $uri) {
                $field = pathinfo($uri, PATHINFO_BASENAME);
                $o->$field = $rec[$uri];
            }
            $this->archive_builder->write_object_to_file($o);
        }
    }
    */
    /* copied template
    private function process_taxon($meta, $ret)
    {   //print_r($meta);
        echo "\nprocess process_taxon()...\n";
        $i = 0;
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
            $o = new \eol_schema\Taxon();
            $uris = array_keys($rec);
            foreach($uris as $uri) {
                $field = pathinfo($uri, PATHINFO_BASENAME);
                $o->$field = $rec[$uri];
            }
            $this->archive_builder->write_object_to_file($o);
        }
    }
    */
    /* copied template
    private function process_occurrence($meta, $task)
    {   //print_r($meta);
        $i = 0;
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
            $taxonID = $rec['http://rs.tdwg.org/dwc/terms/taxonID'];
            if($task == 'info') {
                $this->oID_taxonID_info[$occurrenceID] = $taxonID;
            }
            elseif($task == 'write') {
                $o = new \eol_schema\Occurrence();
                $uris = array_keys($rec);
                foreach($uris as $uri) {
                    $field = pathinfo($uri, PATHINFO_BASENAME);
                    $o->$field = $rec[$uri];
                }
                $this->archive_builder->write_object_to_file($o);
            }
        }
    }
    */
    /* copied template
    private function process_reference($meta)
    {   //print_r($meta);
        echo "\nprocess process_reference()...\n";
        $i = 0;
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
            if(!isset($this->referenceIDs[$rec['http://purl.org/dc/terms/identifier']])) continue;
            $o = new \eol_schema\Reference();
            $uris = array_keys($rec);
            foreach($uris as $uri) {
                $field = pathinfo($uri, PATHINFO_BASENAME);
                $o->$field = $rec[$uri];
            }
            $this->archive_builder->write_object_to_file($o);
        }
    }
    */
}
?>