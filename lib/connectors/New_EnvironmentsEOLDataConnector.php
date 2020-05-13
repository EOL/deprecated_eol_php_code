<?php
namespace php_active_record;
/* connector: [called from DwCA_Utility.php, which is called from 708_new.php] */
class New_EnvironmentsEOLDataConnector
{
    function __construct($archive_builder, $resource_id)
    {
        $this->resource_id = $resource_id;
        $this->archive_builder = $archive_builder;
        
        if(Functions::is_production()) {
            // $this->eol_taxon_concept_names_tab    = "/extra/other_files/DWH/from_OpenData/EOL_dynamic_hierarchyV1Revised/taxa.txt"; //working but old DH ver.
            $this->eol_taxon_concept_names_tab    = "/extra/other_files/DWH/TRAM-809/DH_v1_1/taxon.tab";    //latest active DH ver.
        }
        else {
            // $this->eol_taxon_concept_names_tab    = "/Volumes/AKiTiO4/other_files/from_OpenData/EOL_dynamic_hierarchyV1Revised/taxa.txt"; //working but old DH ver.
            $this->eol_taxon_concept_names_tab = "/Volumes/AKiTiO4/d_w_h/EOL Dynamic Hierarchy Active Version/DH_v1_1/taxon.tab"; //latest active DH ver.
        }
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
        unset($ret);
        print_r($this->debug);
        self::process_measurementorfact_info($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0]); //to get $this->occurrence_id_2delete
        self::process_occurrence($tables['http://rs.tdwg.org/dwc/terms/occurrence'][0]); //this is to exclude taxonID = EOL:11584278 (undescribed)
        self::process_measurementorfact($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0]); //fix source links bec. of obsolete taxonIDs
        unset($this->linkage_oID_tID);
        unset($this->linkage_tID_sName);
        self::process_reference($tables['http://eol.org/schema/reference/reference'][0]); //write references actually used in MoF. Not all references from source.
    }
    private function process_measurementorfact_info($meta)
    {   //print_r($meta);
        $remove_rec_4mRemarks = array('source text: "ridge"', 'source text: "plateau"', 'source text: "plateaus"', 'source text: "crests"', 'source text: "canyon"', 'source text: "terrace"', 
        'source text: "canyons"', 'source text: "gullies"', 'source text: "notches"', 'source text: "terraces"', 'source text: "bluff"', 'source text: "cliffs"', 'source text: "gulch"', 
        'source text: "gully"', 'source text: "llanos"', 'source text: "plantations"', 'source text: "sierra"', 'source text: "tunnel"');
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
            /*Array(
                [http://rs.tdwg.org/dwc/terms/measurementID] => 7efeff6e1f506b3523f66d644e41b75b_708
                [http://rs.tdwg.org/dwc/terms/occurrenceID] => 6c6b79090187369e36a81b8fc84b14f6_708
                [http://eol.org/schema/measurementOfTaxon] => true
                [http://rs.tdwg.org/dwc/terms/measurementType] => http://eol.org/schema/terms/Habitat
                [http://rs.tdwg.org/dwc/terms/measurementValue] => http://purl.obolibrary.org/obo/ENVO_00000446
                [http://rs.tdwg.org/dwc/terms/measurementMethod] => text mining
                [http://rs.tdwg.org/dwc/terms/measurementRemarks] => source text: "terrestrial"
                [http://purl.org/dc/terms/source] => http://eol.org/pages/2
                [http://purl.org/dc/terms/contributor] => <a href="http://environments-eol.blogspot.com/2013/03/welcome-to-environments-eol-few-words.html">Environments-EOL</a>
                [http://eol.org/schema/reference/referenceID] => 
            )*/
            /* --------------------------------------------------- */
            foreach($remove_rec_4mRemarks as $rem) {
                if($rec['http://rs.tdwg.org/dwc/terms/measurementRemarks'] == $rem) $this->occurrence_id_2delete[$rec['http://rs.tdwg.org/dwc/terms/occurrenceID']] = '';
            }
            /* --------------------------------------------------- */
        }
    }
    private function process_measurementorfact($meta)
    {   //print_r($meta);
        $remove_rec_4mRemarks = array('source text: "ridge"', 'source text: "plateau"', 'source text: "plateaus"', 'source text: "crests"', 'source text: "canyon"', 'source text: "terrace"', 
        'source text: "canyons"', 'source text: "gullies"', 'source text: "notches"', 'source text: "terraces"', 'source text: "bluff"', 'source text: "cliffs"', 'source text: "gulch"', 
        'source text: "gully"', 'source text: "llanos"', 'source text: "plantations"', 'source text: "sierra"', 'source text: "tunnel"');
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
            /*Array(
                [http://rs.tdwg.org/dwc/terms/measurementID] => 7efeff6e1f506b3523f66d644e41b75b_708
                [http://rs.tdwg.org/dwc/terms/occurrenceID] => 6c6b79090187369e36a81b8fc84b14f6_708
                [http://eol.org/schema/measurementOfTaxon] => true
                [http://rs.tdwg.org/dwc/terms/measurementType] => http://eol.org/schema/terms/Habitat
                [http://rs.tdwg.org/dwc/terms/measurementValue] => http://purl.obolibrary.org/obo/ENVO_00000446
                [http://rs.tdwg.org/dwc/terms/measurementMethod] => text mining
                [http://rs.tdwg.org/dwc/terms/measurementRemarks] => source text: "terrestrial"
                [http://purl.org/dc/terms/source] => http://eol.org/pages/2
                [http://purl.org/dc/terms/contributor] => <a href="http://environments-eol.blogspot.com/2013/03/welcome-to-environments-eol-few-words.html">Environments-EOL</a>
                [http://eol.org/schema/reference/referenceID] => 
            )*/

            /* --------------------------------------------------- */
            /* per https://eol-jira.bibalex.org/browse/DATA-1739?focusedCommentId=64619&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-64619 */
            if($rec['http://rs.tdwg.org/dwc/terms/measurementRemarks'] == 'source text: "seamounts"')              $rec['http://rs.tdwg.org/dwc/terms/measurementValue'] = 'http://purl.obolibrary.org/obo/ENVO_00000264';
            elseif($rec['http://rs.tdwg.org/dwc/terms/measurementRemarks'] == 'source text: "seamount"')           $rec['http://rs.tdwg.org/dwc/terms/measurementValue'] = 'http://purl.obolibrary.org/obo/ENVO_00000264';
            elseif($rec['http://rs.tdwg.org/dwc/terms/measurementRemarks'] == 'source text: "seamount chain"')     $rec['http://rs.tdwg.org/dwc/terms/measurementValue'] = 'http://purl.obolibrary.org/obo/ENVO_00000264';
            elseif($rec['http://rs.tdwg.org/dwc/terms/measurementRemarks'] == 'source text: "range of seamounts"') $rec['http://rs.tdwg.org/dwc/terms/measurementValue'] = 'http://purl.obolibrary.org/obo/ENVO_00000264';
            else { //https://eol-jira.bibalex.org/browse/DATA-1739?focusedCommentId=64620&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-64620
                $saveYN = true;
                foreach($remove_rec_4mRemarks as $rem) {
                    if($rec['http://rs.tdwg.org/dwc/terms/measurementRemarks'] == $rem) $saveYN = false;
                }
                if(!$saveYN) continue; //remove MoF record
            }
            /* --------------------------------------------------- */
            if(isset($this->exclude['occurrenceID'][$rec['http://rs.tdwg.org/dwc/terms/occurrenceID']])) continue;

            /* fix source link */
            $taxonID = $this->linkage_oID_tID[$rec['http://rs.tdwg.org/dwc/terms/occurrenceID']];
            if($taxonID == "EOL:11584278") continue; //exclude
            $sciname = $this->linkage_tID_sName[$taxonID];
            $rec['http://purl.org/dc/terms/source'] = "https://eol.org/search?q=".str_replace(" ", "%20", $sciname);
            
            /* START DATA-1841 terms remapping */
            $index = 'http://rs.tdwg.org/dwc/terms/measurementType';    if($new_uri = @$this->remapped_terms[$rec[$index]]) $rec[$index] = $new_uri;
            $index = 'http://rs.tdwg.org/dwc/terms/measurementValue';   if($new_uri = @$this->remapped_terms[$rec[$index]]) $rec[$index] = $new_uri;
            /* END DATA-1841 terms remapping */
            
            $o = new \eol_schema\MeasurementOrFact();
            $uris = array_keys($rec);
            foreach($uris as $uri) {
                $field = pathinfo($uri, PATHINFO_BASENAME);
                $o->$field = $rec[$uri];
            }
            if($val = $o->referenceID) $this->referenceIDs[$val] = ''; //later, will write only refs actually used in MoF
            $this->archive_builder->write_object_to_file($o);
            // if($i >= 10) break; //debug only
        }
    }
    private function process_taxon($meta, $ret)
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
            /*Array(
                [http://rs.tdwg.org/dwc/terms/taxonID] => EOL:2
                [http://rs.tdwg.org/dwc/terms/scientificName] => Acanthocephala
                [http://rs.tdwg.org/dwc/terms/kingdom] => Animalia
                [http://rs.tdwg.org/dwc/terms/phylum] => 
                [http://rs.tdwg.org/dwc/terms/class] => 
                [http://rs.tdwg.org/dwc/terms/family] => 
                [http://rs.tdwg.org/dwc/terms/order] => 
                [http://rs.tdwg.org/dwc/terms/genus] => 
            )*/
            
            // /* May 13, 2020 per: https://eol-jira.bibalex.org/browse/DATA-1739?focusedCommentId=64845&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-64845
            // We don't need habitat records for high rank taxa; they are very hit or miss. Please remove all taxa, and their associated occurrence and MoF records, 
            // that have taxon IDs "up to" EOL:9038, and also, individually, EOL:5251339 and EOL:11592540
            $taxonID = $rec['http://rs.tdwg.org/dwc/terms/taxonID'];
            $taxon_id = str_replace('EOL:', '', $taxonID);
            if($taxon_id <= 9038) {
                $this->remove_higher_rank_taxonIDs[$taxonID] = '';
                continue;
            }
            if(in_array($taxonID, array('EOL:5251339', 'EOL:11592540'))) {
                $this->remove_higher_rank_taxonIDs[$taxonID] = '';
                continue;
            }
            // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
            
            if($rec['http://rs.tdwg.org/dwc/terms/taxonID'] == 'EOL:11584278') continue;
            
            $this->linkage_tID_sName[$rec['http://rs.tdwg.org/dwc/terms/taxonID']] = $rec['http://rs.tdwg.org/dwc/terms/scientificName'];
            
            $ranks = array('kingdom', 'phylum', 'class', 'order', 'family', 'genus');
            $ranks = array('phylum');
            foreach($ranks as $rangk) {
                if($val = $rec['http://rs.tdwg.org/dwc/terms/'.$rangk]) {
                    $val = Functions::canonical_form($val);
                    if(!isset($ret['ancestry'][$rangk][$val])) {
                        // $this->debug["not $rangk in DH"][$val] = @$ret['taxa'][$val]." in DH";
                        $rec['http://rs.tdwg.org/dwc/terms/'.$rangk] = ''; //discarded
                        if($correct_rank = @$ret['taxa'][$val]) {
                            $this->debug["not $rangk in DH"][$val] = " - $correct_rank in DH. Moved.";
                            $rec['http://rs.tdwg.org/dwc/terms/'.$correct_rank] = $val;
                            $this->debug['moved to'][$val] = $correct_rank;
                        }
                        else $this->debug["not $rangk in DH"][$val] = @$ret['taxa'][$val]." - not found in DH. Discarded.";
                    }
                }
            }
            
            $o = new \eol_schema\Taxon();
            $uris = array_keys($rec);
            foreach($uris as $uri) {
                $field = pathinfo($uri, PATHINFO_BASENAME);
                $o->$field = $rec[$uri];
            }
            if(!$o->scientificName) { //e.g. taxonID 'EOL:10646115'
                $this->exclude['taxonID'][$o->taxonID] = '';
                continue;
            }
            if($o->taxonID == 'EOL:11584278') { //exclude scientificName = '(undescribed)'
                $this->exclude['taxonID'][$o->taxonID] = '';
                continue;
            }
            
            //start of adjustments: https://eol-jira.bibalex.org/browse/DATA-1768?focusedCommentId=63624&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-63624
            $o->scientificName = self::fix_sciname($o->scientificName);
            $o = self::remove_authority_in_ancestry_fields($o);
            
            $this->archive_builder->write_object_to_file($o);
            // if($i >= 10) break; //debug only
        }
    }
    private function remove_authority_in_ancestry_fields($o)
    {   /* From Jen: 
        ancestry columns: I think authority strings may cause problems here- they vary for the same taxon, and I'm not sure what that does to the ancestry tree. 
        I'm going to suggest the crude solution of truncating the values in all 6 ancestry columns after the first word. 
        */
        $ranks = array('kingdom', 'phylum', 'class', 'order', 'family', 'genus');
        foreach($ranks as $rank) {
            if($str = trim($o->$rank)) {
                $arr = explode(" ", $str);
                $o->$rank = $arr[0];
            }
        }
        return $o;
    }
    private function fix_sciname($sci)
    {   /* From Jen: scientificName: If the first word in the namestring is in (), remove them- the parentheses, not the namestring */
        // EOL:10652191 (tribe) Borophagini G. G. Simpson, 1945
        // EOL:11584278 (undescribed)
        // EOL:37671125 (Multipeniata) sp. Ax & Schmidt-Rhaesa, 1992
        if(substr($sci,0,1) == '(') {
            if(preg_match("/\((.*?)\)/ims", $sci, $arr)) {
                $inside = $arr[1]; //str inside the parenthesis
                if(ctype_lower(substr($inside,0,1))) { //if first letter of str inside parenthesis is LOWER case
                    $sci = str_replace("($inside)", "", $sci);
                    $sci = Functions::remove_whitespace($sci);
                    return trim($sci);
                }
                else { //if first letter of str inside parenthesis is UPPER case
                    $sci = str_replace("($inside)", "$inside", $sci);
                    $sci = Functions::remove_whitespace($sci);
                    return trim($sci);
                }
            }
        }
        
        /* Cases were: one word, and starts with small letter
        EOL:62196	collomia	Plantae	Tracheophyta				
        EOL:62197	colubrina	Plantae	Tracheophyta				
        */
        if(ctype_lower(substr($sci,0,1))) $sci = ucfirst($sci);
        
        return $sci;
    }
    private function process_occurrence($meta)
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
            /*Array(
                [http://rs.tdwg.org/dwc/terms/occurrenceID] => 6c6b79090187369e36a81b8fc84b14f6_708
                [http://rs.tdwg.org/dwc/terms/taxonID] => EOL:2
            )*/
            
            // /* May 13, 2020: remove higher rank taxa
            $taxonID = $rec['http://rs.tdwg.org/dwc/terms/taxonID'];
            if(isset($this->remove_higher_rank_taxonIDs[$taxonID])) {
                $this->exclude['occurrenceID'][$rec['http://rs.tdwg.org/dwc/terms/occurrenceID']] = '';
                continue;
            }
            // */
            
            if(isset($this->exclude['taxonID'][$rec['http://rs.tdwg.org/dwc/terms/taxonID']])) {
                $this->exclude['occurrenceID'][$rec['http://rs.tdwg.org/dwc/terms/occurrenceID']] = '';
                continue;
            }
            
            if(isset($this->occurrence_id_2delete[$rec['http://rs.tdwg.org/dwc/terms/occurrenceID']])) continue;
            
            $this->linkage_oID_tID[$rec['http://rs.tdwg.org/dwc/terms/occurrenceID']] = $rec['http://rs.tdwg.org/dwc/terms/taxonID'];
            
            $o = new \eol_schema\Occurrence();
            $uris = array_keys($rec);
            foreach($uris as $uri) {
                $field = pathinfo($uri, PATHINFO_BASENAME);
                $o->$field = $rec[$uri];
            }
            if($o->taxonID == 'EOL:11584278') continue; //exclude scientificName = '(undescribed)'
            $this->archive_builder->write_object_to_file($o);
            // if($i >= 10) break; //debug only
        }
    }
    private function process_reference($meta)
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
            /*Array(
                [http://purl.org/dc/terms/identifier] => 49f4bc89592c2fab757bdfa036b47d44
                [http://eol.org/schema/reference/full_reference] => "Gigantorhynchida." <i>Wikipedia, The Free Encyclopedia</i>. 20 Mar 2013, 16:54 UTC. 26 Aug 2013 &lt;<a href="http://en.wikipedia.org/w/index.php?title=Gigantorhynchida&oldid=570187986">http://en.wikipedia.org/w/index.php?title=Gigantorhynchida&oldid=570187986</a>&gt;.
            )*/
            
            
            if(!isset($this->referenceIDs[$rec['http://purl.org/dc/terms/identifier']])) continue;
            
            $o = new \eol_schema\Reference();
            $uris = array_keys($rec);
            foreach($uris as $uri) {
                $field = pathinfo($uri, PATHINFO_BASENAME);
                $o->$field = $rec[$uri];
            }
            $this->archive_builder->write_object_to_file($o);
            // if($i >= 10) break; //debug only
        }
    }
    private function get_all_phylum_in_DH($path = false, $listOnly = false) //total rows = 2,724,672 | rows where EOLid is not blank = 2,237,550
    {
        if(!$path) $path = $this->eol_taxon_concept_names_tab;
        $i = 0;
        foreach(new FileIterator($path) as $line => $row) {
            if(!$row) continue;
            $i++;
            if($i == 1) $fields = explode("\t", $row);
            else {
                $rec = explode("\t", $row);
                $k = -1; $rek = array();
                foreach($fields as $field) {
                    $k++;
                    $rek[$field] = $rec[$k];
                }
                // print_r($rek); exit;
                /*Array(
                    [taxonID] => EOL-000000000001
                    [source] => trunk:1bfce974-c660-4cf1-874a-bdffbf358c19,NCBI:1
                    [furtherInformationURL] => 
                    [acceptedNameUsageID] => 
                    [parentNameUsageID] => 
                    [scientificName] => Life
                    [higherClassification] => 
                    [taxonRank] => clade
                    [taxonomicStatus] => valid
                    [taxonRemarks] => 
                    [datasetID] => trunk
                    [canonicalName] => Life
                    [EOLid] => 2913056
                    [EOLidAnnotations] => 
                    [Landmark] => 3
                )*/
                
                /* worked but only fixes Phylums. Below will fix all ancestry.
                if($rek['taxonRank'] == 'phylum') {
                    if($val = $rek['canonicalName']) $phylums[$val] = '';
                    else {
                        $val = Functions::canonical_form($rek['scientificName']);
                        $phylums[$val] = '';
                    }
                }
                */
                if(in_array($rek['taxonRank'], array('kingdom', 'phylum', 'class', 'order', 'family', 'genus'))) {
                    $rank = $rek['taxonRank'];
                    if($val = $rek['canonicalName']) $ancestry[$rank][$val] = '';
                    else {
                        $val = Functions::canonical_form($rek['scientificName']);
                        $ancestry[$rank][$val] = '';
                    }
                }
                
                /* this gets all higher-level taxa and its ranks */
                if(in_array($rek['taxonRank'], array('kingdom', 'phylum', 'class', 'order', 'family', 'genus'))) {
                    if($val = $rek['canonicalName']) $taxa[$val] = $rek['taxonRank'];
                    else {
                        $val = Functions::canonical_form($rek['scientificName']);
                        $taxa[$val] = $rek['taxonRank'];
                    }
                }
            }
        }
        return array('ancestry' => $ancestry, 'taxa' => $taxa);
    }
    /*================================================================= ENDS HERE ======================================================================*/
}
?>
