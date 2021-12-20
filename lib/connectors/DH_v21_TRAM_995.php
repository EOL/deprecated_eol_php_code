<?php
namespace php_active_record;
/* connector: [taxonIDs_for_DH21.php] - TRAM-995
*/
class DH_v21_TRAM_995
{
    function __construct($folder)
    {
        exit("\nB. Moved to ver. 2.\n");
        $this->resource_id = $folder;
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->debug = array();
        if(Functions::is_production()) {} //not used
        else {
            $this->download_options = array(
                'download_wait_time' => 250000, 'timeout' => 600, 'download_attempts' => 1, 'delay_in_minutes' => 0, 'expire_seconds' => false);
            $this->main_path = "/Volumes/AKiTiO4/d_w_h/TRAM-995/";
        }
        $this->tsv['DH11_Jen'] = $this->main_path."/dh1_1/DH1_1working.txt";
        $this->tsv['DH21_Jen'] = $this->main_path."/dh2_1/DH2_1working.txt";
        $this->tsv['DH11'] = $this->main_path."/DH11_working_new.txt";
        $this->tsv['DH21'] = $this->main_path."/DH21_working_new.txt";
    }
    // ----------------------------------------------------------------- start TRAM-807 -----------------------------------------------------------------
    function start()
    {   
        /* works
        self::get_taxID_nodes_info($this->tsv['DH11']); //un-comment in real operation
        // self::get_taxID_nodes_info($this->main_path."/work_4.txt"); //un-comment in real operation
        $taxonID = "EOL-000002321109"; //'-8365'; //'EOL-000002321109';
        $ancestry = self::get_ancestry_of_taxID($taxonID); print_r($ancestry); echo "ancestry"; //exit; //working OK but not used yet
        
        foreach($ancestry as $id) {
            if($rec = $this->taxID_info[$id]) print_r($rec);
        }
        
        $taxonID = "EOL-000002321109";  //'-8365'; //'EOL-000002321109';
        $children = self::get_descendants_of_taxID($taxonID); print_r($children); echo "children"; exit("\n-end test-\n");
        */
        
        /* ######################################################## first step: run only once in lifetime - DONE 
        // new cols for DH1 and DH2 - need to build-up:
        // if genus or species => canonical_family_ancestor
        // else                => canonical_parent AND canonical_grandparent
        self::pre_build_up_DH(); exit("\n-end pre_build_up_DH-\n");
        ######################################################## */
        
        
        /* GROUP 1: DH2 taxa (homonyms or not) that have no canonical match in DH1, i.e., DH1canonicalName = DH2canonicalName is never true
        Create a new EOL-xxx style identifier for each of these taxa and update all relevant parentNameUsageID values. 
        Also, put "new" in the EOLidAnnotations column for each taxon.
        */
        /* ######################################################## Ok good
        self::tag_DH2_with_NoCanonicalMatch_in_DH1();   //ends with work_2.txt
        self::tag_DH2_with_Homonyms_YN();               //ends with work_3.txt
        self::tag_DH2_with_group();                     //ends with work_4.txt -> also generates stats to see if all categories are correctly covered...
        ######################################################## */
        
        // /* worked OK
        self::proc_Group_2_1();     //ends with work_6.txt
        // */
        exit("\n-stop muna-\n");
    }
    private function proc_Group_2_1()
    {
        $this->DH1_canonicals = self::parse_tsv2($this->tsv['DH11'], 'get_canonicals_and_info_DH1'); // print_r($this->DH1_canonicals);
            // -> used in main_G2_1 and main_G2_2
        $this->DH2_canonicals = self::parse_tsv2($this->main_path."/work_4.txt", 'get_canonicals_and_info_DH2'); // print_r($this->DH2_canonicals);
            // -> used in main_G3_1 and main_G3_2
        $this->replaced_by = array();
        self::parse_tsv2($this->main_path."/work_4.txt", 'group_2_1'); //generates work_5.txt
        unset($this->DH1_canonicals);
        unset($this->DH2_canonicals);
        self::parse_tsv2($this->main_path."/work_5.txt", 'refresh_parentIDs_work_5'); //generates work_6.txt
        unset($this->replaced_by);
    }
    private function parse_tsv2($txtfile, $task)
    {   $i = 0;
        foreach(new FileIterator($txtfile) as $line_number => $line) {
            $i++; if(($i % 200000) == 0) echo "\n".number_format($i)." [$task]";
            if($i == 1) $line = strtolower($line);
            $row = explode("\t", $line); // print_r($row);
            if($i == 1) {
                $fields = $row;
                $fields = array_filter($fields); //print_r($fields);
                if($task == 'group_2_1') {
                    $tmp_fields = $fields;
                    $WRITE = fopen($this->main_path."/work_5.txt", "w");
                    fwrite($WRITE, implode("\t", $tmp_fields)."\n");
                }
                elseif($task == 'refresh_parentIDs_work_5') {
                    $WRITE = fopen($this->main_path."/work_6.txt", "w");
                    fwrite($WRITE, implode("\t", $fields)."\n");
                }
                //==========================================================================================
                if(in_array($task, array('build_up_useful_cols_DH11', 'build_up_useful_cols_DH21'))) {
                    $tmp_fields = $fields;
                    $tmp_fields[] = 'canonical_family_ancestor';
                    $tmp_fields[] = 'canonical_parent';
                    $tmp_fields[] = 'canonical_grandparent';
                }
                if($task == 'build_up_useful_cols_DH11') {
                    $WRITE = fopen($this->main_path."/DH11_working_new.txt", "w");
                    fwrite($WRITE, implode("\t", $tmp_fields)."\n");
                }
                elseif($task == 'build_up_useful_cols_DH21') {
                    $WRITE = fopen($this->main_path."/DH21_working_new.txt", "w");
                    fwrite($WRITE, implode("\t", $tmp_fields)."\n");
                }
                //==========================================================================================
                continue;
            }
            else {
                if(!@$row[0]) continue;
                $k = 0; $rec = array();
                foreach($fields as $fld) { $rec[$fld] = @$row[$k]; $k++; }
            }
            // $rec = array_map('trim', $rec);
            // print_r($rec); exit("\nstopxy\n");
            
            if(in_array($task, array('build_up_useful_cols_DH11', 'build_up_useful_cols_DH21'))) {
                $rec = self::main_build_up($rec); 
                fwrite($WRITE, implode("\t", $rec)."\n");
            }
            
            if($task == 'group_2_1') {
                    if($rec['group'] == 'G2_1') {$rec = self::main_G2_1($rec); fwrite($WRITE, implode("\t", $rec)."\n");}
                elseif($rec['group'] == 'G2_2') {$rec = self::main_G2_2($rec); fwrite($WRITE, implode("\t", $rec)."\n");}
                elseif($rec['group'] == 'G3_1') {$rec = self::main_G3_1($rec); fwrite($WRITE, implode("\t", $rec)."\n");}
                elseif($rec['group'] == 'G3_2') {$rec = self::main_G3_2($rec); fwrite($WRITE, implode("\t", $rec)."\n");}
                else fwrite($WRITE, implode("\t", $rec)."\n"); //carryover the rest
            }
            elseif($task == 'get_canonicals_and_info_DH1') { //print_r($rec); exit("\n172\n");
                /*Array(
                    [taxonid] => EOL-000000000001
                    [source] => trunk:1bfce974-c660-4cf1-874a-bdffbf358c19,NCBI:1
                    [acceptednameusageid] => 
                    [parentnameusageid] => 
                    [scientificname] => Life
                    [taxonrank] => clade
                    [taxonomicstatus] => valid
                    [canonicalname] => Life
                    [eolid] => 2913056
                )*/
                $final[$rec['canonicalname']][] = array('ID' => $rec['taxonid'], 'pID' => $rec['parentnameusageid'], 'r' => $rec['taxonrank'],
                'can_fam_anc' => $rec['canonical_family_ancestor'], 'can_par' => $rec['canonical_parent'], 'can_gpa' => $rec['canonical_grandparent']);
            }
            elseif($task == 'get_canonicals_and_info_DH2') {    // print_r($rec); exit("\n173\n");
                /*Array(
                    [taxonid] => 4038af35-41da-469e-8806-40e60241bb58
                    [source] => trunk:4038af35-41da-469e-8806-40e60241bb58,NCBI:1
                    [furtherinformationurl] => 
                    [acceptednameusageid] => 
                    [parentnameusageid] => 
                    [scientificname] => Life
                    [taxonrank] => 
                    [taxonomicstatus] => accepted
                    [taxonremarks] => 
                    [datasetid] => trunk
                    [canonicalname] => Life
                    [eolid] => 
                    [eolidannotations] => 
                    [landmark] => 
                    [canonical_family_ancestor] => 
                    [canonical_parent] => 
                    [canonical_grandparent] => 
                    [canomatchdh1_yn] => 1
                    [homonyms_yn] => N
                    [group] => G2_1
                )*/
                if(in_array($rec['group'], array('G3_1', 'G3_2'))) {
                    $final[$rec['canonicalname']][] = array('ID' => $rec['taxonid'], 'pID' => $rec['parentnameusageid'], 'r' => $rec['taxonrank'],
                    'can_fam_anc' => $rec['canonical_family_ancestor'], 'can_par' => $rec['canonical_parent'], 'can_gpa' => $rec['canonical_grandparent']);
                }
            }
            elseif($task == 'refresh_parentIDs_work_5') {
                $parent_ID = $rec['parentnameusageid'];
                $accept_ID = $rec['acceptednameusageid'];
                if($val = @$this->replaced_by[$parent_ID]) $rec['parentnameusageid'] = $val;
                if($val = @$this->replaced_by[$accept_ID]) $rec['acceptednameusageid'] = $val;
                fwrite($WRITE, implode("\t", $rec)."\n");
            }
        }
        if($task == 'get_canonicals_and_info_DH1') return $final;
        elseif($task == 'get_canonicals_and_info_DH2') return $final;
        elseif($task == 'group_2_1') {
            fclose($WRITE);
            $total = self::get_total_rows($this->main_path."/work_5.txt"); echo "\n work_5 [$total]\n";
        }
        elseif($task == 'refresh_parentIDs_work_5') {
            fclose($WRITE);
            $total = self::get_total_rows($this->main_path."/work_5.txt"); echo "\n work_5 [$total]\n";
            $total = self::get_total_rows($this->main_path."/work_6.txt"); echo "\n work_6 [$total]\n";
        }
        if($task == 'build_up_useful_cols_DH11') {
            fclose($WRITE);
            $total = self::get_total_rows($this->tsv['DH11_Jen']); echo "\n DH11 [$total]\n";
            $total = self::get_total_rows($this->main_path."/DH11_working_new.txt"); echo "\n DH11_working_new [$total]\n";
        }
        elseif($task == 'build_up_useful_cols_DH21') {
            fclose($WRITE);
            $total = self::get_total_rows($this->tsv['DH21_Jen']); echo "\n DH21 [$total]\n";
            $total = self::get_total_rows($this->main_path."/DH21_working_new.txt"); echo "\n DH21_working_new [$total]\n";
        }
    }
    private function RANK_TEST_yn($taxonrank, $rek)
    {
        if($taxonrank && $rek['r'] && $taxonrank != $rek['r']) return false;
        else {
            if($taxonrank == $rek['r'] || !$taxonrank || !$rek['r']) return true;
            exit("\ninvestigate code 105\n");
        }
        exit("\ninvestigate code 104\n"); //will not go this line
    }
    private function main_G3_2($rec)
    {
        $orig_taxonid = $rec['taxonid'];
        $canonicalname = $rec['canonicalname'];
        $orig_taxonrank = $rec['taxonrank'];
        // RANK TEST as above for each DH1/DH2 pair
        
        $DH2_homonyms = $this->DH2_canonicals[$canonicalname];
        if(count($DH2_homonyms) <= 1) exit("\nInvestigate code 401. It should always be > 1\n");
        $reks = $this->DH1_canonicals[$canonicalname];
        if(count($reks) <= 1) exit("\nInvestigate code 402. It should always be > 1\n");
        /*Array( $reks or $DH2_homonyms
            [0] => Array(
                    [ID] => EOL-000000000001
                    [pID] => 
                    [r] => clade
                    [can_fam_anc] => 
                    [can_par] => 
                    [can_gpa] => )
        )*/
        
        $rank_test_success_DH1 = array();
        $rank_test_success_DH2 = array();
        $ancestry_test_success_DH1 = array();
        $ancestry_test_success_DH2 = array();
        foreach($DH2_homonyms as $homonym_rec) {
            $taxonrank_homonym = $homonym_rec['r'];
            $rank_test_pass_YN = false;
            foreach($reks as $rek) { //$reks here are always > 1
                //@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
                // RANK TEST
                if(self::RANK_TEST_yn($taxonrank_homonym, $rek)) { $rank_test_pass_YN = true; 
                    $rank_test_success_DH2[$homonym_rec['ID']] = $rek['ID'];
                    $rank_test_success_DH1[$rek['ID']] = $homonym_rec['ID'];
                //@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
                    $ancestry_test_pass_YN = false;
                    // For DH2 homonyms that pass the rank test with at least one DH1 candidate, 
                    // do an ANCESTRY TEST (as above) for each of the passing candidates.
                    foreach($reks as $rek2) {
                        //@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
                        /* ANCESTRY TEST */
                        $DH2_fam = $homonym_rec['can_fam_anc'];
                        $DH1_fam = $rek2['can_fam_anc'];
                        $taxonrank = $homonym_rec['r'];
                        if(in_array($taxonrank, array('genus', 'species')) || !$DH2_fam || $DH1_fam) {
                            /* DH2 TAXA WITH RANK GENUS OR SPECIES */
                            if($DH1_fam == $DH2_fam) {
                                $ancestry_test_pass_YN = true;
                                //these next 2 rows function together
                                $ancestry_test_success_DH2[$homonym_rec['ID']] = $rek2['ID'];
                                $ancestry_test_success_DH1[$rek2['ID']] = $homonym_rec['ID'];
                            }
                        }
                        else {
                            /* DH TAXA WITH OTHER RANKS */
                            $DH2_parent = $homonym_rec['can_par'];
                            $DH2_grandparent = $homonym_rec['can_gpa'];
                            $DH1_parent = $rek2['can_par'];
                            $DH1_grandparent = $rek2['can_gpa'];
                            if($DH1_parent == $DH2_parent || $DH1_parent == $DH2_grandparent || $DH1_grandparent == $DH2_parent || $DH1_grandparent == $DH2_grandparent) {
                                $ancestry_test_pass_YN = true;
                                //these next 2 rows function together
                                $ancestry_test_success_DH2[$homonym_rec['ID']] = $rek2['ID'];
                                $ancestry_test_success_DH1[$rek2['ID']] = $homonym_rec['ID'];
                            }
                        }
                        //@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
                    } //end foreach($reks2)
                    
                    if($ancestry_test_pass_YN == false) {
                        // If the ancestry tests fail for all of the DH1 candidates, 
                        // leave the old DH2 taxonID and put "h-ancestorMismatch: family1, family2" 
                        // or "h-ancestorMismatch: parent1-grandparent1, parent2-grandparent2" in the EOLidAnnotations column, 
                        // depending on the rank of the DH2 taxon.
                        if(in_array($taxonrank, array('genus', 'species'))) $rec['eolidannotations'] = "h-ancestorMismatch";
                        else                                                $rec['eolidannotations'] = "h-ancestorMismatch";
                    }
                    
                    
                }
            }
            if($rank_test_pass_YN == false) {
                // For DH2 homonyms that fail the rank test with all of the DH1 candidates, 
                // leave the old DH2 taxonID and put "h-RankMismatch" in the EOLidAnnotations column for this taxon.
                if($orig_taxonid == $homonym_rec['ID']) $rec['eolidannotations'] = 'h-RankMismatch';
            }
        } //end foreach($DH2_homonyms)

        $both_DH1 = array();    $both_DH2 = array();
        foreach($rank_test_success_DH1 as $DH1_id => $DH2_id)      $both_DH1[$DH1_id][$DH2_id][] = 'rank test OK';
        foreach($rank_test_success_DH2 as $DH2_id => $DH1_id)      $both_DH2[$DH2_id][$DH1_id][] = 'rank test OK';
        foreach($ancestry_test_success_DH1 as $DH1_id => $DH2_id)  $both_DH1[$DH1_id][$DH2_id][] = 'ancestry test OK';
        foreach($ancestry_test_success_DH2 as $DH2_id => $DH1_id)  $both_DH2[$DH2_id][$DH1_id][] = 'ancestry test OK';
        /*Array( print_r($both_DH1);
            [173] => Array(
                    [300] => Array(
                            [0] => rank test OK
                            [1] => ancestry test OK
                        )
                    [301] => Array(
                            [0] => ancestry test OK
                        )
                )
        )*/
        $DH1_passed_tests = array();
        foreach($both_DH1 as $dh1_id => $rekord) {  //FOR DH 1
            foreach($rekord as $dh2_id => $tests) {
                // echo "\n[$dh1_id] [$dh2_id]\n"; print_r($tests);
                if(count($tests) > 1) $DH1_passed_tests[$dh1_id] = $dh2_id;
            }
        }
        /* evaluating echo "\n[$dh1_id] [$dh2_id]\n"; print_r($tests);
        [173] [300]
        Array(
            [0] => rank test OK
            [1] => ancestry test OK
        )
        [173] [301]
        Array(
            [0] => ancestry test OK
        )*/
        
        /*Array( print_r($DH1_passed_tests);
            [173] => 300
        )*/
        // echo "\nDH1_passed_tests: ".count($DH1_passed_tests)."\n";
        // DH1_passed_tests: 1

        $DH2_passed_tests = array();
        foreach($both_DH2 as $dh2_id => $rekord) {  //FOR DH 2
            foreach($rekord as $dh1_id => $tests) {
                // echo "\n[$dh2_id] [$dh1_id]\n"; print_r($tests);
                if(count($tests) > 1) $DH2_passed_tests[$dh2_id] = $dh1_id;
            }
        }

        if(count($DH1_passed_tests) == 1) {
            //step 1: get the DH1 ID
            $arr = array_keys($DH1_passed_tests);
            $DH1_id = $arr[0];
            //step 2: 
            $DH2_IDs = self::get_DH2_homonyms_that_passed_tests_with_this_DH1_candidate($DH2_passed_tests, $DH1_id);
            if(!$DH2_IDs) {
                // If there is only one DH1 candidate that passes both the rank test and the ancestry test 
                // AND there are no other DH2 homonyms that have passed the rank & ancestry tests with this DH1 candidate, 
                // replace the current DH2 taxonID with the taxonID of the DH1 candidate and update all relevant parentNameUsageID values. 
                // Also, put "h-ancestorMatch" in the EOLidAnnotations column for this taxon.
                $this->replaced_by[$rec['taxonid']] = $DH1_id;
                $rec['taxonid'] = $DH1_id;
                $rec['eolidannotations'] = 'h-ancestorMatch';
            }
        }
        elseif(count($DH1_passed_tests) > 1) {
            // If there is more than one DH1 candidate that passes both the rank test and the ancestry test, leave the old DH2 taxonID, 
            // and put "multipleMatches" in the EOLidAnnotations column for this taxon.
            $rec['eolidannotations'] = 'multipleMatches';
        }

        if(count($DH2_passed_tests) > 1) {
            // If there are multiple DH2 homonyms that pass both the rank test and the ancestry test with a given DH1 candidate, 
            // leave the old taxonIDs for the DH2 homonyms, and put "multipleMatches" in the EOLidAnnotations column for these taxa.
            $rec['eolidannotations'] = 'multipleMatches';
        }
        return $rec;
        // The final result of this should be a file with most DH2 taxonID values replaced by DH1 taxonID values. 
        // Each taxon should have a value in the EOLidAnnotations field, and each taxonID should be unique.
    }
    private function get_DH2_homonyms_that_passed_tests_with_this_DH1_candidate($DH2_passed_tests, $DH1_id)
    {
        /*Array( print_r($DH2_passed_tests);
            [dh2 id] => dh1 id
        )*/
        $final = array();
        foreach($DH2_passed_tests as $dh2_id => $dh1_id) {
            if($dh1_id == $DH1_id) $final[] = $dh2_id;
        }
        return $final;
    }
    private function main_G3_1($rec)
    {
        $orig_taxonid = $rec['taxonid'];
        $canonicalname = $rec['canonicalname'];
        $orig_taxonrank = $rec['taxonrank'];
        if($reks = $this->DH1_canonicals[$canonicalname]) { //print_r($reks); exit("\nelix1\n");
            if(count($reks) > 1) exit("\nInvestigate code 200. Should always be eq to 1 aaa\n");
            if(count($reks) < 1) exit("\nInvestigate code 200. Should always be eq to 1 bbb\n");
            /*Array( $reks
                [0] => Array(
                        [ID] => EOL-000000000001
                        [pID] => 
                        [r] => clade
                        [can_fam_anc] => 
                        [can_par] => 
                        [can_gpa] => )
            )*/
            $DH2_homonyms = $this->DH2_canonicals[$canonicalname];
            if(count($DH2_homonyms) <= 1) exit("\nInvestigate code 201. It should always be > 1\n");

            $rank_test_success = array(); $ancestry_test_success = array();
            foreach($reks as $rek) { //but we know that there is only 1 rec from DH1
                foreach($DH2_homonyms as $homonym_rec) {
                    $taxonrank_homonym = $homonym_rec['r'];
                    //@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
                    // RANK TEST
                    if(self::RANK_TEST_yn($taxonrank_homonym, $rek)) $rank_test_success[] = $homonym_rec['ID']; //$rank_test_success++;
                    else {
                        // RANK TEST as above
                        // If the rank test fails for any of the DH2 homonyms, create new identifiers,
                        // update all relevant parentNameUsageID values, and put "h-RankMismatch" in the EOLidAnnotations column.
                        @$this->ctr_G31++;
                        $new_id = 'EOL-G31' . sprintf("%09d", $this->ctr_G31);
                        $this->replaced_by[$rec['taxonid']] = $new_id;
                        $rec['taxonid'] = $new_id;
                        $rec['eolidannotations'] = 'h-RankMismatch';
                        return $rec;
                    }
                    //@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
                } //end foreach($DH2_homonyms)
            } //end foreach($reks)
            
            if(count($rank_test_success) >= 1) {
                // ANCESTRY TEST
                // If the rank test passes for at least one DH2 candidate, do an ANCESTRY TEST (as above) for each of the DH2 candidates.
                foreach($reks as $rek) { //but we know that there is only 1 rec from DH1
                    foreach($DH2_homonyms as $homonym_rec) {
                        //@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
                        /* ANCESTRY TEST */
                        $DH2_fam = $homonym_rec['can_fam_anc'];
                        $DH1_fam = $rek['can_fam_anc'];
                        $taxonrank = $homonym_rec['r'];
                        if(in_array($taxonrank, array('genus', 'species')) || !$DH2_fam || $DH1_fam) {
                            /* DH2 TAXA WITH RANK GENUS OR SPECIES */
                            if($DH1_fam == $DH2_fam) {
                                $ancestry_test_success[] = $homonym_rec['ID'];
                                $success[$homonym_rec['ID']] = $rek;
                            }
                            else {}
                        }
                        else {
                            /* DH TAXA WITH OTHER RANKS */
                            $DH2_parent = $homonym_rec['can_par'];
                            $DH2_grandparent = $homonym_rec['can_gpa'];
                            $DH1_parent = $rek['can_par'];
                            $DH1_grandparent = $rek['can_gpa'];
                            if($DH1_parent == $DH2_parent || $DH1_parent == $DH2_grandparent || $DH1_grandparent == $DH2_parent || $DH1_grandparent == $DH2_grandparent) {
                                $ancestry_test_success[] = $homonym_rec['ID'];
                                $success[$homonym_rec['ID']] = $rek;
                            }
                            else {}
                        }
                        //@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
                    }
                }
            }
            if(count($ancestry_test_success) == 0) {
                // If the ancestry tests fail for all of the DH2 candidates, leave the old taxonID, 
                // and put "h-ancestorMismatch: family1, family2" or "h-ancestorMismatch: parent1-grandparent1, parent2-grandparent2" 
                // in the EOLidAnnotations column, depending on the rank of the DH2 taxon.
                $rec['eolidannotations'] = 'h-ancestorMismatch';
            }
            /* replaced by one below
            if(count($rank_test_success) == 1 && count($ancestry_test_success) == 1 && $rank_test_success == $ancestry_test_success) {
                // If there is only one DH2 candidate that passes both the rank test and the ancestry test, 
                // replace the current DH2 taxonID of that candidate with the taxonID of the DH1 taxon 
                // and update all relevant parentNameUsageID values. 
                // Also, put "h-ancestorMatch" in the EOLidAnnotations column for this taxon.
                if($success['DH2 candidate ID'] == $orig_taxonid) {
                    $this->replaced_by[$rec['taxonid']] = $success['ID'];
                    $rec['taxonid'] = $success['ID'];
                    $rec['eolidannotations'] = 'h-ancestorMatch';
                }
            }*/
            
            $both = array();
            foreach($rank_test_success as $id)      $both[$id][] = 'rank test OK';
            foreach($ancestry_test_success as $id)  $both[$id][] = 'ancestry test OK';
            $candidates_pass_both = 0; //no. of candidates that passe both rank and ancestry test
            foreach($both as $id => $tests) {
                if(count($tests) > 1) {
                    $candidates_pass_both++;
                    $id_dh2 = $id;
                }
            }
            if($candidates_pass_both == 1) {
                // If there is only one DH2 candidate that passes both the rank test and the ancestry test, 
                // replace the current DH2 taxonID of that candidate with the taxonID of the DH1 taxon 
                // and update all relevant parentNameUsageID values. 
                // Also, put "h-ancestorMatch" in the EOLidAnnotations column for this taxon.
                if($s = $success[$id_dh2]) {
                    $this->replaced_by[$rec['taxonid']] = $s['ID'];
                    $rec['taxonid'] = $s['ID'];
                    $rec['eolidannotations'] = 'h-ancestorMatch';
                }
                else exit("\ninvestigate code 300\n");
            }
            if($candidates_pass_both > 1) {
                // If there is more than one DH2 candidate that passes both the rank test and the ancestry test, leave the old taxonIDs, 
                // and put "multipleMatches" in the EOLidAnnotations column for these taxa.
                $rec['eolidannotations'] = 'multipleMatches';
            }
        }
        else exit("\nInvestigate code 201. There should always be a single record returned here.\n");
        return $rec;
    } //end main_G3_1()
    private function main_G2_2($rec)
    {   /*Array(
            [taxonid] => 4038af35-41da-469e-8806-40e60241bb58
            [source] => trunk:4038af35-41da-469e-8806-40e60241bb58,NCBI:1
            [acceptednameusageid] => 
            [parentnameusageid] => 
            [scientificname] => Life
            [taxonrank] => 
            [taxonomicstatus] => accepted
            [datasetid] => trunk
            [canonicalname] => Life
            [eolid] => 
            [eolidannotations] => 
            [canonical_family_ancestor] => 
            [canonical_parent] => 
            [canonical_grandparent] => 
            [canomatchdh1_yn] => 1
            [homonyms_yn] => N
            [group] => G2_1
        )*/
        $orig_taxonid = $rec['taxonid'];
        $canonicalname = $rec['canonicalname'];
        $taxonrank = $rec['taxonrank'];
        if($reks = $this->DH1_canonicals[$canonicalname]) { //print_r($reks); exit("\nelix1\n");
            if(count($reks) == 1) exit("\nInvestigate code 103. Should always be > 1\n");
            /*Array( $reks
                [0] => Array(
                        [ID] => EOL-000000000001
                        [pID] => 
                        [r] => clade
                        [can_fam_anc] => 
                        [can_par] => 
                        [can_gpa] => )
            )*/
            $rank_test_success = 0;
            $ancestry_test_success = 0;
            foreach($reks as $rek) {
                //@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
                // RANK TEST
                if(self::RANK_TEST_yn($taxonrank, $rek)) $rank_test_success++;
                //@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
            } //end foreach($reks)

            if($rank_test_success >= 1) {
                foreach($reks as $rek) {
                    //@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
                    /* ANCESTRY TEST */
                    $DH2_fam = $rec['canonical_family_ancestor'];
                    $DH1_fam = $rek['can_fam_anc'];
                    if(in_array($taxonrank, array('genus', 'species')) || !$DH2_fam || $DH1_fam) {
                        /* DH2 TAXA WITH RANK GENUS OR SPECIES */
                        if($DH1_fam == $DH2_fam) {
                            $ancestry_test_success++;
                            $success = $rek;
                            //copied $rec['eolidannotations'] = 'ancestorMatch';
                        }
                        else {} //copied $rec['eolidannotations'] = "ancestorMismatch: [$DH1_fam], [$DH2_fam]";
                    }
                    else {
                        /* DH TAXA WITH OTHER RANKS */
                        $DH2_parent = $rec['canonical_parent'];
                        $DH2_grandparent = $rec['canonical_grandparent'];
                        $DH1_parent = $rek['can_par'];
                        $DH1_grandparent = $rek['can_gpa'];
                        if($DH1_parent == $DH2_parent || $DH1_parent == $DH2_grandparent || $DH1_grandparent == $DH2_parent || $DH1_grandparent == $DH2_grandparent) {
                             $ancestry_test_success++;
                             $success = $rek;
                             //copied $rec['eolidannotations'] = 'ancestorMatch';
                        }
                        else {} //copied $rec['eolidannotations'] = "ancestorMismatch: [$DH1_parent]-[$DH1_grandparent], [$DH2_parent]-[$DH2_grandparent]";
                    }
                    //@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
                } //end foreach($reks)
            } //end if statement
            
            if($rank_test_success == 0) {
                // If the rank test fails with all DH1 candidates, create a new identifier for the DH2 taxon, 
                // update all relevant parentNameUsageID values, and put "h-RankMismatch" in the EOLidAnnotations column.
                @$this->ctr_G22++;
                $new_id = 'EOL-G22' . sprintf("%09d", $this->ctr_G22);
                $this->replaced_by[$rec['taxonid']] = $new_id;
                $rec['taxonid'] = $new_id;
                $rec['eolidannotations'] = 'h-RankMismatch';
                return $rec;
            }
            if($ancestry_test_success == 0) {
                // If the ancestry tests fail for all of the DH1 candidates, 
                // leave the old DH2 taxonID and put 
                // "h-ancestorMismatch: family1, family2" or "h-ancestorMismatch: parent1-grandparent1, parent2-grandparent2"
                // in the EOLidAnnotations column, depending on the rank of the DH2 taxon.
                $rec['taxonid'] = $orig_taxonid;
                if(in_array($taxonrank, array('genus', 'species'))) $rec['eolidannotations'] = "h-ancestorMismatch: multiple";
                else $rec['eolidannotations'] = "h-ancestorMismatch: multiple";
            }
            if($rank_test_success == 1 && $ancestry_test_success == 1) {
                // If there is only one DH1 candidate that passes both the rank test and the ancestry test, 
                // replace the current DH2 taxonID with the taxonID of the DH1 candidate and 
                // update all relevant parentNameUsageID values. Also, put "h-ancestorMatch" in the EOLidAnnotations column for this taxon.
                $this->replaced_by[$rec['taxonid']] = $success['ID'];
                $rec['taxonid'] = $success['ID'];
                $rec['eolidannotations'] = "h-ancestorMatch";
            }
            if($rank_test_success > 1 && $ancestry_test_success > 1) {
                // If there is more than one DH1 candidate that passes both the rank test and the ancestry test, 
                // leave the old DH2 taxonID, 
                // and put "multipleMatches" in the EOLidAnnotations column for this taxon.
                $rec['taxonid'] = $orig_taxonid;
                $rec['eolidannotations'] = "multipleMatches";
            }
        }
        else exit("\nerror: should not go here 1.\n");
        return $rec;
    } // end main_G2_2()
    private function main_G2_1($rec)
    {   //print_r($rec); exit;
        /*Array(
            [taxonid] => 4038af35-41da-469e-8806-40e60241bb58
            [source] => trunk:4038af35-41da-469e-8806-40e60241bb58,NCBI:1
            [furtherinformationurl] => 
            [acceptednameusageid] => 
            [parentnameusageid] => 
            [scientificname] => Life
            [taxonrank] => 
            [taxonomicstatus] => accepted
            [taxonremarks] => 
            [datasetid] => trunk
            [canonicalname] => Life
            [eolid] => 
            [eolidannotations] => 
            [landmark] => 
            [canonical_family_ancestor] => 
            [canonical_parent] => 
            [canonical_grandparent] => 
            [canomatchdh1_yn] => 1
            [homonyms_yn] => N
            [group] => G2_1
        )*/
        $canonicalname = $rec['canonicalname'];
        $taxonrank = $rec['taxonrank'];
        if($reks = $this->DH1_canonicals[$canonicalname]) { //print_r($reks); exit("\nelix1\n");
            if(count($reks) > 1) exit("\nInvestigate code 102\n");
            /*Array( $reks
                [0] => Array(
                        [ID] => EOL-000000000001
                        [pID] => 
                        [r] => clade
                        [can_fam_anc] => 
                        [can_par] => 
                        [can_gpa] => 
                    )
            )*/
            $rek = $reks[0];
            // RANK TEST
            if(self::RANK_TEST_yn($taxonrank, $rek)) {
                // If this is TRUE, the rank test passes, and we can transfer the DH1 taxonID: 
                // Replace the current DH2 taxonID with the DH1 taxonID and update all relevant parentNameUsageID values.
                $this->replaced_by[$rec['taxonid']] = $rek['ID'];
                $rec['taxonid'] = $rek['ID'];
                
                /* ANCESTRY TEST
                ANCESTRY TEST (do this for all DH2 non-homonyms that passed the rank test). How to conduct the test depends on the rank of the DH2 taxon.
                */
                $DH2_fam = $rec['canonical_family_ancestor'];
                $DH1_fam = $rek['can_fam_anc'];
                if(in_array($taxonrank, array('genus', 'species')) || !$DH2_fam || $DH1_fam) {
                    /* DH2 TAXA WITH RANK GENUS OR SPECIES
                    Find the nearest ancestor where taxonRank=family for the DH2 and matching DH1 taxon. 
                    Check if the following is true: canonicalNameDH1 family = canonicalNameDH2 family. 
                    (If one of the taxa does not have an ancestor where taxonRank=family, do the TAXA OF OTHER RANKS test instead.)
                    If this is FALSE, put "ancestorMismatch: family1, family2" in the EOLidAnnotations column for this taxon. 
                        Where family1 is the canonicalName of the family in DH1 and family2 is the canonicalName of the family in DH2.
                    If this is TRUE, put "ancestorMatch" in the EOLidAnnotations column for this taxon.
                    */
                    if($DH1_fam == $DH2_fam) $rec['eolidannotations'] = 'ancestorMatch';
                    else                     $rec['eolidannotations'] = "ancestorMismatch: [$DH1_fam], [$DH2_fam]";
                }
                else {
                    /* DH TAXA WITH OTHER RANKS
                    Check if either the parents or the grandparents of the DH1 and DH2 taxa are a canonical match, i.e., any one of these are true:
                    canonicalName of DH1parent = canonicalName of DH2parent OR 
                    canonicalName of DH1parent = canonicalName of DH2grandparent OR 
                    canonicalName of DH1grandparent = canonicalName of DH2parent OR 
                    canonicalName of DH1grandparent = canonicalName of DH2grandparent
                    If this is FALSE, put “ancestorMismatch: parent1-grandparent1, parent2-grandparent2” in the EOLidAnnotations column for this taxon.
                    If this is TRUE, put “ancestorMatch” in the EOLidAnnotations column for this taxon.
                    */
                    $DH2_parent = $rec['canonical_parent'];
                    $DH2_grandparent = $rec['canonical_grandparent'];
                    $DH1_parent = $rek['can_par'];
                    $DH1_grandparent = $rek['can_gpa'];
                    if($DH1_parent == $DH2_parent || $DH1_parent == $DH2_grandparent || $DH1_grandparent == $DH2_parent || $DH1_grandparent == $DH2_grandparent) {
                         $rec['eolidannotations'] = 'ancestorMatch';
                    }
                    else $rec['eolidannotations'] = "ancestorMismatch: [$DH1_parent]-[$DH1_grandparent], [$DH2_parent]-[$DH2_grandparent]";
                }
            }
            else {
                // If this is FALSE, the rank test fails, and we won't transfer the DH1 taxonID. 
                // Instead, create a new identifier for the DH2 taxon and update all relevant parentNameUsageID values. 
                // Also, put "rankMismatch" in the EOLidAnnotations column for this taxon.
                @$this->ctr_G21++;
                $new_id = 'EOL-G21' . sprintf("%09d", $this->ctr_G21);
                $this->replaced_by[$rec['taxonid']] = $new_id;
                $rec['taxonid'] = $new_id;
                $rec['eolidannotations'] = 'rankMismatch';
            }
        }
        else exit("\nerror: should not go here 1.\n");
        return $rec;
    } //end main_G2_1()
    private function main_build_up($rec)
    {   //print_r($rec); exit("\ncha\n");
        /*Array( DH11
            [taxonid] => EOL-000000000001
            [source] => trunk:1bfce974-c660-4cf1-874a-bdffbf358c19,NCBI:1
            [furtherinformationurl] => 
            [acceptednameusageid] => 
            [parentnameusageid] => 
            [scientificname] => Life
            [taxonrank] => clade
            [taxonomicstatus] => valid
            [taxonremarks] => 
            [datasetid] => trunk
            [canonicalname] => Life
            [eolid] => 2913056
            [eolidannotations] => 
            [landmark] => 3
        )
        Array( DH21
            [taxonid] => 4038af35-41da-469e-8806-40e60241bb58
            [source] => trunk:4038af35-41da-469e-8806-40e60241bb58,NCBI:1
            [furtherinformationurl] => 
            [acceptednameusageid] => 
            [parentnameusageid] => 
            [scientificname] => Life
            [taxonrank] => 
            [taxonomicstatus] => accepted
            [taxonremarks] => 
            [datasetid] => trunk
            [canonicalname] => Life
            [eolid] => 
            [eolidannotations] => 
            [landmark] => 
        )*/
        $taxonID = $rec['taxonid'];
        $rank = $rec['taxonrank'];
        $rec['canonical_family_ancestor'] = '';
        $rec['canonical_parent'] = '';
        $rec['canonical_grandparent'] = '';
        /*
        new cols for DH1 and DH2 - need to build-up:
        if genus or species => canonical_family_ancestor
        else                => canonical_parent AND canonical_grandparent
        */
        $ancestry = self::get_ancestry_of_taxID($taxonID); //print_r($ancestry); //first record is the taxon in question -> $rec['taxonid]
        if(in_array($rank, array('genus', 'species'))) { //get canonical_family_ancestor
            $i = -1;
            foreach($ancestry as $id) { $i++;
                if($i === 0) { /* $ancestry[0] is the taxon in question -> $rec['taxonid] */
                    if($taxonID != $id) exit("\nInvestigate code 101\n");
                }
                if($rex = $this->taxID_info[$id]) { //print_r($rex)
                    /*Array(
                        [pID] => EOL-000002321107
                        [r] => subspecies
                        [n] => Leishmania braziliensis guyanensis
                    )*/
                    if($rex['r'] == 'family') $rec['canonical_family_ancestor'] = $rex['n'];
                }
            }
        }
        else { //get canonical_parent AND canonical_grandparent
            if($ancestry) {
                if($rec['parentnameusageid'] != @$ancestry[1]) exit("\nInvestigate code 100\n");
            }
            /* $ancestry[0] is the taxon in question -> $rec['taxonid] */
            if($parent_id = @$ancestry[1]) {
                $rex = $this->taxID_info[$parent_id];
                $rec['canonical_parent'] = $rex['n'];
            }
            if($grandparent_id = @$ancestry[2]) {
                $rex = $this->taxID_info[$grandparent_id];
                $rec['canonical_grandparent'] = $rex['n'];
            }
        }
        return $rec;
    }
    private function pre_build_up_DH()
    {
        self::get_taxID_nodes_info($this->tsv['DH11_Jen']);
        self::parse_tsv2($this->tsv['DH11_Jen'], 'build_up_useful_cols_DH11'); //generates DH11_working_new.txt
        self::get_taxID_nodes_info($this->tsv['DH21_Jen']);
        self::parse_tsv2($this->tsv['DH21_Jen'], 'build_up_useful_cols_DH21'); //generates DH21_working_new.txt
    }
    //+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    private function tag_DH2_with_group()
    {
        $ret = self::parse_tsv($this->main_path."/work_3.txt", 'run_stats_DH2');
        print_r($ret);
        $total = $ret['Group_1'] + $ret['Group_2-1'] + $ret['Group_2-2'] + $ret['Group_3-1'] + $ret['Group_3-2'];
        echo "\ntotal: [$total]\n";
    }
    private function tag_DH2_with_Homonyms_YN()
    {
        $this->DH2_canonicals = self::parse_tsv($this->main_path."/work_2.txt", 'get_canonicals'); // print_r($this->DH2_canonicals);
        self::parse_tsv($this->main_path."/work_2.txt", 'tag_DH2_with_Homonyms_YN'); //generates work_3.txt
        unset($this->DH2_canonicals);
    }
    private function tag_DH2_with_NoCanonicalMatch_in_DH1()
    {
        $this->DH1_canonicals = self::parse_tsv($this->tsv['DH11'], 'get_canonicals');
        self::parse_tsv($this->tsv['DH21'], 'tag_DH2_with_CanonicalMatchInDH1_YN'); //generates work_1.txt
        unset($this->DH1_canonicals);
        self::parse_tsv($this->main_path."/work_1.txt", 'refresh_parentIDs'); //generates work_2.txt
        unset($this->replaced_by);
        echo "\n no_match: [$this->no_match]\n";
    }
    private function parse_tsv($txtfile, $task)
    {   $i = 0;
        foreach(new FileIterator($txtfile) as $line_number => $line) {
            $i++; if(($i % 200000) == 0) echo "\n".number_format($i)." ";
            if($i == 1) $line = strtolower($line);
            $row = explode("\t", $line); // print_r($row);
            if($i == 1) {
                $fields = $row;
                $fields = array_filter($fields); //print_r($fields);
                if($task == 'tag_DH2_with_CanonicalMatchInDH1_YN') {
                    $tmp_fields = $fields;
                    $tmp_fields[] = 'CanoMatchDH1_YN';
                    $WRITE = fopen($this->main_path."/work_1.txt", "w");
                    fwrite($WRITE, implode("\t", $tmp_fields)."\n");
                }
                elseif($task == 'refresh_parentIDs') {
                    $WRITE = fopen($this->main_path."/work_2.txt", "w");
                    fwrite($WRITE, implode("\t", $fields)."\n");
                }
                if($task == 'tag_DH2_with_Homonyms_YN') {
                    $tmp_fields = $fields;
                    $tmp_fields[] = 'Homonyms_YN';
                    $WRITE = fopen($this->main_path."/work_3.txt", "w");
                    fwrite($WRITE, implode("\t", $tmp_fields)."\n");
                }
                if($task == 'run_stats_DH2') {
                    $tmp_fields = $fields;
                    $tmp_fields[] = 'group';
                    $WRITE = fopen($this->main_path."/work_4.txt", "w");
                    fwrite($WRITE, implode("\t", $tmp_fields)."\n");
                }
                continue;
            }
            else {
                if(!@$row[0]) continue;
                $k = 0; $rec = array();
                foreach($fields as $fld) { $rec[$fld] = @$row[$k]; $k++; }
            }
            $rec = array_map('trim', $rec);
            // print_r($rec); exit("\nstopx\n");
            /*Array(    DH1.1
                [taxonid] => EOL-000000000001
                [acceptednameusageid] => 
                [parentnameusageid] => 
                [scientificname] => Life
                [taxonrank] => clade
                [taxonomicstatus] => valid
                [canonicalname] => Life
                [eolid] => 2913056
                [eolidannotations] => 
                [landmark] => 3
            )*/
            
            if($task == 'run_stats_DH2') {
                /*Array(    [canomatchdh1_yn] => 1
                            [homonyms_yn] => N
                )*/
                $canoMatchDH1_YN = $rec['canomatchdh1_yn']; // 1 or >1 or N
                $homonyms_YN = $rec['homonyms_yn'];         // Y or N
                if($canoMatchDH1_YN == "N") {@$stats['Group_1']++; $rec['group'] = 'G1';}
                if($homonyms_YN == "N") { //are not homonyms
                        if($canoMatchDH1_YN == 1) {@$stats['Group_2-1']++; $rec['group'] = 'G2_1';}
                    elseif($canoMatchDH1_YN > 1)  {@$stats['Group_2-2']++; $rec['group'] = 'G2_2';}
                }
                else { //are homonyms
                        if($canoMatchDH1_YN == 1) {@$stats['Group_3-1']++; $rec['group'] = 'G3_1';}
                    elseif($canoMatchDH1_YN > 1)  {@$stats['Group_3-2']++; $rec['group'] = 'G3_2';}
                }
                fwrite($WRITE, implode("\t", $rec)."\n");
            }
            
            if($task == 'tag_DH2_with_Homonyms_YN') {
                $canonicalname = $rec['canonicalname'];
                if($this->DH2_canonicals[$canonicalname] > 1) $rec['Homonyms_YN'] = 'Y';
                elseif($this->DH2_canonicals[$canonicalname] == 1) $rec['Homonyms_YN'] = 'N';
                else { print_r($rec); exit("\nInvestigate 1\n"); }
                fwrite($WRITE, implode("\t", $rec)."\n");
            }
            
            if($task == 'get_canonicals') @$final[$rec['canonicalname']]++;//$final[$rec['canonicalname']] = '';
            elseif($task == 'tag_DH2_with_CanonicalMatchInDH1_YN') {
                /*Array(    print_r($rec); exit("\nstopx\n");
                    [taxonid] => 4038af35-41da-469e-8806-40e60241bb58
                    [source] => trunk:4038af35-41da-469e-8806-40e60241bb58,NCBI:1
                    [acceptednameusageid] => 
                    [parentnameusageid] => 
                    [scientificname] => Life
                    [taxonrank] => 
                    [canonicalname] => Life
                    [eolid] => 
                    [eolidannotations] => 
                )*/
                $canonicalname = $rec['canonicalname'];
                /* 1st ver
                if(isset($this->DH1_canonicals[$canonicalname])) $rec['CanoMatchDH1_YN'] = 'Y';
                */
                if($val = @$this->DH1_canonicals[$canonicalname]) $rec['CanoMatchDH1_YN'] = $val; // value is either blank or 1 or >1
                else { @$this->no_match++;
                    $rec['CanoMatchDH1_YN'] = 'N';
                    $new_id = 'EOL-NoDH1' . sprintf("%07d", $this->no_match);
                    $this->replaced_by[$rec['taxonid']] = $new_id;
                    $rec['taxonid'] = $new_id;
                    $rec['eolidannotations']= 'new';
                }
                fwrite($WRITE, implode("\t", $rec)."\n");
            }
            elseif($task == 'refresh_parentIDs') {
                $parent_ID = $rec['parentnameusageid'];
                $accept_ID = $rec['acceptednameusageid'];
                if($val = @$this->replaced_by[$parent_ID]) {
                    $rec['parentnameusageid'] = $val;
                }
                if($val = @$this->replaced_by[$accept_ID]) {
                    $rec['acceptednameusageid'] = $val;
                }
                fwrite($WRITE, implode("\t", $rec)."\n");
            }
        }

        if($task == 'run_stats_DH2') {
            fclose($WRITE);
            $total = self::get_total_rows($this->main_path."/work_3.txt"); echo "\n work_3 [$total]\n";
            $total = self::get_total_rows($this->main_path."/work_4.txt"); echo "\n work_4 [$total]\n";
            return $stats;
        }
        if($task == 'tag_DH2_with_Homonyms_YN') {
            fclose($WRITE);
            $total = self::get_total_rows($this->main_path."/work_3.txt"); echo "\n work_3 [$total]\n";
        }
        if($task == 'get_canonicals') return $final;
        elseif($task == 'tag_DH2_with_CanonicalMatchInDH1_YN') {
            fclose($WRITE);
            $total = self::get_total_rows($this->tsv['DH21']); echo "\n DH22 [$total]\n";
            $total = self::get_total_rows($this->main_path."/work_1.txt"); echo "\n work_1 [$total]\n";
        }
        elseif($task == 'refresh_parentIDs') {
            fclose($WRITE);
            $total = self::get_total_rows($this->main_path."/work_1.txt"); echo "\n work_1 [$total]\n";
            $total = self::get_total_rows($this->main_path."/work_2.txt"); echo "\n work_2 [$total]\n";
        }
    }
    private function get_taxID_nodes_info($txtfile = false)
    {   $this->taxID_info = array(); $this->descendants = array(); //initialize global vars
        $i = 0;
        foreach(new FileIterator($txtfile) as $line_number => $line) {
            $i++; if(($i % 200000) == 0) echo "\n".number_format($i)." [get_taxID_nodes_info]";
            if($i == 1) $line = strtolower($line);
            $row = explode("\t", $line); // print_r($row);
            if($i == 1) {
                $fields = $row;
                $fields = array_filter($fields); //print_r($fields);
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
            // print_r($rec); //exit("\nstopx\n");
            /*Array(
                [taxonid] => EOL-000000000001
                [source] => trunk:1bfce974-c660-4cf1-874a-bdffbf358c19,NCBI:1
                [furtherinformationurl] => 
                [acceptednameusageid] => 
                [parentnameusageid] => 
                [scientificname] => Life
                [taxonrank] => clade
                [taxonomicstatus] => valid
                [taxonremarks] => 
                [datasetid] => trunk
                [canonicalname] => Life
                [eolid] => 2913056
                [eolidannotations] => 
                [landmark] => 3
            )*/
            $this->taxID_info[$rec['taxonid']] = array("pID" => $rec['parentnameusageid'], 'r' => $rec['taxonrank'], 'n' => $rec['canonicalname']);
            // -> used for ancesty and more
            $this->descendants[$rec['parentnameusageid']][$rec['taxonid']] = '';
            // -> used for descendants (children)
        }
    }
    private function get_ancestry_of_taxID($tax_id)
    {   /* Array(
            [1] => Array(
                    [pID] => 1
                    [r] => no rank
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
    private function get_total_rows($file)
    {   /* source: https://stackoverflow.com/questions/3137094/how-to-count-lines-in-a-document */
        $total = shell_exec("wc -l < ".escapeshellarg($file));
        $total = trim($total);
        return $total;
    }
    function generate_dwca()
    {   $source = $this->main_path."/work_6.txt";
        echo "\nReading [$source]...\n"; $i = 0;
        foreach(new FileIterator($source) as $line_number => $line) {
            $i++; if(($i % 200000) == 0) echo "\n".number_format($i)." [generate_dwca]";
            $row = explode("\t", $line);
            if($i == 1) {
                $fields = $row;
                $fields = array_filter($fields); //print_r($fields);
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
            $rec = array_map('trim', $rec); //print_r($rec); exit("\nstopx\n");
            /*Array(
                [taxonid] => EOL-G21000000001
                [source] => trunk:4038af35-41da-469e-8806-40e60241bb58,NCBI:1
                [furtherinformationurl] => 
                [acceptednameusageid] => 
                [parentnameusageid] => 
                [scientificname] => Life
                [taxonrank] => 
                [taxonomicstatus] => accepted
                [taxonremarks] => 
                [datasetid] => trunk
                [canonicalname] => Life
                [eolid] => 
                [eolidannotations] => rankMismatch
                [landmark] => 

                [canonical_family_ancestor] => 
                [canonical_parent] => 
                [canonical_grandparent] => 
                [canomatchdh1_yn] => 1
                [homonyms_yn] => N
                [group] => G2_1
            )*/
            
            /* copied template
            if($rank = $rec['taxonrank']) {
                if($rank == 'no rank') $rank = '';
                elseif($rank == 'varietas') $rank = 'variety';
                elseif($rank == 'forma.') $rank = 'form';
            }
            */
            
            $tax = new \eol_schema\Taxon();
            $tax->taxonID = $rec['taxonid'];
            $tax->scientificName = $rec['scientificname'];
            $tax->canonicalName = $rec['canonicalname'];
            $tax->parentNameUsageID = $rec['parentnameusageid'];
            $tax->acceptedNameUsageID = $rec['acceptednameusageid'];
            $tax->taxonRank = $rec['taxonrank'];
            $tax->taxonomicStatus = $rec['taxonomicstatus'];
            $tax->source = $rec['source'];
            $tax->furtherInformationURL = $rec['furtherinformationurl'];
            $tax->taxonRemarks = $rec['taxonremarks'];
            $tax->datasetID = $rec['datasetid'];
            $tax->EOLid = $rec['eolid'];
            $tax->EOLidAnnotations = $rec['eolidannotations'];
            // $tax->higherClassification = $rec['higherclassification'];
            $tax->Landmark = $rec['landmark'];
            $this->archive_builder->write_object_to_file($tax);
        }
        $this->archive_builder->finalize(true);
    }
    /* not used anyway --- this was taken from another script
    private function get_descendants_of_taxID($uid, $direct_descendants_only_YN = false, $this_descendants = array())
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
    }*/
}
?>