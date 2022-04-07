<?php
namespace php_active_record;
/* connector: [called from DwCA_Utility.php, which is called from rem_marine_terr_desc.php] */
class Clean_MoF_Habitat_API
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
        self::get_descendants_info(); //generates $this->descendants

        $marine = 'http://purl.obolibrary.org/obo/ENVO_00000447';
        $descendants_of_marine = $this->func->get_descendants_of_taxID($marine, false, $this->descendants);
        echo "\nDescendants of marine ($marine): ".count($descendants_of_marine)."\n"; //print_r($descendants_of_marine);
        $this->descendants_of_marine = self::re_orient($descendants_of_marine); unset($descendants_of_marine);
        $this->descendants_of_marine[$marine] = '';
        echo "\nDescendants of marine ($marine): ".count($this->descendants_of_marine)."\n";

        $terrestrial = 'http://purl.obolibrary.org/obo/ENVO_00000446';
        $descendants_of_terrestrial = $this->func->get_descendants_of_taxID($terrestrial, false, $this->descendants);
        echo "\nDescendants of terrestrial ($terrestrial): ".count($descendants_of_terrestrial)."\n"; //print_r($descendants_of_terrestrial);
        $this->descendants_of_terrestrial = self::re_orient($descendants_of_terrestrial); unset($descendants_of_terrestrial);
        $this->descendants_of_terrestrial[$terrestrial] = '';
        echo "\nDescendants of terrestrial ($terrestrial): ".count($this->descendants_of_terrestrial)."\n";
        
        $tables = $info['harvester']->tables;
        self::process_table($tables['http://rs.tdwg.org/dwc/terms/occurrence'][0], 'build_occurID_taxonID_info'); //gen $this->occurID_taxonID_info
        self::process_table($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0], 'log_habitat_use'); //gen $this->marine_and_terrestrial
        echo "\ntaxonIDs with both marine and terrestrial habitats: ".count($this->marine_and_terrestrial)."\n"; // print_r($this->marine_and_terrestrial);

        unset($this->descendants);
        unset($this->descendants_of_marine);
        unset($this->descendants_of_terrestrial);
        unset($this->occurID_taxonID_info);
        
        /* start writing */
        self::process_table($tables['http://rs.tdwg.org/dwc/terms/occurrence'][0], 'write_occurrence'); //gen $this->occurrenceIDs_2delete
        self::process_table($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0], 'write_MoF'); //gen $this->referenceIDs
        self::process_table($tables['http://eol.org/schema/reference/reference'][0], 'write_reference');
        unset($this->occurrenceIDs_2delete);
        unset($this->referenceIDs);
        self::process_table($tables['http://rs.tdwg.org/dwc/terms/taxon'][0], 'write_taxon');
        // exit("\nstop muna...\n");
    }
    private function process_table($meta, $task)
    {   //print_r($meta);
        echo "\n\nRunning $task..."; $i = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % 300000) == 0) echo "\n".number_format($i);
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
                /*Array(
                    [http://rs.tdwg.org/dwc/terms/occurrenceID] => b93b7c3d84fcdb1705f77f3e802f6f6e_708
                    [http://rs.tdwg.org/dwc/terms/taxonID] => EOL:9063
                )*/
                $this->occurID_taxonID_info[$rec['http://rs.tdwg.org/dwc/terms/occurrenceID']] = $rec['http://rs.tdwg.org/dwc/terms/taxonID'];
            }
            //===================================================================================================================
            if($task == 'log_habitat_use') { // print_r($rec); exit;
                /*Array(
                    [http://rs.tdwg.org/dwc/terms/measurementID] => 06164abc963e5da8fd4030fa3305df59_708
                    [http://rs.tdwg.org/dwc/terms/occurrenceID] => b93b7c3d84fcdb1705f77f3e802f6f6e_708
                    [http://eol.org/schema/measurementOfTaxon] => true
                    [http://rs.tdwg.org/dwc/terms/measurementType] => http://purl.obolibrary.org/obo/RO_0002303
                    [http://rs.tdwg.org/dwc/terms/measurementValue] => http://purl.obolibrary.org/obo/ENVO_00000446
                    [http://rs.tdwg.org/dwc/terms/measurementMethod] => text mining
                    [http://rs.tdwg.org/dwc/terms/measurementRemarks] => source text: "terrestrial"
                    [http://purl.org/dc/terms/source] => https://eol.org/search?q=Sphaerospira
                    [http://purl.org/dc/terms/contributor] => <a href="http://environments-eol.blogspot.com/2013/03/welcome-to-environments-eol-few-words.html">Environments-EOL</a>
                    [http://eol.org/schema/reference/referenceID] => 2a5fe9f9217cd54939ff5bdf16a6d0c0
                )*/
                $mType = $rec['http://rs.tdwg.org/dwc/terms/measurementType'];
                $mValue = $rec['http://rs.tdwg.org/dwc/terms/measurementValue'];
                $occurrenceID = $rec['http://rs.tdwg.org/dwc/terms/occurrenceID'];
                if($taxonID_in_question = $this->occurID_taxonID_info[$occurrenceID]) {}
                else { print_r($rec); exit("\nno link to taxonID\n"); }
                if(self::is_habitat_YN($mType)) {
                    if(self::is_mValue_descendant_of_marine($mValue)) $log[$taxonID_in_question]['marine'] = '';
                    if(self::is_mValue_descendant_of_terrestrial($mValue)) $log[$taxonID_in_question]['terrestrial'] = '';
                }
                if(isset($log[$taxonID_in_question]['marine']) && isset($log[$taxonID_in_question]['terrestrial'])) $this->marine_and_terrestrial[$taxonID_in_question] = '';
            }
            //===================================================================================================================
            if($task == 'write_occurrence') {
                /*Array(
                    [http://rs.tdwg.org/dwc/terms/occurrenceID] => b93b7c3d84fcdb1705f77f3e802f6f6e_708
                    [http://rs.tdwg.org/dwc/terms/taxonID] => EOL:9063
                )*/
                $occurrenceID = $rec['http://rs.tdwg.org/dwc/terms/occurrenceID'];
                $taxonID = $rec['http://rs.tdwg.org/dwc/terms/taxonID'];
                if(isset($this->marine_and_terrestrial[$taxonID])) { //don't save
                    $this->occurrenceIDs_2delete[$occurrenceID] = '';
                    continue;
                }
                else { //save
                    $o = new \eol_schema\Occurrence_specific();
                    $uris = array_keys($rec);
                    foreach($uris as $uri) {
                        $field = pathinfo($uri, PATHINFO_BASENAME);
                        $o->$field = $rec[$uri];
                    }
                    $this->archive_builder->write_object_to_file($o);
                }
            }
            //===================================================================================================================
            if($task == 'write_MoF') {
                /*Array(
                    [http://rs.tdwg.org/dwc/terms/measurementID] => 06164abc963e5da8fd4030fa3305df59_708
                    [http://rs.tdwg.org/dwc/terms/occurrenceID] => b93b7c3d84fcdb1705f77f3e802f6f6e_708
                    [http://eol.org/schema/measurementOfTaxon] => true
                    [http://rs.tdwg.org/dwc/terms/measurementType] => http://purl.obolibrary.org/obo/RO_0002303
                    [http://rs.tdwg.org/dwc/terms/measurementValue] => http://purl.obolibrary.org/obo/ENVO_00000446
                    [http://rs.tdwg.org/dwc/terms/measurementMethod] => text mining
                    [http://rs.tdwg.org/dwc/terms/measurementRemarks] => source text: "terrestrial"
                    [http://purl.org/dc/terms/source] => https://eol.org/search?q=Sphaerospira
                    [http://purl.org/dc/terms/contributor] => <a href="http://environments-eol.blogspot.com/2013/03/welcome-to-environments-eol-few-words.html">Environments-EOL</a>
                    [http://eol.org/schema/reference/referenceID] => 2a5fe9f9217cd54939ff5bdf16a6d0c0
                )*/
                $occurrenceID = $rec['http://rs.tdwg.org/dwc/terms/occurrenceID'];
                if(isset($this->occurrenceIDs_2delete[$occurrenceID])) continue; //don't save
                else {
                    $o = new \eol_schema\MeasurementOrFact_specific();
                    $uris = array_keys($rec);
                    foreach($uris as $uri) {
                        $field = pathinfo($uri, PATHINFO_BASENAME);
                        $o->$field = $rec[$uri];
                    }
                    $this->archive_builder->write_object_to_file($o);
                    
                    // /*
                    if($str = $rec['http://eol.org/schema/reference/referenceID']) {
                        $referenceIDs = explode(";", $str);
                        $referenceIDs = array_map('trim', $referenceIDs);
                        $referenceIDs = array_filter($referenceIDs); //remove null arrays
                        $referenceIDs = array_unique($referenceIDs); //make unique
                        $referenceIDs = array_values($referenceIDs); //reindex key
                        foreach($referenceIDs as $id) $this->referenceIDs[$id] = '';
                    }
                    // */
                }
            }
            //===================================================================================================================
            if($task == 'write_reference') {
                $identifier = $rec['http://purl.org/dc/terms/identifier'];
                if(isset($this->referenceIDs[$identifier])){ //save
                    $o = new \eol_schema\Reference();
                    $uris = array_keys($rec);
                    foreach($uris as $uri) {
                        $field = pathinfo($uri, PATHINFO_BASENAME);
                        $o->$field = $rec[$uri];
                    }
                    $this->archive_builder->write_object_to_file($o);
                }
                else continue;
            }
            //===================================================================================================================
            if($task == 'write_taxon') { //print_r($rec); exit;
                $taxonID = $rec['http://rs.tdwg.org/dwc/terms/taxonID'];
                if(isset($this->marine_and_terrestrial[$taxonID])) continue; //don't save
                else {
                    $o = new \eol_schema\Taxon();
                    $uris = array_keys($rec);
                    foreach($uris as $uri) {
                        $field = pathinfo($uri, PATHINFO_BASENAME);
                        $o->$field = $rec[$uri];
                    }
                    $this->archive_builder->write_object_to_file($o);
                }
            }
            //===================================================================================================================
            //===================================================================================================================
            //===================================================================================================================
            //===================================================================================================================
            //===================================================================================================================
            //===================================================================================================================

        }
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
    private function get_descendants_info()
    {
        $options = $this->download_options;
        $options['expire_seconds'] = 60*60*24*1; //1 day expires
        if($yml = Functions::lookup_with_cache("https://raw.githubusercontent.com/EOL/eol_terms/main/resources/terms.yml", $options)) {
            // exit("\n$yml\n");
            /*
            - attribution: ''
              definition: The marine pelagic biome (pelagic meaning open sea) is that of the marine water column, from the surface to the greatest depths.
              is_hidden_from_select: false
              is_hidden_from_overview: false
              is_hidden_from_glossary: false
              is_text_only: false
              name: marine pelagic
              type: value
              uri: http://purl.obolibrary.org/obo/ENVO_01000023
              parent_uris: []
              synonym_of_uri:
              units_term_uri:
              alias:
            - attribution: ''
              definition: A biome expressed by strips or ridges of rocks, sand, or coral that rises to or near the surface of a body of marine water.
              is_hidden_from_select: false
            */
            $yml .= "alias: ";
            if(preg_match_all("/name\:(.*?)alias\:/ims", $yml, $a)) {
                $arr = array_map('trim', $a[1]); // print_r($arr); exit;
                foreach($arr as $block) { // echo "\n$block\n"; exit;
                    /*
                    */
                    /* debug
                    if(stripos($block, "https://www.gbif.org/dataset/1b2af425-9f6f-4b28-a008-af9757317c4c") !== false) { //string is found
                        echo "\n$block\n"; 
                        // uri:  https://www.gbif.org/dataset/1b2af425-9f6f-4b28-a008-af9757317c4c
                        if(preg_match("/uri\: (.*?)\n/ims", $block, $a)) $rek['uri'] = trim($a[1]); //https://eol.org/schema/terms/thallus_length
                        print_r($rek);
                        exit("\n222\n");
                    }
                    else continue;
                    */
                    $rek = array();
                    if(preg_match("/uri\: (.*?)\n/ims", $block, $a)) $rek['uri'] = trim($a[1]); //https://eol.org/schema/terms/thallus_length
                    if(preg_match("/parent_uris\:(.*?)synonym_of_uri\:/ims", $block, $a)) {
                        $block2 = $a[1]; //echo "\n[$block2]\n";
                        if(preg_match_all("/ - (.*?)\n/ims", $block2, $a)) {
                            $parents = array_map('trim', $a[1]); // print_r($parents);
                            $rek['parents'] = $parents;
                        }
                    }
                    // print_r($rek);
                    /*Array(
                        [uri] => https://www.wikidata.org/entity/Q747463
                    )
                    Array(
                        [uri] => https://eol.org/schema/terms/thallus_length
                        [parents] => Array(
                                [0] => http://purl.obolibrary.org/obo/CMO_0000013
                                [1] => http://purl.obolibrary.org/obo/FLOPO_0014721
                            )
                    )*/
                    if($uri = @$rek['uri']) {
                        /* debug
                        if($uri == 'https://www.gbif.org/dataset/1b2af425-9f6f-4b28-a008-af9757317c4c') {
                            print_r($rek);
                            exit("\nhuli ka\n");
                        }
                        */
                        if($parents = @$rek['parents']) {
                            foreach($parents as $parent) {
                                $this->descendants[$parent][$uri] = ''; //used for descendants (children)
                            }
                        }
                    }
                }
            }
            else exit("\nInvestigate: EOL terms file structure had changed.\n");
        }
        else exit("\nInvestigate: EOL terms file not accessible.\n");
        /*
        [http://grbio.org/cool/cxwr-bj09] => Array
               (
                   [0] =>  https://www.gbif.org/dataset/f58922e2-93ed-4703-ba22-12a0674d1b54
                   [1] =>  https://www.gbif.org/dataset/0214a6a7-898f-4ee8-b888-0be60ecde81f
                   [2] =>  https://www.gbif.org/dataset/b5cdf794-8fa4-4a85-8b26-755d087bf531
                   [3] =>  https://www.gbif.org/dataset/ba0c03ab-fa61-4a3c-8db7-35c8c3454168
                   [4] =>  https://www.gbif.org/dataset/2cc23bac-e94b-414f-95ab-cc838c03f765
                   [5] =>  https://www.gbif.org/dataset/b5cdf587-3342-48ec-9130-ba1281d7166f
                   [6] =>  https://www.gbif.org/dataset/2f23ac81-674e-4e4e-982f-73f49ccdb9df
               )
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