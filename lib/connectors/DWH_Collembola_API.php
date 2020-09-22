<?php
namespace php_active_record;
/* connector: [dwh_Collembola_TRAM_990.php] - TRAM-990
*/
class DWH_Collembola_API
{
    function __construct($folder)
    {
        $this->spreadsheet_ID = "1wWLmuEGyNZ2a91rZKNxLvxKRM_EYV6WBbKxq6XXoqvI"; //old TRAM-803
        $this->spreadsheet_ID = "1ezR2u9s5NMx4hJgnUAmE41VJniRbQjH6JQF7uep92Mg"; //new TRAM-986
        
        $this->resource_id = $folder;
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->taxon_ids = array();
        $this->debug = array();
        $this->alternative_names = array("synonym", "equivalent name", "in-part", "misspelling", "genbank synonym", "misnomer", "anamorph", "genbank anamorph", "teleomorph", "authority");

        $this->prune_further = array();
        
        /* these paths are manually created, since dumps are using explicit dates */
        if(Functions::is_production()) $this->extension_path = DOC_ROOT."../other_files/DWH/dumps/2020-08-01-archive-complete/";
        else                           $this->extension_path = DOC_ROOT."../cp/COL/2020-08-01-archive-complete/";
        
        $this->dwca['iterator_options'] = array('row_terminator' => "\n");
        $this->run = '';
        /* taxonomicStatus values as of dump:   Array(
            [accepted name] => 
            [synonym] => 
            [ambiguous synonym] => 
            [provisionally accepted name] => 
            [misapplied name] => 
            [] => 
        )*/
        $this->unclassified_id_increments = 0;
    }
    // ----------------------------------------------------------------- start TRAM-803 -----------------------------------------------------------------
    function start_tram_803()
    {
        /* test - from copied template
        // 10145857 Amphileptus hirsutus Dumas, 1930
        // 10147309 Aspidisca binucleata Kahl
        $taxID_info = self::get_taxID_nodes_info();
        $ancestry = self::get_ancestry_of_taxID(10145857, $taxID_info); print_r($ancestry);
        $ancestry = self::get_ancestry_of_taxID(10147309, $taxID_info); print_r($ancestry);
        exit("\n-end tests-\n");
        */
        /* - from copied template
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
        echo "\n----------\nRaw source checklist: [$this->extension_path]\n----------\n";
    }
    private function pruneBytaxonID()
    {
        $params['spreadsheetID'] = $this->spreadsheet_ID;
        $params['range']         = 'pruneBytaxonID!A1:C50';
        $params['first_row_is_headerYN'] = true;
        $params['sought_fields'] = array('taxonID');
        $parts = self::get_removed_branches_from_spreadsheet($params);
        $removed_branches = $parts['taxonID']; //print_r($removed_branches); exit;
        return $removed_branches;
    }
    private function main_tram_803()
    {
        $taxID_info = self::get_taxID_nodes_info(); //un-comment in real operation

        // these 2 are from copied template
        /* #1. Remove branches from the PruneBytaxonID list based on their taxonID:
        $removed_branches = self::pruneBytaxonID();
        echo "\nremoved_branches total A COL: ".count($removed_branches)."\n"; //exit("\n111\n");
        */
        /* #2. Create the COL taxon set by pruning the branches from the pruneForCOL list:
        $removed_branches = self::process_pruneForCOL_CLP('COL', $removed_branches); // print_r($removed_branches);
        echo "\nremoved_branches total B COL: ".count($removed_branches)."\n"; //exit("\n222\n");
        */
        
        // /* Include per TRAM-990. From COL 2020-08-01 snapshot
        // 3952391  18bd465a6c133112cd80f73f23776dee    Species 2000    Catalogue of Life in Species 2000 & ITIS Catalogue of Life: 2020-08-01 Beta     3946738class        Collembola  Animalia    Arthropoda  Collembola                                          false
        $included_branches[3952391] = '';
        // */
        
        $meta = self::get_meta_info();
        $i = 0; $filtered_ids = array();
        echo "\nStart main process...Collembola DH...\n";
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
            $ancestry = self::get_ancestry_of_taxID($rec['taxonID'], $taxID_info);
            if(self::an_id_from_ancestry_is_part_of_a_removed_branch($ancestry, $included_branches)) {}
            else {
                $filtered_ids[$rec['taxonID']] = '';
                $removed_branches[$rec['taxonID']] = '';
                continue;
            }
            //end filter
        } //end loop

        echo "\nStart main process 2...Collembola DH...\n"; $i = 0;
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
            if(in_array($rec['taxonomicStatus'], array("synonym", "ambiguous synonym", "misapplied name"))) continue;
            
            /*Array()*/
            
            if(isset($filtered_ids[$rec['taxonID']])) continue;
            if(isset($filtered_ids[$rec['acceptedNameUsageID']])) continue;
            if(isset($filtered_ids[$rec['parentNameUsageID']])) continue;

            if(isset($removed_branches[$rec['taxonID']])) continue;
            if(isset($removed_branches[$rec['acceptedNameUsageID']])) continue;
            if(isset($removed_branches[$rec['parentNameUsageID']])) continue;
            
            // print_r($rec); exit("\nexit muna 2\n");
            /**/
            
            /* Remove branches */
            $ancestry = self::get_ancestry_of_taxID($rec['taxonID'], $taxID_info);
            if(self::an_id_from_ancestry_is_part_of_a_removed_branch($ancestry, $included_branches)) {}
            else continue;
            
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
                // print_r($rec); //exit("\nInvestigate: no [identifier] for [parentNameUsageID]\n");
                $this->debug['no [identifier] for [parentNameUsageID]'][$rec['parentNameUsageID']] = '';
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
        print_r($meta); //exit;
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
            /*Array
            (
                [taxonID] => 1001
                [identifier] => 40c1c4c8925fb02ce99db87c0221a6f6
                [datasetID] => 18
                [datasetName] => LepIndex in Species 2000 & ITIS Catalogue of Life: 2020-08-01 Beta
                [acceptedNameUsageID] => 
                [parentNameUsageID] => 3997251
                [taxonomicStatus] => accepted name
                [taxonRank] => species
                [verbatimTaxonRank] => 
                [scientificName] => Bucculatrix carolinae Braun, 1963
                [kingdom] => Animalia
                [phylum] => Arthropoda
                [class] => Insecta
                [order] => Lepidoptera
                [superfamily] => Gracillarioidea
                [family] => Bucculatricidae
                [genericName] => Bucculatrix
                [genus] => Bucculatrix
                [subgenus] => 
                [specificEpithet] => carolinae
                [infraspecificEpithet] => 
                [scientificNameAuthorship] => Braun, 1963
                [source] => 
                [namePublishedIn] => 
                [nameAccordingTo] => Wing, P.
                [modified] => 2011-05-06
                [description] => 
                [taxonConceptID] => 
                [scientificNameID] => b3b2ff1c-5a86-4ab0-aada-9f209ed8d075
                [references] => http://www.catalogueoflife.org/col/details/species/id/40c1c4c8925fb02ce99db87c0221a6f6
                [isExtinct] => false
            )*/
            if(isset($rec['identifier'])) $final[$rec['taxonID']] = array("pID" => $rec['parentNameUsageID'], 'r' => $rec['taxonRank'], 'i' => $rec['identifier']);
            else                          $final[$rec['taxonID']] = array("pID" => $rec['parentNameUsageID'], 'n' => $rec['scientificName'], 'r' => $rec['taxonRank'], 's' => $rec['taxonomicStatus']);
            
            $temp[$rec['taxonomicStatus']] = ''; //debug
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
        print_r($temp); //exit; //debug
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
        /* this was later abondoned by one below this:
        $rec['scientificName'] = str_ireplace("Unplaced", "unclassified", $rec['scientificName']);
        */
        
        /* From Katja: The original version of this is:
        13663148 b41f2b15ccd7f64e1f5c329eae60e987 5 CCW in Species 2000 & ITIS Catalogue of Life: 20th February 2019 54217965 accepted name species Erioptera (Unplaced) amamiensis Alexander, 1956
        Instead of changing (Unplaced) to (unclassified) here, we should simply remove the pseudo subgenus string and use the simple binomial, 
        i.e., in this case, the scientificName should be "Erioptera amamiensis Alexander, 1956." 
        To fix this you should be able to do a simple search and replace for (Unplaced) in the scientificName field.
        */
        $rec['scientificName'] = Functions::remove_whitespace(str_ireplace("(Unplaced)", "", $rec['scientificName']));
        
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID                 = $rec['taxonID'];
        $taxon->parentNameUsageID       = $rec['parentNameUsageID'];
        
        // /* if one of those removed IDs from de-duplication is a parent_id then the respective retain-id will be the new parent. VERY IMPORTANT
        if($val = @$this->ids2retain[$rec['parentNameUsageID']]) $taxon->parentNameUsageID = $val;
        // */
        
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
    public function duplicate_process_A($what){}
    private function get_remove_keep_ids_from_spreadsheet($params = false){}
    //=========================================================================== end DUPLICATE TAXA letter A ====================================
    
    //=========================================================================== start DUPLICATE TAXA letter B ==================================
    public function duplicate_process_B($what){}
    private function prefer_reject($records, $what){}
    private function select_1_from_list_of_taxonIDs($pair, $what){}
    private function filter8_NoAncestry($pair){}
    private function filter7_isExtinct($pair, $i = -1) //isExtinct IS FALSE 
    {}
    private function filter6_subgenus($pair, $i = -1) //subgenus IS NOT empty
    {}
    private function filter5_authorship($pair, $i = -1) //WITHOUT parentheses
    {}
    private function filter4_authorship($pair, $i = -1) //authority date (4-digit number in scientificNameAuthorship) is smaller | authority date is larger
    {}
    private function get_least_from_multiple_dates($a){}
    private function filter3_authorship($pair, $i = -1) //without 4-digit no.
    {}
    private function filter2_authorship($pair, $i = -1) //without authorship
    {}
    private function filter1_status($pair, $i = -1) //equal to "provisionally accepted name"
    {}
    private function filter5_1_verbatimRank($pair, $i = -1) //verbatimTaxonRank IS empty
    {}
    private function filter5_2_verbatimRank($pair, $i = -1) //verbatimTaxonRank IS var. OR f.
    {}
    private function filter5_3_verbatimRank($pair, $i = -1) //verbatimTaxonRank IS f.
    {}
    private function taxonIDinfo($meta, $file, $all_taxonIDs){}
    //=========================================================================== start DUPLICATE TAXA letter B ==================================
    
    
    //=========================================================================== start adjusting taxon.tab with those 'not assigned' entries ==================================
    public function fix_CLP_taxa_with_not_assigned_entries_V2($what)
    {
        if($what == 'CLP') $extension_path = CONTENT_RESOURCE_LOCAL_PATH."Catalogue_of_Life_Protists_DH_step1/";
        if($what == 'COL') $extension_path = CONTENT_RESOURCE_LOCAL_PATH."Catalogue_of_Life_DH_step1/";
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
                    $ret = self::get_valid_parent_from_ancestry($ancestry, $taxonID, $what);
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
    private function get_valid_parent_from_ancestry($ancestry, $taxonID, $what)
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
                if(!isset($this->unclassified[$sci])) {
                    $this->unclassified_id_increments++;
                    $unclassified_new_taxon = Array(
                        'taxonID' => 'unc-'.$what.Functions::format_number_with_leading_zeros($this->unclassified_id_increments, 3),
                        'acceptedNameUsageID' => '',
                        'parentNameUsageID' => $taxon_id,
                        'scientificName' => 'unclassified '.$sci,
                        'taxonRank' => 'no rank',
                        'taxonomicStatus' => ''
                    );
                    $this->unclassified[$sci] = $unclassified_new_taxon;
                }
                else $unclassified_new_taxon = $this->unclassified[$sci];
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

    private function process_pruneForCOL_CLP($what, $removed_branches)
    {
        //1st step: get the list of [identifier]s. --------------------------------------------------------
        $params['spreadsheetID'] = $this->spreadsheet_ID;

        if($what == "COL") $params['range'] = 'pruneForCOL!A1:A500';
        if($what == "CLP") $params['range'] = 'pruneForCLP!A1:A5';

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
        return $removed_branches;
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