<?php
namespace php_active_record;
/* connector: [dwh_postproc_TRAM_807.php] - TRAM-807
*/
class DH_v1_1_postProcessing
{
    function __construct($folder)
    {
        $this->resource_id = $folder;
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->taxon_ids = array();
        $this->debug = array();
        
        if(Functions::is_production()) {
            /* not used
            $this->download_options = array(
                'cache_path'         => '/extra/eol_cache_smasher/',
                'download_wait_time' => 250000, 'timeout' => 600, 'download_attempts' => 1, 'delay_in_minutes' => 0, 'expire_seconds' => false);
            $this->main_path = "/extra/d_w_h/xxx/"; //download_wait_time is 1/4 of a second -> 1000000/4
            */
        }
        else {
            $this->download_options = array(
                'cache_path'         => '/Volumes/AKiTiO4/eol_cache_smasher/',
                'download_wait_time' => 250000, 'timeout' => 600, 'download_attempts' => 1, 'delay_in_minutes' => 0, 'expire_seconds' => false);
            $this->main_path = "/Volumes/AKiTiO4/d_w_h/TRAM-807/";
            // $this->main_path = "/Users/eagbayani/Sites/TRAM-807/"; //for MacBook
        }
        
        /*
        $this->prune_further = array();
        // these paths are manually created, since dumps are using explicit dates
        if(Functions::is_production()) $this->extension_path = DOC_ROOT."../other_files/DWH/dumps/COL_2019-02-20-archive-complete/";
        else                           $this->extension_path = DOC_ROOT."../cp/COL/2019-02-20-archive-complete/";
        $this->dwca['iterator_options'] = array('row_terminator' => "\n");
        $this->run = '';
        */
        $this->unclassified_parent_id_increments = 0;
    }
    // ----------------------------------------------------------------- start TRAM-807 -----------------------------------------------------------------
    function start_tram_807()
    {
        self::start();
        // exit("\nstop muna\n");
        // self::main_tram_807(); //from another template
        // $this->archive_builder->finalize(TRUE);
        // if($this->debug) Functions::start_print_debug($this->debug, $this->resource_id);
    }
    private function start()
    {
        self::get_taxID_nodes_info(); //un-comment in real operation
        
        // /* tests only
        $uid = 'f4aab039-3ecc-4fb0-a7c0-e125da16b0ff'; //Life
        $uid = '80a181c5-8eff-4f2c-baf7-194e11f32270'; //Cellular Organisms
        // $uid = '-542'; //Cyanobacteria/Melainabacteria group
        // $uid = '-111644';
        // $ancestry = self::get_ancestry_of_taxID($uid); print_r($ancestry); exit; //working OK but not used yet
        $children = self::get_descendants_of_taxID($uid); print_r($children); exit;
        echo "\ncount: ".count($this->taxID_info)."\n";
        self::step_1_of_9($uid); //1. Clean up children of container taxa
        echo "\ncount: ".count($this->taxID_info)."\n";

        $uid = '-119639'; //sample where flag = 'infraspecific'
        self::step_2_of_9($uid); //2. Clean up infraspecifics
        echo "\ncount: ".count($this->taxID_info)."\n";
        self::save_global_var_to_txt();
        print_r($this->unclassified_parent);
        self::write2txt_unclassified_parents();
        return;
        exit("\n-end tests-\n");
        // */
        
        $txtfile = $this->main_path.'/taxonomy.tsv'; $i = 0;
        foreach(new FileIterator($txtfile) as $line_number => $line) {
            $i++; if(($i % 200000) == 0) echo "\n".number_format($i)." ";
            if($i == 1) $line = strtolower($line);
            $row = explode("\t|\t", $line); // print_r($row);
            if($i == 1) {
                $fields = $row;
                $fields = array_filter($fields); print_r($fields);
                continue;
            }
            else {
                if(!@$row[0]) continue;
                $k = 0; $rec = array();
                foreach($fields as $fld) {
                    $rec[$fld] = @$row[$k];
                    $k++;
                }
            }
            // print_r($rec); exit("\nstopx\n");
            /*Array(
                [uid] => f4aab039-3ecc-4fb0-a7c0-e125da16b0ff
                [parent_uid] => 
                [name] => Life
                [rank] => clade
                [sourceinfo] => trunk:1bfce974-c660-4cf1-874a-bdffbf358c19,NCBI:1
                [uniqname] => 
                [flags] => 
            )*/
            $uid = $rec['uid'];
            self::step_1_of_9($uid); //1. Clean up children of container taxa
            self::step_2_of_9($uid); //2. Clean up infraspecifics
        }
        // print_r($this->unclassified_parent);
        self::step_3_of_9(); //3. Rank adjustments
        self::save_global_var_to_txt();
        self::write2txt_unclassified_parents(); //unclassified parents from step 1.
        self::step_4pt2_of_9(); //4.2. remove barren taxa EXCEPT if at least one of the following conditions is true:
    }
    private function step_4pt2_of_9() //4.2. remove barren taxa EXCEPT if at least one of the following conditions is true:
    {   /*
        */
        
    }
    private function step_3_of_9() //3. Rank adjustments
    {   /*(1) Change ranks of the following taxa to genus */
        $uids = array('-1863729', '-367484', '-1078997', '-1534388', '-1065460', '-1534309', '-1657343', '-704485', '-2312101', '-1231958', '-1611983', '-1657167', '-1530527', '-1089167', '-1090558', '-1356886', '-461982', '-1548436', '-1612204', '-1612148', '-150144', '-1356787', '-1612015', '-1613042', '-1090557', '-219833', '-1531408', '-1424032', '-1612667', '-1535028', '-429157', '-2311919', '-12643');
        foreach($uids as $uid) $this->taxID_info[$uid]['r'] = 'genus';
        
        /*(2) Replace "no rank" rank values with nothing, i.e., a blank value
        Notes: TTT & smasher require a rank value, but EOL does not, so we don't have to carry the awkward "no rank" over into the DH. It looks like smasher has built in rules that check the endings for taxa of certain ranks and knock things to no rank if there are unexpected endings. This works pretty well for families where COL tries to slip in a bunch of informal groups as families. But it looks like one of the rules is that genera cannot end in -ae, which is simply not valid. So we'll have to reconstitute these ranks for these genera posthoc for the time being.
        */
        foreach($this->taxID_info as $uid => $xxx) {
            if($this->taxID_info[$uid]['r'] == 'no rank') $this->taxID_info[$uid]['r'] = '';
        }
        
        /*4.1. Remove all taxa where rank is "no rank - terminal" */
        foreach($this->taxID_info as $uid => $xxx) {
            if($this->taxID_info[$uid]['r'] == 'no rank - terminal') unset($this->taxID_info[$uid]);
        }
    }
    private function write2txt_unclassified_parents()
    {   /*Array(
            [Eunicida] => Array(
                    [uid] => unc-000001
                    [pID] => -50186
                    [n] => unclassified Eunicida
                    [r] => no rank
                )
        )
        */
        $WRITE = fopen($this->main_path.'/taxonomy1.txt', "a");
        foreach($this->unclassified_parent as $sci => $rec) {
            // print_r($rec); exit;
            $rek = array();
            $rek[] = $rec['uid'];
            $rek[] = $rec['pID'];
            $rek[] = $rec['n'];
            $rek[] = $rec['r'];
            $rek[] = '';
            $rek[] = '';
            fwrite($WRITE, implode("\t",$rek)."\n");
        }
        fclose($WRITE);
        unset($this->unclassified_parent);
    }
    private function save_global_var_to_txt()
    {
        $WRITE = fopen($this->main_path.'/taxonomy1.txt', "w");
        $fields = array('uid','parent_uid','name','rank','sourceinfo','uniqname','flags');
        fwrite($WRITE, implode("\t",$fields)."\n");
        foreach($this->taxID_info as $uid => $rec) {
            // print_r($rec); exit;
            $rek = array();
            $rek[] = $uid;
            $rek[] = $rec['pID'];
            $rek[] = $rec['n'];
            $rek[] = $rec['r'];
            $rek[] = $rec['s'];
            $rek[] = ''; //for uniqname
            $rek[] = $rec['f'];
            fwrite($WRITE, implode("\t",$rek)."\n");
        }
        fclose($WRITE);
    }
    private function step_2_of_9($uid) //2. Clean up infraspecifics
    {   /*Remove all taxa where all of these are true:
        1. source is NCBI
        2. rank is "no rank"
        3. flag is "infraspecific"
        */
        if($rec = @$this->taxID_info[$uid]) {}
        else return; //meaning taxon have been deleted from step 1.
        // print_r($rec); exit;
        /*Array(
            [pID] => -57132
            [r] => no rank - terminal
            [n] => Desertifilum fontinale KR2012/2
            [s] => NCBI:1549160
            [f] => infraspecific
        */
        $sources = self::get_all_sources($rec['s']);
        // print_r($sources);
        if(in_array('NCBI', $sources) && $rec['r'] == 'no rank' && $rec['f'] == 'infraspecific') unset($this->taxID_info[$uid]); // save to global var. -> $this->taxID_info
    }
    private function get_all_sources($sourceinfo)
    {
        $tmp = explode(",", $sourceinfo);
        foreach($tmp as $t) {
            $tmp2 = explode(":", $t);
            $final[$tmp2[0]] = '';
        }
        $final = array_keys($final);
        return array_map('trim', $final);
    }
    private function step_1_of_9($uid) //1. Clean up children of container taxa
    {
        // - then loop to all descendatns
        // - remove all that is 'was_container'
        // - those flagged as 'incertae_sedis' give it a new parent...
        
        /*In taxonomy.tsv:
        1. Remove remnants of containers:
        Delete all taxa flagged with was_container. These should all be childless, so there's no need to look for children to remove.
        */
        if($val = @$this->descendants[$uid]) $descendants = array_keys($val);
        else return;
        // print_r($descendants);
        //step 1: build-up descendants metadata
        foreach($descendants as $desc) {
            $desc_info[$desc] = $this->taxID_info[$desc];
            $desc_info[$desc]['uid'] = $desc;
        }
        // print_r($desc_info); exit;
        /*sample $desc_info: Array(
            [-146712] => Array(
                    [pID] => -111644
                    [r] => no rank - terminal
                    [n] => unclassified extinct Eunicida
                    [s] => trunk:26248996-ae0e-4e03-9bac-1dba4988bc83
                    [f] => was_container
            [-146713] => Array(
                    [pID] => -111644
                    [r] => family
                    [n] => Onuphidae
                    [s] => trunk:6fcc2a55-e669-41bf-b3d4-42546bca2d06,COL:de5d5d9c500a4759fb8c1bd5db434de9
                    [f] => sibling_higher
        */
        foreach($desc_info as $uid => $info) {
            if(stripos($info['f'], "was_container") !== false) { //string is found
                unset($desc_info[$uid]);
                unset($this->taxID_info[$uid]); // save to global var. -> $this->taxID_info
            }
        }
        $desc_info = array_values($desc_info); //reindex key and more importantly remove in array with null value
        // print_r($desc_info); exit;
        
        /*2. Create new containers for children of containers that remain incertae_sedis and other taxa that smasher considers incertae sedis:
        For all taxa that are flagged as incertae_sedis by smasher, create a new parent that descends from the current parent of these taxa. Call this parent "unclassified name-of-current-parent." If there is more than one direct incertae-sedis child of a given parent, put all incertae_sedis children into a common "unclassified" container. Don't worry about incertae_sedis_inherited flags. These taxa will automatically move to the right place when their parents are moved.
        */
        $i = 0;
        foreach($desc_info as $info) {
            /*[6] => Array(
                    [pID] => -111644
                    [r] => family
                    [n] => Tretoprionidae
                    [s] => COL:9247fcc1da43519fbc148267a152dc34
                    [f] => incertae_sedis
                    [uid] => -146720
            */
            if(stripos($info['f'], "incertae_sedis") !== false) { //string is found
                $new_parent_id = self::get_or_create_new_parent($info['pID']);
                $desc_info[$i]['pID'] = $new_parent_id;
            }
            $i++;
        }
        // save to global var. -> $this->taxID_info
        // print_r($desc_info); //exit;
        foreach($desc_info as $info) {
            if(substr($info['pID'],0,4) == 'unc-') $this->taxID_info[$info['uid']]['pID'] = $info['pID'];
        }
        // print_r($this->unclassified_parent);
        // print_r($this->taxID_info['-146724']);
        // print_r($this->taxID_info['-146722']);
        // print_r($this->taxID_info['-146718']);
        // exit;
    }
    private function get_or_create_new_parent($pID)
    {
        $info = $this->taxID_info[$pID]; // print_r($info);
        /*Array(
            [pID] => -50186
            [r] => order
            [n] => Eunicida
            [s] => trunk:e51ef3de-ee4f-457e-9434-4084ef8d164b,COL:850bd377331655b9421c450bfd5bda3e
            [f] => 
        */
        $sci = $info['n'];
        if(!isset($this->unclassified_parent[$sci])) {
            $this->unclassified_parent_id_increments++;
            $unclassified_new_taxon = Array(
                'uid' => 'unc-'.Functions::format_number_with_leading_zeros($this->unclassified_parent_id_increments, 6),
                'pID' => $pID,
                'n' => 'unclassified '.$sci,
                'r' => 'no rank'
            );
            $this->unclassified_parent[$sci] = $unclassified_new_taxon;
        }
        else $unclassified_new_taxon = $this->unclassified_parent[$sci];
        return $unclassified_new_taxon['uid'];
    }
    private function get_taxID_nodes_info()
    {
        $txtfile = $this->main_path.'/taxonomy.tsv'; $i = 0;
        foreach(new FileIterator($txtfile) as $line_number => $line) {
            $i++; if(($i % 200000) == 0) echo "\n".number_format($i)." ";
            if($i == 1) $line = strtolower($line);
            $row = explode("\t|\t", $line); // print_r($row);
            if($i == 1) {
                $fields = $row;
                $fields = array_filter($fields); print_r($fields);
                continue;
            }
            else {
                if(!@$row[0]) continue;
                $k = 0; $rec = array();
                foreach($fields as $fld) {
                    $rec[$fld] = @$row[$k];
                    $k++;
                }
            }
            $rec = array_map('trim', $rec);
            // print_r($rec); exit("\nstopx\n");
            /*Array(
                [uid] => f4aab039-3ecc-4fb0-a7c0-e125da16b0ff
                [parent_uid] => 
                [name] => Life
                [rank] => clade
                [sourceinfo] => trunk:1bfce974-c660-4cf1-874a-bdffbf358c19,NCBI:1
                [uniqname] => 
                [flags] => 
            )*/
            $this->taxID_info[$rec['uid']] = array("pID" => $rec['parent_uid'], 'r' => $rec['rank'], 'n' => $rec['name'], 's' => $rec['sourceinfo'], 'f' => $rec['flags']); //used for ancesty and more
            $this->descendants[$rec['parent_uid']][$rec['uid']] = ''; //used for descendants (children)
        }
        // return $final; not needed anymore
    }
    private function get_ancestry_of_taxID($tax_id)
    {   /* Array(
            [1] => Array(
                    [pID] => 1
                    [r] => no rank
                    [dID] => 8
                )
        )*/
        $final = array();
        $final[] = $tax_id;
        while($parent_id = @$this->taxID_info[$tax_id]['pID']) {
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
    private function get_descendants_of_taxID($uid, $direct_descendants_only_YN = false)
    {
        $descendants = array();
        if($val = @$this->descendants[$uid]) $descendants = array_keys($val);
        if($direct_descendants_only_YN) return $descendants;
        if($descendants) {
            foreach($descendants as $child) {
                $final[$child] = '';
                if($val = @$this->descendants[$child]) {
                    $descendants2 = array_keys($val);
                    foreach($descendants2 as $child2) {
                        $final[$child2] = '';
                        if($val = @$this->descendants[$child2]) {
                            $descendants3 = array_keys($val);
                            foreach($descendants3 as $child3) {
                                $final[$child3] = '';
                                if($val = @$this->descendants[$child3]) {
                                    $descendants4 = array_keys($val);
                                    foreach($descendants4 as $child4) {
                                        $final[$child4] = '';
                                        if($val = @$this->descendants[$child4]) {
                                            $descendants5 = array_keys($val);
                                            foreach($descendants5 as $child5) {
                                                $final[$child5] = '';
                                                if($val = @$this->descendants[$child5]) {
                                                    $descendants6 = array_keys($val);
                                                    foreach($descendants6 as $child6) {
                                                        $final[$child6] = '';
                                                        if($val = @$this->descendants[$child6]) {
                                                            $descendants7 = array_keys($val);
                                                            foreach($descendants7 as $child7) {
                                                                $final[$child7] = '';
                                                                if($val = @$this->descendants[$child7]) {
                                                                    $descendants8 = array_keys($val);
                                                                    foreach($descendants8 as $child8) {
                                                                        $final[$child8] = '';
                                                                        if($val = @$this->descendants[$child8]) {
                                                                            $descendants9 = array_keys($val);
                                                                            foreach($descendants9 as $child9) {
                                                                                $final[$child9] = '';
                                                                                // exit("\nReached level 9, will need to extend.\n");
                                                                                if($val = @$this->descendants[$child9]) {
                                                                                    $descendants10 = array_keys($val);
                                                                                    foreach($descendants10 as $child10) {
                                                                                        $final[$child10] = '';
                                                                                        // exit("\nReached level 10, will need to extend.\n");
                                                                                        if($val = @$this->descendants[$child10]) {
                                                                                            $descendants11 = array_keys($val);
                                                                                            foreach($descendants11 as $child11) {
                                                                                                $final[$child11] = '';
                                                                                                // exit("\nReached level 11, will need to extend.\n");
                                                                                                if($val = @$this->descendants[$child11]) {
                                                                                                    $descendants12 = array_keys($val);
                                                                                                    foreach($descendants12 as $child12) {
                                                                                                        $final[$child12] = '';
                                                                                                        // exit("\nReached level 12, will need to extend.\n");
                                                                                                        if($val = @$this->descendants[$child12]) {
                                                                                                            $descendants13 = array_keys($val);
                                                                                                            foreach($descendants13 as $child13) {
                                                                                                                $final[$child13] = '';
                                                                                                                // exit("\nReached level 13, will need to extend.\n");
                                                                                                                if($val = @$this->descendants[$child13]) {
                                                                                                                    $descendants14 = array_keys($val);
                                                                                                                    foreach($descendants14 as $child14) {
                                                                                                                        $final[$child14] = '';
                                                                                                                        // exit("\nReached level 14, will need to extend.\n");
                                                                                                                        if($val = @$this->descendants[$child14]) {
                                                                                                                            $descendants15 = array_keys($val);
                                                                                                                            foreach($descendants15 as $child15) {
                                                                                                                                $final[$child15] = '';
                                                                                                                                // exit("\nReached level 15, will need to extend.\n");
                                                                                                                                if($val = @$this->descendants[$child15]) {
                                                                                                                                    $descendants16 = array_keys($val);
                                                                                                                                    foreach($descendants16 as $child16) {
                                                                                                                                        $final[$child16] = '';
                                                                                                                                        // exit("\nReached level 16, will need to extend.\n");
                                                                                                                                        if($val = @$this->descendants[$child16]) {
                                                                                                                                            $descendants17 = array_keys($val);
                                                                                                                                            foreach($descendants17 as $child17) {
                                                                                                                                                $final[$child17] = '';
                                                                                                                                                // exit("\nReached level 17, will need to extend.\n");

if($val = @$this->descendants[$child17]) {
    $descendants18 = array_keys($val);
    foreach($descendants18 as $child18) {
        $final[$child18] = '';
        // exit("\nReached level 18, will need to extend.\n");
        if($val = @$this->descendants[$child18]) {
            $descendants19 = array_keys($val);
            foreach($descendants19 as $child19) {
                $final[$child19] = '';
                // exit("\nReached level 19, will need to extend.\n");
                if($val = @$this->descendants[$child19]) {
                    $descendants20 = array_keys($val);
                    foreach($descendants20 as $child20) {
                        $final[$child20] = '';
                        // exit("\nReached level 20, will need to extend.\n");
                        if($val = @$this->descendants[$child20]) {
                            $descendants21 = array_keys($val);
                            foreach($descendants21 as $child21) {
                                $final[$child21] = '';
                                exit("\nReached level 21, will need to extend.\n");
                            }
                        }
                        
                    }
                }
            }
        }
    }
}


                                                                                                                                            }
                                                                                                                                        }
                                                                                                                                        
                                                                                                                                    }
                                                                                                                                }
                                                                                                                                
                                                                                                                            }
                                                                                                                        }
                                                                                                                        
                                                                                                                    }
                                                                                                                }
                                                                                                                
                                                                                                            }
                                                                                                        }
                                                                                                    }
                                                                                                }
                                                                                            }
                                                                                        }
                                                                                    }
                                                                                }
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        // print_r($final); exit("\n-end here-\n");
        return array_keys($final);
    }
    // ----------------------------------------------------------------- end TRAM-807 -----------------------------------------------------------------
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
        /* */
        $include = array(); $include_identifier = array();
        foreach($identifiers_taxonIDs as $identifier => $taxonIDs) {
            if($taxonIDs) { //needed this validation since there might be a case where the identifier doesn't have a taxonID.
                foreach($taxonIDs as $taxonID) $include[$taxonID] = '';
            }
            $include_identifier[$identifier] = '';
        }
        return array('include' => $include, 'include_identifier' => $include_identifier);
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
    private function main_tram_807()
    {
        $taxID_info = self::get_taxID_nodes_info(); //un-comment in real operation
        /* #1. Remove branches from the PruneBytaxonID list based on their taxonID: */
        $removed_branches = self::pruneBytaxonID();
        echo "\nremoved_branches total A COL: ".count($removed_branches)."\n"; //exit("\n111\n");
        
        /* #2. Create the COL taxon set by pruning the branches from the pruneForCOL list: */
        $removed_branches = self::process_pruneForCOL_CLP('COL', $removed_branches); // print_r($removed_branches);
        echo "\nremoved_branches total B COL: ".count($removed_branches)."\n"; //exit("\n222\n");
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
            if(in_array($rec['taxonomicStatus'], array("synonym", "ambiguous synonym", "misapplied name"))) continue;
            
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
    public function duplicate_process_A($what)
    {
        if($what == 'COL') $extension_path = CONTENT_RESOURCE_LOCAL_PATH."Catalogue_of_Life_DH_step2/";          //for COL
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
        if($what == 'COL') $extension_path = CONTENT_RESOURCE_LOCAL_PATH."Catalogue_of_Life_DH_step3/";          //for COL
        if($what == 'CLP') $extension_path = CONTENT_RESOURCE_LOCAL_PATH."Catalogue_of_Life_Protists_DH_step3/"; //for CLP
        $meta = self::get_meta_info(false, $extension_path); //meta here is now the newly (temporary) created DwCA
        
        /* no longer needed
        //initial step: build up the $this->taxID_info to be used by get_ancestry
        $this->taxID_info = self::get_taxID_nodes_info($meta, $extension_path); echo "\ntaxID_info (".$meta['taxon_file'].") total rows: ".count($this->taxID_info)."\n";
        */
        
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
            // print_r($rec); //exit;
            
            // /* debug only
            $canonical = Functions::canonical_form($rec['scientificName']);
            if($canonical == 'Stelis anthracina') print_r($rec);
            // */
            /* debug only
            if(in_array($rec['taxonID'], array('4330862cc98698ec604377523f42e16d', 'edcac7cc9c02ea1c7eda0daecadd5201'))) print_r($rec);
            */
            
            // if($rec['scientificName'] == 'Phenac') print_r($rec);
            // if($rec['scientificName'] == 'Phenac') print_r($rec);
            // echo "\n".Functions::canonical_form('Phenacoc')."\n";
            // echo "\n".Functions::canonical_form('Phenacoc')."\n";
            // exit;
            
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
                // $a = array('sn' => $rec['scientificName'], 'p' => $rec['parentNameUsageID'], 'g' => $rec['genus'], 's' => $rec['specificEpithet']); ver 1
                $a = array('p' => $rec['parentNameUsageID'], 'g' => $rec['genus'], 's' => $rec['specificEpithet']);
                
                $json = json_encode($a);
                $species[$json][] = $rec['taxonID'];
            }
            elseif($rec['taxonRank'] == 'infraspecies') {
                // $a = array('sn' => $rec['scientificName'], 'p' => $rec['parentNameUsageID'], 'g' => $rec['genus'], 's' => $rec['specificEpithet'], 'i' => $rec['infraspecificEpithet']); ver 1
                $a = array('p' => $rec['parentNameUsageID'], 'g' => $rec['genus'], 's' => $rec['specificEpithet'], 'i' => $rec['infraspecificEpithet']);
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
        
        9477843dc89db0092e017377fb83408d		5364ae605ed1a16f7454bd796b77eb9b	Allonais chelata (Marcus, 1944)	Allonais	chelata	species	(Marcus, 1944)	accepted name	isExtinct:false			
        6ccd2cb5a2a7526b64a35d4ee53e1c14		5364ae605ed1a16f7454bd796b77eb9b	Allonais chelata (Marcus, 1944)	Allonais	chelata	species	(Marcus, 1944)	accepted name	isExtinct:false			
        */
        
        /* debug only - force assign
        $dup_species = array();
        $dup_species[] = array("b5dd6a31ea9b6edbd27b3585d7ae7355", "a9c49d341fe692157910e89f7a473aed");
        */
        
        // print_r($dup_species); print_r($dup_infraspecies);
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
        
        // print_r($this->ids2retain); //exit;
        
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
            $ret = self::select_1_from_list_of_taxonIDs($pair, $what);
            $taxonIDs_removed = array_diff($ret[0], $ret[1]);
            //start build id-to-retain vs id(s)-to-remove
            foreach($taxonIDs_removed as $id) $this->ids2retain[$id] = $ret[1][0];
            //end
            if($taxonIDs_removed) $final = array_merge($final, $taxonIDs_removed);
        }
        return $final;
        // exit("\n111\n");
    }
    private function select_1_from_list_of_taxonIDs($pair, $what)
    {
        /* e.g. of duplicate taxa:
        b5dd6a31ea9b6edbd27b3585d7ae7355     e4598baeb2479608f76a723b22e896c3    Ilyodrilus frantzi Brinkhurst, 1965 Ilyodrilus  frantzi species Brinkhurst, 1965    accepted name   isExtinct:false         
        a9c49d341fe692157910e89f7a473aed     e4598baeb2479608f76a723b22e896c3    Ilyodrilus frantzi Brinkhurst, 1965 Ilyodrilus  frantzi species Brinkhurst, 1965    accepted name   isExtinct:false         

        ca6ee60b36219c555b9b2527f0a9d991     b5dd6a31ea9b6edbd27b3585d7ae7355    Ilyodrilus frantzi frantzi Brinkhurst and Cook, 1966    Ilyodrilus  frantzi infraspecies    Brinkhurst and Cook, 1966   accepted name   isExtinct:false frantzi     
        cc97c34bd718943a9e965873f0ac2cdf     b5dd6a31ea9b6edbd27b3585d7ae7355    Ilyodrilus frantzi capillatus Brinkhurst and Cook, 1966 Ilyodrilus  frantzi infraspecies    Brinkhurst and Cook, 1966   accepted name   isExtinct:false capillatus      

        *** but get_ancestry seems not needed. What is needed is get_children instead (todo). Reject those without children e.g. between b5dd6a31ea9b6edbd27b3585d7ae7355 and a9c49d341fe692157910e89f7a473aed.
        a9c49d341fe692157910e89f7a473aed doesn't have children. So it has to be rejected.
        */

        /* added this in spreadsheet mergeForCOL
        Array(
            [0] => 133d4377d83891a5c5d6f2488b02d2a0
            [1] => a1349bf1ddbc62bb2a93f7cccff3a053     - undefined parent
        )
        4271f4dc9ddece87eb2f65bc7dcc0fdc		a1349bf1ddbc62bb2a93f7cccff3a053	Leptomithrax tuberculatus mortenseni Bennett, 1964	Leptomithrax	tuberculatus	infraspecies	Bennett, 1964	accepted name	
        133d4377d83891a5c5d6f2488b02d2a0		9916bb869074011f8294fd30f7fbe4f0	Leptomithrax tuberculatus Whitelegge, 1900	Leptomithrax	tuberculatus	species	Whitelegge, 1900	accepted name	isExtinct:true	
        a1349bf1ddbc62bb2a93f7cccff3a053		9916bb869074011f8294fd30f7fbe4f0	Leptomithrax tuberculatus Whitelegge, 1900	Leptomithrax	tuberculatus	species	Whitelegge, 1900	accepted name	isExtinct:false	
        */

        $orig_pair = $pair;
        if($what == 'species' || $what == 'infraspecies') { //for both cases actully, we can live without this filter actually.
                                 $pair = self::filter1_status($pair);          //equal to "provisionally accepted name"
                                 // echo "\nresult filter1:\n"; print_r($pair);
            if(count($pair) > 1) {$pair = self::filter2_authorship($pair);      //without authorship
                                 //echo "\nresult filter2:\n"; print_r($pair);
                                 }
            elseif(count($pair) == 1) return array($orig_pair, $pair); //array_diff($orig_pair, $pair);

            if(count($pair) > 1) {$pair = self::filter3_authorship($pair);      //without 4-digit no.
                                 //echo "\nresult filter3:\n"; print_r($pair);
                                 }
            elseif(count($pair) == 1) return array($orig_pair, $pair); //array_diff($orig_pair, $pair);

            if(count($pair) > 1) {$pair = self::filter4_authorship($pair);      //authority date is larger
                                 //echo "\nresult filter4:\n"; print_r($pair);
                                 }
            elseif(count($pair) == 1) return array($orig_pair, $pair); //array_diff($orig_pair, $pair);
            if(count($pair) > 1) {$pair = self::filter5_authorship($pair);      //without parentheses
                                 //echo "\nresult filter5:\n"; print_r($pair);
                                 }
            elseif(count($pair) == 1) return array($orig_pair, $pair); //array_diff($orig_pair, $pair);

            if($what == 'infraspecies') {
                /*  5.1. verbatimTaxonRank IS NOT empty | verbatimTaxonRank IS empty
                    5.2. verbatimTaxonRank IS subsp. | verbatimTaxonRank IS var. OR f.
                    5.3. verbatimTaxonRank IS var. | verbatimTaxonRank IS f.
                */
                if(count($pair) > 1) $pair = self::filter5_1_verbatimRank($pair);   //verbatimTaxonRank IS empty
                elseif(count($pair) == 1) return array($orig_pair, $pair); //array_diff($orig_pair, $pair);
                if(count($pair) > 1) $pair = self::filter5_2_verbatimRank($pair);   //verbatimTaxonRank IS var. OR f.
                elseif(count($pair) == 1) return array($orig_pair, $pair); //array_diff($orig_pair, $pair);
                if(count($pair) > 1) $pair = self::filter5_3_verbatimRank($pair);   //verbatimTaxonRank IS f.
                elseif(count($pair) == 1) return array($orig_pair, $pair); //array_diff($orig_pair, $pair);
            }

            if(count($pair) > 1) {$pair = self::filter6_subgenus($pair);        //subgenus IS NOT empty
                                 // echo "\nresult filter6:\n"; print_r($pair);
                                 }
            elseif(count($pair) == 1) return array($orig_pair, $pair); //array_diff($orig_pair, $pair);
            
            if(count($pair) > 1) {$pair = self::filter7_isExtinct($pair);       //isExtinct IS FALSE
                                 // echo "\nresult filter7:\n"; print_r($pair);
                                 }
            elseif(count($pair) == 1) return array($orig_pair, $pair); //array_diff($orig_pair, $pair);
            
            if(count($pair) > 1) {$pair = self::filter8_NoAncestry($pair);       //NoAncestry
                                 // echo "\nresult filter8:\n"; print_r($pair);
                                 }
            elseif(count($pair) == 1) return array($orig_pair, $pair); //array_diff($orig_pair, $pair);
            
            if(count($pair) > 1) { //should not go here...
                print_r($pair);
                exit("\nstill pair has > 1 records\n");
            }
            elseif(count($pair) == 1) return array($orig_pair, $pair); //array_diff($orig_pair, $pair);
            
            /*Prefer | Reject
            1. accepted name | provisionally accepted name
            2. scientificNameAuthorship IS NOT empty | scientificNameAuthorship IS empty
            3. scientificNameAuthorship WITH 4-digit number | scientificNameAuthorship WITHOUT 4-digit number
            4. authority date (4-digit number in scientificNameAuthorship) is smaller | authority date is larger
            5. scientificNameAuthorship WITH parentheses | scientificNameAuthorship WITHOUT parentheses
            6. subgenus IS empty | subgenus IS NOT empty
            7. isExtinct IS TRUE | isExtinct IS FALSE

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
    private function filter8_NoAncestry($pair)
    {
        /* working but only for $pair with count == 2
        if(count($pair) > 1) unset($pair[1]); //pick one
        $pair = array_values($pair); //reindex key
        return $pair;
        */
        
        foreach($pair as $taxon_id) return array($taxon_id); //just pick one
        
        /* working but get_ancestry() is not the solution but get_children() is. ToDo get_children(), reject those without children vs those with children.
        $orig_pair = $pair;
        $i = -1;
        foreach($pair as $taxonID) { $i++;
            if($info = $this->taxonID_info[$taxonID]) {
                [02dcf48d2ba98f149bbf56a1f91f2da7] => Array(  e.g. rec for $this->taxonID_info
                    [s] => accepted name | [sna] => (Loden, 1977) | [vr] => [sg] => [isE] => 
                )
                $ancestry = self::get_ancestry_of_taxID($taxonID, $this->taxID_info); print_r($ancestry);
                if(!$ancestry) unset($pair[$i]);
            }
        }
        $pair = array_values($pair); //reindex key
        if(!$pair)              $pair = $orig_pair;
        if(count($pair) > 1)    unset($pair[1]); //pick one
        $pair = array_values($pair); //reindex key
        return $pair;
        */
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
        /* commented since this is no longer the last filter. There is now filter8_NoAncestry()
        if(count($pair) > 1)    unset($pair[1]); //pick one
        $pair = array_values($pair); //reindex key
        */
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
            // print_r($ids_with_4digit_no);
            $arr_tmp = array();
            foreach($ids_with_4digit_no as $taxonID => $numeric) $arr_tmp[] = array('id' => $taxonID, 'numeric' => $numeric);
            $to_remove = false;
            // print_r($arr_tmp);
            if(@$arr_tmp[0]['numeric'] > @$arr_tmp[1]['numeric']) $to_remove = $arr_tmp[0]['id'];
            if(@$arr_tmp[1]['numeric'] > @$arr_tmp[0]['numeric']) $to_remove = $arr_tmp[1]['id'];
            if($to_remove) {
                $i = -1;
                foreach($pair as $taxonID) { $i++;
                    if($taxonID == $to_remove) {
                        unset($pair[$i]);
                        $pair = array_values($pair); //reindex key
                        // print_r($pair); exit("\nfollow this format\n");
                        return $pair;
                    }
                }
            }
        }
        elseif(count(@$ids_with_4digit_no) < 2) return $orig_pair;
        elseif(count(@$ids_with_4digit_no) > 2) { //this can also be used for above => if(count(@$ids_with_4digit_no) == 2)
            // print_r($ids_with_4digit_no);
            // exit("\nNeed to script this up...\n");
            $pair = self::get_least_from_multiple_dates($ids_with_4digit_no);
            return $pair;
        }
        return $orig_pair;
    }
    private function get_least_from_multiple_dates($a)
    {
        // $a = Array(
        //     '46463756d3068caa095f0cfdd7d5898f' => 1953,
        //     '224534e3c7a97f445510d28db81dd34a' => 1973,
        //     '88a861a82332663835794693906560bd' => 1880);
        // print_r($a);
        asort($a); //print_r($a);
        foreach($a as $taxon_id => $numeric) break; //get the first taxon_id
        return array($taxon_id);
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
        
        if($pair != $orig_pair) return $pair;
        
        $orig_pair = $pair;
        $i = -1;
        foreach($pair as $taxonID) { $i++;
            if($info = $this->taxonID_info[$taxonID]) {
                /*[02dcf48d2ba98f149bbf56a1f91f2da7] => Array(  e.g. rec for $this->taxonID_info
                    [s] => accepted name | [sna] => (Loden, 1977) | [vr] => [sg] => [isE] => 
                )*/
                if($info['s'] == '') unset($pair[$i]);
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
        $params['spreadsheetID'] = '1wWLmuEGyNZ2a91rZKNxLvxKRM_EYV6WBbKxq6XXoqvI';

        if($what == "COL") $params['range'] = 'pruneForCOL!A1:A600';
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
}
?>