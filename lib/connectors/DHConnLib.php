<?php
namespace php_active_record;
/* connector: [DHconn.php] */
class DHConnLib
{
    function __construct($folder)
    {
        $this->resource_id = $folder;
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->taxon_ids = array();
        $this->debug = array();
        if(Functions::is_production()) { //not yet run in production...
            $this->download_options = array(
                'cache_path'         => '/extra/eol_cache_smasher/',
                'download_wait_time' => 1000000, 'timeout' => 600, 'download_attempts' => 1, 'delay_in_minutes' => 0, 'expire_seconds' => false);
            $this->main_path = "/extra/other_files/DWH/EOL Dynamic Hierarchy Active Version/DH_v1_1/";
        }
        else {
            $this->download_options = array(
                'cache_path'         => '/Volumes/AKiTiO4/eol_cache_smasher/',
                'download_wait_time' => 250000, 'timeout' => 600, 'download_attempts' => 1, 'delay_in_minutes' => 0, 'expire_seconds' => false);
            $this->main_path = "/Volumes/AKiTiO4/d_w_h/EOL Dynamic Hierarchy Active Version/DH_v1_1/";
        }
    }
    // ----------------------------------------------------------------- start TRAM-807 -----------------------------------------------------------------
    function generate_children_of_taxa_from_DH()
    {
        self::get_taxID_nodes_info($this->main_path.'/taxon.tab');
        
        /* tests only
        $eol_id = '46564414'; //Gadus
        $ancestry = self::get_ancestry_of_taxID($eol_id); print_r($ancestry); exit; //working OK but not used yet
        // $eol_id = '-6989';
        // $children = self::get_descendants_of_taxID($eol_id); print_r($children); //exit;
        // echo "\ncount: ".count($this->taxID_info)."\n";
        exit("\n-end tests-\n");
        */

        /*
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
            print_r($rec); exit("\nstopx\n");
            // if($i > 10) break; //debug only
        }
        */
    }
    private function get_taxID_nodes_info($txtfile)
    {
        $this->taxID_info = array(); $this->descendants = array(); //initialize global vars
        $i = 0;
        foreach(new FileIterator($txtfile) as $line_number => $line) {
            $i++; if(($i % 200000) == 0) echo "\n".number_format($i)." ";
            // if($i == 1) $line = strtolower($line);
            $row = explode("\t", $line); // print_r($row);
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
            print_r($rec); exit("\nstopx\n");
            /*Array
            (
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
            )
            */
            $this->taxID_info[$rec['uid']] = array("pID" => $rec['parent_uid'], 'r' => $rec['rank'], 'n' => $rec['name'], 's' => $rec['sourceinfo'], 'f' => $rec['flags']); //used for ancesty and more
            $this->descendants[$rec['parent_uid']][$rec['uid']] = ''; //used for descendants (children)
        }
    }
    private function get_ancestry_of_taxID($tax_id)
    {
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
    /*========================================================================================Ends here. Below here is remnants from a copied template */ 
    //=====================================================start post step 4
    function post_step_4()
    {   /*That's no big deal, we just have to add a step after "4. Clean up deadend branches" to clean up empty containers. If we end up with a lot of containers with only one 
          or a few descendants (<5), we may want to remove those containers too and attach their children directly to the grandparent. The main function of the containers is to keep 
          the taxon tree tidy for browsing. Incertae sedis taxa only create a problem if they occur in larger batches and it doesn't make sense to keep them containerized if there 
          are only a handful of them.
        */
        self::get_taxID_nodes_info($this->main_path.'/taxonomy2.txt'); //un-comment in real operation
        foreach($this->taxID_info as $uid => $info) {
            // print_r($info); exit("\n$uid\n");
            /*Array( $uid = f4aab039-3ecc-4fb0-a7c0-e125da16b0ff
                [pID] => 
                [r] => clade
                [n] => Life
                [s] => trunk:1bfce974-c660-4cf1-874a-bdffbf358c19,NCBI:1
                [f] => 
            */
            if(substr($uid,0,5) == 'unc-P') {
                $children = self::get_descendants_of_taxID($uid);
                if($children) {
                    if(count($children) < 5) {
                        @$totals['unc-P']['< 5 desc']++;
                        /* If we end up with a lot of containers with only one or a few descendants (<5), we may want to remove those containers too 
                           and attach their children directly to the grandparent. */
                        // print_r($children); echo " - $uid \n";
                        if($val = $info['pID']) $parent_of_unclassified = $val;
                        else { //should not go here...
                            print_r($info);
                            exit("\nInvestigate no parent\n");
                        }
                        foreach($children as $child) $this->taxID_info[$child]['pID'] = $parent_of_unclassified;
                        unset($this->taxID_info[$uid]); //then delete the unclassified taxon
                    }
                }
                else {
                    unset($this->taxID_info[$uid]);
                    @$totals['unc-P']['zero desc']++;
                }
            }
        }
        self::save_global_var_to_txt($this->main_path.'/taxonomy3.txt');
        print_r($totals);
    }
    //=======================================================end post step 4
    function step_4pt2_of_9()
    {   /*4.2. remove barren taxa EXCEPT if at least one of the following conditions is true:
        (1) One of the sources of the taxon is trunk
        (2) The rank of the barren taxon is genus
        (3) The barren taxon has descendants whose rank is genus (either direct descendants or indirect, i.e., grandchildren or great grandchildren)
        */
        self::get_taxID_nodes_info($this->main_path.'/taxonomy1.txt'); //un-comment in real operation
        foreach($this->taxID_info as $uid => $info) {
            // print_r($info); exit("\n$uid\n");
            /*Array(
                [pID] => 
                [r] => clade
                [n] => Life
                [s] => trunk:1bfce974-c660-4cf1-874a-bdffbf358c19,NCBI:1
                [f] => 
            */

            /* new added, per Katja: https://eol-jira.bibalex.org/browse/TRAM-807?focusedCommentId=63432&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-63432 */
            if(in_array($uid, array('-1137888', '-10357', '-1072897', '-10305', '-10352', '-10308', '-10365', '-10359', '-10379', '-10326', '-10350', '-10366', '-10311', '-10325', '-10337', '-10339', '-10360', '-1137820', '-10331', '-10392', '-10333', '-10309', '-10314', '-10323', '-10391', '-10368', '-10304', '-1137794', '-10316', '-10364', '-10385', '-10383', '-10377', '-10371', '-10386', '-10293', '-10321', '-10388', '-10336', '-10297', '-10393', '-10303', '-10390', '-10346', '-10342', '-10313', '-10349', '-10382', '-10384', '-10389', '-10298', '-10300', '-1137814', '-10319', '-10294', '-10322', '-1072889', '-10340', '-1137830', '-1137907', '-10334', '-721754', '-10302', '-10369', '-10324', '-721756', '-10291', '-10292', '-10378', '-1137858', '-10332', '-10307', '-10330', '-10310', '-10375', '-10381', '-10351', '-10374', '-10343', '-10338', '-10306', '-10353', '-10312', '-10362', '-10327', '-10335', '-10347', '-10394', '-10328', '-10329', '-10372', '-10355', '-10344', '-10317', '-10370', '-10296', '-10301', '-10348', '-10315', '-1137789', '-10295', '-10363', '-10367', '-10299', '-10356', '-10341', '-10345', '-10318', '-1137906', '-10354', '-10358', '-10380', '-10387', '-10373', '-10361'))) {
                unset($this->taxID_info[$uid]); //115 ids deleted
                continue;
            }

            if(stripos($info['f'], "barren") !== false) { //string is found
                $sources = self::get_all_sources($info['s']); // print_r($sources);
                if(in_array('trunk', $sources)) continue;
                if($info['r'] == 'genus') continue;
                if(self::taxon_has_descendants_whose_rank_is_genus($uid)) continue;
                unset($this->taxID_info[$uid]);
            }
        }
        self::save_global_var_to_txt($this->main_path.'/taxonomy2.txt');
    }
    private function taxon_has_descendants_whose_rank_is_genus($uid)
    {
        $children = self::get_descendants_of_taxID($uid); // print_r($children); exit("\n$uid\n");
        if($children) {
            foreach($children as $child) {
                if($this->taxID_info[$child]['r'] == 'genus') return true;
                // if(substr($children[0],0,5) == 'unc-P') return true; //new by Eli. Was never used. Used Katja's addt'l instruction for step 1 instead.
            }
        }
        return false;
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
                    [uid] => unc-P00001
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
            $rek[] = ''; //$rec['r']; -- no longer 'no rank'
            $rek[] = '';
            $rek[] = '';
            fwrite($WRITE, implode("\t|\t", $rek)."\t|\t"."\n");
        }
        fclose($WRITE);
        unset($this->unclassified_parent);
    }
    private function save_global_var_to_txt($destination = false)
    {
        if(!$destination) $destination = $this->main_path.'/taxonomy1.txt';
        $WRITE = fopen($destination, "w");
        $fields = array('uid','parent_uid','name','rank','sourceinfo','uniqname','flags');
        fwrite($WRITE, implode("\t|\t", $fields)."\t|\t"."\n");
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
            fwrite($WRITE, implode("\t|\t", $rek)."\t|\t"."\n");
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
        $sources = self::get_all_sources($rec['s']); // print_r($sources);
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
        if($val = @$this->descendants[$uid]) {
            $descendants = array_keys($val);
            $descendants_to_create_parent_container_YN = self::check_descendants_to_create_parent_container_YN($descendants);
        }
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
        if($descendants_to_create_parent_container_YN) {
            // echo "\ncreating parent container\n";
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
                /* wrong criteria: 
                if(stripos($info['f'], "incertae_sedis") !== false) { //string is found
                */
                if(self::there_is_incertae_sedis_in_flag($info['f'])) {
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
        }
        // else echo "\nNOT creating parent container\n";
        
        // print_r($this->unclassified_parent);
        // print_r($this->taxID_info['-146724']);
        // print_r($this->taxID_info['-146722']);
        // print_r($this->taxID_info['-146718']);
        // exit;
        
    }
    function there_is_incertae_sedis_in_flag($flag_str)
    {   /* "Clarification, so it should only be for flag where there is 'incertae_sedis'
        Which means it also includes flag = incertae_sedis,barren
        But definitely excludes 'incertae_sedis_inherited'."
        Yes, exactly. Other flags in addition to incertae_sedis are fine, but we don't want things that have the 'incertae_sedis_inherited' flag.
        */
        $flags = explode(",", $flag_str);
        foreach($flags as $flag) {
            if($flag == "incertae_sedis") return true;
            if($flag == "incertae_sedis_inherited") return false;
        }
        return false;
    }
    private function check_descendants_to_create_parent_container_YN($descendants)
    {   /* add a new rule to Step 1 (Clean up children of container taxa): Don't create a new container taxon if all of its descendants will be barren 
           and don't qualify for the rank=genus or source=trunk exception. From: https://eol-jira.bibalex.org/browse/TRAM-807?focusedCommentId=63428&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-63428
        */
        // echo "\ndescendants: ".count($descendants)."\n"; print_r($descendants);
        foreach($descendants as $desc_id) {
            $info = $this->taxID_info[$desc_id]; //print_r($info);
            $sources = self::get_all_sources($info['s']); // print_r($sources);
            // echo "\n[$desc_id]---\n";
            if((stripos($info['f'], "barren") !== false) && (!in_array('trunk', $sources) && $info['r'] != 'genus' && !self::taxon_has_descendants_whose_rank_is_genus($desc_id))) {}
            else return true;
        }
        return false;
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
                'uid' => 'unc-P'.Functions::format_number_with_leading_zeros($this->unclassified_parent_id_increments, 5),
                'pID' => $pID,
                'n' => 'unclassified '.$sci,
                'r' => 'no rank'
            );
            $this->unclassified_parent[$sci] = $unclassified_new_taxon;
        }
        else $unclassified_new_taxon = $this->unclassified_parent[$sci];
        return $unclassified_new_taxon['uid'];
    }
    public function generate_dwca($resource_id)
    {
        $txtfile = $this->main_path.'/taxonomy_4dwca.txt';
        require_library('connectors/DHSourceHierarchiesAPI_v2');
        $func = new DHSourceHierarchiesAPI_v2($resource_id);
        $func->generate_dwca($resource_id, $txtfile, false); //3rd param false means, will not generate synonyms in DwCA
    }
    public function save_all_ids_from_all_hierarchies_2MySQL()
    {
        require_library('connectors/DHSourceHierarchiesAPI_v2');
        $func = new DHSourceHierarchiesAPI_v2(null); //param supposed to be resource_id
        $func->save_all_ids_from_all_hierarchies_2MySQL('write2mysql_v2.txt', true); //2nd param true means it's a postProcess task
        
    }
    function get_descendants_of_taxID($uid, $direct_descendants_only_YN = false, $this_descendants = array())
    {
        if(!isset($this->descendants)) $this->descendants = $this_descendants;
        $final = array();
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
                                // exit("\nReached level 21, will need to extend.\n");
                                if($val = @$this->descendants[$child21]) {
                                    $descendants22 = array_keys($val);
                                    foreach($descendants22 as $child22) {
                                        $final[$child22] = '';
                                        // exit("\nReached level 22, will need to extend.\n");
                                        if($val = @$this->descendants[$child22]) {
                                            $descendants23 = array_keys($val);
                                            foreach($descendants23 as $child23) {
                                                $final[$child23] = '';
                                                // exit("\nReached level 23, will need to extend.\n");
                                                if($val = @$this->descendants[$child23]) {
                                                    $descendants24 = array_keys($val);
                                                    foreach($descendants24 as $child24) {
                                                        $final[$child24] = '';
                                                        // exit("\nReached level 24, will need to extend.\n");
                                                        if($val = @$this->descendants[$child24]) {
                                                            $descendants25 = array_keys($val);
                                                            foreach($descendants25 as $child25) {
                                                                $final[$child25] = '';
                                                                // exit("\nReached level 25, will need to extend.\n");
                                                                if($val = @$this->descendants[$child25]) {
                                                                    $descendants26 = array_keys($val);
                                                                    foreach($descendants26 as $child26) {
                                                                        $final[$child26] = '';
                                                                        // exit("\nReached level 26, will need to extend.\n");
                                                                        if($val = @$this->descendants[$child26]) {
                                                                            $descendants27 = array_keys($val);
                                                                            foreach($descendants27 as $child27) {
                                                                                $final[$child27] = '';
                                                                                // exit("\nReached level 27, will need to extend.\n");
                                                                                if($val = @$this->descendants[$child27]) {
                                                                                    $descendants28 = array_keys($val);
                                                                                    foreach($descendants28 as $child28) {
                                                                                        $final[$child28] = '';
                                                                                        // exit("\nReached level 28, will need to extend.\n");
                                                                                        if($val = @$this->descendants[$child28]) {
                                                                                            $descendants29 = array_keys($val);
                                                                                            foreach($descendants29 as $child29) {
                                                                                                $final[$child29] = '';
                                                                                                // exit("\nReached level 29, will need to extend.\n");
                                                                                                if($val = @$this->descendants[$child29]) {
                                                                                                    $descendants30 = array_keys($val);
                                                                                                    foreach($descendants30 as $child30) {
                                                                                                        $final[$child30] = '';
                                                                                                        exit("\nReached level 30, will need to extend.\n");
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
        if($final) return array_keys($final);
        return array();
    }
    // ----------------------------------------------------------------- end TRAM-807 -----------------------------------------------------------------
}
?>