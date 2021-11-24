<?php
namespace php_active_record;
/* connector: [taxonIDs_for_DH21.php] - TRAM-995
*/
class DH_v21_TRAM_995
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
            $this->main_path = "/Volumes/AKiTiO4/d_w_h/TRAM-995/";
        }
        $this->tsv['DH11'] = $this->main_path."/dh1_1/DH1_1working.txt";
        $this->tsv['DH21'] = $this->main_path."/dh2_1/DH2_1working.txt";
    }
    // ----------------------------------------------------------------- start TRAM-807 -----------------------------------------------------------------
    function start()
    {   
        /* works
        // self::get_taxID_nodes_info($this->tsv['DH21']); //un-comment in real operation
        self::get_taxID_nodes_info($this->main_path."/work_2.txt"); //un-comment in real operation
        $taxonID = 'EOL-N00000000002'; //'EOL-000000000001';
        $ancestry = self::get_ancestry_of_taxID($taxonID); print_r($ancestry); //exit; //working OK but not used yet
        $taxonID = 'EOL-N00000000002'; //'EOL-000000000005';
        $children = self::get_descendants_of_taxID($taxonID); print_r($children); exit("\n");
        */
        /* GROUP 1: DH2 taxa (homonyms or not) that have no canonical match in DH1, i.e., DH1canonicalName = DH2canonicalName is never true
        Create a new EOL-xxx style identifier for each of these taxa and update all relevant parentNameUsageID values. 
        Also, put “new” in the EOLidAnnotations column for each taxon.
        */
        /* Ok good
        self::tag_DH2_with_NoCanonicalMatch_in_DH1();   //ends with work_2.txt
        self::tag_DH2_with_Homonyms_YN();               //ends with work_3.txt
        self::tag_DH2_with_group();                     //ends with work_4.txt -> also generates stats to see if all categories are correctly covered...
        */
        
        exit("\n-stop muna-\n");
    }
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
    {   $this->taxID_info = array(); $this->descendants = array(); //initialize global vars
        $i = 0;
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
                    $new_id = 'EOL-NoDH1' . sprintf("%06d", $this->no_match);
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
            $i++; if(($i % 200000) == 0) echo "\n".number_format($i)." ";
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
    }
}
?>