<?php
namespace php_active_record;
/* connector: [called from DwCA_Utility.php, which is called from rem_marine_terr_desc.php] */
class CladeSpecificFilters4Habitats_API
{
    function __construct($archive_builder, $resource_id)
    {
        $this->resource_id = $resource_id;
        $this->archive_builder = $archive_builder;
        if(Functions::is_production()) {}
        else {}
        $this->download_options = array('expire_seconds' => 60*60*24*1, 'download_wait_time' => 1000000, 'timeout' => 60*5, 'cache' => 1);
        $this->debug = array();
    }
    /*================================================================= STARTS HERE ======================================================================*/
    function start($info)
    {
        /* START DATA-1841 terms remapping --- copied template
        require_library('connectors/TraitGeneric');
        $func = new TraitGeneric(false, false); //params are false and false bec. we just need to access 1 function.
        $this->remapped_terms = $func->initialize_terms_remapping(60*60*24);
        echo "\nremapped_terms local: ".count($this->remapped_terms)."\n";
        END DATA-1841 terms remapping */
        /* copied template
        $tables = $info['harvester']->tables;
        self::process_taxon($tables['http://rs.tdwg.org/dwc/terms/taxon'][0], $ret);
        self::process_occurrence($tables['http://rs.tdwg.org/dwc/terms/occurrence'][0], 'info'); //generates this->oID_taxonID_info
        self::process_measurementorfact_info($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0]); //to get $this->occurrence_id_2delete
        self::process_occurrence($tables['http://rs.tdwg.org/dwc/terms/occurrence'][0], 'write'); //this is to exclude taxonID = EOL:11584278 (undescribed)
        self::process_measurementorfact($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0]); //fix source links bec. of obsolete taxonIDs
        */
        /* start customize --- copied template
        if($this->resource_id == 'xxx') self::process_reference($tables['http://eol.org/schema/reference/reference'][0]);
        */
        
        /* not used here. Info from terms.yml is formatted differently here.
        require_library('connectors/Functions_Pensoft');
        $func = new Functions_Pensoft();
        $this->allowed_terms_URIs = $func->get_allowed_value_type_URIs_from_EOL_terms_file($this->download_options); print_r($this->allowed_terms_URIs);
        echo ("\nallowed_terms_URIs from EOL terms file: [".count($this->allowed_terms_URIs)."]\n");
        */

        // /* use external func for computation of descendants
        require_library('connectors/DH_v1_1_postProcessing');
        $this->func = new DH_v1_1_postProcessing(1);
        // */

        // /* code re-use
        require_library('connectors/Clean_MoF_Habitat_API');
        $func = new Clean_MoF_Habitat_API(false, false);
        $this->descendants = $func->get_descendants_info(); //generates $this->descendants
        // */

        $this->descendants_of_marine = self::get_descendants_of_term('http://purl.obolibrary.org/obo/ENVO_00000447', "marine");
        $this->descendants_of_terrestrial = self::get_descendants_of_term('http://purl.obolibrary.org/obo/ENVO_00000446', "terrestrial");
        $this->descendants_of_coastalLand = self::get_descendants_of_term('http://purl.obolibrary.org/obo/ENVO_00000303', "coastal_land");
        $this->descendants_of_freshwater = self::get_descendants_of_term('http://purl.obolibrary.org/obo/ENVO_00000873', "freshwater");
        exit;
        /* as of Nov 16, 2022
        Descendants of marine (http://purl.obolibrary.org/obo/ENVO_00000447): 147
        Descendants of terrestrial (http://purl.obolibrary.org/obo/ENVO_00000446): 1567
        Descendants of coastal_land (http://purl.obolibrary.org/obo/ENVO_00000303): 88
        Descendants of freshwater (http://purl.obolibrary.org/obo/ENVO_00000873): 153
        */
        /*
        print_r($this->descendants_of_marine);
        print_r($this->descendants_of_terrestrial);
        exit("\nstop munax...\n");
        */
        
        $tables = $info['harvester']->tables;
        self::process_table($tables['http://rs.tdwg.org/dwc/terms/occurrence'][0], 'build_occurID_taxonID_info'); //gen $this->occurID_taxonID_info
        self::process_table($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0], 'build_measurement_occurrence_info');

        // exit("\nstop muna...\n");
    }
    private function get_descendants_of_term($term, $label)
    {
        $descendants_of_term = $this->func->get_descendants_of_taxID($term, false, $this->descendants);
        echo "\nDescendants of $label ($term): ".count($descendants_of_term)."\n"; //print_r($descendants_of_term);
        $this_descendants = self::re_orient($descendants_of_term); unset($descendants_of_term);
        $this_descendants[$term] = ''; // inclusive
        echo "\nDescendants of $label ($term): ".count($this_descendants)."\n";
        print_r($this_descendants);
        return $this_descendants;
    }
    private function process_table($meta, $task)
    {   //print_r($meta);
        echo "\n\nRunning $task..."; $i = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % 300000) == 0) echo "\n".number_format($i);
            // /* ----- writing headers for the report -----
            if($task == "write_MoF" && $i == 1) {
                $file = CONTENT_RESOURCE_LOCAL_PATH.$this->resource_id."_MoF_removed.txt";
                $fhandle = Functions::file_open($file, "w");
                // /* build headers array
                foreach($meta->fields as $field) {
                    if(!$field['term']) continue;
                    @$fields[] = pathinfo($field['term'], PATHINFO_FILENAME);
                }
                // */
                print_r($fields);
                fwrite($fhandle, implode("\t", $fields)."\n");
            }
            // ----- end ----- */
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
            //===================================================================================================================
            if($task == 'build_occurID_taxonID_info') {
            }
            //===================================================================================================================
            //===================================================================================================================
            //===================================================================================================================
            //===================================================================================================================
            //===================================================================================================================
            //===================================================================================================================
            //===================================================================================================================
            //===================================================================================================================
            //===================================================================================================================
        }
        if(isset($fhandle)) fclose($fhandle);
    }
    private function is_mValue_descendant_of_marine($mValue)
    {
        if(isset($this->descendants_of_marine[$mValue])) return true;
        else return false;
    }
    private function is_mValue_descendant_of_terrestrial($mValue)
    {
        if(isset($this->descendants_of_terrestrial[$mValue])) return true;
        else return false;
    }
    private function is_habitat_YN($mType)
    {
        if($mType == 'http://purl.obolibrary.org/obo/RO_0002303') return true;
        else return false;
    }
    private function re_orient($arr)
    {
        foreach($arr as $item) $final[$item] = '';
        return $final;
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