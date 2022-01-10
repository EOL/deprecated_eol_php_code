<?php
namespace php_active_record;
/* connector: [taxonIDs_for_DH21.php] - TRAM-995
*/
class DH_v21_TRAM_995_v2
{
    function __construct($folder)
    {
        $this->resource_id = $folder;
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->debug = array();
        if(Functions::is_production()) {} //not used
        else {
            $this->download_options = array(
                'download_wait_time' => 250000, 'timeout' => 600, 'download_attempts' => 1, 'delay_in_minutes' => 0, 'expire_seconds' => false);
            $this->main_path = "/Volumes/AKiTiO4/d_w_h/TRAM-995_v2/";
        }
        $this->tsv['DH11_Jen'] = $this->main_path."/dh1_1/DH1_1working.txt";
        $this->tsv['DH21_Jen'] = $this->main_path."/dh2_1/DH2_1working.txt";
        $this->tsv['DH11'] = $this->main_path."/DH11_working_new.txt";
        $this->tsv['DH21'] = $this->main_path."/DH21_working_new.txt";
        $this->tsv['remappings_Katja'] = $this->main_path."/Katja/remappings.txt";
    }
    // ----------------------------------------------------------------- start TRAM-807 -----------------------------------------------------------------
    function start()
    {   
        /* works
        // self::get_taxID_nodes_info($this->tsv['DH11']); //un-comment in real operation
        self::get_taxID_nodes_info($this->tsv['DH11_Jen']); //un-comment in real operation
        self::get_taxID_nodes_info($this->tsv['DH21_Jen']); //un-comment in real operation
        // self::get_taxID_nodes_info($this->main_path."/work_4.txt"); //un-comment in real operation
        $taxonID = "EOL-000002321109"; //'-8365'; //'EOL-000002321109';
        $taxonID = "EOL-000000095866";  // sciname = "Amoeba Bory de St. Vincent, 1822" //sample for DH21
        $taxonID = "-58274";            // sciname = "Amoeba Bory de St. Vincent, 1822" //sample for DH11
        $ancestry = self::get_ancestry_of_taxID($taxonID); print_r($ancestry); echo "ancestry"; //exit; //working OK but not used yet
        
        foreach($ancestry as $id) { echo "\n [$id]";
            if($rec = $this->taxID_info[$id]) print_r($rec);
        }
        exit("\n-end test-\n");
        */

        /* not used here anyway...
        $taxonID = "EOL-000002321109";  //'-8365'; //'EOL-000002321109';
        $children = self::get_descendants_of_taxID($taxonID); print_r($children); echo "children"; exit("\n-end test-\n");
        */
        
        /* ######################################################## first step: run only once in lifetime - DONE 
        // new cols for DH1 and DH2 - need to build-up:
        // if genus or species => canonical_family_ancestor
        // else                => canonical_parent AND canonical_grandparent
        self::pre_build_up_DH(); exit("\n-end pre_build_up_DH-\n");
        ####################################################### */
        
        /* GROUP 1: DH2 taxa (homonyms or not) that have no canonical match in DH1, i.e., DH1canonicalName = DH2canonicalName is never true
        Create a new EOL-xxx style identifier for each of these taxa and update all relevant parentNameUsageID values. 
        Also, put "new" in the EOLidAnnotations column for each taxon.
        */
        // /* ######################################################## Ok good --- run each of these three one at a time
        // self::tag_DH2_with_NoCanonicalMatch_in_DH1();   //ends with work_1.txt AND work_2.txt
        // self::tag_DH2_with_Homonyms_YN();               //ends with work_3.txt
        // self::tag_DH2_with_group();                     //ends with work_4.txt -> also generates stats to see if all categories are correctly covered...
        ####################################################### */
        
        /* worked OK --- run one at a time
        // self::proc_Group_2_1();     //works with work_4.txt AND work_5.txt -> ends with work_6.txt
        // self::proc_Group_2_2();     //works with work_6.txt AND work_7.txt -> ends with work_8.txt
        // self::proc_Group_3_1();     //works with work_8.txt AND work_9.txt -> ends with work_10.txt
        self::proc_Group_3_2();     //works with work_10.txt AND work_11.txt -> ends with work_12.txt
        */
        
        // /* Jan 2022 series:
        self::implement_remappings_txt(); //works with work_12.txt
        // */
        
        exit("\n-stop muna-\n");
    }
    private function implement_remappings_txt()
    {
        /*
        $this->remappings_info = self::parse_tsv2($this->tsv['remappings_Katja'], 'read_remappings_txt'); // print_r($this->remappings_info); exit;
        $this->replaced_by = array();
        self::parse_tsv2($this->main_path."/work_12.txt", 'do_remappings'); //generates work_13.txt
        unset($this->remappings_info);
        self::parse_tsv2($this->main_path."/work_13.txt", 'refresh_parentIDs_work_13'); //generates work_14.txt
        unset($this->replaced_by);
        */

        // /*
        $this->ctr_novel = 200;
        $this->replaced_by = array();
        self::parse_tsv2($this->main_path."/work_14.txt", 'do_remappings_v2'); //generates work_15.txt
        self::parse_tsv2($this->main_path."/work_15.txt", 'refresh_parentIDs_work_15'); //generates work_16.txt
        unset($this->replaced_by);
        echo "\nctr_novel: [$this->ctr_novel]\n";
        // */
    }
    private function proc_Group_2_1()
    {   $this->DH1_canonicals = self::parse_tsv2($this->tsv['DH11'], 'get_canonicals_and_info_DH1'); //-> for G2_1 G2_2 G3_1 G3_2
        $this->replaced_by = array();
        self::parse_tsv2($this->main_path."/work_4.txt", 'group_2_1'); //generates work_5.txt
        unset($this->DH1_canonicals);
        self::parse_tsv2($this->main_path."/work_5.txt", 'refresh_parentIDs_work_5'); //generates work_6.txt
        unset($this->replaced_by);
    }
    private function proc_Group_2_2()
    {   $this->DH1_canonicals = self::parse_tsv2($this->tsv['DH11'], 'get_canonicals_and_info_DH1'); //-> for G2_1 G2_2 G3_1 G3_2
        $this->replaced_by = array();
        self::parse_tsv2($this->main_path."/work_6.txt", 'group_2_2'); //generates work_7.txt
        unset($this->DH1_canonicals);
        self::parse_tsv2($this->main_path."/work_7.txt", 'refresh_parentIDs_work_7'); //generates work_8.txt
        unset($this->replaced_by);
    }
    private function proc_Group_3_1()
    {
        // /*
        $WRITE = fopen($this->main_path."/json_3_1.txt", "w"); fclose($WRITE); //initialize json file
        $this->DH1_canonicals = self::parse_tsv2($this->tsv['DH11'], 'get_canonicals_and_info_DH1');              //-> for G2_1 G2_2 G3_1 G3_2
        $this->DH2_canonicals = self::parse_tsv2($this->main_path."/work_8.txt", 'get_canonicals_and_info_DH2');  //-> for G3_1 and G3_2 only
        $this->replaced_by = array();
        self::parse_tsv2($this->main_path."/work_8.txt", 'group_3_1'); //does not generate any .txt file here
        // exit("\nstop munax\n");
        unset($this->DH1_canonicals); unset($this->DH2_canonicals);
        // */
        // /* New: 
        $this->json_info = self::read_json_file($this->main_path."/json_3_1.txt");
        $this->replaced_by = array();
        self::parse_tsv2($this->main_path."/work_8.txt", 'group_3_1_post'); //generates work_9.txt
        // */
        self::parse_tsv2($this->main_path."/work_9.txt", 'refresh_parentIDs_work_9'); //generates work_10.txt
        unset($this->replaced_by);
    }
    private function proc_Group_3_2()
    {
        // /*
        $WRITE = fopen($this->main_path."/json_3_2.txt", "w"); fclose($WRITE); //initialize json file
        $this->DH1_canonicals = self::parse_tsv2($this->tsv['DH11'], 'get_canonicals_and_info_DH1');              //-> for G2_1 G2_2 G3_1 G3_2
        $this->DH2_canonicals = self::parse_tsv2($this->main_path."/work_10.txt", 'get_canonicals_and_info_DH2');  //-> for G3_1 and G3_2 only
        $this->replaced_by = array();
        self::parse_tsv2($this->main_path."/work_10.txt", 'group_3_2'); //does not generate any .txt file here
        // exit("\nstop munax\n");
        unset($this->DH1_canonicals); unset($this->DH2_canonicals);
        // */
        // /* New: 
        $this->json_info = self::read_json_file($this->main_path."/json_3_2.txt");
        $this->replaced_by = array();
        self::parse_tsv2($this->main_path."/work_10.txt", 'group_3_2_post'); //generates work_11.txt
        // */
        self::parse_tsv2($this->main_path."/work_11.txt", 'refresh_parentIDs_work_11'); //generates work_12.txt
        unset($this->replaced_by);
    }
    private function read_json_file($txtfile)
    {   
        foreach(new FileIterator($txtfile) as $line_number => $line) {
            $arr = json_decode($line, true); 
            // if($arr['ID'] == '-41870') print_r($arr); //good debug
            /* Array(
                [ID] => -1237296
                [pass_ANCESTRY_TEST] => Y
                [transform_annote] => ancestorMatch: [Encyrtidae], [Encyrtidae]
                [transform_DH1_rek] => Array(
                        [ID] => EOL-000001006132
                        [pID] => EOL-000001003897
                        [r] => genus
                        [cf] => Encyrtidae
                        [cp] => Encyrtidae
                        [cg] => Chalcidoidea
                    )
            $xxx['transform_new_id'] = $new_id;
            $xxx['transform_annote'] = 'h-RankMismatch';
            $yyy['transform_annote'] = 'h-ancestorMismatch';
            $zzz['transform_annote'] = 'multipleMatches';
            */
            $flds = array('ID', 'transform_annote', 'transform_DH1_rek', 'transform_new_id');
            $save = array();
            foreach($flds as $fld) {
                if($val = @$arr[$fld]) $save[$fld] = $val;
            }
            if($ID = $arr['ID']) $final[$ID] = $save;
        }
        // print_r($final); exit;
        return $final;
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
                //**************************************************************
                if($task == 'do_remappings') { //works with 12 AND 13 -> ends with 14
                    $tmp_fields = $fields;  $WRITE = fopen($this->main_path."/work_13.txt", "w"); fwrite($WRITE, implode("\t", $tmp_fields)."\n");
                }
                if($task == 'refresh_parentIDs_work_13') {$WRITE = fopen($this->main_path."/work_14.txt", "w"); fwrite($WRITE, implode("\t", $fields)."\n");}
                //**************************************************************
                if($task == 'do_remappings_v2') { //works with 14 AND 15 -> ends with 16
                    $tmp_fields = $fields;  $WRITE = fopen($this->main_path."/work_15.txt", "w"); fwrite($WRITE, implode("\t", $tmp_fields)."\n");
                }
                if($task == 'refresh_parentIDs_work_15') {$WRITE = fopen($this->main_path."/work_16.txt", "w"); fwrite($WRITE, implode("\t", $fields)."\n");}
                //**************************************************************
                if($task == 'group_2_1') { //works with 4 AND 5 -> ends with 6
                    $tmp_fields = $fields;  $WRITE = fopen($this->main_path."/work_5.txt", "w"); fwrite($WRITE, implode("\t", $tmp_fields)."\n");
                }
                if($task == 'refresh_parentIDs_work_5') {$WRITE = fopen($this->main_path."/work_6.txt", "w"); fwrite($WRITE, implode("\t", $fields)."\n");}
                //**************************************************************
                if($task == 'group_2_2') { //works with 6 AND 7 -> ends with 8
                    $tmp_fields = $fields;  $WRITE = fopen($this->main_path."/work_7.txt", "w"); fwrite($WRITE, implode("\t", $tmp_fields)."\n");
                }
                if($task == 'refresh_parentIDs_work_7') {$WRITE = fopen($this->main_path."/work_8.txt", "w"); fwrite($WRITE, implode("\t", $fields)."\n");}
                //**************************************************************
                if($task == 'group_3_1_post') { //works with 8 AND 9 -> ends with 10
                    $tmp_fields = $fields;  $WRITE = fopen($this->main_path."/work_9.txt", "w"); fwrite($WRITE, implode("\t", $tmp_fields)."\n");
                }
                if($task == 'refresh_parentIDs_work_9') {$WRITE = fopen($this->main_path."/work_10.txt", "w"); fwrite($WRITE, implode("\t", $fields)."\n");}
                //**************************************************************
                if($task == 'group_3_2_post') { //works with 10 AND 11 -> ends with 12
                    $tmp_fields = $fields;  $WRITE = fopen($this->main_path."/work_11.txt", "w"); fwrite($WRITE, implode("\t", $tmp_fields)."\n");
                }
                if($task == 'refresh_parentIDs_work_11') {$WRITE = fopen($this->main_path."/work_12.txt", "w"); fwrite($WRITE, implode("\t", $fields)."\n");}
                //**************************************************************
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
            $rec = array_map('trim', $rec); // print_r($rec); exit("\nstopxy\n");
            
            if(in_array($task, array('build_up_useful_cols_DH11', 'build_up_useful_cols_DH21'))) {
                $rec = self::main_build_up($rec); 
                fwrite($WRITE, implode("\t", $rec)."\n");
            }

            if($task == 'do_remappings') { // print_r($rec); exit("\nelix 100\n");
                /*Array(
                    [taxonid] => EOL-000000000001
                    [acceptednameusageid] => 
                    [parentnameusageid] => 
                )
                The latest version of the file is great. After checking some of the ID mappings, 
                I found a few taxonIDs that need to be remapped. 
                I will attach a file called remappings.txt with 3 columns: currentID, newID, notes. 
                The currentID is the taxonID in your latest version of the file. 
                Please replace this ID with the newID value for relevant taxa. 
                If the newID value is “new,” please create a novel ID for this taxon. 
                Please make sure to also update any parentNameUsageIDs for the descendants of these taxa. */
                $taxonid = $rec['taxonid'];
                if($newID = @$this->remappings_info[$taxonid]) {
                    if($newID == 'new') {
                        @$this->ctr_novel++;
                        $new_id = 'EOL-new_' . sprintf("%08d", $this->ctr_novel);
                        $this->replaced_by[$taxonid] = $new_id;
                        $rec['taxonid'] = $new_id;
                    }
                    else {
                        $this->replaced_by[$taxonid] = $newID;
                        $rec['taxonid'] = $newID;
                    }
                }
                fwrite($WRITE, implode("\t", $rec)."\n");
            }
            if($task == 'do_remappings_v2') { //print_r($rec); exit("\nelix 100\n");
                /*Array(
                    [taxonid] => EOL-000000000001
                    [acceptednameusageid] => 
                    [parentnameusageid] => 
                )
                After fixing those IDs, there should then be 295 remaining taxa where taxonID is still the old-ID. 
                Please create new EOL-xxx taxonIDs for those.
                For the next output, I only need the DwCA, not the temp file with all the extra columns. 
                Please also clear the EOLidAnnotations column. We won't need this information going forward.
                */
                $taxonid = $rec['taxonid'];
                if(substr($taxonid, 0,4) != 'EOL-') {
                    $this->ctr_novel++; //started with 200
                    $new_id = 'EOL-new_' . sprintf("%08d", $this->ctr_novel);
                    $this->replaced_by[$taxonid] = $new_id;
                    $rec['taxonid'] = $new_id;
                }
                $rec['eolidannotations'] = '';
                fwrite($WRITE, implode("\t", $rec)."\n");
            }
            
            if($task == 'group_2_1') {
                if($rec['group'] == 'G2_1') {$rec = self::main_G2_1($rec); $rec = self::revive_fields($rec, $tmp_fields); fwrite($WRITE, implode("\t", $rec)."\n");}
                else fwrite($WRITE, implode("\t", $rec)."\n"); //carryover the rest
            }
            if($task == 'group_2_2') {
                if($rec['group'] == 'G2_2') {$rec = self::main_G2_2($rec); $rec = self::revive_fields($rec, $tmp_fields); fwrite($WRITE, implode("\t", $rec)."\n");}
                else fwrite($WRITE, implode("\t", $rec)."\n"); //carryover the rest
            }

            if($task == 'group_3_1') {
                if($rec['group'] == 'G3_1') self::main_G3_1($rec);
            }
            elseif($task == 'group_3_1_post') {
                if($rec['group'] == 'G3_1') {$rec = self::main_G3_1and2_post($rec); $rec = self::revive_fields($rec, $tmp_fields); fwrite($WRITE, implode("\t", $rec)."\n");}
                else fwrite($WRITE, implode("\t", $rec)."\n"); //carryover the rest
            }
            
            if($task == 'group_3_2') {
                if($rec['group'] == 'G3_2') self::main_G3_2($rec);
            }
            elseif($task == 'group_3_2_post') {
                if($rec['group'] == 'G3_2') {$rec = self::main_G3_1and2_post($rec); $rec = self::revive_fields($rec, $tmp_fields); fwrite($WRITE, implode("\t", $rec)."\n");}
                else fwrite($WRITE, implode("\t", $rec)."\n"); //carryover the rest
            }
            
            if($task == 'get_canonicals_and_info_DH1') { //print_r($rec); exit("\n172\n");
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
                // 'can_fam_anc' => $rec['canonical_family_ancestor'], 'can_par' => $rec['canonical_parent'], 'can_gpa' => $rec['canonical_grandparent']);
                'cf' => $rec['canonical_family_ancestor'], 'cp' => $rec['canonical_parent'], 'cg' => $rec['canonical_grandparent']);
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
                    'cf' => $rec['canonical_family_ancestor'], 'cp' => $rec['canonical_parent'], 'cg' => $rec['canonical_grandparent']);
                }
            }
            if(in_array($task, array('refresh_parentIDs_work_5', 'refresh_parentIDs_work_7', 'refresh_parentIDs_work_9', 'refresh_parentIDs_work_11', 'refresh_parentIDs_work_13', 'refresh_parentIDs_work_15'))) {
                $parent_ID = $rec['parentnameusageid'];
                $accept_ID = $rec['acceptednameusageid'];
                if($val = @$this->replaced_by[$parent_ID]) $rec['parentnameusageid'] = $val;
                if($val = @$this->replaced_by[$accept_ID]) $rec['acceptednameusageid'] = $val;
                // /*
                if($task == 'refresh_parentIDs_work_11') { //considered last step
                    if($rec['taxonid'] == $rec['old_taxonid']) $rec['old_taxonid'] = '';
                    if($rec['old_taxonid'] == '-1710587') {} //deliberately manually excluded this one record: https://eol-jira.bibalex.org/browse/TRAM-995?focusedCommentId=66576&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-66576
                    else fwrite($WRITE, implode("\t", $rec)."\n");
                }
                else fwrite($WRITE, implode("\t", $rec)."\n");
                // */
            }
            
            if($task == 'read_remappings_txt') { //print_r($rec); exit("\n172\n");
                /*Array(
                    [currentid] => EOL-000000043416
                    [newid] => new
                    [notes] => G2_1 ancestorMismatch
                )*/
                $final[$rec['currentid']] = $rec['newid'];
            }
            
        } //end foreach()
        if($task == 'read_remappings_txt') return $final;
        
        if($task == 'get_canonicals_and_info_DH1') return $final;
        elseif($task == 'get_canonicals_and_info_DH2') return $final;
        elseif($task == 'group_2_1') {fclose($WRITE); $total = self::get_total_rows($this->main_path."/work_5.txt"); echo "\n work_5 [$total]\n";}
        elseif($task == 'group_2_2') {fclose($WRITE); $total = self::get_total_rows($this->main_path."/work_7.txt"); echo "\n work_7 [$total]\n";}
        elseif($task == 'group_3_1_post') {fclose($WRITE); $total = self::get_total_rows($this->main_path."/work_9.txt"); echo "\n work_9 [$total]\n";}
        elseif($task == 'group_3_2_post') {fclose($WRITE); $total = self::get_total_rows($this->main_path."/work_11.txt"); echo "\n work_11 [$total]\n";}
        elseif($task == 'do_remappings')    {fclose($WRITE); $total = self::get_total_rows($this->main_path."/work_13.txt"); echo "\n work_13 [$total]\n";}
        elseif($task == 'do_remappings_v2') {fclose($WRITE); $total = self::get_total_rows($this->main_path."/work_15.txt"); echo "\n work_15 [$total]\n";}
        
        elseif($task == 'refresh_parentIDs_work_5') { fclose($WRITE);
            $total = self::get_total_rows($this->main_path."/work_5.txt"); echo "\n work_5 [$total]\n";
            $total = self::get_total_rows($this->main_path."/work_6.txt"); echo "\n work_6 [$total]\n";
        }
        elseif($task == 'refresh_parentIDs_work_7') { fclose($WRITE);
            $total = self::get_total_rows($this->main_path."/work_7.txt"); echo "\n work_7 [$total]\n";
            $total = self::get_total_rows($this->main_path."/work_8.txt"); echo "\n work_8 [$total]\n";
        }
        elseif($task == 'refresh_parentIDs_work_9') { fclose($WRITE);
            $total = self::get_total_rows($this->main_path."/work_9.txt"); echo "\n work_9 [$total]\n";
            $total = self::get_total_rows($this->main_path."/work_10.txt"); echo "\n work_10 [$total]\n";
        }
        elseif($task == 'refresh_parentIDs_work_11') { fclose($WRITE);
            $total = self::get_total_rows($this->main_path."/work_11.txt"); echo "\n work_11 [$total]\n";
            $total = self::get_total_rows($this->main_path."/work_12.txt"); echo "\n work_12 [$total]\n";
        }
        elseif($task == 'refresh_parentIDs_work_13') { fclose($WRITE);
            $total = self::get_total_rows($this->main_path."/work_13.txt"); echo "\n work_13 [$total]\n";
            $total = self::get_total_rows($this->main_path."/work_14.txt"); echo "\n work_14 [$total]\n";
        }
        elseif($task == 'refresh_parentIDs_work_15') { fclose($WRITE);
            $total = self::get_total_rows($this->main_path."/work_15.txt"); echo "\n work_15 [$total]\n";
            $total = self::get_total_rows($this->main_path."/work_16.txt"); echo "\n work_16 [$total]\n";
        }
        
        if($task == 'build_up_useful_cols_DH11') { fclose($WRITE);
            $total = self::get_total_rows($this->tsv['DH11_Jen']); echo "\n DH11 [$total]\n";
            $total = self::get_total_rows($this->main_path."/DH11_working_new.txt"); echo "\n DH11_working_new [$total]\n";
        }
        elseif($task == 'build_up_useful_cols_DH21') { fclose($WRITE);
            $total = self::get_total_rows($this->tsv['DH21_Jen']); echo "\n DH21 [$total]\n";
            $total = self::get_total_rows($this->main_path."/DH21_working_new.txt"); echo "\n DH21_working_new [$total]\n";
        }
    }
    private function revive_fields($rec, $tmp_fields)
    {   // print_r($rec); print_r($tmp_fields); echo "\n-------------\n";
        foreach($tmp_fields as $fld) $final[$fld] = $rec[$fld];
        return $final;
    }
    private function RANK_TEST_yn($taxonrank, $rek)
    {   /* ver 1
        if($taxonrank && $rek['r'] && $taxonrank != $rek['r']) return false;
        else {
            if($taxonrank == $rek['r'] || !$taxonrank || !$rek['r']) return true;
            exit("\ninvestigate code 105\n");
        }
        exit("\ninvestigate code 104\n"); //will not go this line
        */
        // /* ver 2
        // taxonRank DH1 = taxonRank DH2
        // OR taxonRank DH1 is empty
        // OR taxonRank DH2 is empty
        // OR taxonRank DH1 is clade
        // OR taxonRank DH1 is infraspecies AND taxonRank DH2 is (form OR subspecies OR subvariety OR variety)
        // OR taxonRank DH2 is infraspecies AND taxonRank DH1 is (form OR subspecies OR subvariety OR variety)
        $rank_DH1 = $rek['r'];
        $rank_DH2 = $taxonrank;
        if($rank_DH1 == $rank_DH2 || !$rank_DH1 || !$rank_DH2 || $rank_DH1 == 'clade'
            || ($rank_DH1 == 'infraspecies' && in_array($rank_DH2, array("form", "subspecies", "subvariety", "variety")))
            || ($rank_DH2 == 'infraspecies' && in_array($rank_DH1, array("form", "subspecies", "subvariety", "variety")))
        ) return true;
        else return false;
        // */
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
    {   /*
        3-1 SINGLE CANONICAL MATCH IN DH1
        If there is a single canonical match in DH1, but the DH2 taxon is a homonym, 
        we need to figure out which of the DH2 homonyms should be matched to the DH1 taxon.
        RANK TEST as above
        ANCESTRY TEST (as above)
        */
        $in_question = "Pseudobalaninus septempunctatus"; //Lagena
        $canonicalname = $rec['canonicalname'];
        $DH2_homonyms = $this->DH2_canonicals[$canonicalname];
        if(count($DH2_homonyms) <= 1) exit("\nInvestigate code 201. DH2 here should always be > 1\n");
        
        // if($rec['taxonid'] == '-41870') { print_r($rec); print_r($DH2_homonyms); }   //debug only
        // if($canonicalname == "$in_question") print_r($DH2_homonyms);                 //debug only
        
        /*Array(    [0] => Array(
                            [ID] => -13980
                            [pID] => EOL-000000003677
                            [r] => genus
                            [cf] => Bifidobacteriaceae
                            [cp] => Bifidobacteriaceae
                            [cg] => Bifidobacteriales
                    [1] => Array(
                            [ID] => -1138255
                            [pID] => EOL-NoDH00125928
                            [r] => genus
                            [cf] => Lucinidae
                            [cp] => Myrteinae
                            [cg] => Lucinidae
        )*/
        $ancestry_test_success = 0;
        foreach($DH2_homonyms as $DH2_rec) { //@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
            $orig_taxonid = $DH2_rec['ID'];
            $taxonrank    = $DH2_rec['r'];

            if($reks = $this->DH1_canonicals[$canonicalname]) { //$reks is DH1
                if(count($reks) > 1) exit("\nInvestigate code 200. DH1 here should always be eq to 1 aaa\n");
                if(count($reks) < 1) exit("\nInvestigate code 200. DH1 here should always be eq to 1 bbb\n");
            }
            else exit("\nerror: should not go here 1.\n");
            /* debug
            if($orig_taxonid == -236079) {
                print_r($reks); //exit("\ninvestigate now...\n");
            }
            */
            // $rank_test_success = 0;
            foreach($reks as $rek) { //$rek is DH1
                if(self::RANK_TEST_yn($taxonrank, $rek)) { //$rank_test_success++;
                    /* If the rank test passes for at least one DH2 candidate, 
                    do an ANCESTRY TEST for each of the DH2 candidates that passed the rank test. */
                    $DH2_rec['pass_ANCESTRY_TEST'] = 'N';
                    $DH2_rec = self::do_ANCESTRY_TEST($DH2_rec, $rek, 2); //3rd param $rec_type = 2
                    if($DH2_rec['pass_ANCESTRY_TEST'] == 'Y') { $ancestry_test_success++;
                        $DH2_pass_ancestry_rec = array();
                        $DH2_pass_ancestry_rec = $DH2_rec;
                        $DH2_pass_ancestry_rec['transform_annote'] = $DH2_rec['eolidannotations'];
                        $DH2_pass_ancestry_rec['transform_DH1_rek'] = $rek;
                        unset($DH2_pass_ancestry_rec['success_rek']); //to lessen those to save to json
                        self::to_json($DH2_pass_ancestry_rec);
                        // if($canonicalname == "$in_question") { echo "\n111"; print_r($DH2_pass_ancestry_rec); } //debug only
                    }
                    else {
                        $DH2_rec['transform_annote'] = 'h-ancestorMismatch';
                        self::to_json($DH2_rec);
                    }
                }
                else {
                    /* If the rank test fails for any of the DH2 homonyms, 
                    create new identifiers for those homonyms, 
                    update all relevant parentNameUsageID values, 
                    and put “h-RankMismatch” in the EOLidAnnotations column. */
                    @$this->ctr_G31++;
                    $new_id = 'EOL-G31_' . sprintf("%08d", $this->ctr_G31);

                    // $this->replaced_by[$DH2_rec['taxonid']] = $new_id;
                    // $DH2_rec['taxonid'] = $new_id;
                    // $DH2_rec['eolidannotations'] = 'h-RankMismatch';
                    
                    $DH2_rec['transform_new_id'] = $new_id;
                    $DH2_rec['transform_annote'] = 'h-RankMismatch';
                    self::to_json($DH2_rec);
                    // if($canonicalname == "$in_question") { echo "\n222"; print_r($DH2_rec); } //debug only
                }
            } //end foreach() $reks
        } //end foreach() $DH2_homonyms @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

        /* Symptom */

        if($ancestry_test_success == 0) {
            /* If the ancestry tests fail for all of the DH2 candidates, leave the old taxonIDs, 
            and put “h-ancestorMismatch” in the EOLidAnnotations column. */
            foreach($DH2_homonyms as $DH2_rec) {
                $DH2_rec['transform_annote'] = 'h-ancestorMismatch';
                self::to_json($DH2_rec);
            }
        }

        /* If there is only one DH2 candidate that passes both the rank test and the ancestry test, 
        replace the current DH2 taxonID of that candidate with the taxonID of the DH1 taxon 
        and update all relevant parentNameUsageID values. 
        Also, put “h-ancestorMatch” in the EOLidAnnotations column for this taxon. */
        if($ancestry_test_success == 1) {
            $DH2_pass_ancestry_rec['transform_annote'] = 'h-ancestorMatch';
        }
        
        /* If there is more than one DH2 candidate that passes both the rank test and the ancestry test, 
        leave the old taxonIDs, and put “multipleMatches” in the EOLidAnnotations column for these taxa. */
        if($ancestry_test_success > 1) {
            foreach($DH2_homonyms as $DH2_rec) {
                $DH2_rec['transform_annote'] = 'multipleMatches';
                self::to_json($DH2_rec);
            }
        }

        // no 'return' at this point
        // if(isset($rec['pass_ANCESTRY_TEST'])) unset($rec['pass_ANCESTRY_TEST']);
        // if(isset($rec['success_rek'])) unset($rec['success_rek']);
        // return $rec;

    } //end main_G3_1()
    private function main_G3_2($rec)
    {   /*
        3-2 MULTIPLE CANONICAL MATCHES IN DH1
        If both DH1 and DH2 taxa of a canonical match pair are homonyms, 
        we need to perform RANK and ANCESTRY TESTS on all possible pairs. 
        We want to transfer taxonIDs only if a DH2 homonym can be UNIQUELY matched to a DH1 homonym based on rank & ancestry data. 
        Make sure you use the ancestry data from the original DH1 & DH2 files.

        RANK TEST as above for each DH1/DH2 pair
        ANCESTRY TEST (as above)
        */
        $in_question = 'Baileya'; //debug only
        $canonicalname = $rec['canonicalname'];
        $DH2_homonyms = $this->DH2_canonicals[$canonicalname];
        if(count($DH2_homonyms) <= 1) exit("\nInvestigate code 201. DH2 here should always be > 1\n");

        // if($rec['taxonid'] == '-41870') {
        //     print_r($rec);
        //     print_r($DH2_homonyms); //exit;
        // }
        // if($canonicalname == "$in_question") { echo "\nDH2 homonyms: "; print_r($DH2_homonyms); } //debug only

        /*Array(    [0] => Array(
                            [ID] => -13980
                            [pID] => EOL-000000003677
                            [r] => genus
                            [cf] => Bifidobacteriaceae
                            [cp] => Bifidobacteriaceae
                            [cg] => Bifidobacteriales
                    [1] => Array(
                            [ID] => -1138255
                            [pID] => EOL-NoDH00125928
                            [r] => genus
                            [cf] => Lucinidae
                            [cp] => Myrteinae
                            [cg] => Lucinidae
        )*/
        $DH1_passed_both_rank_and_ancestry_test = array();
        $DH1_passes_both = array();
        foreach($DH2_homonyms as $DH2_rec) { //@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
            $orig_taxonid = $DH2_rec['ID'];
            $taxonrank    = $DH2_rec['r'];

            if($reks = $this->DH1_canonicals[$canonicalname]) { //$reks is DH1
                if(count($reks) <= 1) exit("\nInvestigate code 202. DH1 here should always be > 1\n");
            }
            else exit("\nerror: should not go here 1.\n");
            
            // if($canonicalname == "$in_question") { echo "\nDH1 reks:"; print_r($reks); //exit; } //debug only
            
            /* debug
            if($orig_taxonid == -236079) {
                print_r($reks); //exit("\ninvestigate now...\n");
            }
            */
            $rank_test_success = 0;     //monitor per DH2 record
            $ancestry_test_success = 0; //monitor per DH2 record
            foreach($reks as $rek) { //$rek is DH1
                if(self::RANK_TEST_yn($taxonrank, $rek)) { $rank_test_success++;
                    /*
                    For DH2 homonyms that pass the rank test with at least one DH1 candidate, 
                    do an ANCESTRY TEST for each of the passing candidates.
                    */
                    $DH2_rec['pass_ANCESTRY_TEST'] = 'N';
                    $DH2_rec = self::do_ANCESTRY_TEST($DH2_rec, $rek, 2); //3rd param $rec_type = 2
                    if($DH2_rec['pass_ANCESTRY_TEST'] == 'Y') { $ancestry_test_success++;
                        $rek['DH2 in question'] = $DH2_rec;
                        $DH1_passed_both_rank_and_ancestry_test[] = $rek;
                        $DH1_passes_both[$rek['ID']][] = $DH2_rec; //to be used below
                        // if($canonicalname == "$in_question") { echo "\nAncestry Passed: DH2 [".$DH2_rec['ID']."]\n"; print_r($rek); } debug only
                    }
                    else {
                        // if($canonicalname == "$in_question") { echo "\nAncestry Failed: DH2 [".$DH2_rec['ID']."]\n"; print_r($rek); } debug only
                    } //wala na lang
                }
                else {} //wala na lang
            } //end foreach() $reks

            if($rank_test_success == 0) {
                /*
                For DH2 homonyms that fail the rank test with all of the DH1 candidates, 
                leave the old DH2 taxonID and put “h-RankMismatch” in the EOLidAnnotations column for this taxon.
                */
                // @$this->ctr_G32++;
                // $new_id = 'EOL-G32_' . sprintf("%08d", $this->ctr_G32);
                // $DH2_rec['transform_new_id'] = $new_id;
                $DH2_rec['transform_annote'] = 'h-RankMismatch xG3.2';
                self::to_json($DH2_rec, "json_3_2.txt");
                continue; //important here
            }
            if($ancestry_test_success == 0) {
                /*
                If the ancestry tests fail for all of the DH1 candidates, 
                leave the old DH2 taxonID and put “h-ancestorMismatch” in the EOLidAnnotations column.
                */
                $DH2_rec['transform_annote'] = 'h-ancestorMismatch xG3.2';
                self::to_json($DH2_rec, "json_3_2.txt");
            }

            foreach($DH1_passes_both as $rek_ID => $DH2_recs) {
                if(count($DH2_recs) == 1) {
                    foreach($DH2_recs as $DH2_rec) {
                        $DH2_pass_ancestry_rec = array();
                        $DH2_pass_ancestry_rec = $DH2_rec;
                        $DH2_pass_ancestry_rec['transform_annote'] = $DH2_rec['eolidannotations'];
                        $rek = $DH2_rec['success_rek'][0];
                        $DH2_pass_ancestry_rec['transform_DH1_rek'] = $rek;
                        unset($DH2_pass_ancestry_rec['success_rek']); //to lessen those to save to json
                        self::to_json($DH2_pass_ancestry_rec, "json_3_2.txt");
                        // if($canonicalname == "$in_question") { echo "\nDH2 rec with only 1 DH1 rek -> "; print_r($DH2_pass_ancestry_rec); } debug only
                    }
                }
            }

            /* If there is more than one DH1 candidate that passes both the rank test and the ancestry test, 
            leave the old DH2 taxonID, and put “multipleMatches” in the EOLidAnnotations column for this taxon. */
            if($ancestry_test_success > 1) {
                $DH2_rec['transform_annote'] = 'multipleMatches A xG3.2';
                self::to_json($DH2_rec, "json_3_2.txt");
            }
            
        } //end foreach() $DH2_homonyms @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

        /* If there is only one DH1 candidate that passes both the rank test and the ancestry test 
        AND there are no other DH2 homonyms that pass the rank & ancestry tests with this DH1 candidate, 
        replace the current DH2 taxonID with the taxonID of the DH1 candidate 
        and update all relevant parentNameUsageID values. 
        Also, put “h-ancestorMatch” in the EOLidAnnotations column for this taxon. */
        if(count($DH1_passed_both_rank_and_ancestry_test) == 1) {
            $rek = $DH1_passed_both_rank_and_ancestry_test[0];
            $DH2 = array();
            $DH2 = $rek['DH2 in question'];
            $DH2['transform_annote'] = 'h-ancestorMatch xG3.2';
            unset($rek['DH2 in question']); //to lessen those to save to json
            $DH2['transform_DH1_rek'] = $rek;
            self::to_json($DH2, "json_3_2.txt");
        }

        /* If there are multiple DH2 homonyms that pass both the rank test and the ancestry test with a given DH1 candidate, 
        leave the old taxonIDs for the DH2 homonyms, 
        and put “multipleMatches” in the EOLidAnnotations column for these taxa. */
        foreach($DH1_passes_both as $rek_ID => $DH2_recs) {
            if(count($DH2_recs) > 1) {
                foreach($DH2_recs as $DH2_rec) {
                    $DH2_rec['transform_annote'] = 'multipleMatches B xG3.2';
                    self::to_json($DH2_rec, "json_3_2.txt");
                }
            }
        }
        // if($canonicalname == "$in_question") exit("\n-end tests...-\n");
    } //end main_G3_2()
    private function main_G3_1and2_post($rec) //$rec is DH2
    {   //print_r($rec); exit("\n111\n");
        /*Array(
            [taxonid] => -13980
            [source] => NCBI:2701
            [furtherinformationurl] => https://www.ncbi.nlm.nih.gov/Taxonomy/Browser/wwwtax.cgi?id=2701
            [acceptednameusageid] => 
            [parentnameusageid] => EOL-000000003677
            [scientificname] => Gardnerella
            [taxonrank] => genus
            [taxonomicstatus] => accepted
            [taxonremarks] => 
            [datasetid] => NCBI
            [canonicalname] => Gardnerella
            [eolid] => 
            [eolidannotations] => 
            [landmark] => 
            [canonical_family_ancestor] => Bifidobacteriaceae
            [canonical_parent] => Bifidobacteriaceae
            [canonical_grandparent] => Bifidobacteriales
            [canomatchdh1_yn] => 1
            [homonyms_yn] => Y
            [group] => G3_1
        )*/
        
        /* sample json lookup
        [-845649] => Array(
                    [ID] => -845649
                    [transform_annote] => h-RankMismatch
                    [transform_new_id] => EOL-G31_00000027
                )
        -------------------------------
        [-151544] => Array(
                [ID] => -151544
                [transform_annote] => h-ancestorMismatch
            )
        -------------------------------
        [-41870] => Array(
                [ID] => -41870
                [transform_annote] => ancestorMatch: [Lagenaceae], [Lagenaceae]
                [transform_DH1_rek] => Array(
                        [ID] => EOL-000000094987
                        [pID] => EOL-000000094986
                        [r] => genus
                        [cf] => Lagenaceae
                        [cp] => Lagenaceae
                        [cg] => Gyrista
                    )
            )
        -------------------------------
        $zzz['transform_annote'] = 'multipleMatches'; ---> this one is for G3.2 multiple matches in DH1
        -------------------------------
        */
        $taxonid = $rec['taxonid'];
        if($arr = @$this->json_info[$taxonid]) {
            if($rek = @$arr['transform_DH1_rek']) {
                $new_id = $rek['ID'];
                $this->replaced_by[$taxonid] = $new_id;
                $rec['taxonid'] = $new_id;
                $rec['eolidannotations'] = $arr['transform_annote'];
            }
            elseif($new_id = @$arr['transform_new_id']) {
                $this->replaced_by[$taxonid] = $new_id;
                $rec['taxonid'] = $new_id;
                $rec['eolidannotations'] = $arr['transform_annote'];
            }
            elseif(stripos($arr['transform_annote'], "ancestorMismatch") !== false) { //string is found
                $rec['eolidannotations'] = $arr['transform_annote'];
            }
        }
        return $rec;
    } //end main_G3_1and2_post()
    
    private function to_json($rec, $filename = 'json_3_1.txt')
    {   // echo "\n-----------------\n"; print_r($rec);
        $ID = $rec['ID'];
        if(!isset($this->saved[$ID])) {
            $json = json_encode($rec);
            $WRITE = fopen($this->main_path."/$filename", "a");
            fwrite($WRITE, $json."\n");
            fclose($WRITE);
        }
        $this->saved[$ID] = '';
    }
    private function main_G2_2($rec) //$rec is DH2
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
        /* 2-2 MULTIPLE CANONICAL MATCHES IN DH1
        If there are multiple canonical matches in DH1 for a given DH2 non-homonym, 
        we need to figure out which of the DH1 homonyms should be matched to the DH2 taxon.
        RANK TEST as above */

        $orig_taxonid = $rec['taxonid'];    $canonicalname = $rec['canonicalname'];     $taxonrank = $rec['taxonrank'];
        if($reks = $this->DH1_canonicals[$canonicalname]) {} //$reks is DH1
        else exit("\nerror: should not go here 1.\n");
        if(count($reks) == 1) exit("\nInvestigate code 103. Should always be > 1\n");
        
        /* debug
        if($orig_taxonid == -236079) {
            print_r($reks); //exit("\ninvestigate now...\n");
        }
        */
        
        $rank_test_success = 0;
        $ancestry_test_success = 0;
        foreach($reks as $rek) { //$rek is DH1
            if(self::RANK_TEST_yn($taxonrank, $rek)) { $rank_test_success++;
                // If the rank test passes for at least one DH1 candidate, 
                // do an ANCESTRY TEST (as above) for each of the candidates that passed the rank test.
                $rec['pass_ANCESTRY_TEST'] = 'N';
                $rec = self::do_ANCESTRY_TEST($rec, $rek);
                if($rec['pass_ANCESTRY_TEST'] == 'Y') { $ancestry_test_success++; }
            }
        }
        
        /* Symptom 1: There are 3218 taxa without EOLidAnnotations. All of them are in group G2_2 or G3_2. 
        Many of these taxa should have passed the RANK & ANCESTRY TESTS with exactly one DH1 taxon, but they did not get matched. 
        They still have their old taxonID. Here’s one example: 
        Reticularia (genus: 2x DH1, 1x DH2)
        DH2 ancestry (​​-236079): … Liceales (order) > Reticulariaceae (family)
        DH1 ancestry (EOL-000000097326): … Liceales (order) > Reticulariaceae (family) -> should have matched -> transfer taxonID, h-ancestorMatch
        DH1 ancestry (EOL-000000867314): … Reticularioidea (superfamily) > Reticulariidae (family) -> no match, disregard
        [success_rek] => Array(
                [0] => Array(
                        [ID] => EOL-000000097326
                        [pID] => EOL-000000097303
                        [r] => genus
                        [cf] => Reticulariaceae
                        [cp] => Reticulariaceae
                        [cg] => Liceales
                        [annote] => ancestorMatch: [Reticulariaceae], [Reticulariaceae]
                )
            )
        )
        rank_test_success: [2]
        ancestry_test_success: [1]
        */
        
        if($rank_test_success == 0) {
            // If the rank test fails with all DH1 candidates, create a new identifier for the DH2 taxon, 
            // update all relevant parentNameUsageID values, and put "h-RankMismatch" in the EOLidAnnotations column.
            @$this->ctr_G22++;
            $new_id = 'EOL-G22_' . sprintf("%08d", $this->ctr_G22);
            $this->replaced_by[$rec['taxonid']] = $new_id;
            $rec['taxonid'] = $new_id;
            $rec['eolidannotations'] = 'h-RankMismatch';
            return $rec; // deliberately to run 'return' here OK
        }
        if($ancestry_test_success == 0) {
            // If the ancestry tests fail for all of the DH1 candidates, 
            // leave the old DH2 taxonIDs and put "h-ancestorMismatch" in the EOLidAnnotations column.
            $rec['taxonid'] = $orig_taxonid;
            $rec['eolidannotations'] = "h-ancestorMismatch";
        }
        // if($rank_test_success == 1 && $ancestry_test_success == 1) { --- this is wrong implementation
        if(count(@$rec['success_rek']) == 1) {                        //--- this is the correct implementation
            // If there is only one DH1 candidate that passes both the rank test and the ancestry test, 
            // replace the current DH2 taxonID with the taxonID of the DH1 candidate and update all relevant parentNameUsageID values. 
            // Also, put "h-ancestorMatch" in the EOLidAnnotations column for this taxon.
            $this->replaced_by[$rec['taxonid']] = $rec['success_rek'][0]['ID'];
            $rec['taxonid']                     = $rec['success_rek'][0]['ID'];
            $rec['eolidannotations'] = "h-".$rec['success_rek'][0]['annote']; //= "h-ancestorMatch";
        }
        // if($rank_test_success > 1 && $ancestry_test_success > 1) { --- this is wrong implementation
        if(count(@$rec['success_rek']) > 1) {                       //--- this is the correct implementation
            // If there is more than one DH1 candidate that passes both the rank test and the ancestry test, 
            // leave the old DH2 taxonID, and put "multipleMatches" in the EOLidAnnotations column for this taxon.
            $rec['taxonid'] = $orig_taxonid;
            $rec['eolidannotations'] = "multipleMatches";
        }
        
        /* good debug
        if($orig_taxonid == -236079) {
            print_r($reks);
            print_r($rec);
            echo "\n rank_test_success: [$rank_test_success]\n";
            echo "\n ancestry_test_success: [$ancestry_test_success]\n";
            exit("\n\ninvestigate now...\n");
        }
        */

        if(isset($rec['pass_ANCESTRY_TEST'])) unset($rec['pass_ANCESTRY_TEST']);
        if(isset($rec['success_rek'])) unset($rec['success_rek']);
        return $rec;
    } // end main_G2_2()
    private function main_G2_1($rec) //$rec is DH2
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
            /* 2-1 SINGLE CANONICAL MATCH IN DH1
            There is only one DH1 taxon that’s a canonical match for the DH2 non-homonym taxon. */
            if(count($reks) == 1) {}
            else exit("\nInvestigate code 102\n");
            /*Array( $reks
                [0] => Array(
                        [ID] => EOL-000000000001
                        [pID] => 
                        [r] => clade
                        [cf] => 
                        [cp] => 
                        [cg] => 
                    )
            )*/
            $rek = $reks[0];
            // RANK TEST
            if(self::RANK_TEST_yn($taxonrank, $rek)) {
                /* If this is TRUE, the rank test passes, and we can transfer the DH1 taxonID: 
                Replace the current DH2 taxonID with the DH1 taxonID and update all relevant parentNameUsageID values. */
                $this->replaced_by[$rec['taxonid']] = $rek['ID'];
                $rec['taxonid'] = $rek['ID'];
                /* >>>>> ANCESTRY TEST (REVISED!): FAMILY TEST or PARENT/GRANDPARENT TEST <<<<<
                Do this for all DH2 non-homonyms that passed the rank test. */
                $rec = self::do_ANCESTRY_TEST($rec, $rek);
            }
            else {
                /* If this is FALSE, the rank test fails, and we won't transfer the DH1 taxonID. 
                Instead, create a new identifier for the DH2 taxon and update all relevant parentNameUsageID values. 
                Also, put “rankMismatch” in the EOLidAnnotations column for this taxon. */
                @$this->ctr_G21++;
                $new_id = 'EOL-G21_' . sprintf("%08d", $this->ctr_G21);
                $this->replaced_by[$rec['taxonid']] = $new_id;
                $rec['taxonid'] = $new_id;
                $rec['eolidannotations'] = 'rankMismatch';
            }
        }
        else exit("\nerror: should not go here 1.\n");
        return $rec;
    } //end main_G2_1()
    private function do_ANCESTRY_TEST($rec, $rek, $rec_type = 1)
    {
        /* >>>>> ANCESTRY TEST (REVISED!): FAMILY TEST or PARENT/GRANDPARENT TEST <<<<<
        Do this for all DH2 non-homonyms that passed the rank test.
        
        FAMILY TEST:
        Do the FAMILY TEST for taxa of any rank, if both the DH2 taxon and the matching DH1 taxon have an ancestor where taxonRank=family. 
        If one or both matching taxa do not have a family in their ancestry, do the rank-agnostic PARENT/GRANDPARENT TEST instead.
        canonicalNameDH1 family = canonicalNameDH2 family
        If this is FALSE, put “ancestorMismatch” in the EOLidAnnotations column for this taxon.
        If this is TRUE, put “ancestorMatch” in the EOLidAnnotations column for this taxon.
        */
        // $DH2_fam = $rec['canonical_family_ancestor'];
        $DH2_fam = ($rec_type == 1) ? $rec['canonical_family_ancestor'] : $rec['cf'];
        
        $DH1_fam = $rek['cf'];
        if($DH2_fam && $DH1_fam) {
            if($DH1_fam == $DH2_fam) {
                $rec['eolidannotations'] = "ancestorMatch: [$DH1_fam], [$DH2_fam]";
                // /* used in G2_2
                $rec['pass_ANCESTRY_TEST'] = 'Y';
                $rek['annote'] = $rec['eolidannotations'];
                $rec['success_rek'][] = $rek;
                // */
            }
            else {
                $rec['eolidannotations'] = "ancestorMismatch: [$DH1_fam], [$DH2_fam]";
                // /* used in G2_2
                $rec['pass_ANCESTRY_TEST'] = 'N';
                // */
            }
        }
        else { /* PARENT/GRANDPARENT TEST:
            canonicalName of DH1parent = canonicalName of DH2parent OR 
            canonicalName of DH1parent = canonicalName of DH2grandparent OR 
            canonicalName of DH1grandparent = canonicalName of DH2parent OR 
            canonicalName of DH1grandparent = canonicalName of DH2grandparent
            If this is FALSE, put “ancestorMismatch” in the EOLidAnnotations column for this taxon.
            If this is TRUE, put “ancestorMatch” in the EOLidAnnotations column for this taxon. */
            // $DH2_parent = $rec['canonical_parent'];
            $DH2_parent = ($rec_type == 1) ? $rec['canonical_parent'] : $rec['cp'];
            // $DH2_grandparent = $rec['canonical_grandparent'];
            $DH2_grandparent = ($rec_type == 1) ? $rec['canonical_grandparent'] : $rec['cg'];
            $DH1_parent = $rek['cp'];               
            $DH1_grandparent = $rek['cg'];
            if($DH1_parent == $DH2_parent || $DH1_parent == $DH2_grandparent || $DH1_grandparent == $DH2_parent || $DH1_grandparent == $DH2_grandparent) {
                 $rec['eolidannotations'] = "ancestorMatch: [$DH1_parent]-[$DH1_grandparent], [$DH2_parent]-[$DH2_grandparent]";
                 // /* used in G2_2
                 $rec['pass_ANCESTRY_TEST'] = 'Y';
                 $rek['annote'] = $rec['eolidannotations'];
                 $rec['success_rek'][] = $rek;
                 // */
            }
            else {
                $rec['eolidannotations'] = "ancestorMismatch: [$DH1_parent]-[$DH1_grandparent], [$DH2_parent]-[$DH2_grandparent]";
                // /* used in G2_2
                $rec['pass_ANCESTRY_TEST'] = 'N';
                // */
            }
        }
        return $rec;
    }
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
                if($i == 0) { /* $ancestry[0] is the taxon in question -> $rec['taxonid] */
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
            //copied below: added in v2
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
                    $tmp_fields[] = 'old_taxonid';
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
                // $rec = self::revive_fields($rec, $tmp_fields); //new
                fwrite($WRITE, implode("\t", $rec)."\n");
            }
            
            if($task == 'tag_DH2_with_Homonyms_YN') {
                $canonicalname = $rec['canonicalname'];
                if($this->DH2_canonicals[$canonicalname] > 1) $rec['Homonyms_YN'] = 'Y';
                elseif($this->DH2_canonicals[$canonicalname] == 1) $rec['Homonyms_YN'] = 'N';
                else { print_r($rec); exit("\nInvestigate 1\n"); }
                // $rec = self::revive_fields($rec, $tmp_fields); //new
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
                $rec['old_taxonid'] = $rec['taxonid']; //initialize old_taxonid
                $canonicalname = $rec['canonicalname'];
                /* 1st ver
                if(isset($this->DH1_canonicals[$canonicalname])) $rec['CanoMatchDH1_YN'] = 'Y';
                */
                if($val = @$this->DH1_canonicals[$canonicalname]) $rec['CanoMatchDH1_YN'] = $val; // value is either blank or 1 or >1
                else { @$this->no_match++;
                    $rec['CanoMatchDH1_YN'] = 'N';
                    $new_id = 'EOL-NoDH' . sprintf("%08d", $this->no_match);
                    $this->replaced_by[$rec['taxonid']] = $new_id;
                    $rec['taxonid'] = $new_id;
                    $rec['eolidannotations'] = 'new';
                }
                $rec = self::revive_fields($rec, $tmp_fields); //new
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
        $source = $this->main_path."/work_12.txt";
        $source = $this->main_path."/work_16.txt";
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
            
            if(!isset($IDs[$rec['taxonid']])) {
                $IDs[$rec['taxonid']] = '';
            }
            else echo("\nDuplicate ID: ".$rec['taxonid']."\n");
            // print_r($rec);
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