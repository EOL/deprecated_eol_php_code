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
            [accepted name] => 
            [provisionally accepted name] => 
            [] => 
            [synonym] => 
            [ambiguous synonym] => 
            [misapplied name] => 
        )
        From Katja:
        Since we are only using COL taxa with statuses "accepted name" or "provisionally accepted name" or blank for the DH, 
        we should actually removed taxa with status "synonym," "ambiguous synonym" or "misapplied name" before we do anything else with this data set. 
        Sorry I didn't think to make this more explicit in the workflow above. I don't think it will do harm if you remove these taxa now. 
        */
        $this->unclassified_id_increments = 0;
    }
    // ----------------------------------------------------------------- start TRAM-803 -----------------------------------------------------------------
    function start_CoLProtists()
    {
        $this->run = "Col Protists";
        self::main_CoLProtists();
        $this->archive_builder->finalize(TRUE);
        if($this->debug) Functions::start_print_debug($this->debug, $this->resource_id);
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
        $identifiers_taxonIDs = self::get_taxonID_from_identifer_values($identifiers2inc); // print_r($identifiers_taxonIDs); exit;
        /* sample $identifiers_taxonIDs
        Array(
            [3e82dc989115d4eba3f60aa727ed27ad] => Array[0] => 54116272
            [15f4032e6086cbaf85add7bb0f7f2dd0] => Array[0] => 54120102
            [7e9a2136364786573525abe99b4e6c8a] => Array[0] => 54113942
            [b65c21e94995363e3587c88d0f1058d4] => Array[0] => 54116909
            [993a87f1c3b2dd7c0db26028c5d38aea] => Array[0] => 54116745
            [45355e80b0240c3ec5d2cb22d299cecc] => Array[0] => 54114995
            [6725287d6288335b83ad2aec848a2931] => Array[0] => 54122305
            [0d43b10e96b44a32def3545bdccf7c0a] => Array[0] => 54122356 */
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
        $include                  = $ret['include']; // print_r($include); exit("\nsample include\n");
        $this->include_identifier = $ret['include_identifier'];
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
            if(in_array($rec['taxonomicStatus'], array("synonym", "ambiguous synonym", "misapplied name"))) continue;
            
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
            if(in_array($rec['taxonomicStatus'], array("synonym", "ambiguous synonym", "misapplied name"))) continue;
            
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
    private function get_taxID_nodes_info($meta = false, $extension_path = false)
    {
        if(!$meta) $meta = self::get_meta_info();
        if(!$extension_path) $extension_path = $this->extension_path;
        // print_r($meta); exit;
        echo "\nGenerating taxID_info...";
        $final = array(); $i = 0;
        foreach(new FileIterator($extension_path.$meta['taxon_file'], false, true, @$this->dwc['iterator_options']) as $line => $row) { //2nd and 3rd param; false and true respectively are default values
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
            /*Array( possible record
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
            if(isset($rec['identifier'])) $final[$rec['taxonID']] = array("pID" => $rec['parentNameUsageID'], 'r' => $rec['taxonRank'], 'i' => $rec['identifier']);
            else $final[$rec['taxonID']] = array("pID" => $rec['parentNameUsageID'], 'n' => $rec['scientificName'], 'r' => $rec['taxonRank'], 's' => $rec['taxonomicStatus']);
            /*Array( another possible record
                [taxonID] => fc0886d15759a01525b1469534189bb5
                [acceptedNameUsageID] => 
                [parentNameUsageID] => d2a21892b23f5453d7655b082869cfca
                [scientificName] => Bryometopus alekperovi Foissner, 1998
                [taxonRank] => species
                [taxonomicStatus] => accepted name
            )*/
            
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
        /* for DUPLICATE TAXA process...
        Find duplicate taxa where taxonRank:species      AND the following fields all have the same value: parentNameUsageID, genus, specificEpithet.
        Find duplicate taxa where taxonRank:infraspecies AND the following fields all have the same value: parentNameUsageID, genus, specificEpithet, infraspecificEpithet.
        */
        if($val = @$rec['genus'])                       $taxon->genus = $val;
        if($val = @$rec['specificEpithet'])             $taxon->specificEpithet = $val;
        if($val = @$rec['infraspecificEpithet'])        $taxon->infraspecificEpithet = $val;
        if($val = @$rec['scientificNameAuthorship'])    $taxon->scientificNameAuthorship = $val;
        if($val = @$rec['verbatimTaxonRank'])           $taxon->verbatimTaxonRank = $val;
        if($val = @$rec['subgenus'])                    $taxon->subgenus = $val;
        if($val = @$rec['taxonRemarks'])                $taxon->taxonRemarks = $val;            //for taxonRemarks but for a later stage
        if($val = @$rec['isExtinct'])                   $taxon->taxonRemarks = "isExtinct:$val";//for taxonRemarks but for an earlier stage

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
    private function get_meta_info($row_type = false, $extension_path = false)
    {
        if(!$extension_path) $extension_path = $this->extension_path; //default extension_path to use
        require_library('connectors/DHSourceHierarchiesAPI'); $func = new DHSourceHierarchiesAPI();
        $meta = $func->analyze_eol_meta_xml($extension_path."meta.xml", $row_type); //2nd param $row_type is rowType in meta.xml
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
    //=========================================================================== start DUPLICATE TAXA letter A ==================================
    public function duplicate_process_A($what)
    {
        if($what == 'COL') $extension_path = CONTENT_RESOURCE_LOCAL_PATH."Catalogue_of_Life_DH_step1/";          //for COL
        if($what == 'CLP') $extension_path = CONTENT_RESOURCE_LOCAL_PATH."Catalogue_of_Life_Protists_DH_step2/"; //for CLP
        $meta = self::get_meta_info(false, $extension_path); //meta here is now the newly (temporary) created DwCA
        
        /*step 1: get the remove_keep IDs */
        $params['spreadsheetID'] = '1wWLmuEGyNZ2a91rZKNxLvxKRM_EYV6WBbKxq6XXoqvI';
        $params['range']         = 'mergeForCOL!A1:D470';
        $params['first_row_is_headerYN'] = true;
        $params['sought_fields'] = array('Keep identifier', 'Remove identifier');
        $remove_keep_ids = self::get_remove_keep_ids_from_spreadsheet($params); //print_r($remove_keep_ids);
        echo "\nremove_keep_ids total: ".count($remove_keep_ids)."\n";
        
        //start main process
        $i = 0;
        foreach(new FileIterator($extension_path.$meta['taxon_file']) as $line => $row) {
            $i++; if(($i % 100000) == 0) echo "\n".number_format($i);
            if($meta['ignoreHeaderLines'] && $i == 1) continue;
            if(!$row) continue;
            $row = Functions::conv_to_utf8($row); //possibly to fix special chars
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta['fields'] as $field) {
                if(!$field) continue;
                $rec[$field] = $tmp[$k];
                $k++;
            }
            $orig_rec = $rec;
            // print_r($rec); exit;
            /*Array(
                [taxonID] => 316502
                [acceptedNameUsageID] => 6a3ba2fef8659ce9708106356d875285
                [parentNameUsageID] => 
                [scientificName] => Canceraspis brasiliensis Hempel, 1934
                [taxonRank] => species
                [taxonomicStatus] => synonym
            )*/
            //----------------------------------------------------------------------------------------------------------------------------start process
            /* if taxonID is a remove_id then ignore rec */
            $taxonID = $rec['taxonID'];
            if(isset($remove_keep_ids[$taxonID])) continue;
            //----------------------------------------------------------------------------------------------------------------------------
            /* if parentNameUsageID is a remove_id then replace the parentNameUsageID with the respective keep_id; */
            $parent_id = $rec['parentNameUsageID'];
            if(isset($remove_keep_ids[$parent_id])) {
                // print_r($rec);
                $new_parent_id = $remove_keep_ids[$parent_id];
                $rec['parentNameUsageID'] = $new_parent_id;
                // print_r($rec); exit("\nold and new if parent_id is a remove_id\n");
            }
            //----------------------------------------------------------------------------------------------------------------------------
            self::write_taxon_DH($rec);
        }
        $this->archive_builder->finalize(TRUE);
    }
    private function get_remove_keep_ids_from_spreadsheet($params = false)
    {
        $rows = Functions::get_google_sheet_using_GoogleClientAPI($params); //print_r($rows);
        if(@$params['first_row_is_headerYN']) $fields = $rows[0];
        else                                  exit("\nNo headers in spreadsheet.\n");
        $final = array(); $i = -1;
        foreach($rows as $items) {
            $i++; if($i == 0) continue;
            $rec = array();
            $k = 0;
            foreach($items as $item) {
                $rec[$fields[$k]] = $item;
                $k++;
            }
            // print_r($rec); //exit;
            /*[464] => Array(
                        [Keep identifier] => 503a1ade20288fdd120c41da2f442c0d
                        [Keep scientificName] => Xestobium
                        [Remove identifier] => af662112a1bbc3e97d9162e72cc1ed50
                        [Remove scientificName] => Xestobium
                    )*/
            $final[$rec['Remove identifier']] = $rec['Keep identifier']; //use as best orientation which is left and which is on the right.
        }
        return $final;
    }
    //=========================================================================== end DUPLICATE TAXA letter A ====================================
    
    //=========================================================================== start DUPLICATE TAXA letter B ==================================
    public function duplicate_process_B($what)
    {
        if($what == 'COL') $extension_path = CONTENT_RESOURCE_LOCAL_PATH."Catalogue_of_Life_DH_step2/";          //for COL
        if($what == 'CLP') $extension_path = CONTENT_RESOURCE_LOCAL_PATH."Catalogue_of_Life_Protists_DH_step3/"; //for CLP
        $meta = self::get_meta_info(false, $extension_path); //meta here is now the newly (temporary) created DwCA
        //step 1: format array records to see which are duplicate taxa
        $i = 0;
        foreach(new FileIterator($extension_path.$meta['taxon_file']) as $line => $row) {
            $i++; if(($i % 500000) == 0) echo "\n".number_format($i);
            if($meta['ignoreHeaderLines'] && $i == 1) continue;
            if(!$row) continue;
            $row = Functions::conv_to_utf8($row); //possibly to fix special chars
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta['fields'] as $field) {
                if(!$field) continue;
                $rec[$field] = $tmp[$k];
                $k++;
            }
            // print_r($rec); exit;
            /*Array(
                [taxonID] => fc0886d15759a01525b1469534189bb5
                [acceptedNameUsageID] => 
                [parentNameUsageID] => d2a21892b23f5453d7655b082869cfca
                [scientificName] => Bryometopus alekperovi Foissner, 1998
                [genus] => Bryometopus
                [specificEpithet] => alekperovi
                [taxonRank] => species
                [scientificNameAuthorship] => Foissner, 1998
                [taxonomicStatus] => accepted name
                [infraspecificEpithet] => 
                [verbatimTaxonRank] => 
                [subgenus] => 
            )*/
            /*SPECIES
            Find duplicate taxa where taxonRank:species AND the following fields all have the same value: parentNameUsageID, genus, specificEpithet.
            INFRASPECIES
            Find duplicate taxa where taxonRank:infraspecies AND the following fields all have the same value: parentNameUsageID, genus, specificEpithet, infraspecificEpithet.
            */
            
            if(!in_array($rec['taxonomicStatus'], array("accepted name", "provisionally accepted name"))) continue;
            
            if($rec['taxonRank'] == 'species') {
                $a = array('sn' => $rec['scientificName'], 'p' => $rec['parentNameUsageID'], 'g' => $rec['genus'], 's' => $rec['specificEpithet']);
                $json = json_encode($a);
                $species[$json][] = $rec['taxonID'];
            }
            elseif($rec['taxonRank'] == 'infraspecies') {
                $a = array('sn' => $rec['scientificName'], 'p' => $rec['parentNameUsageID'], 'g' => $rec['genus'], 's' => $rec['specificEpithet'], 'i' => $rec['infraspecificEpithet']);
                $json = json_encode($a);
                $infraspecies[$json][] = $rec['taxonID'];
            }
        }
        // print_r($species); print_r($infraspecies); exit;
        
        //step 2: create pairs of taxonIDs of duplicate taxa
        $dup_species = array(); $dup_infraspecies = array(); //initialize
        foreach($species as $json => $taxonIDs) {
            if(count($taxonIDs) > 1) $dup_species[] = $taxonIDs;
        }
        $species = '';
        foreach($infraspecies as $json => $taxonIDs) {
            if(count($taxonIDs) > 1) $dup_infraspecies[] = $taxonIDs;
        }
        $infraspecies = '';
        
        /* sample of duplicate species:
        0df0b41d1fb8756e6272e62be944c812		dde980c765191db8e8178f59a091da99	Genysa decorsei (Simon, 1902)	Genysa	decorsei	species	(Simon, 1902)	provisionally accepted name			
        1ab1fbb89c355b4198bdef93869b809c		dde980c765191db8e8178f59a091da99	Genysa decorsei (Simon, 1902)	Genysa	decorsei	species	(Simon, 1902)	accepted name			
        */
        
        print_r($dup_species);
        print_r($dup_infraspecies);
        echo "\ndup_species: ".count($dup_species)."\n";
        echo "\ndup_infraspecies: ".count($dup_infraspecies)."\n";
        if(!$dup_species && !$dup_infraspecies) {
            echo "\nNo duplicate species for [$what].\n\n";
            return;
        }
        
        // step 3: get all taxonIDs, to be used in step 4.
        foreach($dup_species as $dup) {
            foreach($dup as $taxonID) $all_taxonIDs[$taxonID] = '';
        }
        foreach($dup_infraspecies as $dup) {
            foreach($dup as $taxonID) $all_taxonIDs[$taxonID] = '';
        }
        echo "\nall_taxonIDs: ".count($all_taxonIDs)."\n"; //exit;
        
        // step 4: create a taxonIDinfo - list for all taxonIDs in step 3.
        $this->taxonID_info = self::taxonIDinfo($meta, $extension_path.$meta['taxon_file'], $all_taxonIDs);
        // print_r($taxonID_info);
        
        // step 5: prefer_reject process
        $taxonIDs_2be_removed1 = self::prefer_reject($dup_species, 'species');
        $taxonIDs_2be_removed2 = self::prefer_reject($dup_infraspecies, 'infraspecies');
        $ids_2be_removed = array_merge($taxonIDs_2be_removed1, $taxonIDs_2be_removed2);
        
        // step 6: remove rejected duplicates from step 5 and write to DwCA
        $i = 0;
        foreach(new FileIterator($extension_path.$meta['taxon_file']) as $line => $row) {
            $i++; if(($i % 500000) == 0) echo "\n".number_format($i);
            if($meta['ignoreHeaderLines'] && $i == 1) continue;
            if(!$row) continue;
            $row = Functions::conv_to_utf8($row); //possibly to fix special chars
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta['fields'] as $field) {
                if(!$field) continue;
                $rec[$field] = $tmp[$k];
                $k++;
            }
            // print_r($rec); exit;
            if(!in_array($rec['taxonID'], $ids_2be_removed)) self::write_taxon_DH($rec);
        }
        // exit("\nexit yy\n");
        $this->archive_builder->finalize(TRUE);
    }
    private function prefer_reject($records, $what)
    {
        $final = array();
        foreach($records as $pair) { //$pair can be more than 2 taxonIDs
            $taxonIDs_removed = self::select_1_from_list_of_taxonIDs($pair, $what);
            if($taxonIDs_removed) $final = array_merge($final, $taxonIDs_removed);
        }
        return $final;
        // exit("\n111\n");
    }
    private function select_1_from_list_of_taxonIDs($pair, $what)
    {
        $orig_pair = $pair;
        if($what == 'species' || $what == 'infraspecies') { //for both cases actully, we can live without this filter actually.
                                 $pair = self::filter1_status($pair);          //equal to "provisionally accepted name"
            if(count($pair) > 1) $pair = self::filter2_authorship($pair);      //without authorship
            elseif(count($pair) == 1) return array_diff($orig_pair, $pair);

            if(count($pair) > 1) $pair = self::filter3_authorship($pair);      //without 4-digit no.
            elseif(count($pair) == 1) return array_diff($orig_pair, $pair);

            if(count($pair) > 1) $pair = self::filter4_authorship($pair);      //authority date is larger
            elseif(count($pair) == 1) return array_diff($orig_pair, $pair);
            
            if(count($pair) > 1) $pair = self::filter5_authorship($pair);      //without parentheses
            elseif(count($pair) == 1) return array_diff($orig_pair, $pair);

            if($what == 'infraspecies') {
                /*  5.1. verbatimTaxonRank IS NOT empty | verbatimTaxonRank IS empty
                    5.2. verbatimTaxonRank IS subsp. | verbatimTaxonRank IS var. OR f.
                    5.3. verbatimTaxonRank IS var. | verbatimTaxonRank IS f.
                */
                if(count($pair) > 1) $pair = self::filter5_1_verbatimRank($pair);   //verbatimTaxonRank IS empty
                elseif(count($pair) == 1) return array_diff($orig_pair, $pair);
                if(count($pair) > 1) $pair = self::filter5_2_verbatimRank($pair);   //verbatimTaxonRank IS var. OR f.
                elseif(count($pair) == 1) return array_diff($orig_pair, $pair);
                if(count($pair) > 1) $pair = self::filter5_3_verbatimRank($pair);   //verbatimTaxonRank IS f.
                elseif(count($pair) == 1) return array_diff($orig_pair, $pair);
            }

            if(count($pair) > 1) $pair = self::filter6_subgenus($pair);        //subgenus IS NOT empty
            elseif(count($pair) == 1) return array_diff($orig_pair, $pair);
            
            if(count($pair) > 1) $pair = self::filter7_isExtinct($pair);       //isExtinct IS FALSE
            elseif(count($pair) == 1) return array_diff($orig_pair, $pair);
            
            /*Prefer | Reject
            1. accepted name | provisionally accepted name
            2. scientificNameAuthorship IS NOT empty | scientificNameAuthorship IS empty
            3. scientificNameAuthorship WITH 4-digit number | scientificNameAuthorship WITHOUT 4-digit number
            4. authority date (4-digit number in scientificNameAuthorship) is smaller | authority date is larger
            5. scientificNameAuthorship WITH parentheses | scientificNameAuthorship WITHOUT parentheses
            6. subgenus IS empty | subgenus IS NOT empty
            7. isExtinct IS TRUE | isExtinct IS FALSE
            */
        }
        if($what == 'infraspecies') {
            /*Prefer | Reject
            1. accepted name | provisionally accepted name
            2. scientificNameAuthorship IS NOT empty | scientificNameAuthorship IS empty
            3. scientificNameAuthorship WITH 4-digit number | scientificNameAuthorship WITHOUT 4-digit number
            4. authority date (4-digit number in scientificNameAuthorship) is smaller | authority date is larger
            5. scientificNameAuthorship WITH parentheses | scientificNameAuthorship WITHOUT parentheses
            5.1. verbatimTaxonRank IS NOT empty | verbatimTaxonRank IS empty
            5.2. verbatimTaxonRank IS subsp. | verbatimTaxonRank IS var. OR f.
            5.3. verbatimTaxonRank IS var. | verbatimTaxonRank IS f.
            6. subgenus IS empty | subgenus IS NOT empty
            7. isExtinct IS TRUE | isExtinct IS FALSE
            */
        }
    }
    private function filter7_isExtinct($pair, $i = -1) //isExtinct IS FALSE
    {
        $orig_pair = $pair;
        foreach($pair as $taxonID) { $i++;
            if($info = $this->taxonID_info[$taxonID]) {
                /*[02dcf48d2ba98f149bbf56a1f91f2da7] => Array(  e.g. rec for $this->taxonID_info
                    [s] => accepted name | [sna] => (Loden, 1977) | [vr] => [sg] => [isE] => 
                )*/
                if(stripos($info['isE'], "isExtinct:false") !== false) unset($pair[$i]); //string is found
            }
        }
        $pair = array_values($pair); //reindex key
        if(!$pair)              $pair = $orig_pair;
        if(count($pair) > 1)    unset($pair[1]); //pick one
        $pair = array_values($pair); //reindex key
        return $pair;
    }
    private function filter6_subgenus($pair, $i = -1) //subgenus IS NOT empty
    {
        $orig_pair = $pair;
        foreach($pair as $taxonID) { $i++;
            if($info = $this->taxonID_info[$taxonID]) {
                /*[02dcf48d2ba98f149bbf56a1f91f2da7] => Array(  e.g. rec for $this->taxonID_info
                    [s] => accepted name | [sna] => (Loden, 1977) | [vr] => [sg] => [isE] => 
                )*/
                if($info['sg']) unset($pair[$i]);
            }
        }
        $pair = array_values($pair); //reindex key
        if(!$pair) return $orig_pair; //exit("\nInvestigate filter6\n");
        return $pair;
    }
    private function filter5_authorship($pair, $i = -1) //WITHOUT parentheses
    {
        $orig_pair = $pair;
        foreach($pair as $taxonID) { $i++;
            if($info = $this->taxonID_info[$taxonID]) {
                /*[02dcf48d2ba98f149bbf56a1f91f2da7] => Array(  e.g. rec for $this->taxonID_info
                    [s] => accepted name | [sna] => (Loden, 1977) | [vr] => [sg] => [isE] => 
                )*/
                if((stripos($info['sna'], "(") !== false) && stripos($info['sna'], ")") !== false) {} // "(" and ")" are found
                else unset($pair[$i]);
            }
        }
        $pair = array_values($pair); //reindex key
        if(!$pair) return $orig_pair; //exit("\nInvestigate filter5\n");
        return $pair;
    }
    private function filter4_authorship($pair, $i = -1) //authority date (4-digit number in scientificNameAuthorship) is smaller | authority date is larger
    {
        $ids_with_4digit_no = array();
        $orig_pair = $pair;
        foreach($pair as $taxonID) { $i++;
            if($info = $this->taxonID_info[$taxonID]) {
                /*[02dcf48d2ba98f149bbf56a1f91f2da7] => Array(  e.g. rec for $this->taxonID_info
                    [s] => accepted name | [sna] => (Loden, 1977) | [vr] => [sg] => [isE] => 
                )*/
                if(preg_match_all('!\d+!', $info['sna'], $arr)) {
                    $xxx = $arr[0];
                    foreach($xxx as $numeric) {
                        if($numeric) {
                            if(strlen($numeric) == 4) $ids_with_4digit_no[$taxonID] = $numeric;
                        }
                    }
                }
            }
        }
        
        if(count(@$ids_with_4digit_no) == 2) {
            foreach($ids_with_4digit_no as $taxonID => $numeric) $arr[] = array('id' => $taxonID, 'numeric' => $numeric);
            $to_remove = false;
            if(@$arr[0]['numeric'] > @$arr[1]['numeric']) $to_remove = $arr[0]['id'];
            if(@$arr[1]['numeric'] > @$arr[0]['numeric']) $to_remove = $arr[1]['id'];
            if($to_remove) {
                $i = -1;
                foreach($pair as $taxonID) { $i++;
                    if($taxonID == $to_remove) {
                        unset($pair[$i]);
                        $pair = array_values($pair); //reindex key
                        return $pair;
                    }
                }
            }
        }
        elseif(count(@$ids_with_4digit_no) < 2) return $orig_pair;
        elseif(count(@$ids_with_4digit_no) > 2) exit("\nNeed to script this up...\n");
        return $orig_pair;
    }
    private function filter3_authorship($pair, $i = -1) //without 4-digit no.
    {
        $orig_pair = $pair;
        foreach($pair as $taxonID) { $i++;
            if($info = $this->taxonID_info[$taxonID]) {
                /*[02dcf48d2ba98f149bbf56a1f91f2da7] => Array(  e.g. rec for $this->taxonID_info
                    [s] => accepted name | [sna] => (Loden, 1977) | [vr] => [sg] => [isE] => 
                )*/
                $with_4_digit_no = false;
                if(preg_match_all('!\d+!', $info['sna'], $arr)) {
                    foreach($arr[0] as $numeric) {
                        if(strlen($numeric) == 4) $with_4_digit_no = true;
                    }
                }
                if(!$with_4_digit_no) unset($pair[$i]);
            }
        }
        $pair = array_values($pair); //reindex key
        if(!$pair) return $orig_pair; //exit("\nInvestigate filter3\n");
        return $pair;
    }
    private function filter2_authorship($pair, $i = -1) //without authorship
    {
        $orig_pair = $pair;
        foreach($pair as $taxonID) { $i++;
            if($info = $this->taxonID_info[$taxonID]) {
                /*[02dcf48d2ba98f149bbf56a1f91f2da7] => Array(  e.g. rec for $this->taxonID_info
                    [s] => accepted name | [sna] => (Loden, 1977) | [vr] => [sg] => [isE] => 
                )*/
                if(!$info['sna']) unset($pair[$i]);
            }
        }
        $pair = array_values($pair); //reindex key
        if(!$pair) return $orig_pair; //exit("\nInvestigate filter2\n");
        return $pair;
    }
    private function filter1_status($pair, $i = -1) //equal to "provisionally accepted name"
    {
        $orig_pair = $pair;
        foreach($pair as $taxonID) { $i++;
            if($info = $this->taxonID_info[$taxonID]) {
                /*[02dcf48d2ba98f149bbf56a1f91f2da7] => Array(  e.g. rec for $this->taxonID_info
                    [s] => accepted name | [sna] => (Loden, 1977) | [vr] => [sg] => [isE] => 
                )*/
                if($info['s'] == 'provisionally accepted name') unset($pair[$i]);
            }
        }
        $pair = array_values($pair); //reindex key
        if(!$pair) return $orig_pair; //exit("\nInvestigate filter1\n"); ...add the $orig_pair process for all filter process... except for filter7
        return $pair;
    }
    private function filter5_1_verbatimRank($pair, $i = -1) //verbatimTaxonRank IS empty
    {
        $orig_pair = $pair;
        foreach($pair as $taxonID) { $i++;
            if($info = $this->taxonID_info[$taxonID]) {
                /*[02dcf48d2ba98f149bbf56a1f91f2da7] => Array(  e.g. rec for $this->taxonID_info
                    [s] => accepted name | [sna] => (Loden, 1977) | [vr] => [sg] => [isE] => 
                )*/
                if(!$info['vr']) unset($pair[$i]);
            }
        }
        $pair = array_values($pair); //reindex key
        if(!$pair) return $orig_pair; //exit("\nInvestigate filter2\n");
        return $pair;
    }
    private function filter5_2_verbatimRank($pair, $i = -1) //verbatimTaxonRank IS var. OR f.
    {
        $orig_pair = $pair;
        foreach($pair as $taxonID) { $i++;
            if($info = $this->taxonID_info[$taxonID]) {
                /*[02dcf48d2ba98f149bbf56a1f91f2da7] => Array(  e.g. rec for $this->taxonID_info
                    [s] => accepted name | [sna] => (Loden, 1977) | [vr] => [sg] => [isE] => 
                )*/
                if(in_array($info['vr'], array("var.", "f."))) unset($pair[$i]);
            }
        }
        $pair = array_values($pair); //reindex key
        if(!$pair) return $orig_pair; //exit("\nInvestigate filter2\n");
        return $pair;
    }
    private function filter5_3_verbatimRank($pair, $i = -1) //verbatimTaxonRank IS f.
    {
        $orig_pair = $pair;
        foreach($pair as $taxonID) { $i++;
            if($info = $this->taxonID_info[$taxonID]) {
                /*[02dcf48d2ba98f149bbf56a1f91f2da7] => Array(  e.g. rec for $this->taxonID_info
                    [s] => accepted name | [sna] => (Loden, 1977) | [vr] => [sg] => [isE] => 
                )*/
                if(in_array($info['vr'], array("f."))) unset($pair[$i]);
            }
        }
        $pair = array_values($pair); //reindex key
        if(!$pair) return $orig_pair; //exit("\nInvestigate filter2\n");
        return $pair;
    }
    private function taxonIDinfo($meta, $file, $all_taxonIDs)
    {
        $i = 0;
        foreach(new FileIterator($file) as $line => $row) {
            $i++; if(($i % 500000) == 0) echo "\n".number_format($i);
            if($meta['ignoreHeaderLines'] && $i == 1) continue;
            if(!$row) continue;
            $row = Functions::conv_to_utf8($row); //possibly to fix special chars
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta['fields'] as $field) {
                if(!$field) continue;
                $rec[$field] = $tmp[$k];
                $k++;
            }
            if(isset($all_taxonIDs[$rec['taxonID']])) {
                // print_r($rec); exit("\nxxx\n");
                $final[$rec['taxonID']] = array("s" => $rec['taxonomicStatus'], 'sna' => $rec['scientificNameAuthorship'], 'vr' => $rec['verbatimTaxonRank'], 
                                               'sg' => $rec['subgenus'], 'isE' => $rec['taxonRemarks']);
            }
            /*Array(
                [taxonID] => fc0886d15759a01525b1469534189bb5
                [acceptedNameUsageID] => 
                [parentNameUsageID] => d2a21892b23f5453d7655b082869cfca
                [scientificName] => Bryometopus alekperovi Foissner, 1998
                [genus] => Bryometopus
                [specificEpithet] => alekperovi
                [taxonRank] => species
                [scientificNameAuthorship] => Foissner, 1998
                [taxonomicStatus] => accepted name
                [infraspecificEpithet] => 
                [verbatimTaxonRank] => 
                [subgenus] => 
            )*/
        }
        return $final;
    }
    //=========================================================================== start DUPLICATE TAXA letter B ==================================
    
    
    //=========================================================================== start adjusting taxon.tab with those 'not assigned' entries ==================================
    public function fix_CLP_taxa_with_not_assigned_entries_V2()
    {
        $extension_path = CONTENT_RESOURCE_LOCAL_PATH."Catalogue_of_Life_Protists_DH_step1/";
        $meta = self::get_meta_info(false, $extension_path); //meta here is now the newly temporary created DwCA
        $this->taxID_info = self::get_taxID_nodes_info($meta, $extension_path); echo "\ntaxID_info (".$meta['taxon_file'].") total rows: ".count($this->taxID_info)."\n";
        $i = 0;
        $WRITE = fopen($extension_path.$meta['taxon_file'].".txt", "w"); //e.g. new taxon.tab will be taxon.tab.txt --- writing to taxon.tab.txt is actually not needed anymore since you're creating the DwC anyway.
        foreach(new FileIterator($extension_path.$meta['taxon_file']) as $line => $row) {
            $i++; if(($i % 100000) == 0) echo "\n".number_format($i);
            if($meta['ignoreHeaderLines'] && $i == 1) {
                fwrite($WRITE, $row."\n");
                continue;
            }
            if(!$row) continue;
            $row = Functions::conv_to_utf8($row); //possibly to fix special chars
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta['fields'] as $field) {
                if(!$field) continue;
                $rec[$field] = $tmp[$k];
                $k++;
            }
            $orig_rec = $rec;
            // print_r($rec); exit;
            /*Array(
                [taxonID] => fc0886d15759a01525b1469534189bb5
                [acceptedNameUsageID] => 
                [parentNameUsageID] => d2a21892b23f5453d7655b082869cfca
                [scientificName] => Bryometopus alekperovi Foissner, 1998
                [taxonRank] => species
                [taxonomicStatus] => accepted name
            )*/
            $taxonID = $rec['taxonID'];
            /* the if() below this line is a good debug; uncomment to debug per taxon */
            // if($taxonID == '181b15bc1f7c588f7ebf64474f86d76f') { // 8fd3cb6a84d4e49e3bfbe3313c76df07 - Diaxonella
                                                                    // 3e82dc989115d4eba3f60aa727ed27ad - Ciliophora
                                                                    // 4693ed96493faf8f58e7ece01d0e1afb		54116747	Ordosporidae	family	 --- good test case
                                                                    // 181b15bc1f7c588f7ebf64474f86d76f		unc-000151	Windalia	genus
                $ancestry = self::get_ancestry_of_taxID($rec['taxonID'], $this->taxID_info); //print_r($ancestry);
                /*Array(
                    [0] => 8fd3cb6a84d4e49e3bfbe3313c76df07
                    [1] => 54117935
                    [2] => 54117933
                    [3] => 54117932
                    [4] => 3e82dc989115d4eba3f60aa727ed27ad
                )
                42998538 42987356 Diaxonella genus
                42987356 42987354 Family not assigned family
                42987354 42987353 Order not assigned order
                42987353 42984770 Class not assigned class
                42984770 Ciliophora phylum
                */
                // foreach($ancestry as $taxonID) echo "\n".$this->taxID_info[$taxonID]['n']; //good debug
                // echo "\n------------------------\n";
                if(self::name_is_not_assigned($rec['scientificName'])) continue; //ignore e.g. "Order not assigned" or "Family not assigned"
                elseif(self::is_immediate_ancestor_Not_Assigned($rec['parentNameUsageID'])) {
                    $ret = self::get_valid_parent_from_ancestry($ancestry, $taxonID);
                    $rec['parentNameUsageID'] = $ret['valid_parent'];
                    self::write_taxon_DH($rec);                         // echo "\nold row: $row\n";
                    $new_row = implode("\t", $rec);                     // echo "\nnew row: $new_row\n";
                    fwrite($WRITE, $new_row."\n");
                    if($val = $ret['unclassified_new_taxon']) {
                        self::write_taxon_DH($val);
                        $unclassified_row = implode("\t", $val);        // echo "\nunclassified_row: $unclassified_row\n";
                        fwrite($WRITE, $unclassified_row."\n");
                    }
                }
                else {
                    fwrite($WRITE, $row."\n"); //regular row
                    self::write_taxon_DH($orig_rec);
                }
                // exit("\nexit muna\n");
            // }
        }
        fclose($WRITE);
        $txtfile_o = $extension_path.$meta['taxon_file'];        $old = self::get_total_rows($txtfile_o); echo "\nOld taxon.tab: [$old]\n";
        $txtfile_n = $extension_path.$meta['taxon_file'].".txt"; $new = self::get_total_rows($txtfile_n); echo "\nNew taxon.tab.txt: [$new]\n";
        $this->archive_builder->finalize(TRUE);
    }
    private function get_valid_parent_from_ancestry($ancestry, $taxonID)
    {
        array_shift($ancestry); //remove first element of array, bec first element of $ancestry is the taxon in question.
        foreach($ancestry as $taxon_id) {
            $sci = $this->taxID_info[$taxon_id]['n'];
            if(stripos($sci, "not assigned") !== false) {} //string is found
            else { //found the valid parent.
                $valid_parent_sciname = $sci;
                /* 1. create the 'unclassified' new taxon
                   2. make the 'unclassified' taxon as parent of taxon in question
                   3. make the valid parent as the parent of the 'unclassified' taxon
                */
                $this->unclassified_id_increments++;
                $unclassified_new_taxon = Array(
                    'taxonID' => 'unc-'.Functions::format_number_with_leading_zeros($this->unclassified_id_increments, 6),
                    'acceptedNameUsageID' => '',
                    'parentNameUsageID' => $taxon_id,
                    'scientificName' => 'unclassified '.$sci,
                    'taxonRank' => 'no rank',
                    'taxonomicStatus' => ''
                );
                return array('valid_parent' => $unclassified_new_taxon['taxonID'], 'unclassified_new_taxon' => $unclassified_new_taxon);
            }
        }
        exit("\nInvestigate no valid parent for taxon_id = [$taxonID]\n");
    }
    private function is_immediate_ancestor_Not_Assigned($parent_id)
    {
        if(!$parent_id) return false;
        $sci = $this->taxID_info[$parent_id]['n'];
        if(stripos($sci, "not assigned") !== false) return true; //string is found
        return false;
    }
    private function name_is_not_assigned($str)
    {
        if(stripos($str, "not assigned") !== false) return true;
        return false;
    }
    private function get_total_rows($file)
    {
        /* source: https://stackoverflow.com/questions/3137094/how-to-count-lines-in-a-document */
        $total = shell_exec("wc -l < ".escapeshellarg($file));
        $total = trim($total);
        return $total;
    }
    //=========================================================================== end adjusting taxon.tab with those 'not assigned' entries ====================================
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