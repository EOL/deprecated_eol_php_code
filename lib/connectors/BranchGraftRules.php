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
        /* 2nd:
        require_library('connectors/DwCA_Utility');
        $this->HC = new DwCA_Utility(); // HC - higherClassification functions
        */
        // /* 3rd:
        $this->temp_dir = CONTENT_RESOURCE_LOCAL_PATH . '/Branch_Graft/';
        if(!is_dir($this->temp_dir)) mkdir($this->temp_dir);

        /*
        print_r($this->input); exit;
        [path] => /opt/homebrew/var/www/eol_php_code//applications/branch_graft/temp/
        */

        $this->trimmed_File_A = $this->input['path'] . "trimmed_File_A_" . $this->arr_json['uuid'] . ".txt";
        $WRITE = Functions::file_open($this->trimmed_File_A, "w"); fclose($WRITE);

        $this->descendants_File_A = $this->input['path'] . "descendants_File_A_" . $this->arr_json['uuid'] . ".txt";
        $WRITE = Functions::file_open($this->descendants_File_A, "w"); fclose($WRITE);

        $this->descendants_File_B = $this->input['path'] . "descendants_File_B_" . $this->arr_json['uuid'] . ".txt";
        $WRITE = Functions::file_open($this->descendants_File_B, "w"); fclose($WRITE);

        $this->descendants_File_B2 = $this->input['path'] . "descendants_File_B2_" . $this->arr_json['uuid'] . ".txt";
        $WRITE = Functions::file_open($this->descendants_File_B2, "w"); fclose($WRITE);

        $this->trimmed_File_A2 = $this->input['path'] . "trimmed_File_A2_" . $this->arr_json['uuid'] . ".txt";
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

        // /* step 1: generate $parentID_taxonID from File A.
        $parentID_taxonID = self::parse_TSV_file($input_fileA, "generate parentID_taxonID");
        // */
        // /* step 2: read file A, get all descendants of fileA_taxonID
        $parent_ids = array($this->arr_json['fileA_taxonID']);
        require_library('connectors/PaleoDBAPI_v2');
        $func = new PaleoDBAPI_v2("");
        $descendants_A = $func->get_all_descendants_of_these_parents($parent_ids, $parentID_taxonID); // print_r($descendants_A);
        unset($parentID_taxonID); unset($func);
        $this->descendants_A = array_flip($descendants_A); //print_r($this->descendants_A); exit;
        echo "\nFile A total descendants: [".count($descendants_A)."]\n";
        echo "\nFile A total descendants: [".count($this->descendants_A)."]\n";
        unset($descendants_A);
        // */
        // /* step 3: now remove all descendants of fileA_taxonID, and their synonyms
        self::parse_TSV_file($input_fileA, "generate trimmed File A");
        unset($this->descendants_A);
        // */

        /* step 4: If there is no value for yyy, we are ready to create the output file, with the descendants & their synonyms removed 
        and the note in the notes column added for the basal taxon. */
        if($fileB_taxonID = $this->arr_json['fileB_taxonID']) {
            self::process_with_yyy($input_fileB);
            // $with_yyy = true; self::prepare_download_link($with_yyy);
        }
        else { // trimmed File A is now the final result
            $with_yyy = false; self::prepare_download_link($with_yyy);
        }

        exit("\n- exit muna-\n");
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
        echo "\nFile B total descendants: [".count($descendants_B)."]\n";
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

        exit("\n- end muna process yyy -\n");
    }
    private function parse_TSV_file($txtfile, $task)
    {   
        $modulo = self::get_modulo($txtfile);
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
                if($i == 2) $this->trimmed_File_A_headers = array_keys($rec);
                // */
                self::write_output_rec_2txt($rec, $this->trimmed_File_A); // start writing
                self::write_output_rec_2txt($rec, $this->trimmed_File_A2); // start writing
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
                $fields = $this->trimmed_File_A_headers; //fields to use are from File A
                $save = array();
                foreach($fields as $fld) $save[$fld] = @$rec[$fld];
                self::write_output_rec_2txt($save, $this->trimmed_File_A2);
            }
            //###############################################################################################
        } //end foreach()

        if($task == "generate parentID_taxonID") return $final;
        if($task == "generate trimmed File A") {
            $orig = self::txtfile_row_count($txtfile);
            $new  = self::txtfile_row_count($this->trimmed_File_A);
            $diff = $orig - $new;
            echo "\n         File A: ".$orig."\n";
            echo "\n Trimmed File A: ".$new."\n";
            echo "\n     Difference: ".$diff."\n";
            echo "\nStats (deleted): ".$this->debug_rules['deleted']."\n";
            $new = self::txtfile_row_count($this->descendants_File_A);
            echo "\n Removed descendants from File A: ".$new."\n";
        }
        if($task == "save File B descendants and its synonyms") {
            echo "\nStats (created): ".$this->debug_rules['created B']."\n";
            $num = self::txtfile_row_count($this->descendants_File_B);
            echo "\n Descendants and its synonyms from File B: ".$num."\n";
            echo "\n New taxonIDs with '-G': ".count(@$this->with_Gs)."\n";
        }
        if($task == "update parentID and acceptID affected by -G") {
            $num  = self::txtfile_row_count($this->descendants_File_B2);
            echo "\n Descendants and its synonyms from File B (updated): ".$num."\n";
        }
        if($task == "copy from File B to File A") {
            $orig = self::txtfile_row_count($this->trimmed_File_A);
            $new  = self::txtfile_row_count($this->trimmed_File_A2);
            $diff = $orig - $new;
            echo "\n Trimmed File A: ".$orig."\n";
            echo "\n   Final File A: ".$new."\n";
            echo "\n     Difference: ".$diff."\n";

            $num  = self::txtfile_row_count($this->descendants_File_B2);
            echo "\n Descendants and its synonyms from File B (to be added to A): ".$num."\n";


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
    private function prepare_download_link($with_yyy)
    {   // zip -r temp.zip Documents
        // echo "\n".$this->temp_dir."\n"; echo "\n".$this->resource_id."\n";

        // $this->temp_dir ---> CONTENT_RESOURCE_LOCAL_PATH . '/Branch_Graft/';
        // $ zip archive.zip file1 file2 file3
        $files = array();
        if($with_yyy) {
        }
        else $files[] = $this->trimmed_File_A;
        $source = implode(" ", $files);
        $destination = $this->temp_dir.$this->resource_id.".zip";

        if($GLOBALS['ENV_DEBUG']) {
            echo "\n     source: [$source]\n";
            echo "\ndestination: [$destination]\n";    
        }
        $cmd = "zip -rj $destination $source";
        $out = shell_exec($cmd);
        echo "\n$out\n";
        return;
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
    ###################################################### all below is copied tempate
    function main() //copied template
    {
        $this->summary_report['info']['user file'] = $txtfile;
        // echo "\n[".$txtfile."] [$this->resource_id]\n"; exit;
        self::initialize();
        if($tsvFileYN) {
            self::parse_user_file($txtfile);
            self::parse_TSV_file($this->DH_file, 'load DH file');
            self::parse_TSV_file($this->temp_dir."processed.txt", 'name match and validate');
            self::summary_report();
            self::prepare_download_link();
            // echo "\n".$this->temp_dir."\n"; echo "\n".$this->resource_id."\n";
            recursive_rmdir($this->temp_dir);
        }
        // print_r($this->user_canonicalNames); //good debug though
        // exit("\n-stop muna-\n");
        $this->DH_info = '';
        $this->IncompatibleAncestors_1 = '';
        $this->IncompatibleAncestors_2 = '';
        $this->RoR = '';
        $this->HC = '';
        $this->taxon_fields = '';
        return;
    }
    private function name_match_validate($rec)
    {   /*Array(
            [taxonID] => ABPR3_Abrus_precatorius
            [scientificName] => Abrus precatorius
            [canonicalName] => Abrus precatorius
            [scientificNameAuthorship] => 
            [taxonRank] => species
            OR
            [taxonRank] => species (inferred)
            [taxonomicStatus] => accepted
            [higherClassification] => Plantae|Magnoliopsida|Fabales|Fabaceae|Abrus
        )
        For each matched taxon, record the following fields:
            - taxonID from user file, if available
            - eolID of matched DH taxon
            - canonicalName from user file or gnparser
            - scientificName from user file
            - scientificName of matched DH taxon
            - scientificNameAuthorship from user file or gnparser, if available
            - scientificNameAuthorship of matched DH taxon
            - taxonRank from user file or inferred, if available
            - taxonRank of matched DH taxon
            - taxonomicStatus from user file or inferred
            - taxonomicStatus of matched DH taxon
            - higherClassification from user file, if available
            - higherClassification of matched DH taxon
            - quality notes, see below */
        if($rec['taxonRank']) $rec = self::Cardinality_Test($rec);

        $u_canonicalName = $rec['canonicalName'];
        // /* ----- Duplicate canonical - add if there is more than 1 name with the same canonical in the user file
        if($u_canonicalName) {
            if($val_arr = $this->user_canonicalNames[$u_canonicalName]) { // print_r($val_arr);
                if(count($val_arr) > 1) $rec['addtl']['quality notes'][] = 'Duplicate canonical';
            }    
        }
        // */

        if($DH_recs = @$this->DH_info[$u_canonicalName]) { //matchedNames
            // /* ----- Multiple DH matches - add if there is more than 1 exact canonical match in the DH
            if(count($DH_recs) > 1) $rec['addtl']['quality notes'][] = 'Multiple DH matches';
            // */
            foreach($DH_recs as $DH_rec) {
                $matched = array();
                $matched['taxonID'] = $rec['taxonID'];

                /* syn part 1: per Katja: Also, I would like to revise the reporting of names matched to a DH synonym. 
                In those cases, please put the eolID of the synonyms's acceptedNameUsageID taxon in the DH_eolID column 
                and add a "Synonym match" warning to the quality notes. */
                $Synonym_match_YN = false;
                if($DH_acceptedNameUsageID = $DH_rec['acceptedNameUsageID']) { //means a synonym with taxonomicStatus = 'not accepted'
                    if($sought_eolID = $this->DH_taxonID_eolID[$DH_acceptedNameUsageID]) {
                        $matched['DH_eolID'] = $sought_eolID;
                        $Synonym_match_YN = true;
                    }
                    else exit("\nShould not go here. [$DH_acceptedNameUsageID]\n");
                }
                else $matched['DH_eolID'] = $DH_rec['eolID']; //regular normal case             
               
                $matched['canonicalName'] = $rec['canonicalName'];
                $matched['scientificName'] = $rec['scientificName'];
                $matched['DH_scientificName'] = $DH_rec['scientificName'];
                $matched['scientificNameAuthorship'] = $rec['scientificNameAuthorship'];
                $matched['DH_scientificNameAuthorship'] = @$DH_rec['scientificNameAuthorship'];
                $matched['taxonRank'] = $rec['taxonRank'];
                $matched['DH_taxonRank'] = $DH_rec['taxonRank'];
                $matched['taxonomicStatus'] = $rec['taxonomicStatus'];
                $matched['DH_taxonomicStatus'] = $DH_rec['taxonomicStatus'];
                $matched['higherClassification'] = $rec['higherClassification'];
                $matched['DH_higherClassification'] = $DH_rec['higherClassification'];

                $rec_new = self::excluded_based_on_3($rec, $DH_rec);
                $matched['quality notes'] = @$rec_new['addtl']['quality notes'];

                // /* syn part 2:
                if($Synonym_match_YN) $matched['quality notes'][] = 'Synonym match';
                // */

                if(@$rec_new['addtl']['incompatible_pairs_arr'] || 
                   $rec_new['addtl']['Family_mismatch_YN'] === true ||
                   $rec_new['addtl']['Fatal_rank_mismatch_YN'] === true ) { //unmatchedNames based on 3
                    $unmatched = $matched; $matched = array();
                    self::write_output_rec_2txt($unmatched, "unmatchedNames");
                    @$this->summary_report['totals'][unmatchedNames]++;
                }
                else {
                    self::write_output_rec_2txt($matched, "matchedNames");
                    @$this->summary_report['totals'][matchedNames]++;
                }
            } //end foreach()
        }
        else { //unmatchedNames - blank for DH fields
            $rec['addtl']['quality notes'][] = 'No match';
            $unmatched = array();
            $unmatched['taxonID'] = $rec['taxonID'];
            $unmatched['DH_eolID'] = '';
            $unmatched['canonicalName'] = $rec['canonicalName'];
            $unmatched['scientificName'] = $rec['scientificName'];
            $unmatched['DH_scientificName'] = '';
            $unmatched['scientificNameAuthorship'] = $rec['scientificNameAuthorship'];
            $unmatched['DH_scientificNameAuthorship'] = '';
            $unmatched['taxonRank'] = $rec['taxonRank'];
            $unmatched['DH_taxonRank'] = '';
            $unmatched['taxonomicStatus'] = $rec['taxonomicStatus'];
            $unmatched['DH_taxonomicStatus'] = '';
            $unmatched['higherClassification'] = $rec['higherClassification'];
            $unmatched['DH_higherClassification'] = '';
            $unmatched['quality notes'] = $rec['addtl']['quality notes'];
            self::write_output_rec_2txt($unmatched, "unmatchedNames");
            @$this->summary_report['totals'][unmatchedNames]++;
        }
    }
    private function excluded_based_on_3($rec, $DH_rec)
    {
        $rec['addtl']['Family_mismatch_YN']         = '-default-';
        $rec['addtl']['Fatal_rank_mismatch_YN']     = '-default-';
        $rec['addtl']['Non_fatal_rank_mismatch_YN'] = '-default-';

        if($u_higherClassification = $rec['higherClassification']) { //then check for: Ancestry Conflicts
            $rec = self::has_Incompatible_ancestors($rec, $DH_rec); //Incompatible ancestors
            $rec = self::has_Family_mismatch($rec, $DH_rec); //Family mismatch
            // if($rec['addtl']['Family_mismatch_YN']) return true;
        }
        if($rec['taxonRank'] && $DH_rec['taxonRank']) { //then check for: Rank Conflicts
            $rec = self::Fatal_rank_mismatch($rec, $DH_rec);
            // if($rec['addtl']['Fatal_rank_mismatch_YN']) return true;
        }
        else {
            $rec = self::Non_fatal_rank_mismatch($rec, $DH_rec);
            // if($rec['addtl']['Non_fatal_rank_mismatch_YN']) return true;
        }

        // print_r($rec); exit("\nditox 7\n");
        return $rec;
    }
    private function Cardinality_Test($rec)
    {   /* Cardinality Test
        If the user file provides taxonRank data, we will check if the names for certain ranks have the expected name structure using a simple cardinality test.
        We can get the cardinality data for each name from gnparser.
        Expected cardinalities:
            family, genus: 1
            species: 2
            infraspecies, subspecies, variety, subvariety, form, forma: ≥3 */
        
        // /* for calling gnparser
        // $rec['scientificName'] = "Gadus morhua xxx jack and the "; //debug only
        $input = array('sciname' => $rec['scientificName']);
        $json = $this->RoR->retrieve_data($input); //call gnparser
        $obj = json_decode($json); //print_r($obj); //exit("\nditox 8\n"); //echo("\n[".$json."]\n");
        // */
        // /* ----- Unparsed - add if gnparser  "parsed": false
        if(!@$obj->parsed) $rec['addtl']['quality notes'][] = "Unparsed";
        // */
        // /* ----- If gnparser provides any qualityWarnings, add the value of each warning, separated by commas. 
        // If the warning is "Unparsed tail", also add the value of “tail” in parentheses.
        if($warnings = @$obj->qualityWarnings) { $csv_str = "";
            foreach($warnings as $w) {
                if($w->warning == "Unparsed tail") $csv_str .= "$w->warning ($obj->tail), ";
                else                               $csv_str .= "$w->warning, ";
            }
            $csv_str = trim($csv_str);
            $csv_str = substr($csv_str,0,-1); //remove ending strings
            $rec['addtl']['quality notes'][] = $csv_str;
            // print_r($rec); exit("\nditox 9\n");
        }
        // */
        /* ----- Check name structure - If any of the matched or unmatched names from the user file have an unexpected cardinality, 
           add “Check name structure” and then in parentheses one of the following remarks depending on the rank of the taxon:
            (family|genus) names should be a uninomials
            species names should be binomials
            (infraspecies|subspecies|variety|subvariety|form|forma) names should have at least 3 parts
        */
        $rank = $rec['taxonRank'];
        // /* Rank inferred - add if the rank is inferred
        if(stripos($rank, " (inferred)") !== false) { //found string
            $rec['addtl']['quality notes'][] = "Rank inferred";
            $rank = str_replace(" (inferred)", "", $rank);
            $rec['taxonRank'] = $rank; //remove " (inferred)" substring
        }
        // */
        if(in_array($rank, array('family', 'genus'))) {
            if($obj->cardinality != 1) { //fail
                $rec['addtl']['quality notes'][] = "Check name structure ($rank names should be a uninomials)"; 
            }
        }
        elseif($rank == 'species') {
            if($obj->cardinality != 2) { //fail
                $rec['addtl']['quality notes'][] = "Check name structure (species names should be binomials)";
            }
        }
        elseif(in_array($rank, array('infraspecies', 'subspecies', 'variety', 'subvariety', 'form', 'forma'))) {
            if($obj->cardinality < 3) { //fail
                $rec['addtl']['quality notes'][] = "Check name structure ($rank names should have at least 3 parts)";
            }
        }
        return $rec;
    }
    private function parse_user_file($txtfile)
    {   $i = 0; debug("\n[$txtfile]\n");
        $modulo = self::get_modulo($txtfile);
        echo "\nReading user file... ";
        foreach(new FileIterator($txtfile) as $line_number => $line) {
            if(!$line) continue;
            $i++; if(($i % $modulo) == 0) echo "\n".number_format($i)." ";
            $row = explode("\t", $line); // print_r($row);
            if($i == 1) {
                $fields = $row;
                $fields = array_filter($fields); //print_r($fields);
                $this->summary_report['List of fields'] = $fields;
                continue;
            }
            else {
                $k = 0; $rec = array();
                foreach($fields as $fld) { $rec[$fld] = @$row[$k]; $k++; }
            }
            $rec = array_map('trim', $rec);
            if(!@$rec['scientificName']) continue;
            @$this->summary_report['Number of taxa']++;
            // echo "\nRAW REC:"; print_r($rec); //exit("\nstopx\n"); //good debug
            /*Array(
                [taxonID] => Archaea
                [scientificName] => Archaea
                [EOLid] => 7920
            )*/

            // /* ---------- for higherClassification
            if($i == 2) { //will pass here only once
                if($this->can_compute_higherClassificationYN = self::can_compute_higherClassification($rec)) {
                    if($records = $this->HC->create_records_array($txtfile)) {
                        $this->HC->build_id_name_array($records); //print_r($this->HC->id_name); exit;
                    }
                    else exit("\nNo records\n");
                }
                // else exit("\nCannot compute HC\n"); //no need to trap, acceptable case
            }
            // ---------- */

            // /* for calling gnparser
            $input = array('sciname' => $rec['scientificName']);
            $json = $this->RoR->retrieve_data($input); //call gnparser
            $obj = json_decode($json); //print_r($obj); echo("\n[".$json."]\n");
            // */
            $raw = array();
            $raw['taxonID']                     = self::build_taxonID($rec);
            $raw['scientificName']              = self::build_scientificName($rec);
            $raw['canonicalName']               = self::build_canonicalName($rec, $obj);
            // /*
            if($val = $raw['canonicalName']) {
                // @$this->user_canonicalNames[$val]++; //good for computing totals only
                @$this->user_canonicalNames[$val][] = array("taxonID" => $raw['taxonID'], "scientificName" => $raw['scientificName']);
            }
            // */
            $raw['scientificNameAuthorship']    = self::build_scientificNameAuthorship($rec, $obj);
            $raw['taxonRank']                   = self::build_taxonRank($rec, $obj, $raw['canonicalName']);
            @$this->summary_report['Taxon ranks'][$raw['taxonRank']]++;
            $raw['taxonomicStatus']             = self::build_taxonomicStatus($rec);
            @$this->summary_report['Taxonomic status'][$raw['taxonomicStatus']]++;

            $raw['higherClassification']        = self::build_higherClassification($rec);
            if($val = $raw['higherClassification']) {
                $root = self::get_root_from_HC($val);
                $this->summary_report['Number of roots'][$root] = '';
            }
            // echo "\nPROCESSED REC:"; print_r($raw); //good debug
            self::write_output_rec_2txt($raw, "processed");
            // break; //debug only
            // if($i >= 5) break; //debug only
        } //end foreach()
    }
    private function build_higherClassification($rec)
    {   /* If there is a higherClassification field, use the value from this field. 
        If not construct a pipe-separated list of ancestors based on information in the parentNameUsageID fields or 
        the taxonomy fields (kingdom|phylum|class|order|family|subfamily|genus|subgenus). Some files will not have any higher classification information at all. */
        if($val = @$rec['higherClassification']) return $val;
        else {
            if(isset($rec['parentNameUsageID'])) return self::get_higherClassification($rec); //1st option
            // /* 2nd option
            $ranks = array('kingdom', 'phylum', 'class', 'order', 'family', 'subfamily', 'genus', 'subgenus');
            foreach($ranks as $rank) {
                if(isset($rec[$rank])) {
                    return self::generate_higherClass_using_ancestry_fields($rec); //2nd option
                    break;
                }
            }
            // */
        }
    }
    private function build_taxonomicStatus($rec)
    {   /* If there is a taxonomicStatus field, use the value from this field. If not, we infer that all taxa have taxonomicStatus = accepted. */
        if($val = @$rec['taxonomicStatus']) return $val;
        else return 'accepted';
    }
    private function build_taxonRank($rec, $obj, $canonicalName)
    {   /* If there is a taxonRank field, use the value from this field. If not, we can to infer the rank for some taxa as follows:
        - It the canonical name is a binomial (gnparser: cardinality=2), infer taxonRank = species
        - If the canonical name is a trinomial or multinomial (gnparser: cardinality≥3), infer taxonRank = infraspecies
        - If the canonical name is a uninomial (gnparser cardinality=1) and ends in “idae” or “aceae”, infer taxonRank= family 
        Whenever a rank is inferred, we’ll want to add a flag to the quality notes in the report (see below). */
        if($val = @$rec['taxonRank']) return $val;
        else {
            if($obj->cardinality == 2) return 'species (inferred)';
            if($obj->cardinality >= 3) return 'infraspecies (inferred)';
            if($obj->cardinality == 1) {
                if(substr($canonicalName, -4) == "idae")  return 'family (inferred)';
                if(substr($canonicalName, -5) == "aceae") return 'family (inferred)';
            }
        }
    }
    private function build_scientificNameAuthorship($rec, $obj)
    {   /* If there is a scientificNameAuthorship field, use the value from that field. 
        If not, get the authority data from gnparser ( ​​"authorship">"normalized"). Not all files will have authorship data in the scientificName column, 
        so scientificNameAuthorship may be empty. */
        if    ($val = @$rec['scientificNameAuthorship']) return $val;
        elseif($val = @$obj->authorship->normalized)     return $val;
    }
    private function build_canonicalName($rec, $obj)
    {   /* If a canonicalName field is available use the value from that field. If not, use the gnparser canonical. 
        For names that don’t get parsed ("parsed": false), use the full scientificName string as the canonicalName value and 
        add an unparsed flag to the quality warnings in the report (see below). 
        For names that do get parsed, use the CanonicalSimple value as the canonicalName value for all taxa except the following:
        - Use the gnparser CanonicalFull value for hybrids, i.e., if the gnparser report has a "hybrid": "NAMED_HYBRID" statement.
        - Use the gnparser CanonicalFull value for subgenera, i.e., if the gnparser CanonicalFull value has the string “ subgen. ” in it. */
        if($val = @$rec['canonicalName']) return $val;
        else {
            // exit("\nparsed: [".$obj->parsed."]\n");
            if(@$obj->parsed == 1) { // names that get parsed
                $CanonicalFull = $obj->canonical->full;
                if(@$obj->hybrid == "NAMED_HYBRID")                 return $CanonicalFull;
                if(stripos($CanonicalFull, " subgen. ") !== false)  return $CanonicalFull; //found string
                return $obj->canonical->simple;
            }
            else {
                // echo "\ngot entire sciname: for "; print_r($rec);
                return $rec['scientificName'];
            }
        }
    }
    private function build_scientificName($rec)
    {   /* If a scientificName field is available use the value from that field. Every DwC-A or taxa file should have this field. 
           If the user file is a taxa list, we will interpret the names as scientificName values. */
        if($val = @$rec['scientificName']) return $val;
    }
    private function build_taxonID($rec)
    {
        if($val = @$rec['taxonID']) return $val;
    }
    private function get_higherClassification($rec)
    {
        $parent_id = $rec['parentNameUsageID'];
        $str = "";
        while($parent_id) {
            if($parent_id) {
                $str .= trim(@$this->HC->id_name[$parent_id]['scientificName'])."|";
                $parent_id = @$this->HC->id_name[$parent_id]['parentNameUsageID'];
            }
        }
        $str = substr($str, 0, strlen($str)-1); // echo "\norig: [$str]";
        $arr = explode("|", $str);
        $arr = array_reverse($arr);
        $str = implode("|", $arr); // echo "\n new: [$str]\n";
        return $str;
    }
    private function generate_higherClass_using_ancestry_fields($rec)
    {
        $ranks = array('kingdom', 'phylum', 'class', 'order', 'family', 'subfamily', 'genus', 'subgenus');
        $str = "";
        foreach($ranks as $rank) {
            if($val = @$rec[$rank]) $str .= $val."|";
        }
        $str = substr(trim($str), 0, -1); // remove last char from string
        return $str;
    }
    private function can_compute_higherClassification($rec)
    {
        if(!isset($rec["taxonID"])) return false;
        if(!isset($rec["scientificName"])) return false;
        if(!isset($rec["parentNameUsageID"])) return false;
        return true;
    }
    private function get_IncompatibleAncestors()
    {   // https://docs.google.com/spreadsheets/d/1kAqXnqGMBa3bED3vl1KIL2rPO_ZpmBxqXQYOVqY8-0g/edit?pli=1#gid=0
        require_library('connectors/GoogleClientAPI');
        $func = new GoogleClientAPI(); //get_declared_classes(); will give you how to access all available classes
        $params['spreadsheetID'] = '1kAqXnqGMBa3bED3vl1KIL2rPO_ZpmBxqXQYOVqY8-0g';
        $params['range']         = 'Sheet1!A2:B930'; //where "A" is the starting column, "C" is the ending column, and "1" is the starting row.
        $arr = $func->access_google_sheet($params);
        //start massage array
        foreach($arr as $item) $final[$item[0]][] = $item[1];
        $this->IncompatibleAncestors_1 = $final;

        $final = array();
        foreach($arr as $item) $final[$item[1]][] = $item[0];
        $this->IncompatibleAncestors_2 = $final; // print_r($this->IncompatibleAncestors_2); exit("\nditox 4\n");
    }
    private function has_Incompatible_ancestors($rec, $DH_rec)
    {   /*Array( this->IncompatibleAncestors_1  this->IncompatibleAncestors_2
            [Acoela] => Array(
                    [0] => Angiospermae
                    [1] => Angiosperms
                    ...
                )
        1. Incompatible ancestors
        a. We will use the IncompatibleAncestors file for this. This file has pairs of incompatible ancestors. 
            If any of the listed ancestors (in either column) occur in the higherClassification of a taxon, 
            that taxon cannot be matched with a taxon that has one of the paired ancestors in its higherClassification. 
            For example, if a taxon has Acoela as one of its ancestors, it cannot be matched with any taxa that have Angiospermae or Angiosperms or Archaeplastida 
                or Bryophyta or Chlorophyta or Liliopsida or Magnoliophyta or Magnoliopsida or Plantae or Rhodophyta or Viridiplantae in their higherClassification.
        b. Make sure to only match full taxon names in higherClassification strings, i.e., “Fungi” should only match Fungi not Fungiidae
        c. If there are incompatible ancestors, add the data for the taxon match to the unmatchedNames file and 
            add information about the ancestor incompatibility to the quality notes (see below). */
        $u_hC = $rec['higherClassification'];       $u_ancestors = explode("|", $u_hC); // print_r($u_ancestors); exit;
        $DH_hC = $DH_rec['higherClassification'];   $DH_ancestors = explode("|", $DH_hC);
        /* Array(
            [0] => Plantae
            [1] => Magnoliopsida
            [2] => Fabales
            [3] => Fabaceae
            [4] => Abrus
        ) */
        $incompatible_pairs = array();
        foreach($u_ancestors as $u_ancestor) {
            if($incompatibles = @$this->IncompatibleAncestors_1[$u_ancestor]) {
                foreach($incompatibles as $incompatible) {
                    if(in_array($incompatible, $DH_ancestors)) $incompatible_pairs[] = array($u_ancestor, $incompatible); //A
                }
            }
            //===================
            if($incompatibles = @$this->IncompatibleAncestors_2[$u_ancestor]) {
                foreach($incompatibles as $incompatible) {
                    if(in_array($incompatible, $DH_ancestors)) $incompatible_pairs[] = array($u_ancestor, $incompatible); //B
                }
            }
        } //end foreach()

        /* ----- Incompatible ancestors - If there are any incompatible ancestors, add “Incompatible ancestors” and list the incompatible pairs in parentheses. */
        if($incompatible_pairs) {
            // $incompatible_pairs = array_map('trim', $incompatible_pairs);                    //cannot do this for array() type
            $incompatible_pairs = array_filter($incompatible_pairs); //remove null arrays
            // $incompatible_pairs = array_unique($incompatible_pairs); //make unique           //cannot do this for array() type
            $incompatible_pairs = array_values($incompatible_pairs); //reindex key    
        }
        $rec['addtl']['incompatible_pairs_arr'] = $incompatible_pairs;
        if($incompatible_pairs) {
            if($GLOBALS['ENV_DEBUG']) {
                // echo "\nIncompatible pairs: "; print_r($incompatible_pairs); //exit("\n\n"); //good debug
            }
            $csv_str = "";
            foreach($incompatible_pairs as $pair) $csv_str .= "[".implode(",", $pair)."] ";
            $rec['addtl']['quality notes'][] = "Incompatible ancestors (".trim($csv_str).")";
        }
        return $rec;
    }
    private function has_Family_mismatch($rec, $DH_rec)
    {   /* Family mismatch
        - For each taxon match, we also want to check if the family placement of matched taxa is congruent. 
            We can do this only if both taxa have family information. 
            We can identify family names in ancestor strings by looking for names ending in “idae” or “aceae”.
        - If both taxa in a match have family names in their ancestor strings and those family names are different, 
            the data for that taxon match can still go in the matchedNames file if both families have the same ending, 
            i.e., if both end in “idae” or “aceae”. However, we will want to add information about the family mismatch to the quality notes (see below).
        - If both taxa in a match have family names in their ancestor strings and those family names are different and 
            have different endings, add the data for that taxon match to the unmatchedNames file and 
            add information about the family mismatch to the quality notes (see below).
        */
        // print_r($rec); print_r($DH_rec); //exit("\nditox 5\n");
        $u_family  = self::get_family_from_record($rec);
        $DH_family = self::get_family_from_record($DH_rec);
        // echo "\n[$u_family] [$DH_family]\n"; exit("\nditox 6\n");
        $rec['addtl']['Family_mismatch_YN'] = false;
        if($u_family && $DH_family) {
            if($u_family != $DH_family) {
                if(substr($u_family, -4) == "idae" && substr($DH_family, -4) == "idae") { // matchedNames
                    $rec['addtl']['Family_mismatch_YN'] = false; //set to false so it goes to matchedNames
                    $rec['addtl']['quality notes'][] = "Family mismatch, same ending ($u_family, $DH_family)";
                }
                elseif(substr($u_family, -5) == "aceae" && substr($DH_family, -5) == "aceae") { // matchedNames
                    $rec['addtl']['Family_mismatch_YN'] = false; //set to false so it goes to matchedNames
                    $rec['addtl']['quality notes'][] = "Family mismatch, same ending ($u_family, $DH_family)";
                }
                else { // unmatchedNames
                    $rec['addtl']['Family_mismatch_YN'] = true;
                    $rec['addtl']['quality notes'][] = "Family mismatch, diff. ending ($u_family, $DH_family)";
                }
            }
        }
        return $rec;
        /* ----- Family mismatch -  If both taxa have families in their higherClassification and there is a family mismatch, 
        add “Family mismatch” and list the families in parentheses. */
    }
    private function get_family_from_record($rek)
    {
        if($val = @$rek['family']) return $val;
        if($pipe_separated = @$rek['higherClassification']) {
            $parts = explode("|", $pipe_separated);
            foreach($parts as $part) { // “idae” or “aceae”
                if(substr($part, -4) == "idae" || substr($part, -5) == "aceae") return $part;
            }
        }
    }
    /*
    Rank Conflicts
    If the user file provides taxonRank data and the matched DH taxon also has a taxonRank value, we will check to see if there is a rank conflict. 
    For some rank conflicts, we will just add a warning to the quality notes (see below), while others will prevent the matching of taxa.
    */
    private function Fatal_rank_mismatch($rec, $DH_rec)
    {   /* Fatal rank mismatch. Add the data for a taxon match to the unmatchedNames file, if both taxa have a taxonRank value and:
            One taxonRank value is family and the other is any other rank
            One taxonRank value is genus and the other is any other rank
            One taxonRank value is species and the other is any other rank */
        $u_rank  = strtolower($rec['taxonRank']);
        $DH_rank = strtolower($DH_rec['taxonRank']);
        $rec['addtl']['Fatal_rank_mismatch_YN'] = false;
        if($u_rank == 'family' && $DH_rank != 'family') { // unmatchedNames
            $rec['addtl']['Fatal_rank_mismatch_YN'] = true;
            $rec['addtl']['quality notes'][] = "Different ranks ($u_rank, $DH_rank)"; return $rec;
        }
        if($u_rank == 'genus' && $DH_rank != 'genus') { // unmatchedNames
            $rec['addtl']['Fatal_rank_mismatch_YN'] = true;
            $rec['addtl']['quality notes'][] = "Different ranks ($u_rank, $DH_rank)"; return $rec;
        }
        if($u_rank == 'species' && $DH_rank != 'species') { // unmatchedNames
            $rec['addtl']['Fatal_rank_mismatch_YN'] = true;
            $rec['addtl']['quality notes'][] = "Different ranks ($u_rank, $DH_rank)"; return $rec;
        }
        //-----------------------------------------
        if($DH_rank == 'family' && $u_rank != 'family') { // unmatchedNames
            $rec['addtl']['Fatal_rank_mismatch_YN'] = true;
            $rec['addtl']['quality notes'][] = "Different ranks ($u_rank, $DH_rank)"; return $rec;
        }
        if($DH_rank == 'genus' && $u_rank != 'genus') { // unmatchedNames
            $rec['addtl']['Fatal_rank_mismatch_YN'] = true;
            $rec['addtl']['quality notes'][] = "Different ranks ($u_rank, $DH_rank)"; return $rec;
        }
        if($DH_rank == 'species' && $u_rank != 'species') { // unmatchedNames
            $rec['addtl']['Fatal_rank_mismatch_YN'] = true;
            $rec['addtl']['quality notes'][] = "Different ranks ($u_rank, $DH_rank)"; return $rec;
        }
        //-----------------------------------------
        if($u_rank != $DH_rank) {
            if(!in_array($u_rank, array('family', 'genus', 'species')) && !in_array($DH_rank, array('family', 'genus', 'species'))) {
                $rec['addtl']['Non_fatal_rank_mismatch_YN'] = true;
                $rec['addtl']['quality notes'][] = "Different ranks, non-fatal ($u_rank, $DH_rank)";
            }            
        }        
        return $rec;
    }
    private function Non_fatal_rank_mismatch($rec, $DH_rec)
    {   /* Non-fatal rank mismatch. Add the data for a taxon match to the matchedNames file, if: 
            - One or both taxa lack a taxonRank value 
            - The taxonRank values don’t match but neither is family|genus|species */
        $u_rank  = strtolower($rec['taxonRank']);
        $DH_rank = strtolower($DH_rec['taxonRank']);
        if(!$u_rank && !$DH_rank) {
            $rec['addtl']['Non_fatal_rank_mismatch_YN'] = true; //matchedNames
        }
        if($u_rank && !$DH_rank) {
            $rec['addtl']['Non_fatal_rank_mismatch_YN'] = true; //matchedNames
            $rec['addtl']['quality notes'][] = "Different ranks ($u_rank, $DH_rank)";
        }
        if(!$u_rank && $DH_rank) {
            $rec['addtl']['Non_fatal_rank_mismatch_YN'] = true; //matchedNames
            $rec['addtl']['quality notes'][] = "Different ranks ($u_rank, $DH_rank)";
        }
        //-----------------------------------------
        if($u_rank != $DH_rank) {
            if(!in_array($u_rank, array('family', 'genus', 'species')) && !in_array($DH_rank, array('family', 'genus', 'species'))) {
                $rec['addtl']['Non_fatal_rank_mismatch_YN'] = true;
                $rec['addtl']['quality notes'][] = "Different ranks ($u_rank, $DH_rank)";
            }            
        }        
        return $rec;
    }
    private function clean_array($arr)
    {
        $arr = array_filter($arr); //remove null arrays
        $arr = array_unique($arr); //make unique
        $arr = array_values($arr); //reindex key
        return $arr;
    }
    ///============================================== START Summary Report
    private function write_summary_report()
    {
        $r = $this->summary_report;
        $filename = $this->temp_dir."summary_report.txt";
        $WRITE = Functions::file_open($filename, "w");
        fwrite($WRITE, "Number of taxa: ".$r['Number of taxa'] . "\n");
        fwrite($WRITE, "--------------------------------------------------"."\n");
        $spaces = " _____ ";
        fwrite($WRITE, "List of fields and their DwC-A mappings: "."\n");
        foreach($r['List of fields'] as $field) {
            $field2 = str_pad($field, 30, " ", STR_PAD_LEFT);
            if($val = @$this->taxon_fields[$field]) fwrite($WRITE, "$spaces $field2"." -> ".$val."\n");
            else                                   fwrite($WRITE, "$spaces $field2"." -> "."unmapped"."\n");
        }
        fwrite($WRITE, "--------------------------------------------------"."\n");
        fwrite($WRITE, "Number of roots: ".count(@$r['Number of roots'])."\n");
        if($roots = @$r['Number of roots']) {
            $i = 0;
            foreach(array_keys($roots) as $root) { $i++;
                fwrite($WRITE, "$spaces $i. $root"."\n");
            }    
        }
        fwrite($WRITE, "--------------------------------------------------"."\n");
        fwrite($WRITE, "Taxon ranks: "."\n");
        if($ranks = $r['Taxon ranks']) { $grand_total = 0;
            $ranks = self::sort_key_val_array($ranks);
            foreach($ranks as $rank => $total) { $grand_total += $total;
                if(!$rank) $rank = "{blank}";
                $rank = str_pad($rank, 30, " ", STR_PAD_LEFT);
                fwrite($WRITE, "$spaces $rank -> $total"."\n");
            }
            fwrite($WRITE, "$spaces Total -> $grand_total"."\n");
        }
        fwrite($WRITE, "--------------------------------------------------"."\n");
        fwrite($WRITE, "Taxonomic status: "."\n");
        if($ranks = $r['Taxonomic status']) { $grand_total = 0;
            $ranks = self::sort_key_val_array($ranks);
            foreach($ranks as $rank => $total) { $grand_total += $total;
                if(!$rank) $rank = "{blank}";
                $rank = str_pad($rank, 30, " ", STR_PAD_LEFT);
                fwrite($WRITE, "$spaces $rank -> $total"."\n");
            }
            fwrite($WRITE, "$spaces Total -> $grand_total"."\n");
        }
        fwrite($WRITE, "--------------------------------------------------"."\n");
        $canonical_duplicates = $r['No. of canonical duplicates'];
        fwrite($WRITE, "Number of canonical duplicates: ".count($canonical_duplicates)."\n");
        foreach($canonical_duplicates as $sciname => $recs) {
            fwrite($WRITE, "$spaces $sciname "."\n");
            foreach($recs as $rec) {
                foreach($rec as $key => $value) fwrite($WRITE, "$spaces $spaces $key: $value "."\n");
            }
        }
        fwrite($WRITE, "--------------------------------------------------"."\n");
        fwrite($WRITE, "Number of matched names: ".$r['totals']['matchedNames']."\n");
        fwrite($WRITE, "--------------------------------------------------"."\n");
        $multiple_matches = $r['Number of names with multiple matches'];
        // asort($multiple_matches);
        $multiple_matches = self::sort_key_val_array($multiple_matches);
        fwrite($WRITE, "Number of names with multiple matches: ".count($multiple_matches)."\n");
        foreach($multiple_matches as $sciname => $total) {
            fwrite($WRITE, "$spaces $sciname -> $total "."\n");
        }
        fwrite($WRITE, "--------------------------------------------------"."\n");
        fwrite($WRITE, "Number of unmatched names: ".$r['totals']['unmatchedNames']."\n");
        fwrite($WRITE, "--------------------------------------------------"."\n");
        fwrite($WRITE, "-end of report-"."\n");
        fclose($WRITE);
    }
    private function summary_report()
    {   /* File statistics:
        1. Number of taxa - rows in the taxon file.
        2. List of fields and their DwC-A mappings - e.g.:
            col1: http://rs.tdwg.org/dwc/terms/taxonID
            col2: http://rs.tdwg.org/dwc/terms/scientificName
            col3: http://rs.tdwg.org/dwc/terms/parentNameUsageID
            col4: http://rs.tdwg.org/dwc/terms/taxonRank
            col5: unmapped
        3. Number of roots - if there is hierarchy information
        4. Taxon ranks - a breakdown of taxon ranks and the number of taxa that have those ranks (including inferred ranks), e.g.:
            species: 11,000 taxa
            genus: 900 taxa
            no rank value: 700 taxa
            family: 40 taxa
            subspecies: 20 taxa
        5. Taxonomic status - a breakdown of the taxonomicStatus values in the file and the number of taxa that have those values
        6. Number of canonical duplicates - with a list of those duplicates
        7. Number of matched names
        8. Number of names with multiple matches
        9. Number of unmatched name */
        $this->summary_report['Number of taxa 2'] = self::total_rows_on_file($this->summary_report['info']['user file']);
        $this->summary_report['No. of canonical duplicates'] = self::get_canonical_duplicates();
        $this->summary_report['Number of names with multiple matches'] = self::get_names_with_multiple_matches(); // user file taxon matches with DH taxon
        
        if($GLOBALS['ENV_DEBUG']) print_r($this->summary_report); //exit("\nditox 20\n");

        // /* reconcile 
        if($names = $this->summary_report['Number of names with multiple matches']) {
            $sum = 0;
            foreach($names as $name => $total) $sum += ($total - 1);
        }
        $totals = $this->summary_report['totals']['matchedNames'] + $this->summary_report['totals']['unmatchedNames'];
        $diff = $totals - $sum;
        debug("\nDiff: [$diff] = $totals - $sum | ".$this->summary_report['Number of taxa']."\n");
        // */
        // /* reconcile 
        if($GLOBALS['ENV_DEBUG']) {
            $arrays = array($this->summary_report['Taxon ranks'], $this->summary_report['Taxonomic status']);
            foreach($arrays as $array) {
                $sum = 0;
                foreach($array as $item => $total) $sum += $total;
                echo "\nShould be equal: [$sum] | ".$this->summary_report['Number of taxa']."\n";
            }    
        }
        // */
        self::write_summary_report();
    }
    private function get_names_with_multiple_matches()
    {   
        // if($u_canonicalName) {
        //     if($val = $this->user_canonicalNames[$u_canonicalName]) {
        //         if($val > 1) $rec['addtl']['quality notes'][] = 'Duplicate canonical';
        //     }    
        // }

        $final = array(); $grand_total = 0;
        $user_canonicals = array_keys($this->user_canonicalNames);
        foreach($user_canonicals as $u_canonicalName) {
            if($DH_recs = @$this->DH_info[$u_canonicalName]) { //matchedNames
                // /* ----- Multiple DH matches - add if there is more than 1 exact canonical match in the DH
                // if(count($DH_recs) > 1) $final[$u_canonicalName] = $DH_recs; //good
                $total_DH_recs = count($DH_recs);
                if($total_DH_recs > 1) {
                    $final[$u_canonicalName] = $total_DH_recs; //good
                    $grand_total += ($total_DH_recs - 1);
                }

                // */
            }
        }
        // echo "\ngrand_total: [$grand_total]\n";
        return $final;

        
        /* wrong
        $final = array();
        foreach($this->DH_info as $canonicalName => $duplicates) {
            if(count($duplicates) > 1) $final[$canonicalName] = $duplicates;
        }
        return $final;
        */
    }
    private function get_canonical_duplicates()
    {   $final = array();
        /* works for totals only
        foreach($this->user_canonicalNames as $sciname => $totals) {
            if($totals > 1) $final[$sciname] = $totals;
        } */
        foreach($this->user_canonicalNames as $sciname => $duplicates) {
            if(count($duplicates) > 1) $final[$sciname] = $duplicates;
        }
        return $final;
    }
    private function get_root_from_HC($higherClassification)
    {
        $ancestors = explode("|", $higherClassification);
        return $ancestors[0];
    }
    private function total_rows_on_file($file)
    {
        $total = shell_exec("wc -l < ".escapeshellarg($file));
        $total = trim($total);
        return $total;
    }
    private function set_taxon_fields()
    {   // /* List of fields:
        $this->taxon_fields['taxonID']                  = 'http://rs.tdwg.org/dwc/terms/taxonID';
        $this->taxon_fields['parentNameUsageID']        = 'http://rs.tdwg.org/dwc/terms/parentNameUsageID';
        $this->taxon_fields['scientificName']           = 'http://rs.tdwg.org/dwc/terms/scientificName';
        $this->taxon_fields['scientificNameAuthorship'] = 'http://rs.tdwg.org/dwc/terms/scientificNameAuthorship';
        $this->taxon_fields['taxonomicStatus']          = 'http://rs.tdwg.org/dwc/terms/taxonomicStatus';
        $this->taxon_fields['taxonRank']                = 'http://rs.tdwg.org/dwc/terms/taxonRank';
        $this->taxon_fields['source']                   = 'http://purl.org/dc/terms/source';
        $this->taxon_fields['acceptedNameUsageID']      = 'http://rs.tdwg.org/dwc/terms/acceptedNameUsageID';
        $this->taxon_fields['kingdom']                  = 'http://rs.tdwg.org/dwc/terms/kingdom';
        $this->taxon_fields['phylum']                   = 'http://rs.tdwg.org/dwc/terms/phylum';
        $this->taxon_fields['class']                    = 'http://rs.tdwg.org/dwc/terms/class';
        $this->taxon_fields['order']                    = 'http://rs.tdwg.org/dwc/terms/order';
        $this->taxon_fields['family']                   = 'http://rs.tdwg.org/dwc/terms/family';
        $this->taxon_fields['genus']                    = 'http://rs.tdwg.org/dwc/terms/genus';
        $this->taxon_fields['higherClassification']     = 'http://rs.tdwg.org/dwc/terms/higherClassification';
        $this->taxon_fields['furtherInformationURL']    = 'http://rs.tdwg.org/ac/terms/furtherInformationURL';
        $this->taxon_fields['taxonRemarks']             = 'http://rs.tdwg.org/dwc/terms/taxonRemarks';
        $this->taxon_fields['namePublishedIn']          = 'http://rs.tdwg.org/dwc/terms/namePublishedIn';
        $this->taxon_fields['referenceID']              = 'http://eol.org/schema/reference/referenceID';
        $this->taxon_fields['EOLid']                    = 'http://eol.org/schema/EOLid';
        //below from Katja
        $this->taxon_fields['scientificNameID']         = 'http://rs.tdwg.org/dwc/terms/scientificNameID';
        $this->taxon_fields['namePublishedInID']        = 'http://rs.tdwg.org/dwc/terms/namePublishedInID';
        $this->taxon_fields['acceptedNameUsage']        = 'http://rs.tdwg.org/dwc/terms/acceptedNameUsage';
        $this->taxon_fields['parentNameUsage']          = 'http://rs.tdwg.org/dwc/terms/parentNameUsage';
        $this->taxon_fields['namePublishedInYear']      = 'http://rs.tdwg.org/dwc/terms/namePublishedInYear';
        $this->taxon_fields['subgenus']                 = 'http://rs.tdwg.org/dwc/terms/subgenus';
        $this->taxon_fields['specificEpithet']          = 'http://rs.tdwg.org/dwc/terms/specificEpithet';
        $this->taxon_fields['infraspecificEpithet']     = 'http://rs.tdwg.org/dwc/terms/infraspecificEpithet';
        $this->taxon_fields['nomenclaturalCode']        = 'http://rs.tdwg.org/dwc/terms/nomenclaturalCode';
        $this->taxon_fields['nomenclaturalStatus']      = 'http://rs.tdwg.org/dwc/terms/nomenclaturalStatus';
        $this->taxon_fields['modified']                 = 'http://purl.org/dc/terms/modified';
        $this->taxon_fields['bibliographicCitation']    = 'http://purl.org/dc/terms/bibliographicCitation';
        $this->taxon_fields['references']               = 'http://purl.org/dc/terms/references';
        $this->taxon_fields['license']                  = 'http://purl.org/dc/terms/license';
        $this->taxon_fields['rightsHolder']             = 'http://purl.org/dc/terms/rightsHolder';
        $this->taxon_fields['datasetName']              = 'http://rs.tdwg.org/dwc/terms/datasetName';
        $this->taxon_fields['institutionCode']          = 'http://rs.tdwg.org/dwc/terms/institutionCode';
    }
    function add_header_to_file($file, $string_tobe_added)
    {
        echo "<pre>\nuser file: [$file]\n";                             // [temp/1687337313.txt]
        $needle = pathinfo($file, PATHINFO_FILENAME);                   //       1687337313
        $tmp_file = str_replace("$needle.txt", "$needle.tmp", $file);   // [temp/1687337313.tmp]
        echo("\n[$file]\n[$needle]\n[$tmp_file]</pre>\n"); //good debug
        $WRITE = Functions::file_open($tmp_file, "w");
        fwrite($WRITE, $string_tobe_added . "\n");
        $contents = file_get_contents($file);
        fwrite($WRITE, $contents . "\n");
        fclose($WRITE);
        shell_exec("cp $tmp_file $file");
    }
    function sort_key_val_array($multi_array, $key_orientation = SORT_ASC, $value_orientation = SORT_ASC)
    {
        $data = array();
        foreach($multi_array as $key => $value) $data[] = array('language' => $key, 'count' => $value);
        // Obtain a list of columns
        /* before PHP 5.5.0
        foreach ($data as $key => $row) {
            $language[$key]  = $row['language'];
            $count[$key] = $row['count'];
        }
        */
        
        // as of PHP 5.5.0 you can use array_column() instead of the above code
        $language  = array_column($data, 'language');
        $count = array_column($data, 'count');

        // Sort the data with language descending, count ascending
        // Add $data as the last parameter, to sort by the common key
        // array_multisort($count, SORT_ASC, $language, SORT_ASC, $data); // an example run
        array_multisort($count, $value_orientation, $language, $key_orientation, $data);

        // echo "<pre>"; print_r($data); echo "</pre>"; exit;
        /* Array(
            [0] => Array(
                    [language] => infraspecies (inferred)
                    [count] => 42
                )
            [1] => Array(
                    [language] => family (inferred)
                    [count] => 240
                )
        */
        $final = array();
        foreach($data as $d) $final[$d['language']] = $d['count'];
        return $final;
    }
}
?>