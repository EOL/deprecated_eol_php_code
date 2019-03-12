<?php
namespace php_active_record;
/* connector: [dwh_col_TRAM_803.php] - TRAM-803
*/
class DWH_CoL_API_20Feb2019
{
    function __construct($folder)
    {
        $this->resource_id = $folder;
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->taxon_ids = array();
        $this->debug = array();
        $this->alternative_names = array("synonym", "equivalent name", "in-part", "misspelling", "genbank synonym", "misnomer", "anamorph", "genbank anamorph", "teleomorph", "authority");

        //start TRAM-803 -----------------------------------------------------------
        $this->prune_further = array();
        $this->extension_path = DOC_ROOT."../cp/COL/2019-02-20-archive-complete/";
        $this->dwca['iterator_options'] = array('row_terminator' => "\n");
        $this->run = '';
        
        /* taxonomicStatus values as of Feb 20, 2019 dump: Array(
            [synonym] => 
            [accepted name] => 
            [ambiguous synonym] => 
            [misapplied name] => 
            [provisionally accepted name] => 
            [] => 
        )
        */
    }
    // ----------------------------------------------------------------- start TRAM-803 -----------------------------------------------------------------
    function start_CoLProtists()
    {
        $this->run = "Col Protists";
        self::main_CoLProtists();
        $this->archive_builder->finalize(TRUE);
        if($this->debug) {
            Functions::start_print_debug($this->debug, $this->resource_id);
        }
    }
    private function get_CLP_roots()
    {
        $params['spreadsheetID'] = '1wWLmuEGyNZ2a91rZKNxLvxKRM_EYV6WBbKxq6XXoqvI';
        $params['range']         = 'extractForCLP!A1:B10';
        $params['first_row_is_headerYN'] = true;
        $params['sought_fields'] = array('identifier');
        $parts = self::get_removed_branches_from_spreadsheet($params);
        $identifiers2inc = $parts['identifier'];
        echo "\nidentifiers2inc CLP total: ".count($identifiers2inc)."\n";

        // get the corresponding taxonID of this list of [identifier]s. --------------------------------------------------------
        $identifiers_taxonIDs = self::get_taxonID_from_identifer_values($identifiers2inc);
        // print_r($identifiers_taxonIDs); exit;
        /* sample $identifiers_taxonIDs
        Array(
            [3e82dc989115d4eba3f60aa727ed27ad] => Array
                    [0] => 54116272
            [15f4032e6086cbaf85add7bb0f7f2dd0] => Array
                    [0] => 54120102
            [7e9a2136364786573525abe99b4e6c8a] => Array
                    [0] => 54113942
            [b65c21e94995363e3587c88d0f1058d4] => Array
                    [0] => 54116909
            [993a87f1c3b2dd7c0db26028c5d38aea] => Array
                    [0] => 54116745
            [45355e80b0240c3ec5d2cb22d299cecc] => Array
                    [0] => 54114995
            [6725287d6288335b83ad2aec848a2931] => Array
                    [0] => 54122305
            [0d43b10e96b44a32def3545bdccf7c0a] => Array
                    [0] => 54122356
        */
        $include = array(); $include_identifier = array();
        foreach($identifiers_taxonIDs as $identifier => $taxonIDs) {
            if($taxonIDs) { //needed this validation since there might be a case where the identifier doesn't have a taxonID.
                foreach($taxonIDs as $taxonID) $include[$taxonID] = '';
            }
            $include_identifier[$identifier] = '';
        }
        return array('include' => $include, 'include_identifier' => $include_identifier);
    }
    private function main_CoLProtists()
    {
        $taxID_info = self::get_taxID_nodes_info();
        
        $removed_branches = self::pruneBytaxonID();
        echo "\nremoved_branches total A: ".count($removed_branches)."\n"; //exit("\n111\n");

        $ret = self::get_CLP_roots();
        $include                  = $ret['include'];
        $this->include_identifier = $ret['include_identifier'];
        // print_r($include); exit("\nsample include\n");
        /* old
        $include[42984770] = "Ciliophora";
        $include[42990646] = "Oomycota";
        $include[42981251] = "Polycystina";
        $include[42985937] = "Eccrinida";
        $include[42985691] = "Microsporidia";
        $include[42983291] = "Mycetozoa";
        $include[42993626] = "Chaetocerotaceae";
        $include[42993677] = "Naviculaceae";
        new values:
        Array(
            [54116272] => 
            [54120102] => 
            [54113942] => 
            [54116909] => 
            [54116745] => 
            [54114995] => 
            [54122305] => 
            [54122356] => 
        )*/
        $this->include = $include;
        
        $meta = self::get_meta_info();
        $i = 0; $filtered_ids = array();
        echo "\nStart main process...Col Protists...\n";
        foreach(new FileIterator($this->extension_path.$meta['taxon_file'], false, true, @$this->dwc['iterator_options']) as $line => $row) { //2nd and 3rd param; false and true respectively are default values
            $i++;
            if(($i % 500000) == 0) echo "\n count:[".number_format($i)."] ";
            if($meta['ignoreHeaderLines'] && $i == 1) continue;
            if(!$row) continue;
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta['fields'] as $field) {
                $rec[$field] = $tmp[$k];
                $k++;
            }
            $rec = array_map('trim', $rec);
            // print_r($rec); exit;
            /* good debug
            if(isset($include[$rec['taxonID']])) print_r($rec);
            */
            /*Array(
                [taxonID] => 10145857
                [scientificNameID] => Cil-CILI00024223
                [acceptedNameUsageID] => 
                [parentNameUsageID] => 42998474
                [scientificName] => Amphileptus hirsutus Dumas, 1930
                [kingdom] => Chromista
                [phylum] => Ciliophora
                [class] => Gymnostomatea
                [order] => Pleurostomatida
                [family] => Amphileptidae
                [genus] => Amphileptus
                [taxonRank] => species
                [scientificNameAuthorship] => Dumas, 1930
                [taxonomicStatus] => accepted name
                [taxonRemarks] => 
            )*/
            $will_cont = false;
            
            /* This is commented because: there is Ciliophora that is genus, must be excluded. The Phylum Ciliophora is the one to be included. So name comparison was commented out.
            $ranks2check = array('kingdom', 'phylum', 'class', 'order', 'family', 'genus');
            foreach($ranks2check as $rank2check) {
                $sciname = $rec[$rank2check];
                if(in_array($sciname, $include)) $will_cont = true;
            }
            */
            
            $ancestry = self::get_ancestry_of_taxID($rec['taxonID'], $taxID_info);
            if(self::an_id_from_ancestry_is_part_of_a_removed_branch($ancestry, $include)) $will_cont = true; //this will actually include what is in the branch
            
            //==============================================================================
            if($will_cont) {
                if(self::an_id_from_ancestry_is_part_of_a_removed_branch($ancestry, $removed_branches)) {
                    $filtered_ids[$rec['taxonID']] = '';
                    $removed_branches[$rec['taxonID']] = '';
                    continue;
                }
                else $inclusive_taxon_ids[$rec['taxonID']] = '';
            }
            //==============================================================================
        } //end loop
        echo "\ntotal ids: ".count($inclusive_taxon_ids)."\n";
        
        //start 2nd loop
        $i = 0; echo "\nStart main process 2...CoL Protists...\n";
        foreach(new FileIterator($this->extension_path.$meta['taxon_file'], false, true, @$this->dwc['iterator_options']) as $line => $row) { //2nd and 3rd param; false and true respectively are default values
            $i++;
            if(($i % 500000) == 0) echo "\n count:[".number_format($i)."] ";
            if($meta['ignoreHeaderLines'] && $i == 1) continue;
            if(!$row) continue;
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta['fields'] as $field) {
                $rec[$field] = $tmp[$k];
                $k++;
            }
            $rec = array_map('trim', $rec);
            if(isset($inclusive_taxon_ids[$rec['taxonID']])) {
                if(isset($filtered_ids[$rec['taxonID']])) continue;
                if(isset($filtered_ids[$rec['acceptedNameUsageID']])) continue;
                if(isset($filtered_ids[$rec['parentNameUsageID']])) continue;
                if(isset($removed_branches[$rec['taxonID']])) continue;
                if(isset($removed_branches[$rec['acceptedNameUsageID']])) continue;
                if(isset($removed_branches[$rec['parentNameUsageID']])) continue;
                
                $ancestry = self::get_ancestry_of_taxID($rec['taxonID'], $taxID_info);
                if(self::an_id_from_ancestry_is_part_of_a_removed_branch($ancestry, $removed_branches)) {
                    continue;
                }
                $rec = self::replace_taxonID_with_identifier($rec, $taxID_info); //new - replace [taxonID] with [identifier]
                self::write_taxon_DH($rec);
            }
        }
    }
    function start_tram_803()
    {
        /* test
        // 10145857 Amphileptus hirsutus Dumas, 1930
        // 10147309 Aspidisca binucleata Kahl
        $taxID_info = self::get_taxID_nodes_info();
        $ancestry = self::get_ancestry_of_taxID(10145857, $taxID_info); print_r($ancestry);
        $ancestry = self::get_ancestry_of_taxID(10147309, $taxID_info); print_r($ancestry);
        exit("\n-end tests-\n");
        */
        /*
        $taxID_info = self::get_taxID_nodes_info();
        $parts = self::get_removed_branches_from_spreadsheet();
        $removed_branches = $parts['removed_brances'];
        $one_word_names = $parts['one_word_names'];
        $ids = array(42987761,42987788,42987780,42987793,42987792,42987781,42987798,42987775,42987777,40160866,40212453);
        foreach($ids as $id) {
            $ancestry = self::get_ancestry_of_taxID($id, $taxID_info);
            echo "\n ancestry of [$id]:"; print_r($ancestry);
            if(self::an_id_from_ancestry_is_part_of_a_removed_branch($ancestry, $removed_branches)) echo "\n[$id] removed\n";
            else                                                                                    echo "\n[$id] NOT removed\n";
        }
        exit("\n-end tests-\n");
        */
        
        self::main_tram_803(); //exit("\nstop muna\n");
        $this->archive_builder->finalize(TRUE);
        if($this->debug) {
            Functions::start_print_debug($this->debug, $this->resource_id);
        }
    }
    private function pruneBytaxonID()
    {
        $params['spreadsheetID'] = '1wWLmuEGyNZ2a91rZKNxLvxKRM_EYV6WBbKxq6XXoqvI';
        $params['range']         = 'pruneBytaxonID!A1:C50';
        $params['first_row_is_headerYN'] = true;
        $params['sought_fields'] = array('taxonID');
        $parts = self::get_removed_branches_from_spreadsheet($params);
        $removed_branches = $parts['taxonID']; // print_r($removed_branches);
        return $removed_branches;
    }
    private function main_tram_803()
    {
        $taxID_info = self::get_taxID_nodes_info(); //un-comment in real operation
        /* #1. Remove branches from the PruneBytaxonID list based on their taxonID: */
        $removed_branches = self::pruneBytaxonID();
        echo "\nremoved_branches total A: ".count($removed_branches)."\n"; //exit("\n111\n");
        
        /* #2. Create the COL taxon set by pruning the branches from the pruneForCOL list: */
        //1st step: get the list of [identifier]s. --------------------------------------------------------
        $params['spreadsheetID'] = '1wWLmuEGyNZ2a91rZKNxLvxKRM_EYV6WBbKxq6XXoqvI';
        $params['range']         = 'pruneForCOL!A1:A505';
        $params['first_row_is_headerYN'] = true;
        $params['sought_fields'] = array('identifier');
        $parts = self::get_removed_branches_from_spreadsheet($params);
        $identifiers = $parts['identifier']; // print_r($identifiers);
        echo "\nidentifiers total: ".count($identifiers)."\n";  //exit;

        //2nd step: get the corresponding taxonID of this list of [identifier]s. --------------------------------------------------------
        $identifiers_taxonIDs = self::get_taxonID_from_identifer_values($identifiers);
        /* sample $identifiers_taxonIDs
        [80c3f23a7edaef0c690f5fa89206db80] => Array(
                [0] => 54305335
                [1] => 54305340
            )
        [1fb14375baf8c0e97b78da7cf24933ca] => Array(
                [0] => 54328762
            )
        */
        foreach($identifiers_taxonIDs as $identifier => $taxonIDs) {
            if($taxonIDs) { //needed this validation since there is one case where the identifier doesn't have a taxonID.
                foreach($taxonIDs as $taxonID) $removed_branches[$taxonID] = '';
            }
        }
        // print_r($removed_branches);
        echo "\nremoved_branches total B: ".count($removed_branches)."\n"; //exit("\n222\n");
        // end #2 -----------------------------------------------------------------------------------------------------------------------------------------------
        
        
        $meta = self::get_meta_info();
        $i = 0; $filtered_ids = array();
        echo "\nStart main process...main CoL DH...\n";
        foreach(new FileIterator($this->extension_path.$meta['taxon_file'], false, true, @$this->dwc['iterator_options']) as $line => $row) { //2nd and 3rd param; false and true respectively are default values
            $i++;
            if(($i % 500000) == 0) echo "\n count:[".number_format($i)."] ";
            if($meta['ignoreHeaderLines'] && $i == 1) continue;
            if(!$row) continue;
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta['fields'] as $field) {
                $rec[$field] = $tmp[$k];
                $k++;
            }
            $rec = array_map('trim', $rec);
            //start filter
            if(isset($identifiers_taxonIDs[$rec['identifier']])) continue;
            // eli added start ----------------------------------------------------------------------------
            /* working in TRAM_797
            $ranks2check = array('kingdom', 'phylum', 'class', 'order', 'family', 'genus');
            $vcont = true;
            foreach($ranks2check as $rank2check) {
                $sciname = $rec[$rank2check];
                if(isset($one_word_names[$sciname])) {
                    $filtered_ids[$rec['taxonID']] = '';
                    $removed_branches[$rec['taxonID']] = '';
                    $vcont = false;
                }
            }
            if(!$vcont) continue; //next taxon
            */
            // eli added end ----------------------------------------------------------------------------
            
            // if($rec['taxonomicStatus'] == "accepted name") {
                /* Remove branches */
                $ancestry = self::get_ancestry_of_taxID($rec['taxonID'], $taxID_info);
                if(self::an_id_from_ancestry_is_part_of_a_removed_branch($ancestry, $removed_branches)) {
                    // $this->debug['taxon where an id in its ancestry is included among removed branches'][$rec['taxonID']] = ''; //not usefule anymore
                    $filtered_ids[$rec['taxonID']] = '';
                    $removed_branches[$rec['taxonID']] = '';
                    /* debug
                    if($rec['taxonID'] == 42987761) {
                        print_r($rec); exit("\n stopped 200 \n");
                    }
                    */
                    continue;
                }
            // }
        } //end loop

        echo "\nStart main process 2...main CoL DH...\n"; $i = 0;
        foreach(new FileIterator($this->extension_path.$meta['taxon_file'], false, true, @$this->dwc['iterator_options']) as $line => $row) { //2nd and 3rd param; false and true respectively are default values
            $i++; if(($i % 500000) == 0) echo "\n count:[".number_format($i)."] ";
            if($meta['ignoreHeaderLines'] && $i == 1) continue;
            if(!$row) continue;
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta['fields'] as $field) {
                $rec[$field] = $tmp[$k];
                $k++;
            }
            $rec = array_map('trim', $rec);
            if(isset($identifiers_taxonIDs[$rec['identifier']])) continue;
            
            /*Array()*/
            
            if(isset($filtered_ids[$rec['taxonID']])) continue;
            if(isset($filtered_ids[$rec['acceptedNameUsageID']])) continue;
            if(isset($filtered_ids[$rec['parentNameUsageID']])) continue;

            if(isset($removed_branches[$rec['taxonID']])) continue;
            if(isset($removed_branches[$rec['acceptedNameUsageID']])) continue;
            if(isset($removed_branches[$rec['parentNameUsageID']])) continue;
            
            // print_r($rec); exit("\nexit muna\n");
            /*Array(
                [taxonID] => 316502
                [identifier] => 
                [datasetID] => 26
                [datasetName] => ScaleNet in Species 2000 & ITIS Catalogue of Life: 20th February 2019
                [acceptedNameUsageID] => 316423
                [parentNameUsageID] => 
                [taxonomicStatus] => synonym
                [taxonRank] => species
                [verbatimTaxonRank] => 
                [scientificName] => Canceraspis brasiliensis Hempel, 1934
                [kingdom] => Animalia
                [phylum] => 
                [class] => 
                [order] => 
                [superfamily] => 
                [family] => 
                [genericName] => Canceraspis
                [genus] => Limacoccus
                [subgenus] => 
                [specificEpithet] => brasiliensis
                [infraspecificEpithet] => 
                [scientificNameAuthorship] => Hempel, 1934
                [source] => 
                [namePublishedIn] => 
                [nameAccordingTo] => 
                [modified] => 
                [description] => 
                [taxonConceptID] => 
                [scientificNameID] => Coc-100-7
                [references] => http://www.catalogueoflife.org/col/details/species/id/6a3ba2fef8659ce9708106356d875285/synonym/3eb3b75ad13a5d0fbd1b22fa1074adc0
                [isExtinct] => 
            )*/
            
            // if($rec['taxonomicStatus'] == "accepted name") {
                /* Remove branches */
                $ancestry = self::get_ancestry_of_taxID($rec['taxonID'], $taxID_info);
                if(self::an_id_from_ancestry_is_part_of_a_removed_branch($ancestry, $removed_branches)) {
                    // $this->debug['taxon where an id in its ancestry is included among removed branches'][$rec['taxonID']] = ''; //good debug
                    continue;
                }
            // }
            
            $rec = self::replace_taxonID_with_identifier($rec, $taxID_info); //new - replace [taxonID] with [identifier]
            self::write_taxon_DH($rec);
        } //end loop
    }
    private function replace_taxonID_with_identifier($rec, $taxID_info)
    {
        if(in_array($rec['taxonomicStatus'], array("accepted name","provisionally accepted name"))) {
            if($val = $taxID_info[$rec['taxonID']]['i'])            $rec['taxonID'] = $val;
            else {
                print_r($rec); exit("\nInvestigate: no [identifier] for [taxonID]\n");
            }
            if($val = $taxID_info[$rec['parentNameUsageID']]['i'])  $rec['parentNameUsageID'] = $val;
            else {
                print_r($rec); exit("\nInvestigate: no [identifier] for [parentNameUsageID]\n");
            }
            if($accepted_id = @$rec['acceptedNameUsageID']) {
                if($val = $taxID_info[$accepted_id]['i'])  $rec['acceptedNameUsageID'] = $val;
                else {
                    print_r($rec); exit("\nInvestigate: no [identifier] for [acceptedNameUsageID]\n");
                }
            }
        }
        else {
            if($val = $taxID_info[$rec['taxonID']]['i'])    $rec['taxonID'] = $val;
            if($parent_id = @$rec['parentNameUsageID']) {
                if($val = $taxID_info[$parent_id]['i'])     $rec['parentNameUsageID'] = $val;
            }
            if($accepted_id = @$rec['acceptedNameUsageID']) {
                if($val = $taxID_info[$accepted_id]['i'])   $rec['acceptedNameUsageID'] = $val;
            }
        }
        return $rec;
    }
    private function replace_NotAssigned_name($rec)
    {   /*42981143 -- Not assigned -- order
        We would want to change the scientificName value to “Order not assigned” */
        $sciname = $rec['scientificName'];
        if($rank = $rec['taxonRank']) $sciname = ucfirst(strtolower($rank))." not assigned";
        return $sciname;
    }
    private function get_taxID_nodes_info()
    {
        echo "\nGenerating taxID_info...";
        $final = array(); $i = 0;
        $meta = self::get_meta_info();
        foreach(new FileIterator($this->extension_path.$meta['taxon_file'], false, true, @$this->dwc['iterator_options']) as $line => $row) { //2nd and 3rd param; false and true respectively are default values
            $i++; if(($i % 500000) == 0) echo "\n count:[".number_format($i)."] ";
            if($meta['ignoreHeaderLines'] && $i == 1) continue;
            if(!$row) continue;
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta['fields'] as $field) {
                $rec[$field] = $tmp[$k];
                $k++;
            }
            $rec = array_map('trim', $rec);
            // print_r($rec); exit("\nelix\n");
            /*Array(
                [taxonID] => 316502
                [identifier] => 
                [datasetID] => 26
                [datasetName] => ScaleNet in Species 2000 & ITIS Catalogue of Life: 20th February 2019
                [acceptedNameUsageID] => 316423
                [parentNameUsageID] => 
                [taxonomicStatus] => synonym
                [taxonRank] => species
                [verbatimTaxonRank] => 
                [scientificName] => Canceraspis brasiliensis Hempel, 1934
                [kingdom] => Animalia
                [phylum] => 
                [class] => 
                [order] => 
                [superfamily] => 
                [family] => 
                [genericName] => Canceraspis
                [genus] => Limacoccus
                [subgenus] => 
                [specificEpithet] => brasiliensis
                [infraspecificEpithet] => 
                [scientificNameAuthorship] => Hempel, 1934
                [source] => 
                [namePublishedIn] => 
                [nameAccordingTo] => 
                [modified] => 
                [description] => 
                [taxonConceptID] => 
                [scientificNameID] => Coc-100-7
                [references] => http://www.catalogueoflife.org/col/details/species/id/6a3ba2fef8659ce9708106356d875285/synonym/3eb3b75ad13a5d0fbd1b22fa1074adc0
                [isExtinct] => 
            )*/
            
            // if($rec['taxonomicStatus'] == "accepted name")
            $final[$rec['taxonID']] = array("pID" => $rec['parentNameUsageID'], 'r' => $rec['taxonRank'], 'i' => $rec['identifier']);
            
            // $temp[$rec['taxonomicStatus']] = ''; //debug
            /* debug
            if($rec['taxonID'] == "xxx") {
                print_r($rec); exit;
            }
            */
            /* debug
            if($rec['scientificName'] == "Not assigned") {
                print_r($rec); exit;
            }
            */
        }
        // print_r($temp); exit; //debug
        return $final;
    }
    private function get_ancestry_of_taxID($tax_id, $taxID_info)
    {   /* Array(
            [1] => Array(
                    [pID] => 1
                    [r] => no rank
                    [dID] => 8
                )
        )*/
        $final = array();
        $final[] = $tax_id;
        while($parent_id = @$taxID_info[$tax_id]['pID']) {
            if(!in_array($parent_id, $final)) $final[] = $parent_id;
            else {
                if($parent_id == 1) return $final;
                else {
                    print_r($final);
                    exit("\nInvestigate $parent_id already in array.\n");
                }
            }
            $tax_id = $parent_id;
        }
        return $final;
    }
    private function write_taxon_DH($rec)
    {   //from NCBI ticket: a general rule
        /* One more thing: synonyms and other alternative names should not have parentNameUsageIDs. In general, if a taxon has an acceptedNameUsageID it should not also have a parentNameUsageID. 
        So in this specific case, we want acceptedNameUsageID's only if name class IS scientific name. */
        if($rec['acceptedNameUsageID']) $rec['parentNameUsageID'] = '';
        
        if($rec['scientificName'] == "Not assigned") $rec['scientificName'] = self::replace_NotAssigned_name($rec);
        
        /* From Katja: If that's not easy to do, we can also change the resource files to use "unclassified" instead of "unplaced" for container taxa. 
        I can do this for the resources under my control (trunk & ONY). You would have to do it for COL, CLP, ictv & WOR. */
        $rec['scientificName'] = str_ireplace("Unplaced", "unclassified", $rec['scientificName']);
        
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID                 = $rec['taxonID'];
        $taxon->parentNameUsageID       = $rec['parentNameUsageID'];
        $taxon->taxonRank               = $rec['taxonRank'];
        $taxon->scientificName          = $rec['scientificName'];
        $taxon->taxonomicStatus         = $rec['taxonomicStatus'];
        $taxon->acceptedNameUsageID     = $rec['acceptedNameUsageID'];
        // $taxon->furtherInformationURL   = $rec['furtherInformationURL']; //removed from 'Feb 20, 2019' dump
        
        if($this->run == "Col Protists") { //Col Protists will be a separate resource file with 8 independent root taxa. 
            if(isset($this->include_identifier[$rec['taxonID']])) $taxon->parentNameUsageID = '';
        }
        
        $this->debug['acceptedNameUsageID'][$rec['acceptedNameUsageID']] = '';
        
        /* optional, I guess
        $taxon->scientificNameID    = $rec['scientificNameID'];
        $taxon->nameAccordingTo     = $rec['nameAccordingTo'];
        $taxon->kingdom             = $rec['kingdom'];
        $taxon->phylum              = $rec['phylum'];
        $taxon->class               = $rec['class'];
        $taxon->order               = $rec['order'];
        $taxon->family              = $rec['family'];
        $taxon->genus               = $rec['genus'];
        $taxon->subgenus            = $rec['subgenus'];
        $taxon->specificEpithet     = $rec['specificEpithet'];
        $taxon->infraspecificEpithet        = $rec['infraspecificEpithet'];
        $taxon->scientificNameAuthorship    = $rec['scientificNameAuthorship'];
        $taxon->taxonRemarks        = $rec['taxonRemarks'];
        $taxon->modified            = $rec['modified'];
        $taxon->datasetID           = $rec['datasetID'];
        $taxon->datasetName         = $rec['datasetName'];
        */

        if(!isset($this->taxon_ids[$taxon->taxonID])) {
            $this->archive_builder->write_object_to_file($taxon);
            $this->taxon_ids[$taxon->taxonID] = '';
        }
    }
    private function an_id_from_ancestry_is_part_of_a_removed_branch($ancestry, $removed_branches)
    {
        foreach($ancestry as $id) {
            /* use isset() instead
            if(in_array($id, $removed_branches)) return true;
            */
            if(isset($removed_branches[$id])) return true;
        }
        return false;
    }
    private function get_removed_branches_from_spreadsheet($params = false)
    {
        $final = array();
        $rows = Functions::get_google_sheet_using_GoogleClientAPI($params); //print_r($rows);
        if(@$params['first_row_is_headerYN']) $fields = $rows[0];
        else                                  exit("\nNo headers in spreadsheet.\n");
        $i = -1;
        foreach($rows as $items) {
            $i++; if($i == 0) continue;
            $rec = array();
            $k = 0;
            foreach($items as $item) {
                $rec[$fields[$k]] = $item;
                $k++;
            }
            // print_r($rec); //exit;
            /* e.g. $rec
            Array(
                [taxonID] => 6922677
                [identifier] => 66cd79222c1eb0f16349f503173c63ba
                [scientificName] => Amphichaeta americana Chen, 1944
            )
            */
            foreach($rec as $key => $val) {
                if(in_array($key, $params['sought_fields'])) {
                    if($key && $val) $final[$key][$val] = '';
                }
            }
        }
        return $final;
        /* if google spreadsheet suddenly becomes offline, use this: Array() */
    }
    private function more_ids_to_remove()
    {
        $a = array();
        $b = array();
        $c = array_merge($a, $b);
        return array_unique($c);
    }
    private function get_meta_info($row_type = false)
    {
        require_library('connectors/DHSourceHierarchiesAPI'); $func = new DHSourceHierarchiesAPI();
        $meta = $func->analyze_eol_meta_xml($this->extension_path."meta.xml", $row_type); //2nd param $row_type is rowType in meta.xml
        // if($GLOBALS['ENV_DEBUG']) print_r($meta); //good debug
        return $meta;
    }
    private function get_taxonID_from_identifer_values($identifiers)
    {
        echo "\nGenerating taxID_info...";
        $final = array(); $i = 0; $this->debug['elix'] = 0;
        $meta = self::get_meta_info();
        foreach(new FileIterator($this->extension_path.$meta['taxon_file'], false, true, @$this->dwc['iterator_options']) as $line => $row) { //2nd and 3rd param; false and true respectively are default values
            $i++; if(($i % 500000) == 0) echo "\n count:[".number_format($i)."] ";
            if($meta['ignoreHeaderLines'] && $i == 1) continue;
            if(!$row) continue;
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta['fields'] as $field) {
                $rec[$field] = $tmp[$k];
                $k++;
            }
            $rec = array_map('trim', $rec);
            // print_r($rec); exit("\nelix2\n");
            /*Array(
                [taxonID] => 316502
                [identifier] => 
                ...
            )*/
            
            /* debug
            if(in_array($rec['taxonID'], array('54116638','54126383'))) print_r($rec);
            */
            
            if(isset($identifiers[$rec['identifier']])) {
                $identifiers[$rec['identifier']][] = $rec['taxonID'];
                // $this->debug['elix']++;
            }
            
        }
        // print_r($identifiers); print_r($this->debug['elix']); exit("\n".count($identifiers)."\nyyy\n"); //good debug - check if all identifiers were paired with a taxonID.
        return $identifiers;
    }
    // ----------------------------------------------------------------- end TRAM-803 -----------------------------------------------------------------
    /*
    private function get_tax_ids_from_taxon_tab_working()
    {
        echo "\n get taxonIDs from taxon_working.tab\n";
        require_library('connectors/DWCADiagnoseAPI');
        $func = new DWCADiagnoseAPI();
        $url = CONTENT_RESOURCE_LOCAL_PATH . $this->resource_id."_working" . "/taxon_working.tab";
        $suggested_fields = explode("\t", "taxonID	furtherInformationURL	referenceID	acceptedNameUsageID	parentNameUsageID	scientificName	taxonRank	taxonomicStatus"); //taxonID is what is important here.
        $var = $func->get_fields_from_tab_file($this->resource_id, array("taxonID"), $url, $suggested_fields, false); //since there is $url, the last/5th param is no longer needed, set to false.
        return $var['taxonID'];
    }
    */
}
?>