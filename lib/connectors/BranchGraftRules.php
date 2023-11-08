<?php
namespace php_active_record;
/*
*/
class BranchGraftRules
{
    function __construct()
    {   
    }
    private function initialize()
    {   
        $this->temp_dir = CONTENT_RESOURCE_LOCAL_PATH . '/Branch_Graft/';
        if(!is_dir($this->temp_dir)) mkdir($this->temp_dir);

        /*
        print_r($this->input); exit;
        [path] => /opt/homebrew/var/www/eol_php_code//applications/branch_graft/temp/
        */

        $this->trimmed_File_A = $this->input['path'] . "branch_removed_File_A_" . $this->arr_json['uuid'] . ".txt"; //old trimmed_File_A_
        $WRITE = Functions::file_open($this->trimmed_File_A, "w"); fclose($WRITE);

        $this->descendants_File_A = $this->input['path'] . "removed_taxa_File_A_" . $this->arr_json['uuid'] . ".txt";
        $WRITE = Functions::file_open($this->descendants_File_A, "w"); fclose($WRITE);

        $this->descendants_File_B = $this->input['path'] . "removed_taxa_File_B_" . $this->arr_json['uuid'] . ".txt";
        $WRITE = Functions::file_open($this->descendants_File_B, "w"); fclose($WRITE);

        $this->descendants_File_B2 = $this->input['path'] . "grafted_branch_File_B_" . $this->arr_json['uuid'] . ".txt"; //old removed_taxa_File_B2_
        $WRITE = Functions::file_open($this->descendants_File_B2, "w"); fclose($WRITE);

        $this->trimmed_File_A2 = $this->input['path'] . "revised_File_A_" . $this->arr_json['uuid'] . ".txt"; //old trimmed_File_A2_
        $WRITE = Functions::file_open($this->trimmed_File_A2, "w"); fclose($WRITE);

        $this->debug_rules = array();
    }
    function start_grafting($input_fileA, $input_fileB)
    {   self::initialize();
        /* Array(
        [Filename_ID] => 
        [Short_Desc] => 
        [timestart] => 0.002263
        [newfile_File_A] => File_A_1688396971.tab
        [newfile_File_B] => File_B_1688396971.tsv
        [fileA_taxonID] => EOL-000000095511
        [fileB_taxonID] => eli02
        [uuid] => 1688396971
        ) */
        // print_r($this->arr_json); exit("\nend 200\n");

        // /* ~~~~~~~~~~ step 1: generate $parentID_taxonID from File A.
        $parentID_taxonID = self::parse_TSV_file($input_fileA, "generate parentID_taxonID");
        // */
        // /* ~~~~~~~~~~ step 2: read file A, get all descendants of fileA_taxonID
        $parent_ids = array($this->arr_json['fileA_taxonID']);
        require_library('connectors/PaleoDBAPI_v2');
        $func = new PaleoDBAPI_v2("");
        $descendants_A = $func->get_all_descendants_of_these_parents($parent_ids, $parentID_taxonID); // print_r($descendants_A);
        unset($parentID_taxonID); unset($func);
        $this->descendants_A = array_flip($descendants_A); //print_r($this->descendants_A); exit;
        echo "\nFile A total descendants: [".count($descendants_A)."]";
        echo "\nFile A total descendants: [".count($this->descendants_A)."]\n";
        unset($descendants_A);
        // */
        // /* ~~~~~~~~~~ step 3: now remove all descendants of fileA_taxonID, and their synonyms
        self::parse_TSV_file($input_fileA, "generate trimmed File A");
        unset($this->descendants_A);
        // */

        /* ~~~~~~~~~~ step 4: If there is no value for yyy, we are ready to create the output file, with the descendants & their synonyms removed 
                              and the note in the notes column added for the basal taxon. */
        if($fileB_taxonID = $this->arr_json['fileB_taxonID']) {
            self::process_with_yyy($input_fileB);
            $with_yyy = true;   //trimmed_File_A2 is the final result
        }
        else $with_yyy = false; //trimmed_File_A is now the final result

        $download_links = array();
        // /* ~~~~~~~~~~ step 4.1: check parents_ids and/or accept_ids
        if($with_yyy) $local_path = $this->trimmed_File_A2;
        else          $local_path = $this->trimmed_File_A;
        self::check_parentIDs_acceptIDs($local_path);

        $download_links = array();
        // start - Diagnostics download link(s) -----
        $download_links = array();
        $resource_id = "/Branch_Graft/diagnostics_".$this->arr_json['uuid'];
        $files = array(); //possible diagnostics reports
        $files[] = "_undefined_parent_ids.txt";
        $files[] = "_undefined_acceptedName_ids.txt";
        foreach($files as $what_filename) {
            $possible = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . $what_filename;
            if(file_exists($possible)) $download_links[] = $possible_link;
        }
        // end - Diagnostics download link(s) -----
        // */ 

        // /* ~~~~~~~~~~ step 4.2:
        self::prepare_download_link($with_yyy, $download_links);
        // */

        // exit("\n- exit muna-\n");
    }
    private function process_with_yyy($input_fileB)
    {   /* If there is a yyy value, add the following steps:
        4. Look for the taxon with taxonID yyy in File B and copy all of its descendants to File A.
        5. Change the parentNameUsageID of the immediate children of yyy to xxx.
        6. Copy over all taxa with acceptedNameUsageID values that point to descendants of yyy.
        7. Before copying taxa to file A, check if any of the taxonIDs of the descendants & synonyms to be copied are already used in File A, 
            if so, add -G to the original ID to make it unique. Also, make sure to update any parentNameUsageID or acceptedNameUsageID values, 
            so they point to the updated taxonID.
        8. When copying data from File B to File A, follow the File A column structure. If there are columns in File A that are not in File B, 
            leave those blank. If there are columns in File B that are not in File A, leave those data behind.
        9. For all taxa copied from File B to File A, add the filename of File B in the notes column. */

        ########################################################################## 4. start
        // 4. Look for the taxon with taxonID yyy in File B and copy all of its descendants to File A.
        // step 1: generate $parentID_taxonID from File B.
        $parentID_taxonID = self::parse_TSV_file($input_fileB, "generate parentID_taxonID");

        // step 2: read file B, get all descendants of fileB_taxonID
        $parent_ids = array($this->arr_json['fileB_taxonID']);
        require_library('connectors/PaleoDBAPI_v2');
        $func = new PaleoDBAPI_v2("");
        $descendants_B = $func->get_all_descendants_of_these_parents($parent_ids, $parentID_taxonID); // print_r($descendants_A);
        unset($parentID_taxonID); unset($func);
        $this->descendants_B = array_flip($descendants_B); //print_r($this->descendants_A); exit;
        echo "\nFile B total descendants: [".count($descendants_B)."]";
        echo "\nFile B total descendants: [".count($this->descendants_B)."]\n";
        unset($descendants_B);

        // step 3: write descendants of B to text 
        self::parse_TSV_file($input_fileB, "save File B descendants and its synonyms");
        unset($this->descendants_B);
        ########################################################################## 4. end
        ########################################################################## 5.6. start
        // 5. Change the parentNameUsageID of the immediate children of yyy to xxx.                         --> done already
        // 6. Copy over all taxa with acceptedNameUsageID values that point to descendants of yyy.          --> done already
        // 9. For all taxa copied from File B to File A, add the filename of File B in the notes column.    --> done already
        ########################################################################## 5.6. end
        ########################################################################## 7. end
        // 7. Before copying taxa to file A, check if any of the taxonIDs of the descendants & synonyms to be copied are already used in File A 
        //    (after the descendants of xxx are removed), if so, add -G to the original ID to make it unique. 
        //    Also, make sure to update any parentNameUsageID or acceptedNameUsageID values, so they point to the updated taxonID.

        // Also, make sure to update any parentNameUsageID or acceptedNameUsageID values, so they point to the updated taxonID.
        self::parse_TSV_file($this->descendants_File_B, "update parentID and acceptID affected by -G");
        unset($this->with_Gs);
        // Eli: update parentID and acceptID values where the orig values were added with string "-G".
        ########################################################################## 7. end
        ########################################################################## 8. start
        // 8. When copying data from File B to File A, follow the File A column structure. If there are columns in File A that are not in File B, 
        //     leave those blank. If there are columns in File B that are not in File A, leave those data behind.
        self::parse_TSV_file($this->descendants_File_B2, "copy from File B to File A");
        ########################################################################## 8. end
        // exit("\n- end muna process yyy -\n");
    }
    private function parse_TSV_file($txtfile, $task)
    {   $modulo = self::get_modulo($txtfile);
        if($task == "generate parentID_taxonID") { $modulo = 1000000; }
        $i = 0; $final = array(); debug("\nProcessing: [".pathinfo($txtfile, PATHINFO_BASENAME)."] [$task]\n"); //$syn = 0; for stats only
        foreach(new FileIterator($txtfile) as $line_number => $line) {
            if(!$line) continue;
            $i++; if(($i % $modulo) == 0) echo "\n".number_format($i)." ";
            $row = explode("\t", $line); // print_r($row);
            if($i == 1) {
                $fields = $row;
                $fields = array_filter($fields); //print_r($fields);
                continue;
            }
            else {
                $k = 0; $rec = array();
                foreach($fields as $fld) { $rec[$fld] = @$row[$k]; $k++; }
            }
            $rec = array_map('trim', $rec);
            //###############################################################################################
            if($task == "generate parentID_taxonID") { //print_r($rec); exit("\nstopx\n");
                /* Array(
                    [taxonID] => EOL-000000000001
                    [source] => trunk:4038af35-41da-469e-8806-40e60241bb58
                    [furtherInformationURL] => 
                    [acceptedNameUsageID] => 
                    [parentNameUsageID] => 
                    [scientificName] => Life
                    [taxonRank] => 
                    [taxonomicStatus] => accepted
                    [datasetID] => trunk
                    [canonicalName] => Life
                    [eolID] => 2913056
                    [Landmark] => 3
                )*/
                $parent_id = @$rec["parentNameUsageID"];
                $taxon_id = @$rec["taxonID"];
                if($parent_id && $taxon_id) $final[$parent_id][] = $taxon_id;
            }
            //###############################################################################################
            if($task == "generate trimmed File A") {
                $taxonID = $rec['taxonID'];
                $parentNameUsageID = $rec['parentNameUsageID'];
                $acceptedNameUsageID = $rec['acceptedNameUsageID'];
                $fileA_taxonID = $this->arr_json['fileA_taxonID'];

                if(isset($this->descendants_A[$taxonID])) {             //delete actual descendants
                    @$this->debug_rules['deleted']++;
                    self::write_output_rec_2txt($rec, $this->descendants_File_A);
                    continue;
                }
                if(isset($this->descendants_A[$acceptedNameUsageID])) { //delete synonyms of descendants
                    @$this->debug_rules['deleted']++;
                    self::write_output_rec_2txt($rec, $this->descendants_File_A);
                    continue;
                }
                if(isset($this->descendants_A[$parentNameUsageID])) {   //delete children of descendants; may not need this anymore.
                    @$this->debug_rules['deleted']++;
                    self::write_output_rec_2txt($rec, $this->descendants_File_A);
                    continue;
                }

                if($taxonID == $fileA_taxonID) $rec['notes'] = "new branch";
                else                           $rec['notes'] = @$rec['notes'];

                $this->File_A_taxonIDs[$taxonID] = ''; //to be used in no. 7
                // /* to be used in no. 9
                if($i == 2) {
                    $this->trimmed_File_A_headers = array_keys($rec);
                    // print_r($this->trimmed_File_A_headers); //just debug
                }
                // */
                self::write_output_rec_2txt($rec, $this->trimmed_File_A); // start writing
                // if($this->arr_json['fileB_taxonID']) self::write_output_rec_2txt($rec, $this->trimmed_File_A2); // start writing
            }
            //###############################################################################################
            if($task == "save File B descendants and its synonyms") {
                $taxonID = $rec['taxonID'];
                $parentNameUsageID = $rec['parentNameUsageID'];
                $acceptedNameUsageID = $rec['acceptedNameUsageID'];

                // /* 5. Change the parentNameUsageID of the immediate children of yyy to xxx.
                $fileA_taxonID = $this->arr_json['fileA_taxonID'];  // xxx
                $fileB_taxonID = $this->arr_json['fileB_taxonID'];  // yyy
                if($parentNameUsageID == $fileB_taxonID) $rec['parentNameUsageID'] = $fileA_taxonID;
                if($acceptedNameUsageID == $fileB_taxonID) $rec['acceptedNameUsageID'] = $fileA_taxonID; //Eli's initiative
                /* might need this
                $parentNameUsageID = $rec['parentNameUsageID'];
                $acceptedNameUsageID = $rec['acceptedNameUsageID'];
                */
                // */

                // /* 9. For all taxa copied from File B to File A, add the filename of File B in the notes column.
                if($notes = @$rec['notes']) $notes .= " | ".$this->arr_json['orig_file_B'];
                else                        $notes = $this->arr_json['orig_file_B'];
                $rec['notes'] = $notes;
                // */

                if(isset($this->descendants_B[$taxonID])) {             //get actual descendants
                    @$this->debug_rules['created B']++;
                    $rec = self::step_7_check_taxonID_is_found_inFileA($rec);
                    self::write_output_rec_2txt($rec, $this->descendants_File_B);
                    continue;
                }
                if(isset($this->descendants_B[$acceptedNameUsageID])) { //get synonyms of descendants
                    @$this->debug_rules['created B']++;
                    $rec = self::step_7_check_taxonID_is_found_inFileA($rec);
                    self::write_output_rec_2txt($rec, $this->descendants_File_B);
                    continue;
                }
                if(isset($this->descendants_B[$parentNameUsageID])) {   //get children of descendants; may not need this anymore.
                    @$this->debug_rules['created B']++;
                    $rec = self::step_7_check_taxonID_is_found_inFileA($rec);
                    self::write_output_rec_2txt($rec, $this->descendants_File_B);
                    continue;
                }
            }
            //###############################################################################################
            if($task == "update parentID and acceptID affected by -G") {
                $parentNameUsageID = $rec['parentNameUsageID'];
                $acceptedNameUsageID = $rec['acceptedNameUsageID'];
                if(isset($this->with_Gs[$parentNameUsageID]))   $rec['parentNameUsageID']   = $rec['parentNameUsageID']."-G";
                if(isset($this->with_Gs[$acceptedNameUsageID])) $rec['acceptedNameUsageID'] = $rec['acceptedNameUsageID']."-G";
                self::write_output_rec_2txt($rec, $this->descendants_File_B2);
                // continue; //with or without
            }
            //###############################################################################################
            if($task == "copy from File B to File A") { // print_r($rec); exit;
                /* Array( --- sample data from File B
                    [taxonID] => EOL-000000097774
                    [source] => https://opendata.eol.org/dataset/mip-eol-microbes-patch
                    [acceptedNameUsageID] => 
                    [parentNameUsageID] => EOL-000000095511
                    [scientificName] => Amylotrogus Roze, 1896
                    [canonicalName] => Amylotrogus
                    [authority] => Roze, 1896
                    [taxonRank] => genus
                    [taxonomicStatus] => accepted
                    [furtherInformationURL] => https://eol.org/pages/6795776/names
                    [higherClassification] => Amoebozoa
                    [notes] => amoebozoatest.tsv
                )*/
                $fields_A = $this->trimmed_File_A_headers; //fields to use are from File A
                /* print_r($fields_A); print_r(array_keys($rec)); exit("\ninvestigate 01\n"); */ //good debug
                $save = array();
                foreach($fields_A as $fld_A) {
                    /* working
                    if($val = @$rec[$fld_A]) $save[$fld_A] = $val;
                    else                     $save[$fld_A] = "";
                    */
                    $save[$fld_A] = @$rec[$fld_A]; //seems to be working as well
                }
                self::write_output_rec_2txt($save, $this->trimmed_File_A2); // from B copy to File A
            }
            //###############################################################################################
        } //end foreach()

        if($task == "generate parentID_taxonID") return $final;
        if($task == "generate trimmed File A") {
            $orig = self::txtfile_row_count($txtfile);
            $new  = self::txtfile_row_count($this->trimmed_File_A);
            $diff = $orig - $new;
            echo "\n         File A: ".$orig."";
            echo "\n Trimmed File A: ".$new."";
            echo "\n     Difference: ".$diff."";
            echo "\nStats (deleted): ".$this->debug_rules['deleted']."";
            $new = self::txtfile_row_count($this->descendants_File_A);
            echo "\n Removed descendants from File A: ".$new."";
            echo "\n------------------------------\n";
            
            if($this->arr_json['fileB_taxonID']) {
                if(copy($this->trimmed_File_A, $this->trimmed_File_A2)) echo "<br>Trimmed File A copied to File A2 OK";
                else echo "\n<br>ERRORx: cannot copy [$this->trimmed_File_A] to [$this->trimmed_File_A2]<br>Please inform eagbayani@eol.org.<br>\n";
            }
        }
        if($task == "save File B descendants and its synonyms") {
            echo "\nStats (created): ".$this->debug_rules['created B']."";
            $num = self::txtfile_row_count($this->descendants_File_B);
            echo "\n Descendants and its synonyms from File B: ".$num."";
            echo "\n New taxonIDs with '-G': ".count(@$this->with_Gs)."";
            echo "\n------------------------------\n";
        }
        if($task == "update parentID and acceptID affected by -G") {
            $num  = self::txtfile_row_count($this->descendants_File_B2);
            echo "\n Descendants and its synonyms from File B (updated): ".$num."";
            echo "\n------------------------------\n";
        }
        if($task == "copy from File B to File A") {
            $orig = self::txtfile_row_count($this->trimmed_File_A);
            $new  = self::txtfile_row_count($this->trimmed_File_A2);
            $diff = $orig - $new;
            echo "\n Trimmed File A: ".$orig."";
            echo "\n   Final File A: ".$new."";
            echo "\n     Difference: ".$diff."";
            $num  = self::txtfile_row_count($this->descendants_File_B2);
            echo "\n Descendants and its synonyms from File B (to be added to A): ".$num."";
            echo "\n------------------------------\n";
        }
    }
    private function step_7_check_taxonID_is_found_inFileA($rec)
    {
        // /* 7. Before copying taxa to file A, check if any of the taxonIDs of the descendants & synonyms to be copied are already used in File A 
        //    (after the descendants of xxx are removed), if so, add -G to the original ID to make it unique. 
        //    Also, make sure to update any parentNameUsageID or acceptedNameUsageID values, so they point to the updated taxonID.
        $taxonID = $rec['taxonID'];
        if(isset($this->File_A_taxonIDs[$taxonID])) {
            $rec['taxonID'] = $taxonID."-G";
            $this->with_Gs[$taxonID] = '';
        }
        // */
        return $rec;
    }
    private function write_output_rec_2txt($rec, $filename)
    {   // print_r($rec);
        $fields = array_keys($rec);
        $WRITE = Functions::file_open($filename, "a");
        clearstatcache(); //important for filesize()
        if(filesize($filename) == 0) fwrite($WRITE, implode("\t", $fields) . "\n");
        $save = array();
        foreach($fields as $fld) {
            if(is_array($rec[$fld])) { //if value is array()
                $rec[$fld] = self::clean_array($rec[$fld]);
                $rec[$fld] = implode(", ", $rec[$fld]); //convert to string
                $save[] = trim($rec[$fld]);
            }
            else $save[] = $rec[$fld];
        }
        $tab_separated = (string) implode("\t", $save); 
        fwrite($WRITE, $tab_separated . "\n");
        // echo "\nSaved to [$basename]: "; print_r($save); //echo "\n".implode("\t", $save)."\n"; //exit("\nditox 9\n"); //good debug
        fclose($WRITE);
    }
    private function txtfile_row_count($file, $has_headers_YN = true)
    {
        $total = shell_exec("wc -l < ".escapeshellarg($file));
        $total = trim($total);
        if($has_headers_YN) $total = $total - 1;
        return $total;
    }
    private function prepare_download_link($with_yyy, $download_links)
    {   // zip -r temp.zip Documents
        // echo "\n".$this->temp_dir."\n"; echo "\n".$this->resource_id."\n";

        // $this->temp_dir ---> CONTENT_RESOURCE_LOCAL_PATH . '/Branch_Graft/';
        // $ zip archive.zip file1 file2 file3
        $files = array();
        if($with_yyy) {
            $files[] = $this->trimmed_File_A2;
            $files[] = $this->descendants_File_A;
            $files[] = $this->descendants_File_B2;
            $files[] = $this->trimmed_File_A; //newly added per Katja Nov 8, 2023
        }
        else {
            $files[] = $this->trimmed_File_A;
            $files[] = $this->descendants_File_A;
        }
        if($download_links) $files = array_merge($files, $download_links);
        $source = implode(" ", $files);
        $destination = $this->temp_dir.$this->resource_id.".zip";
        if(is_file($destination)) unlink($destination);

        if($GLOBALS['ENV_DEBUG']) {
            echo "\n     source: [$source]\n";
            echo "\ndestination: [$destination]\n";    
        }
        $cmd = "zip -rj $destination $source";
        $out = shell_exec($cmd);
        echo "\n$out\n";
    }
    private function check_parentIDs_acceptIDs($local_path)
    {   require_library('connectors/DWCADiagnoseAPI');
        $func = new DWCADiagnoseAPI();
        echo "\n------------------------------ Diagnostics \n";
        /* check_if_all_parents_have_entries($resource_id, 
                                             $write_2text_file = false, 
                                             $url = false, 
                                             $suggested_fields = false, 
                                             $sought_field = false, 
                                             $filename = 'taxon.tab') */
        $resource_id = "/Branch_Graft/diagnostics_".$this->arr_json['uuid'];
        $write_2text_file = true;
        $url = $local_path;
        $suggested_fields = false; //array('taxonID', 'source', 'furtherInformationURL', 'acceptedNameUsageID', 'parentNameUsageID'); //has to be same perfect order if u want to use suggested_fields
        $sought_field = false; // false by default it means 'parentNameUsageID'
        $filename = pathinfo($local_path, PATHINFO_BASENAME); //filename + extension

        $undefined_parents = $func->check_if_all_parents_have_entries($resource_id, $write_2text_file, $url, $suggested_fields, $sought_field, $filename);
        echo "\nTotal undefined parents:" . count($undefined_parents)."\n";

        $sought_field = 'acceptedNameUsageID';
        $undefined_parents = $func->check_if_all_parents_have_entries($resource_id, $write_2text_file, $url, $suggested_fields, $sought_field, $filename);
        echo "\nTotal undefined acceptedNames:" . count($undefined_parents)."\n";

        /* copied template
        $without = $func->get_all_taxa_without_parent($resource_id, true); //true means output will write to text file
        echo "\nTotal taxa without parents:" . count($without)."\n"; unset($without);
        */
    }
    private function get_modulo($txtfile)
    {
        $total = self::total_rows_on_file($txtfile);
        if($total <= 1000) $modulo = 200;
        elseif($total > 1000 && $total <= 50000) $modulo = 5000;
        elseif($total > 50000 && $total <= 100000) $modulo = 5000;
        elseif($total > 100000 && $total <= 500000) $modulo = 50000;
        elseif($total > 500000 && $total <= 1000000) $modulo = 100000;
        elseif($total > 1000000 && $total <= 2000000) $modulo = 100000;
        elseif($total > 2000000) $modulo = 100000;
        return $modulo;
    }
    private function clean_array($arr)
    {
        $arr = array_filter($arr); //remove null arrays
        $arr = array_unique($arr); //make unique
        $arr = array_values($arr); //reindex key
        return $arr;
    }
    private function total_rows_on_file($file)
    {
        $total = shell_exec("wc -l < ".escapeshellarg($file));
        $total = trim($total);
        return $total;
    }
}
?>