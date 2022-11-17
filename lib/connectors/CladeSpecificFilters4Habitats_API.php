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
        // exit;
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
        self::process_table($tables['http://rs.tdwg.org/dwc/terms/taxon'][0], 'classify_taxa');
        /* generates:
        $this->Insecta[$taxonID]
        $this->Arachnida[$taxonID]
        $this->Malacostraca[$taxonID]
        $this->Maxillopoda[$taxonID]
        */
        self::process_table($tables['http://rs.tdwg.org/dwc/terms/occurrence'][0], 'classify_occurrence');
        /* generates:
        $this->occur_Insecta[$occurrenceID]
        $this->occur_Arachnida[$occurrenceID]
        $this->occur_Malacostraca[$occurrenceID]
        $this->occur_Maxillopoda[$occurrenceID]
        */
        self::process_table($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0], 'classify_MoF');
        /* generates: $this->to_delete_occurID */
        echo "\n to_delete_occurID: ".count($this->to_delete_occurID)."\n";
        
        unset($this->Insecta);
        unset($this->Arachnida);
        unset($this->Malacostraca);
        unset($this->Maxillopoda);
        unset($this->occur_Insecta);
        unset($this->occur_Arachnida);
        unset($this->occur_Malacostraca);
        unset($this->occur_Maxillopoda);
        
        self::process_table($tables['http://rs.tdwg.org/dwc/terms/occurrence'][0], 'write_occurrence');
        self::process_table($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0], 'write_MoF');
        self::process_table($tables['http://rs.tdwg.org/dwc/terms/taxon'][0], 'write_taxa');
        // exit("\nstop muna...\n");
    }
    private function process_table($meta, $task)
    {   //print_r($meta);
        echo "\n\nRunning $task..."; $i = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % 300000) == 0) echo "\n".number_format($i);
            /* ----- writing headers for the report ----- copied template
            if($task == "write_MoF" && $i == 1) {
                $file = CONTENT_RESOURCE_LOCAL_PATH.$this->resource_id."_MoF_removed.txt";
                $fhandle = Functions::file_open($file, "w");
                // build headers array
                foreach($meta->fields as $field) {
                    if(!$field['term']) continue;
                    @$fields[] = pathinfo($field['term'], PATHINFO_FILENAME);
                }
                // 
                print_r($fields);
                fwrite($fhandle, implode("\t", $fields)."\n");
            }
            ----- end ----- */
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
            if($task == 'classify_taxa') {
                /*Array(
                    [http://rs.tdwg.org/dwc/terms/taxonID] => DB5AFC3EC73E5732C0D8E33CFDC6FD63.taxon
                    [http://rs.tdwg.org/dwc/terms/acceptedNameUsageID] => 
                    [http://rs.tdwg.org/dwc/terms/parentNameUsageID] => 
                    [http://rs.tdwg.org/dwc/terms/originalNameUsageID] => 
                    [http://rs.tdwg.org/dwc/terms/scientificName] => Laemosaccus rileyi Hespenheide 2019
                    [http://rs.tdwg.org/dwc/terms/namePublishedIn] => 
                    [http://rs.tdwg.org/dwc/terms/kingdom] => Animalia
                    [http://rs.tdwg.org/dwc/terms/phylum] => Arthropoda
                    [http://rs.tdwg.org/dwc/terms/class] => Insecta
                    [http://rs.tdwg.org/dwc/terms/order] => Coleoptera
                    [http://rs.tdwg.org/dwc/terms/family] => Curculionidae
                    [http://rs.tdwg.org/dwc/terms/genus] => Laemosaccus
                    [http://rs.tdwg.org/dwc/terms/taxonRank] => species
                    [http://rs.tdwg.org/dwc/terms/scientificNameAuthorship] => Hespenheide 2019
                    [http://rs.tdwg.org/dwc/terms/taxonomicStatus] => 
                    [http://rs.tdwg.org/dwc/terms/nomenclaturalStatus] => 
                    [http://purl.org/dc/terms/references] => http://treatment.plazi.org/id/DB5AFC3EC73E5732C0D8E33CFDC6FD63
                    [http://rs.gbif.org/terms/1.0/canonicalName] => Laemosaccus rileyi
                )*/
                $taxonID = $rec['http://rs.tdwg.org/dwc/terms/taxonID'];
                $class = $rec['http://rs.tdwg.org/dwc/terms/class'];
                $family = $rec['http://rs.tdwg.org/dwc/terms/family'];
                
                // class = Insecta                          ---> Habitat=Reasonably Terrestrial
                if($class == 'Insecta') $this->Insecta[$taxonID] = '';
                // class = Arachnida AND family NOT =       ---> Habitat=Reasonably Terrestrial
                if($class == 'Arachnida' && !in_array($family, array('Halacaridae', 'Selenoribatidae', 'Fortuyniidae', 'Ameronothridae', 'Pontarachnidae', 'Hyadesiidae'))) {
                    $this->Arachnida[$taxonID] = '';
                }
                // class = Malacostraca AND family NOT =    ---> Habitat=Reasonably Aquatic
                if($class == 'Malacostraca' && !in_array($family, array('Talitridae', 'Philosciidae', 'Trichoniscidae', 'Scleropactidae', 'Trachelipodidae', 'Armadillidae', 'Styloniscidae', 'Armadillidiidae', 'Porcellionidae', 'Eubelidae', 'Agnaridae', 'Pudeoniscidae', 'Platyarthridae', 'Bathytropidae', 'Olibrinidae', 'Oniscidae', 'Detonidae', 'Halophilosciidae', 'Scyphacidae', 'Cylisticidae', 'Mesoniscidae', 'Rhyscotidae', 'Spelaeoniscidae', 'Stenoniscidae'))) {
                    $this->Malacostraca[$taxonID] = '';
                }
                // class = Maxillopoda                      ---> Habitat=Reasonably Aquatic
                if($class == 'Maxillopoda') $this->Maxillopoda[$taxonID] = '';
            }
            //===================================================================================================================
            if($task == 'classify_occurrence') {
                /*Array(
                    [http://rs.tdwg.org/dwc/terms/occurrenceID] => d72e5d93a891c80dc422db176d0337ec_TreatmentB
                    [http://rs.tdwg.org/dwc/terms/taxonID] => DB5AFC3EC73E5732C0D8E33CFDC6FD63.taxon
                )*/
                $taxonID = $rec['http://rs.tdwg.org/dwc/terms/taxonID'];
                $occurrenceID = $rec['http://rs.tdwg.org/dwc/terms/occurrenceID'];
                if(isset($this->Insecta[$taxonID])) $this->occur_Insecta[$occurrenceID] = '';
                if(isset($this->Arachnida[$taxonID])) $this->occur_Arachnida[$occurrenceID] = '';
                if(isset($this->Malacostraca[$taxonID])) $this->occur_Malacostraca[$occurrenceID] = '';
                if(isset($this->Maxillopoda[$taxonID])) $this->occur_Maxillopoda[$occurrenceID] = '';
            }
            //===================================================================================================================
            if($task == 'classify_MoF') {
                /*Array(
                    [http://rs.tdwg.org/dwc/terms/measurementID] => 6f6a469d65e8a77862a74235a2e0534c_TreatmentB
                    [http://rs.tdwg.org/dwc/terms/occurrenceID] => d72e5d93a891c80dc422db176d0337ec_TreatmentB
                    [http://eol.org/schema/measurementOfTaxon] => true
                    [http://rs.tdwg.org/dwc/terms/measurementType] => http://eol.org/schema/terms/Present
                    [http://rs.tdwg.org/dwc/terms/measurementValue] => http://www.geonames.org/4736286
                    [http://rs.tdwg.org/dwc/terms/measurementRemarks] => source text: "texas"
                    [http://purl.org/dc/terms/source] => http://treatment.plazi.org/id/DB5AFC3EC73E5732C0D8E33CFDC6FD63
                    [http://purl.org/dc/terms/bibliographicCitation] => Hespenheide, Henry A. (2019): A Review of the Genus Laemosaccus Schönherr, 1826 (Coleoptera: Curculionidae: Mesoptiliinae) from Baja California and America North of Mexico: Diversity and Mimicry. The Coleopterists Bulletin 73 (4): 905-939, DOI: 10.1649/0010-065X-73.4.905, URL: http://dx.doi.org/10.1649/0010-065x-73.4.905
                )*/
                $habitat_trait = 'http://purl.obolibrary.org/obo/RO_0002303';
                $mType = $rec['http://rs.tdwg.org/dwc/terms/measurementType'];
                $mValue = $rec['http://rs.tdwg.org/dwc/terms/measurementValue'];
                $occurrenceID = $rec['http://rs.tdwg.org/dwc/terms/occurrenceID'];
                if($mType == $habitat_trait) { // is a habitat trait record
                    /*
                    class = Insecta
                    Habitat=Reasonably Terrestrial
                    */
                    if(isset($this->occur_Insecta[$occurrenceID])) {
                        if(self::is_mValue_Reasonably_Terrestrial($mValue)) {}
                        else $this->to_delete_occurID[$occurrenceID] = '';
                    }
                    
                    
                    /*
                    class = Arachnida AND family NOT = Halacaridae, Selenoribatidae, Fortuyniidae, Ameronothridae, Pontarachnidae, or Hyadesiidae
                    Habitat=Reasonably Terrestrial
                    */
                    if(isset($this->occur_Arachnida[$occurrenceID])) {
                        if(self::is_mValue_Reasonably_Terrestrial($mValue)) {}
                        else $this->to_delete_occurID[$occurrenceID] = '';
                    }
                    
                    /*
                    class = Malacostraca AND family NOT = Talitridae, Philosciidae, Trichoniscidae, Scleropactidae, Trachelipodidae, Armadillidae, Styloniscidae, Armadillidiidae, Porcellionidae, Eubelidae, Agnaridae, Pudeoniscidae, Platyarthridae, Bathytropidae, Olibrinidae, Oniscidae, Detonidae, Halophilosciidae, Scyphacidae, Cylisticidae, Mesoniscidae, Rhyscotidae, Spelaeoniscidae, Stenoniscidae
                    Habitat=Reasonably Aquatic
                    */
                    if(isset($this->occur_Malacostraca[$occurrenceID])) {
                        if(self::is_mValue_Reasonably_Aquatic($mValue)) {}
                        else $this->to_delete_occurID[$occurrenceID] = '';
                    }
                    

                    /*
                    class = Maxillopoda
                    Habitat=Reasonably Aquatic
                    */
                    if(isset($this->occur_Maxillopoda[$occurrenceID])) {
                        if(self::is_mValue_Reasonably_Aquatic($mValue)) {}
                        else $this->to_delete_occurID[$occurrenceID] = '';
                    }
                    
                }
            }
            //===================================================================================================================
            if($task == 'write_occurrence') {
                /*Array(
                    [http://rs.tdwg.org/dwc/terms/occurrenceID] => d72e5d93a891c80dc422db176d0337ec_TreatmentB
                    [http://rs.tdwg.org/dwc/terms/taxonID] => DB5AFC3EC73E5732C0D8E33CFDC6FD63.taxon
                )*/
                $taxonID = $rec['http://rs.tdwg.org/dwc/terms/taxonID'];
                $occurrenceID = $rec['http://rs.tdwg.org/dwc/terms/occurrenceID'];
                if(!isset($this->to_delete_occurID[$occurrenceID])) { // saving
                    $o = new \eol_schema\Occurrence_specific();
                    $uris = array_keys($rec);
                    foreach($uris as $uri) {
                        $field = pathinfo($uri, PATHINFO_BASENAME);
                        $o->$field = $rec[$uri];
                    }
                    $this->archive_builder->write_object_to_file($o);
                    $this->taxa_has_occurrence[$taxonID] = '';
                }
            }
            //===================================================================================================================
            if($task == 'write_MoF') {
                /*Array(
                    [http://rs.tdwg.org/dwc/terms/measurementID] => 6f6a469d65e8a77862a74235a2e0534c_TreatmentB
                    [http://rs.tdwg.org/dwc/terms/occurrenceID] => d72e5d93a891c80dc422db176d0337ec_TreatmentB
                    [http://eol.org/schema/measurementOfTaxon] => true
                    [http://rs.tdwg.org/dwc/terms/measurementType] => http://eol.org/schema/terms/Present
                    [http://rs.tdwg.org/dwc/terms/measurementValue] => http://www.geonames.org/4736286
                    [http://rs.tdwg.org/dwc/terms/measurementRemarks] => source text: "texas"
                    [http://purl.org/dc/terms/source] => http://treatment.plazi.org/id/DB5AFC3EC73E5732C0D8E33CFDC6FD63
                    [http://purl.org/dc/terms/bibliographicCitation] => Hespenheide, Henry A. (2019): A Review of the Genus Laemosaccus Schönherr, 1826 (Coleoptera: Curculionidae: Mesoptiliinae) from Baja California and America North of Mexico: Diversity and Mimicry. The Coleopterists Bulletin 73 (4): 905-939, DOI: 10.1649/0010-065X-73.4.905, URL: http://dx.doi.org/10.1649/0010-065x-73.4.905
                )*/
                $occurrenceID = $rec['http://rs.tdwg.org/dwc/terms/occurrenceID'];
                // $parentMeasurementID = @$rec['http://eol.org/schema/parentMeasurementID']; // not implemented
                if(!isset($this->to_delete_occurID[$occurrenceID])) { // saving
                    $o = new \eol_schema\MeasurementOrFact_specific();
                    $uris = array_keys($rec);
                    foreach($uris as $uri) {
                        $field = pathinfo($uri, PATHINFO_BASENAME);
                        $o->$field = $rec[$uri];
                    }
                    $this->archive_builder->write_object_to_file($o);
                }
            }
            //===================================================================================================================
            if($task == 'write_taxa') {
                $taxonID = $rec['http://rs.tdwg.org/dwc/terms/taxonID'];
                if(isset($this->taxa_has_occurrence[$taxonID])) { // saving
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
        }
        if(isset($fhandle)) fclose($fhandle);
    }
    private function is_mValue_Reasonably_Terrestrial($mValue)
    {
        // $this->descendants_of_marine
        // $this->descendants_of_terrestrial
        // $this->descendants_of_coastalLand
        // $this->descendants_of_freshwater
        
        // Reasonably Terrestrial: Excluding all children of marine, http://purl.obolibrary.org/obo/ENVO_00000447, 
        // except for children of coastal land, http://purl.obolibrary.org/obo/ENVO_00000303
        if(isset($this->descendants_of_coastalLand[$mValue])) return true;
        if(isset($this->descendants_of_marine[$mValue])) return false;
        return true;
    }
    private function is_mValue_Reasonably_Aquatic($mValue)
    {
        // Reasonably Aquatic: Excluding all children of terrestrial, http://purl.obolibrary.org/obo/ENVO_00000446, 
        // except for children of coastal land, http://purl.obolibrary.org/obo/ENVO_00000303
        if(isset($this->descendants_of_coastalLand[$mValue])) return true;
        if(isset($this->descendants_of_terrestrial[$mValue])) return false;
        return true;
    }
    private function get_descendants_of_term($term, $label)
    {
        $descendants_of_term = $this->func->get_descendants_of_taxID($term, false, $this->descendants);
        echo "\nDescendants of $label ($term): ".count($descendants_of_term)."\n"; //print_r($descendants_of_term);
        $this_descendants = self::re_orient($descendants_of_term); unset($descendants_of_term);
        $this_descendants[$term] = ''; // inclusive
        echo "\nDescendants of $label ($term): ".count($this_descendants)."\n";
        // print_r($this_descendants);
        return $this_descendants;
    }
    private function re_orient($arr)
    {
        foreach($arr as $item) $final[$item] = '';
        return $final;
    }
    /* copied template 1st level
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
    */
    /* copied template 2nd level
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