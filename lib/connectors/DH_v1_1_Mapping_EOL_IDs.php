<?php
namespace php_active_record;
/* connector: [dwh_postproc_TRAM_808.php] - TRAM-808
*/
class DH_v1_1_mapping_EOL_IDs
{
    function __construct($folder) {
        $this->resource_id = $folder;
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->taxon_ids = array();
        $this->debug = array();
        if(Functions::is_production()) { //not used in eol-archive yet, might never be used anymore...
            /*
            $this->download_options = array(
                'cache_path'         => '/extra/eol_cache_smasher/',
                'download_wait_time' => 250000, 'timeout' => 600, 'download_attempts' => 1, 'delay_in_minutes' => 0, 'expire_seconds' => false);
            $this->main_path = "/extra/other_files/DWH/TRAM-807/"; //download_wait_time is 1/4 of a second -> 1000000/4
            */
        }
        else {
            $this->download_options = array(
                'cache_path'         => '/Volumes/AKiTiO4/eol_cache_smasher/',
                'download_wait_time' => 250000, 'timeout' => 600, 'download_attempts' => 1, 'delay_in_minutes' => 0, 'expire_seconds' => false);
            $this->main_path = "/Volumes/AKiTiO4/d_w_h/TRAM-808/";
            $this->file['new DH'] = $this->main_path."DH_v1_1_postproc/taxon.tab";
            $this->file['old DH'] = $this->main_path."eoldynamichierarchywithlandmarks/taxa.txt";
        }
        $this->mysqli =& $GLOBALS['db_connection'];
    }
    /* Main Steps:
    step. manually put JRice's eolpageids.csv to MySQL as well. Created eolpageids.csv.tsv, replace 'comma' with 'tab'.
            $ mysql -u root -p --local-infile DWH;
            to load from txt file:
            mysql> load data local infile '/Volumes/AKiTiO4/d_w_h/TRAM-808/eolpageids.csv.tsv' into table EOLid_map;
    step. run create_append_text(); --> creates [append_taxonID_source_id_2mysql.txt]
          then saves it to MySQL table [taxonID_source_ids]
    step. run step_1()
    */
    //==========================================================================start final_clean_up_for_EOLids
    function final_clean_up_for_EOLids()
    {
        
    }
    //============================================================================end final_clean_up_for_EOLids
    //==========================================================================start step 4
    function step_4()
    {   /* Manual steps:
            - manually run the 'Generate higherClassification Tool' on the old DH. Will be used in step4_2(). Move the file (e.g. 1234567890.txt) accordingly
            - manually run the 'Generate higherClassification Tool' on the latest new DH. Will be used in step4_3(). Move the file (e.g. 1234567890.txt) accordingly
                         
        step4_1: save to MySQL table [taxonomy_tsv_uniqname] all rows from taxonomy.tsv with uniqname
        self::step4_1(); DONE
        step4_2: save to MySQL table [old_DH_with_higherClassification] all rows from old DH. Importantly with the field higherClassification.
        step4_3: loop on new DH with higherC, filter with table taxonomy_tsv_uniqname */
        /*
        Known homonyms have an entry in the uniqname column of the smasher taxonomy.tsv file. There are about 4000 of these. I would like to have a file that makes it easy for me to double-check the EOLid mappings for these taxa.
        For those taxa that have an entry in the uniqname column of taxonomy.tsv AND that are still in the latest version of the new DH, please create a file with the following columns:
        taxonID - the new uuid you have minted
        smasherTaxonID - the one used in the taxonomy.tsv file
        source
        furtherInformationURL
        parentNameUsageID
        scientificName
        taxonRank
        taxonRemarks
        datasetID
        canonicalName
        higherClassification
        oldHigherClassification - the higherClassification of the old DH taxon that provided the EOLid match
        EOLid
        EOLidAnnotations
        ----------------
        Conversion completed. 
        This is the URL of the converted file [new_DH_after_Step3.txt.zip] with higherClassification:
         http://localhost/eol_php_code//applications/genHigherClass_jenkins/temp/1558240552.txt
         http://localhost/eol_php_code//applications/genHigherClass_jenkins/temp/1558240552.txt.zip
         Reminder: These files will be deleted from the server after 24 hours.
        */
    }
    function step4_3()
    {   // /*
        $sql = "SELECT m.minted_id, t.uid FROM DWH.minted_records m JOIN DWH.taxonomy_tsv_uniqname t ON m.uid = t.uid";
        $result = $this->mysqli->query($sql);
        while($result && $row=$result->fetch_assoc()) $EOLids[$row['minted_id']] = array('uid' => $row['uid'], 'hC' => ''); //$row['old_DH_hC']
        echo "\nEOLids with unigname in latest new DH: ".count($EOLids)."\n"; //exit;
        // */
        /* start loop of DH */
        $file_append = $this->main_path."/known_homonyms.txt"; $WRITE = fopen($file_append, "w"); //will overwrite existing (old name was 'subset_new_DH_with_uniqname.txt')
        $i = 0;
        $write_fields = array('taxonID', 'smasherTaxonID', 'source', 'furtherInformationURL', 'parentNameUsageID', 'scientificName', 'taxonRank', 'taxonRemarks', 
                              'datasetID', 'canonicalName', 'higherClassification', 'oldHigherClassification', 'EOLid', 'EOLidAnnotations');
        foreach(new FileIterator($this->main_path."/with_higherClassification/1558361160.txt") as $line_number => $line) {
            $i++; if(($i % 200000) == 0) echo "\n".number_format($i)." ";
            $row = explode("\t", $line);
            if($i == 1) {
                $fields = $row;
                $fields = array_filter($fields); print_r($fields);
                fwrite($WRITE, implode("\t", $write_fields)."\n");
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
            // print_r($rec); //exit;
            /*Array(
                [taxonID] => EOL-000000000001
                [source] => trunk:1bfce974-c660-4cf1-874a-bdffbf358c19,NCBI:1
                [furtherInformationURL] => 
                [parentNameUsageID] => 
                [scientificName] => Life
                [taxonRank] => clade
                [taxonRemarks] => 
                [datasetID] => trunk
                [canonicalName] => Life
                [EOLid] => 2913056
                [EOLidAnnotations] => 
                [higherClassification] => 
            )*/
            /* debug only
            if($rec['taxonID'] == 'EOL-000000000003') {
                print_r($rec); exit;
            }
            */
            if($val = @$EOLids[$rec['taxonID']]) {
                $rec['smasherTaxonID'] = $val['uid'];               // smasherTaxonID - the one used in the taxonomy.tsv file
                $rec['oldHigherClassification'] = self::get_old_DH_higherClassification($rec['EOLid']);       // oldHigherClassification - the higherClassification of the old DH taxon that provided the EOLid match
                // print_r($rec);
                /* start writing */
                $save = array();
                foreach($write_fields as $head) $save[] = $rec[$head];
                fwrite($WRITE, implode("\t", $save)."\n");
            }
        }
        fclose($WRITE);
    }
    private function get_old_DH_higherClassification($eol_id)
    {
        $sql = "SELECT o.taxonID, o.higherClassification from DWH.old_DH_with_higherClassification o JOIN DWH.EOLid_map m ON o.taxonID = m.smasher_id WHERE m.EOL_id = '".$eol_id."'";
        $result = $this->mysqli->query($sql);
        while($result && $row=$result->fetch_assoc()) return $row['higherClassification'];
    }
    function step4_2()
    {
        $sql = "SELECT t.uid from DWH.taxonomy_tsv_uniqname t ;";
        $result = $this->mysqli->query($sql);
        while($result && $row=$result->fetch_assoc()) $taxonIDs[$row['uid']] = '';
        echo "\n taxonIDs with unigname in taxonomy.tsv: ".count($taxonIDs)."\n"; //exit;
        
        $file_append = $this->main_path."/old_DH_with_higherClassification.txt"; $WRITE = fopen($file_append, "w"); //will overwrite existing
        $txtfile = '/Volumes/AKiTiO4/d_w_h/TRAM-808/with_higherClassification/old_DH/1558355336.txt'; $i = 0;
        foreach(new FileIterator($txtfile) as $line_number => $line) {
            $i++; if(($i % 200000) == 0) echo "\n".number_format($i)." ";
            $row = explode("\t", $line);
            if($i == 1) {
                $fields = $row;
                $fields = array_filter($fields); print_r($fields);
                /* not used here...will just save two fields
                fwrite($WRITE, implode("\t", $fields)."\n");
                */
                fwrite($WRITE, implode("\t", array('taxonID', 'higherClassification'))."\n");
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
                [taxonID] => -100000
                [acceptedNameUsageID] => -100000
                [parentNameUsageID] => -79407
                [scientificName] => Frescocyathus nagagreboensis Barta-Calmus, 1969
                [taxonRank] => species
                [source] => gbif:4943435
                [taxonomicStatus] => accepted
                [canonicalName] => Frescocyathus nagagreboensis
                [scientificNameAuthorship] => Barta-Calmus, 1969
                [scientificNameID] => 
                [taxonRemarks] => 
                [namePublishedIn] => 
                [furtherInformationURL] => https://www.gbif-uat.org/species/4943435
                [datasetID] => 6cfd67d6-4f9b-400b-8549-1933ac27936f
                [EOLid] => 
                [EOLidAnnotations] => 
                [Landmark] => 
                [higherClassification] => Life|Cellular|Eukaryota|Opisthokonta|Metazoa|Cnidaria|Anthozoa|Hexacorallia|Scleractinia|Frescocyathus
            )*/
            
            /* NEVER confine it like this since: old DH's smasher ID is not same as taxonomy.tsv's smasher ID
            if(isset($taxonIDs[$rec['taxonID']])) {
            }
            */
            
            // print_r($rec);
            /* start writing */
            /* NOT used here... ONLY two fields are saved below
            $save = array();
            foreach($fields as $head) $save[] = $rec[$head];
            */
            $save = array($rec['taxonID'], $rec['higherClassification']); //save to text just two fields
            fwrite($WRITE, implode("\t", $save)."\n");
        }
        fclose($WRITE);
        /* append to MySQL table */
        $table = 'old_DH_with_higherClassification';
        self::append_to_MySQL_table($table, $file_append);
    }
    private function step4_1()
    {
        $file_append = $this->main_path."/taxonomy_tsv_uniqname.txt"; $WRITE = fopen($file_append, "w"); //will overwrite existing
        $txtfile = '/Volumes/AKiTiO4/d_w_h/TRAM-807/taxonomy.tsv'; $i = 0;
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
            if($val = $rec['uniqname']) {
                // print_r($rec);
                /* start writing */
                $save = array();
                foreach($fields as $head) $save[] = $rec[$head];
                fwrite($WRITE, implode("\t", $save)."\n");
            }
        }
        fclose($WRITE);
        /* append to MySQL table */
        $table = 'taxonomy_tsv_uniqname';
        self::append_to_MySQL_table($table, $file_append);
    }
    //============================================================================end step 4
    //==========================================================================start step 3
    function pre_step_3()
    {   $this->debug = array();
        /* use new_DH_multiple_match_fixed.txt
        step: fill-up blank canonical using gnparser
        gnparser file -f json-compact --input step3_scinames.txt --output step3_gnparsed.txt
        gnparser name -f simple 'Tricornina (Bicornina) jordan, 1964'
        gnparser name -f simple 'Ceroputo pilosellae Šulc, 1898'
        */
        // /* main operations OK
        self::generate_canonicals_to_text_files('old_DH_after_step2');
        self::fill_old_DH_with_blank_canonical('old_DH_after_step2');
        
        $this->retired_old_DH_taxonID = array();
        unset($this->retired_old_DH_taxonID);
        self::retire_old_DH_with_these_taxonIDs("old_DH_gnparsed_tbl", $this->main_path."/old_DH_gnparsed.txt");
        // */
    }
    function step_3()
    {
        echo "\nStart step 3...\n";
        $this->debug = array();
        $this->retired_old_DH_taxonID = array();
        /* 2.1 get list of used EOL_ids ----------------------------------------------------------------------------*/
        $file = $this->main_path."/new_DH_multiple_match_fixed.txt";
        $this->used_EOLids = self::get_results_tool($file, 'get EOLids');
        // echo "\n".count($used_EOLids)."\n"; exit;
        
        /* 2.2 initialize info global ------------------------------------------------------------------------------*/
        require_library('connectors/DH_v1_1_postProcessing');
        $func = new DH_v1_1_postProcessing(1);

        self::get_taxID_nodes_info($file); //for new DH
        $children_of['Lissamphibia'] = $func->get_descendants_of_taxID("EOL-000000618833", false, $this->descendants);
        $children_of['Neoptera'] = $func->get_descendants_of_taxID("EOL-000000987353", false, $this->descendants);
        $children_of['Arachnida'] = $func->get_descendants_of_taxID("EOL-000000890725", false, $this->descendants);
        $children_of['Embryophytes'] = $func->get_descendants_of_taxID("EOL-000000105445", false, $this->descendants);
        unset($this->descendants);
        
        self::get_taxID_nodes_info($this->main_path."/old_DH_gnparsed.txt");
        $old_DH_tbl = "old_DH_gnparsed_tbl";
        $children_of_oldDH['Endopterygota'] = $func->get_descendants_of_taxID("-556430", false, $this->descendants);
        $children_of_oldDH['Embryophytes'] = $func->get_descendants_of_taxID("-30127", false, $this->descendants);
        $children_of_oldDH['Fungi'] = $func->get_descendants_of_taxID("352914", false, $this->descendants);
        $children_of_oldDH['Metazoa'] = $func->get_descendants_of_taxID("691846", false, $this->descendants);
        unset($this->descendants);
        
        /* 2.3 loop new DH -----------------------------------------------------------------------------------------*/
        $file_append = $this->main_path."/new_DH_after_step3.txt"; $WRITE = fopen($file_append, "w"); //will overwrite existing
        $i = 0;
        foreach(new FileIterator($file) as $line_number => $line) {
            $i++; if(($i % 200000) == 0) echo "\n".number_format($i)." ";
            $row = explode("\t", $line);
            if($i == 1) {
                $fields = $row;
                $fields = array_filter($fields); //print_r($fields);
                fwrite($WRITE, implode("\t", $fields)."\n");
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
            //------------------------------------------------------------------------------------------
            if(in_array($rec['EOLidAnnotations'], array('unmatched', 'multiple', 'manual'))) {
                @$this->debug['totals steps 1 & 2'][$rec['EOLidAnnotations'].' count']++;
                // start writing
                $save = array();
                foreach($fields as $head) $save[] = $rec[$head];
                fwrite($WRITE, implode("\t", $save)."\n");
                continue;
            }
            if($rec['EOLid']) {
                @$this->debug['totals steps 1 & 2']['matched EOLid count']++;
                /* start writing */
                $save = array();
                foreach($fields as $head) $save[] = $rec[$head];
                fwrite($WRITE, implode("\t", $save)."\n");
                continue;
            }
            //------------------------------------------------------------------------------------------
            /*Array(
                [taxonID] => EOL-000000095335
                [source] => trunk:b6259274-728a-4b38-a135-f7286fdc5917,WOR:582466
                [furtherInformationURL] => 
                [parentNameUsageID] => EOL-000000095234
                [scientificName] => Opalozoa
                [taxonRank] => phylum
                [taxonRemarks] => 
                [datasetID] => trunk
                [canonicalName] => Opalozoa
                [EOLid] => 2912001
                [EOLidAnnotations] => 
            */
            $canonical_4sql = str_replace("'", "\'", $rec['canonicalName']);
            if(in_array($rec['taxonRank'], array('', 'clade', 'cohort', 'division', 'hyporder', 'informal group', 'infracohort', 'megacohort', 'paraphyletic group', 'polyphyletic group', 'section', 'subcohort', 'supercohort'))) {
                $sql = "SELECT m.EOL_id, o.source, o.taxonID from DWH.".$old_DH_tbl." o join DWH.EOLid_map m ON o.taxonId = m.smasher_id where o.canonicalName = '".$canonical_4sql."' and o.taxonRank not in('genus', 'subgenus', 'family');";
                if($info = self::query_EOL_id(false, $sql)) { //Note: sometimes here, EOLid from old DH already has a value.
                    if($EOL_id = $info['EOL_id']) {
                        $o_taxonID = $info['taxonID']; //111
                        $rec = self::proc_RULES($EOL_id, $rec, $info, 'EXC1', $children_of, $children_of_oldDH);
                    }
                }
                else {} //No sql rows
            }
            elseif($rec['taxonRank'] == 'infraspecies') { //EXC2
                $sql = "SELECT m.EOL_id, o.source, o.taxonID FROM DWH.".$old_DH_tbl." o JOIN DWH.EOLid_map m ON o.taxonId = m.smasher_id WHERE o.canonicalName = '".$canonical_4sql."' AND o.taxonRank IN('form', 'subspecies', 'subvariety', 'variety');";
                if($info = self::query_EOL_id(false, $sql)) {
                    if($EOL_id = $info['EOL_id']) {
                        $o_taxonID = $info['taxonID']; //222
                        $rec = self::proc_RULES($EOL_id, $rec, $info, 'EXC2', $children_of, $children_of_oldDH);
                    }
                }
                else {} //No sql rows
            }
            else { //EXC0
                $sql = "SELECT m.EOL_id, o.source, o.taxonID FROM DWH.".$old_DH_tbl." o JOIN DWH.EOLid_map m ON o.taxonId = m.smasher_id WHERE o.canonicalName = '".$canonical_4sql."' AND o.taxonRank = '".$rec['taxonRank']."';";
                if($info = self::query_EOL_id(false, $sql)) {
                    if($EOL_id = $info['EOL_id']) {
                        $o_taxonID = $info['taxonID']; //000
                        $rec = self::proc_RULES($EOL_id, $rec, $info, 'EXC0', $children_of, $children_of_oldDH);
                    }
                }
                else {} //No sql rows
            }
            
            if($rec['EOLid']) {
                @$this->debug['totals']['matched EOLid count from Step3']++;
                $this->retired_old_DH_taxonID[$o_taxonID] = ''; //step 3
            }
            else {
                $rec['EOLidAnnotations'] = 'unmatched';
                @$this->debug['totals']['unmatched count from Step3']++;
            }
            /* start writing */
            $save = array();
            foreach($fields as $head) $save[] = $rec[$head];
            fwrite($WRITE, implode("\t", $save)."\n");
        }
        fclose($WRITE);
        Functions::start_print_debug($this->debug, $this->resource_id."_after_step3");
        self::retire_old_DH_with_these_taxonIDs("old_DH_after_step3", $this->main_path."/old_DH_gnparsed.txt");
    }
    private function fill_old_DH_with_blank_canonical($sourcef)
    {
        /* step: get all taxonID - canonicals list */
        $sciname_canonical = self::build_sciname_canonical_list(); echo "\nsciname_canonical: ".count($sciname_canonical)."\n";

        /* step: fill blank canonicals */
        echo "\nSaving to old_DH_gnparsed.txt...\n";
        $file_append = $this->main_path."/old_DH_gnparsed.txt"; $WRITE = fopen($file_append, "w"); //will overwrite existing
        $i = 0; $this->debug = array();
        foreach(new FileIterator($this->main_path."/".$sourcef.".txt") as $line_number => $line) {
            $i++; if(($i % 200000) == 0) echo "\n".number_format($i)." ";
            $row = explode("\t", $line);
            if($i == 1) {
                $fields = $row;
                $fields = array_filter($fields); //print_r($fields);
                fwrite($WRITE, implode("\t", $fields)."\n");
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
            // print_r($rec); exit("\neliboy\n");
            /**/
            
            if(!$rec['canonicalName']) {
                if($rec['canonicalName'] = $sciname_canonical[$rec['scientificName']]) {
                    // echo "\nassigned ".$rec['scientificName']." --> ".$rec['canonicalName']."\n";
                }
                else {
                    if(stripos($rec['scientificName'], "unplaced extinct") !== false) {} //string is found
                    elseif(stripos($rec['scientificName'], "extinct ") !== false) {} //string is found
                    elseif(stripos($rec['scientificName'], "unplaced ") !== false) {} //string is found
                    elseif(stripos($rec['scientificName'], "fragile ") !== false) {} //string is found
                    elseif(stripos($rec['scientificName'], "virus") !== false) {} //string is found
                    elseif(stripos($rec['scientificName'], " incertae sedis") !== false) {} //string is found
                    elseif(in_array($rec['scientificName'], array('Aloe x L.C. Leach', 'landbirds', 'waterbirds', 'berothid clade', 'Tricornina (Bicornina) jordan, 1964', 'HaploVejdovskya Ax, 1954', 'Burana orthonairovirus', 'HaploVejdovskya subterranea Ax, 1954'))) {}
                    else {
                        print_r($rec);
                        // exit("\nInvestigate 001\n");
                    }
                }
            }
            
            /* start writing */
            $save = array();
            foreach($fields as $head) $save[] = $rec[$head];
            fwrite($WRITE, implode("\t", $save)."\n");
        }
        fclose($WRITE);
    }
    private function build_sciname_canonical_list()
    {
        $ctr = 0;
        while(true) {
            $ctr++;
            $file = $this->main_path."/gnparser/step3_gnparsed_".$ctr.".txt";
            if(file_exists($file)) {
                echo "\nProcessing [$file]...\n";
                $file_array = file($file);
                foreach($file_array as $line) {
                    $row = explode("\t", $line);
                    $final[$row[1]] = $row[3];
                }
            }
            else break;
        }
        return $final;
    }
    private function generate_canonicals_to_text_files($sourcef)
    {
        echo "\nUsing gnparser...\n";
        $file_append = $this->main_path."/gnparser/step3_scinames_1.txt"; $WRITE = fopen($file_append, "w"); //will overwrite existing
        $i = 0; $this->debug = array(); $xxx = 0; $ctr = 1;
        foreach(new FileIterator($this->main_path."/".$sourcef.".txt") as $line_number => $line) {
            $i++; if(($i % 200000) == 0) echo "\n".number_format($i)." ";
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
            $rec = array_map('trim', $rec);
            // print_r($rec); //exit("\neliboy\n");
            /*Array(
                [taxonID] => -106000
                [acceptedNameUsageID] => -106000
                [parentNameUsageID] => -81247
                [scientificName] => Dystactella eisenmanni Hryniewicz, Jakubowicz, Belka, Dopieralska & Kaim, 2016
                [taxonRank] => species
                [source] => gbif:8946026
                [taxonomicStatus] => accepted
                [canonicalName] => Dystactella eisenmanni
                [scientificNameAuthorship] => Hryniewicz, Jakubowicz, Belka, Dopieralska & Kaim, 2016
                [scientificNameID] => 
                [taxonRemarks] => 
                [namePublishedIn] => 
                [furtherInformationURL] => https://www.gbif-uat.org/species/8946026
                [datasetID] => b9a214b7-c368-4d22-aa53-b1fc16a1210a
                [EOLid] => 
                [EOLidAnnotations] => 
                [Landmark] => 
            )*/
            if(!$rec['canonicalName']) {
                fwrite($WRITE, $rec['scientificName']."\n");
                @$xxx++;
                if($xxx > 400000) {
                    $xxx = 0; $ctr++;
                    fclose($WRITE);
                    $file_append = $this->main_path."/gnparser/step3_scinames_".$ctr.".txt"; $WRITE = fopen($file_append, "w"); //will overwrite existing
                }
            }
        }
        fclose($WRITE);
        echo "\nWithout canonicals: [$xxx]\n";
        /* step: convert file to gnparsed */
        for($i = 1; $i <= $ctr; $i++) {
            $source = $this->main_path."/gnparser/step3_scinames_".$i.".txt";
            $destination = $this->main_path."/gnparser/step3_gnparsed_".$i.".txt";
            $cmd = "gnparser file -f simple --input $source --output $destination"; //'simple' or 'json-compact'
            $out = shell_exec($cmd); echo "\n$out\n";
        }
    }
    //============================================================================end step 3
    //==========================================================================start before step 2
    function before_step_2_or_3($tbl, $what) //fix prob. described in an email to Katja
    {   echo "\nstart before_step_2_or_3() [$tbl] [$what]\n";
        $this->debug = array();
        $file = $this->main_path."/".$tbl.".txt";
        $recs = self::get_results_tool($file, "get EOLid - taxa list");
        $more_than_one = 0; $final = array();
        foreach($recs as $eol_id => $taxa) {
            if(count($taxa) > 1) {
                $more_than_one++;
                if($what == 'step 1')     self::fix_same_EOLid_for_multiple_taxa_step1($eol_id, $taxa);
                elseif($what == 'step 2') $final[$eol_id] = $taxa;
                elseif($what == 'step 3') $final[$eol_id] = $taxa;
            }
        }
        echo "\n-end before [$what] [$more_than_one]-\n";
        if($what == 'step 2') {
            if($final) self::fix_same_EOLid_for_multiple_taxa_step2_3($final, 'new_DH_before_step3', 'new_DH_after_step2', 3);
        }
        elseif($what == 'step 3') {
            if($final) self::fix_same_EOLid_for_multiple_taxa_step2_3($final, 'new_DH_before_step4', 'new_DH_after_step3', 4);
        }
    }
    private function fix_same_EOLid_for_multiple_taxa_step2_3($final, $destinef, $sourcef, $which)
    {   /*If there are still multiple matches for a given scientificName string once these rules are applied, take the unresolved new DH taxa out of the matching pool 
          and flag them as "multiple." */
        $used_when_saving_2text = array();
        foreach($final as $eol_id => $taxa) {
            foreach($taxa as $rec) {
                /*Array(
                    [taxonID] => EOL-000000085511
                    [source] => trunk:06e1feb1-fc37-4596-a605-46601d3f74a9,NCBI:33634,WOR:368898
                    [furtherInformationURL] => 
                    [parentNameUsageID] => EOL-000000025792
                    [scientificName] => Stramenopiles
                    [taxonRank] => clade
                    [taxonRemarks] => 
                    [datasetID] => trunk
                    [canonicalName] => Stramenopiles
                    [EOLid] => 2912001
                    [EOLidAnnotations] => 
                )*/
                $rec['EOLidAnnotations'] = 'multiple';
                $used_when_saving_2text[$rec['taxonID']] = $rec;
            }
        }
        self::save_to_text($used_when_saving_2text, $destinef, $sourcef, $which); //save to text file
    }
    private function fix_same_EOLid_for_multiple_taxa_step1($eol_id, $taxa)
    {   /*Hi Eli, 
        Ah yes, we tried some weird synonym mappings in that version of the DH that we have given up on since. 
        If you encounter situations where multiple new DH nodes map to the same old DH taxon during step 1, 
        try to resolve the proper mapping by also looking at the scientificName, 
        i.e., if one of the new DH sourceID matches also matches on scientificName, give the EOLid to that new DH taxon (in your example EOL-000000085511) 
        and return the other new DH taxa to the matching pool. If none of the scientificNames match the old DH taxon or if there still are multiple matches, 
        don't assign the EOLid based on the source identifier and leave all taxa involved in the matching pool for subsequent steps.
        Thanks!  Katja
        */
        $matched = 0;
        foreach($taxa as $rec) {
            /*Array(
                [taxonID] => EOL-000000085511
                [source] => trunk:06e1feb1-fc37-4596-a605-46601d3f74a9,NCBI:33634,WOR:368898
                [furtherInformationURL] => 
                [parentNameUsageID] => EOL-000000025792
                [scientificName] => Stramenopiles
                [taxonRank] => clade
                [taxonRemarks] => 
                [datasetID] => trunk
                [canonicalName] => Stramenopiles
                [EOLid] => 2912001
                [EOLidAnnotations] => 
            )*/
            $source_ids = self::get_all_source_identifiers($rec['source']);
            foreach($source_ids as $source_id) {
                $sciname = str_replace("'", "\'", $rec['scientificName']);
                $sql = "SELECT m.EOL_id FROM DWH.taxonID_source_ids o JOIN DWH.EOLid_map m ON o.taxonId = m.smasher_id JOIN DWH.old_DH o2 ON o.taxonID = o2.taxonID WHERE o2.scientificName = '".$sciname."' AND o.source_id = '".$source_id."'";
                if($row = self::query_EOL_id(false, $sql)) {
                    if($eol_id != $row['EOL_id']) exit("\nInvestigate 001\n"); //just a test, should always be false
                    $rec['EOLid'] = $eol_id;
                    $matched++;
                    /* $this->retired_old_DH_taxonID[$row['taxonID']] = ''; --- not needed I think...already retired in step 1 */
                }
                else {
                    $rec['EOLid'] = '';
                    //start un-retire --- not needed I think...
                    /*
                    $source_ids = self::get_all_source_identifiers($rec['source']);
                    foreach($source_ids as $source_id) {
                        if($source_id) $sql = "SELECT o.taxonID FROM DWH.taxonID_source_ids o JOIN DWH.EOLid_map m ON o.taxonID = m.smasher_id WHERE o.source_id = '".$source_id."'";
                        $result = $this->mysqli->query($sql);
                        while($result && $row=$result->fetch_assoc()) {
                            un-retire $row['taxonID'];
                        }
                    }
                    */
                }
                $used_when_saving_2text[$rec['taxonID']] = $rec;
            }
        }

        echo("\nMatched:[$matched]\n");
        if($matched == 1) self::save_to_text($used_when_saving_2text, 'new_DH_before_step2', 'new_DH_after_step1', 2); //save to text file
        elseif($matched == 0 || $matched > 1) { //set all EOLid to blank, then save to text file
            $final = array();
            foreach($used_when_saving_2text as $taxonID => $rec) {
                $rec['EOLid'] = '';
                $final[$taxonID] = $rec;
            }
            self::save_to_text($final, 'new_DH_before_step2', 'new_DH_after_step1', 2);
        }
    }
    private function save_to_text($used_when_saving_2text, $destinef, $sourcef, $which)
    {
        echo "\nSaving to text...\n";
        $file_append = $this->main_path."/".$destinef.".txt"; $WRITE = fopen($file_append, "w"); //will overwrite existing
        $i = 0; $this->debug = array();
        foreach(new FileIterator($this->main_path."/".$sourcef.".txt") as $line_number => $line) {
            $i++; if(($i % 200000) == 0) echo "\n".number_format($i)." ";
            $row = explode("\t", $line);
            if($i == 1) {
                $fields = $row;
                $fields = array_filter($fields); //print_r($fields);
                fwrite($WRITE, implode("\t", $fields)."\n");
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
            // print_r($rec); exit("\neliboy\n");
            /*Array(
                [taxonID] => EOL-000000000001
                [source] => trunk:1bfce974-c660-4cf1-874a-bdffbf358c19,NCBI:1
                [furtherInformationURL] => 
                [parentNameUsageID] => 
                [scientificName] => Life
                [taxonRank] => clade
                [taxonRemarks] => 
                [datasetID] => trunk
                [canonicalName] => Life
                [EOLid] => 
                [EOLidAnnotations] => 
            )*/
            
            if($rek = @$used_when_saving_2text[$rec['taxonID']]) $rec = $rek;
            
            if($rec['EOLid'])                           @$this->debug['totals']['matched EOLid count']++;
            if($rec['EOLidAnnotations'] == 'unmatched') @$this->debug['totals']['unmatched count']++;
            if($rec['EOLidAnnotations'] == 'multiple') @$this->debug['totals']['multiple count']++;
            if($rec['EOLidAnnotations'] == 'manual') @$this->debug['totals']['manual count']++;
            
            /* start writing */
            // $headers = array_keys($rec);
            $save = array();
            foreach($fields as $head) $save[] = $rec[$head];
            fwrite($WRITE, implode("\t", $save)."\n");
        }
        fclose($WRITE);
        Functions::start_print_debug($this->debug, $this->resource_id."_before_step".$which);
    }
    //==========================================================================end before step 2
    //==========================================================================start step 2
    function step_2()
    {
        $this->debug = array();
        $this->retired_old_DH_taxonID = array();
        /* 2.1 get list of used EOL_ids ----------------------------------------------------------------------------*/
        $file = $this->main_path."/new_DH_before_step2.txt";
        $this->used_EOLids = self::get_results_tool($file, 'get EOLids');
        // echo "\n".count($used_EOLids)."\n"; exit;
        
        /* 2.2 initialize info global ------------------------------------------------------------------------------*/
        require_library('connectors/DH_v1_1_postProcessing');
        $func = new DH_v1_1_postProcessing(1);

        self::get_taxID_nodes_info($file); //for new DH
        $children_of['Lissamphibia'] = $func->get_descendants_of_taxID("EOL-000000618833", false, $this->descendants);
        $children_of['Neoptera'] = $func->get_descendants_of_taxID("EOL-000000987353", false, $this->descendants);
        $children_of['Arachnida'] = $func->get_descendants_of_taxID("EOL-000000890725", false, $this->descendants);
        $children_of['Embryophytes'] = $func->get_descendants_of_taxID("EOL-000000105445", false, $this->descendants);
        unset($this->descendants);
        
        // self::get_taxID_nodes_info($this->file['old DH']); //for old DH
        self::get_taxID_nodes_info($this->main_path."/old_DH_after_step1.txt");
        $old_DH_tbl = "old_DH_after_step1";
        $children_of_oldDH['Endopterygota'] = $func->get_descendants_of_taxID("-556430", false, $this->descendants);
        $children_of_oldDH['Embryophytes'] = $func->get_descendants_of_taxID("-30127", false, $this->descendants);
        $children_of_oldDH['Fungi'] = $func->get_descendants_of_taxID("352914", false, $this->descendants);
        $children_of_oldDH['Metazoa'] = $func->get_descendants_of_taxID("691846", false, $this->descendants);
        unset($this->descendants);
        
        /* 2.3 loop new DH -----------------------------------------------------------------------------------------*/
        $file_append = $this->main_path."/new_DH_after_step2.txt"; $WRITE = fopen($file_append, "w"); //will overwrite existing
        $i = 0;
        foreach(new FileIterator($file) as $line_number => $line) {
            $i++; if(($i % 200000) == 0) echo "\n".number_format($i)." ";

            /* debug only - force assign
            if($i > 1) {
                $line = "EOL-000000638862	trunk:4acff1d9-7d02-4206-b332-cc742744b6c5		EOL-000000638603	Reithrodonini	tribe		trunk	Reithrodonini		
                ";
                $line = "EOL-000002098677	trunk:a11819bb-4f09-4261-8e31-ba4f576dfec2		EOL-000002098642	Peracarida	superorder		trunk	Peracarida		
                ";
                $line = "EOL-000000733938	trunk:775458eb-0abc-43f3-805b-84d3bcd5542f		EOL-000000733071	Octopodiformes Berthold and Engeser, 1987	superorder		trunk	Octopodiformes		
                ";
                $line = "EOL-000001631839	COL:becddd197de172686bc3449097a7c1ab	http://www.catalogueoflife.org/col/details/species/id/becddd197de172686bc3449097a7c1ab	EOL-000001631838	Unumgar siccus Chandler, 2001	species		COL-204	Unumgar siccus		
                ";
            }
            */

            $row = explode("\t", $line);
            if($i == 1) {
                $fields = $row;
                $fields = array_filter($fields); //print_r($fields);
                fwrite($WRITE, implode("\t", $fields)."\n");
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
            //------------------------------------------------------------------------------------------
            /* NEW: kinda irrelevant since no 'unmatched' will come from step 1
            if($rec['EOLidAnnotations'] == 'unmatched') {
                @$this->debug['totals']['unmatched count']++;
                // start writing
                $save = array();
                foreach($fields as $head) $save[] = $rec[$head];
                fwrite($WRITE, implode("\t", $save)."\n");
                continue;
            }
            */
            if($rec['EOLid']) {
                @$this->debug['totals']['matched EOLid count from Step1']++;
                /* start writing */
                $save = array();
                foreach($fields as $head) $save[] = $rec[$head];
                fwrite($WRITE, implode("\t", $save)."\n");
                continue;
            }
            //------------------------------------------------------------------------------------------
            /*Array(
                [taxonID] => EOL-000000095335
                [source] => trunk:b6259274-728a-4b38-a135-f7286fdc5917,WOR:582466
                [furtherInformationURL] => 
                [parentNameUsageID] => EOL-000000095234
                [scientificName] => Opalozoa
                [taxonRank] => phylum
                [taxonRemarks] => 
                [datasetID] => trunk
                [canonicalName] => Opalozoa
                [EOLid] => 2912001
                [EOLidAnnotations] => 
            For the new DH taxa that remain unmatched after step 1, try to find an exact scientificName match in the old DH file, making sure that taxonRank is the same for the matched taxa.
            EXCEPTIONS:
            If taxonRank is blank or if it is clade, cohort, division, hyporder, informal group, infracohort, megacohort, paraphyletic group, polyphyletic group, section, subcohort or supercohort, 
                you can match with taxa of any rank EXCEPT genus, subgenus or family.
            If taxonRank is infraspecies, you can match with taxa that have one of the following taxonRank values: form, subspecies, subvariety, variety
            */
            $sciname_4sql = str_replace("'", "\'", $rec['scientificName']);
            if(in_array($rec['taxonRank'], array('', 'clade', 'cohort', 'division', 'hyporder', 'informal group', 'infracohort', 'megacohort', 'paraphyletic group', 'polyphyletic group', 'section', 'subcohort', 'supercohort'))) {
                $sql = "SELECT m.EOL_id, o.source, o.taxonID from DWH.".$old_DH_tbl." o join DWH.EOLid_map m ON o.taxonId = m.smasher_id where o.scientificName = '".$sciname_4sql."' and o.taxonRank not in('genus', 'subgenus', 'family');";
                if($info = self::query_EOL_id(false, $sql)) { //Note: sometimes here, EOLid from old DH already has a value.
                    if($EOL_id = $info['EOL_id']) {
                        $o_taxonID = $info['taxonID']; //111
                        $rec = self::proc_RULES($EOL_id, $rec, $info, 'EXC1', $children_of, $children_of_oldDH);
                    }
                }
                else {} //No sql rows
            }
            elseif($rec['taxonRank'] == 'infraspecies') { //EXC2
                $sql = "SELECT m.EOL_id, o.source, o.taxonID FROM DWH.".$old_DH_tbl." o JOIN DWH.EOLid_map m ON o.taxonId = m.smasher_id WHERE o.scientificName = '".$sciname_4sql."' AND o.taxonRank IN('form', 'subspecies', 'subvariety', 'variety');";
                if($info = self::query_EOL_id(false, $sql)) {
                    if($EOL_id = $info['EOL_id']) {
                        $o_taxonID = $info['taxonID']; //222
                        $rec = self::proc_RULES($EOL_id, $rec, $info, 'EXC2', $children_of, $children_of_oldDH);
                    }
                }
                else {} //No sql rows
            }
            else { //EXC0
                $sql = "SELECT m.EOL_id, o.source, o.taxonID FROM DWH.".$old_DH_tbl." o JOIN DWH.EOLid_map m ON o.taxonId = m.smasher_id WHERE o.scientificName = '".$sciname_4sql."' AND o.taxonRank = '".$rec['taxonRank']."';";
                if($info = self::query_EOL_id(false, $sql)) {
                    if($EOL_id = $info['EOL_id']) {
                        $o_taxonID = $info['taxonID']; //000
                        $rec = self::proc_RULES($EOL_id, $rec, $info, 'EXC0', $children_of, $children_of_oldDH);
                    }
                }
                else {} //No sql rows
            }
            
            if($rec['EOLid']) {
                @$this->debug['totals']['matched EOLid count from Step2']++;
                $this->retired_old_DH_taxonID[$o_taxonID] = ''; //step 2
            }
            /* if($rec['EOLidAnnotations'] == 'unmatched') @$this->debug['totals']['unmatched count']++; --> irrelevant here... */
            /* start writing */
            $save = array();
            foreach($fields as $head) $save[] = $rec[$head];
            fwrite($WRITE, implode("\t", $save)."\n");
        }
        fclose($WRITE);
        Functions::start_print_debug($this->debug, $this->resource_id."_after_step2");
        self::retire_old_DH_with_these_taxonIDs("old_DH_after_step2", $this->main_path."/old_DH_after_step1.txt");
    }
    private function proc_RULES($EOL_id, $rec, $info, $excep_no, $children_of, $children_of_oldDH)
    {
        if(self::source_is_in_listof_sources($info['source'], array('AMP'))) {
            if(in_array($rec['taxonID'], $children_of['Lissamphibia'])) { //RULE 1
                $rec['EOLid'] = $EOL_id;
                @$this->debug['totals step2']["$excep_no RULE1 count"]++;
            }
        }
        elseif(self::source_is_in_listof_sources($info['source'], array('APH', 'BLA', 'COL', 'COR', 'DER', 'EMB', 'GRY', 'LYG', 'MAN', 'MNT', 'ORTH', 'PHA', 'PLE', 'PSO', 'TER', 'ZOR'))) {
            if(in_array($rec['taxonID'], $children_of['Neoptera'])) { //RULE 2
                $rec['EOLid'] = $EOL_id;
                @$this->debug['totals step2']["$excep_no RULE2 count"]++;
            }
        }
        elseif(self::source_is_in_listof_sources($info['source'], array('SPI'))) {
            if(in_array($rec['taxonID'], $children_of['Arachnida'])) { //RULE 3
                $rec['EOLid'] = $EOL_id;
                @$this->debug['totals step2']["$excep_no RULE3 count"]++;
            }
        }
        elseif(self::source_is_in_listof_sources($info['source'], array('lhw', 'PPG', 'TPL'))) {
            if(in_array($rec['taxonID'], $children_of['Embryophytes'])) { //RULE 4
                $rec['EOLid'] = $EOL_id;
                @$this->debug['totals step2']["$excep_no RULE4 count"]++;
            }
        }
        
        if(self::source_is_in_listof_sources($rec['source'], array('BOM','ERE','COC','VSP'))) { //RULE 5
            if(in_array($info['taxonID'], $children_of_oldDH['Endopterygota'])) {
                $rec['EOLid'] = $EOL_id;
                @$this->debug['totals step2']["$excep_no RULE5 count"]++;
            }
        }
        if(self::source_is_in_listof_sources($rec['source'], array('CLP','NCBI','WOR'))) { //RULE 6
            if(!in_array($info['taxonID'], $children_of_oldDH['Embryophytes'])) {
                $rec['EOLid'] = $EOL_id;
                @$this->debug['totals step2']["$excep_no RULE6 count"]++;
            }
        }
        if(self::source_is_in_listof_sources($rec['source'], array('NCBI','WOR'))) { //RULE 7
            if(!in_array($info['taxonID'], $children_of_oldDH['Fungi'])) {
                $rec['EOLid'] = $EOL_id;
                @$this->debug['totals step2']["$excep_no RULE7 count"]++;
            }
        }
        if(self::source_is_in_listof_sources($rec['source'], array('CLP','NCBI'))) { //RULE 8
            if(!in_array($info['taxonID'], $children_of_oldDH['Metazoa'])) {
                $rec['EOLid'] = $EOL_id;
                @$this->debug['totals step2']["$excep_no RULE8 count"]++;
            }
        }
        $rec = self::proc_RULE_9($EOL_id, $rec, $info, $excep_no);
        // /* it actually made a whole lot of difference... use it for sure! Katja's 4 example were fixed here. https://eol-jira.bibalex.org/browse/TRAM-808?focusedCommentId=63464&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-63464
        if(!$rec['EOLid']) {
            $rec['EOLid'] = $EOL_id;
            @$this->debug['totals step2']["$excep_no NO RULES count"]++;
        }
        // */
        return $rec;
    }
    private function proc_RULE_9($EOL_id, $rec, $info, $exception_no)
    {   /*Array(
            [taxonID] => EOL-000000660433
            [source] => trunk:5593c426-a5d7-4b54-8827-0ee4a5def0c5,IOC:e8fa6ef59a11bde5bf4c97003418c5b5
            [furtherInformationURL] => 
            [parentNameUsageID] => EOL-000000660432
            [scientificName] => Neoaves
            [taxonRank] => clade
            [taxonRemarks] => 
            [datasetID] => trunk
            [canonicalName] => Neoaves
            [EOLid] => 
            [EOLidAnnotations] => 
        )*/
        if(self::source_is_in_listof_sources($rec['source'], array('ictv', 'IOC', 'ODO'))) { //RULE 9
            // print_r($rec); echo("\nstart here...\n");
            if(self::source_is_in_listof_sources($rec['source'], array('ictv')) && self::source_is_in_listof_sources($info['source'], array('ictv'))) {
                $rec['EOLid'] = $EOL_id;
                @$this->debug['totals step2']["$exception_no RULE9 ictv count"]++;
            }
            elseif(self::source_is_in_listof_sources($rec['source'], array('IOC')) && self::source_is_in_listof_sources($info['source'], array('IOC'))) {
                $rec['EOLid'] = $EOL_id;
                @$this->debug['totals step2']["$exception_no RULE9 IOC count"]++;
            }
            elseif(self::source_is_in_listof_sources($rec['source'], array('ODO')) && self::source_is_in_listof_sources($info['source'], array('ODO'))) {
                $rec['EOLid'] = $EOL_id;
                @$this->debug['totals step2']["$exception_no RULE9 ODO count"]++;
            }
        }
        return $rec;
    }
    private function get_taxID_nodes_info($txtfile)
    {
        $this->taxID_info = array(); $this->descendants = array(); //initialize global vars
        $i = 0;
        foreach(new FileIterator($txtfile) as $line_number => $line) {
            $i++; if(($i % 200000) == 0) echo "\n".number_format($i)." ";
            if($i == 1) $line = strtolower($line);
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
            // print_r($rec); exit("\nstopx\n");
            /*Array(
                [taxonid] => EOL-000000000001
                [source] => trunk:1bfce974-c660-4cf1-874a-bdffbf358c19,NCBI:1
                [furtherinformationurl] => 
                [parentnameusageid] => 
                [scientificname] => Life
                [taxonrank] => clade
                [taxonremarks] => 
                [datasetid] => trunk
                [canonicalname] => Life
                [eolid] => 
                [eolidannotations] => 
            )*/
            // $this->taxID_info[$rec['uid']] = array("pID" => $rec['parent_uid'], 'r' => $rec['rank'], 'n' => $rec['name'], 's' => $rec['sourceinfo'], 'f' => $rec['flags']); //used for ancesty and more
            $this->descendants[$rec['parentnameusageid']][$rec['taxonid']] = ''; //used for descendants (children)
        }
    }
    private function get_results_tool($file, $what)
    {
        $i = 0; $final = array();
        foreach(new FileIterator($file) as $line_number => $line) {
            $i++; if(($i % 200000) == 0) echo "\n".number_format($i)." ";
            $row = explode("\t", $line);
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
            // if($rec['EOLid'] == 2912001) print_r($rec); //debug only

            //==================================================================================
            if($what == "get EOLid - taxa list") {
                if($val = $rec['EOLid']) $final[$val][] = $rec;
            }
            //==================================================================================
            if($what == "get EOLids") {
                if($val = $rec['EOLid']) $final[$val] = '';
                /* just for debug, good debug
                {
                    if(isset($final[$val])) print_r($rec);
                    else $final[$val] = '';
                }
                */
            }
            //==================================================================================
        }
        if($what == "get EOLids") return $final; //return array_keys($final);
        if($what == "get EOLid - taxa list") return $final;
    }
    function fix_multiple_matches_after_step2()
    {   $this->debug = array();
        echo "\nRun fix_multiple_matches_after_step2...\n";
        require_library('connectors/DH_v1_1_postProcessing');
        $func = new DH_v1_1_postProcessing(1);
        $file = $this->main_path.'/new_DH_before_step3.txt';
        self::get_taxID_nodes_info($file); //un-comment in real operation
        /* step:
        Finally, some of the multiples were due to taxa not properly merging in smasher. I will add these to the synonyms list for the next smasher run, 
        so hopefully they will merge in the future. For now, please just delete these taxa and any descendants they may have:
        */
        $delete_ids = array('EOL-000000097660','EOL-000000097661','EOL-000000097649','EOL-000000097647','EOL-000000097662','EOL-000000097648','EOL-000000097653','EOL-000000097643','EOL-000000097650','EOL-000000097656','EOL-000000097657');
        $children_2delete = array();
        foreach($delete_ids as $delete_id) {
            $arr = $func->get_descendants_of_taxID($delete_id, false, $this->descendants);
            $children_2delete = array_merge($arr, $children_2delete);
        }
        echo "\n".count($delete_ids)."\n";
        print_r($children_2delete); echo "\n".count($children_2delete)."\n";
        $delete_ids = array_merge($delete_ids, $children_2delete);
        echo "\n".count($delete_ids)."\n"; //exit;

        /* step: Loop DH */
        $file_append = $this->main_path."/new_DH_multiple_match_fixed.txt"; $WRITE = fopen($file_append, "w"); //will overwrite existing
        $i = 0;
        foreach(new FileIterator($file) as $line_number => $line) {
            $i++; if(($i % 200000) == 0) echo "\n".number_format($i)." ";
            $row = explode("\t", $line);
            if($i == 1) {
                $fields = $row;
                $fields = array_filter($fields); //print_r($fields);
                fwrite($WRITE, implode("\t", $fields)."\n");
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
            // print_r($rec); exit("\nstopx old_DH\n");
            /*Array(
                [taxonID] => EOL-000000000001
                [source] => trunk:1bfce974-c660-4cf1-874a-bdffbf358c19,NCBI:1
                [furtherInformationURL] => 
                [parentNameUsageID] => 
                [scientificName] => Life
                [taxonRank] => clade
                [taxonRemarks] => 
                [datasetID] => trunk
                [canonicalName] => Life
                [EOLid] => 2913056
                [EOLidAnnotations] => 
            )*/
            if(in_array($rec['taxonID'], $delete_ids)) continue;
            $rec = self::apply_eolid_and_put_manual($rec);
            
            if($rec['EOLid']) @$this->debug['totals']['matched EOLid count']++;
            if($rec['EOLidAnnotations'] == 'manual') @$this->debug['totals']['manual count']++;
            if($rec['EOLidAnnotations'] == 'multiple') @$this->debug['totals']['multiple count']++;
            if($rec['EOLidAnnotations'] == 'unmatched') @$this->debug['totals']['unmatched count']++;
            
            /* start writing */
            $save = array();
            foreach($fields as $head) $save[] = $rec[$head];
            fwrite($WRITE, implode("\t", $save)."\n");
        }
        fclose($WRITE);
        Functions::start_print_debug($this->debug, $this->resource_id."_after_multiple_fix");
    }
    private function apply_eolid_and_put_manual($rec)
    {   /* Following up on the "multiple" matches in Step 2. Please apply the following EOLid mappings and put "manual" in the EOLidAnnotations field of these records: */
        $arr['EOL-000000688413'] = 7989;    $arr['EOL-000000097598'] = 21243;   $arr['EOL-000000096028'] = 21414;   $arr['EOL-000000097620'] = 22017;
        $arr['EOL-000000097592'] = 22130;   $arr['EOL-000000097608'] = 22273;   $arr['EOL-000000097605'] = 22933;   $arr['EOL-000000107750'] = 29168;
        $arr['EOL-000000171804'] = 29338;   $arr['EOL-000000191866'] = 29548;   $arr['EOL-000000392132'] = 32577;   $arr['EOL-000000183167'] = 35384;
        $arr['EOL-000000097615'] = 35391;   $arr['EOL-000000097623'] = 35397;   $arr['EOL-000000097618'] = 35400;   $arr['EOL-000000232187'] = 59393;
        $arr['EOL-000000267238'] = 59433;   $arr['EOL-000000268789'] = 60821;   $arr['EOL-000000412776'] = 61710;   $arr['EOL-000002173027'] = 65109;
        $arr['EOL-000000097596'] = 75600;   $arr['EOL-000000476199'] = 78249;   $arr['EOL-000000157580'] = 80153;   $arr['EOL-000000380326'] = 92394;
        $arr['EOL-000000014525'] = 97742;   $arr['EOL-000000006871'] = 97760;   $arr['EOL-000002172343'] = 100752;  $arr['EOL-000000097636'] = 101783;
        $arr['EOL-000000189560'] = 108306;  $arr['EOL-000000095424'] = 2912226; $arr['EOL-000000484142'] = 5302490; $arr['EOL-000000343895'] = 5380037;
        $arr['EOL-000000274767'] = 8807432;     $arr['EOL-000002321022'] = 11808699;    $arr['EOL-000002173414'] = 11927148;    $arr['EOL-000000271088'] = 11927159;
        $arr['EOL-000000013753'] = 45284459;    $arr['EOL-000002173437'] = 46702872;    $arr['EOL-000000393931'] = 47109635;    $arr['EOL-000000206020'] = 47129164;
        $arr['EOL-000000313356'] = 47135734;    $arr['EOL-000000268511'] = 47142374;    $arr['EOL-000000335746'] = 47146899;    $arr['EOL-000000139381'] = 47160531;
        $arr['EOL-000000519366'] = 47177223;    $arr['EOL-000000520622'] = 47179258;    $arr['EOL-000000021227'] = 47181406;    $arr['EOL-000000102279'] = 52185892;
        /* Also, the following taxa should have their EOLid field left blank. There are no good matches for them. Also, please put "manual" in the EOLidAnnotations field for them: */
        $arr['EOL-000000090957'] = '';  $arr['EOL-000000531663'] = '';  $arr['EOL-000002172659'] = '';  $arr['EOL-000000035438'] = '';
        $arr['EOL-000000006893'] = '';  $arr['EOL-000000531384'] = '';  $arr['EOL-000002321721'] = '';  $arr['EOL-000000091237'] = '';
        $arr['EOL-000000024275'] = '';  $arr['EOL-000002172624'] = '';  $arr['EOL-000000040604'] = '';  $arr['EOL-000000037708'] = '';
        $arr['EOL-000000029515'] = '';  $arr['EOL-000000035203'] = '';  $arr['EOL-000000010438'] = '';  $arr['EOL-000000021105'] = '';
        $arr['EOL-000000016315'] = '';  $arr['EOL-000000035925'] = '';  $arr['EOL-000000035202'] = '';
        if(isset($arr[$rec['taxonID']])) {
            $rec['EOLid'] = $arr[$rec['taxonID']];
            $rec['EOLidAnnotations'] = 'manual';
        }
        return $rec;
    }
    //==========================================================================end step 2
    //==========================================================================start step 1
    function step_1() //1. Match EOLid based on source identifiers
    {   
        $file_append = $this->main_path."/new_DH_after_step1.txt"; $WRITE = fopen($file_append, "w"); //will overwrite existing
        //loop new DH
        $i = 0;
        foreach(new FileIterator($this->file['new DH']) as $line_number => $line) {
            $i++; if(($i % 200000) == 0) echo "\n".number_format($i)." ";
            $row = explode("\t", $line);
            if($i == 1) {
                $fields = $row;
                $fields = array_filter($fields); print_r($fields);
                //special
                $fields[] = 'EOLid';
                $fields[] = 'EOLidAnnotations';
                fwrite($WRITE, implode("\t", $fields)."\n");
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
                [taxonID] => EOL-000000000001
                [source] => trunk:1bfce974-c660-4cf1-874a-bdffbf358c19,NCBI:1
                [furtherInformationURL] => 
                [parentNameUsageID] => 
                [scientificName] => Life
                [taxonRank] => clade
                [taxonRemarks] => 
                [datasetID] => trunk
                [canonicalName] => Life
            */
            $rec['EOLid'] = '';
            $rec['EOLidAnnotations'] = '';
            
            if(self::source_is_in_listof_sources($rec['source'], array('ictv', 'IOC', 'ODO'))) { //NEW. will not work in step 1, but will process in step 2.
                /* start writing */
                $headers = array_keys($rec);
                $save = array();
                foreach($headers as $head) $save[] = $rec[$head];
                fwrite($WRITE, implode("\t", $save)."\n");
                /* end writing */
                continue;
            }
            
            $source_ids = self::get_all_source_identifiers($rec['source']);
            // /* MySQL option
            if($EOL_id = self::get_EOL_id($source_ids)) {
                // echo "\nwith EOL_id [$EOL_id]\n";
                $rec['EOLid'] = $EOL_id;
                @$this->debug['totals']['matched EOLid count']++;
            }
            else { //No EOL_id
                /* obsolete per: https://eol-jira.bibalex.org/browse/TRAM-808?focusedCommentId=63456&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-63456
                if(self::source_is_in_listof_sources($rec['source'], array('ictv', 'IOC', 'ODO'))) {
                    $rec['EOLidAnnotations'] = 'unmatched';
                    @$this->debug['totals']['unmatched count']++;
                }
                */
            }
            // */

            /* debug
            if($rec['EOLid']) print_r($rec);
            if($rec['EOLidAnnotations']) {
                print_r($rec);
                exit("\nstopx\n");
            }
            */
            
            /* start writing */
            $headers = array_keys($rec);
            $save = array();
            foreach($headers as $head) $save[] = $rec[$head];
            fwrite($WRITE, implode("\t", $save)."\n");
            
            // if($i > 100) break; //debug only
        }
        fclose($WRITE);
        Functions::start_print_debug($this->debug, $this->resource_id."_after_step1");
        self::retire_old_DH_with_these_taxonIDs("old_DH_after_step1", $this->file['old DH']); //not yet implemented... may not be implemented anymore 
    }
    // /*
    private function retire_old_DH_with_these_taxonIDs($table, $sourcef) //$table is destination
    {
        echo "\nStart retiring process...[$table]\n";
        $file_append = $this->main_path."/".$table.".txt";
        $WRITE = fopen($file_append, "w"); //will overwrite existing
        $i = 0;
        foreach(new FileIterator($sourcef) as $line_number => $line) {
            $i++; if(($i % 200000) == 0) echo "\n".number_format($i)." ";
            $row = explode("\t", $line);
            if($i == 1) {
                $fields = $row;
                $fields = array_filter($fields); //print_r($fields);
                fwrite($WRITE, implode("\t", $fields)."\n");
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
            // print_r($rec); exit("\nstopx old_DH\n");
            /*Array(
                [taxonID] => -100000
                [acceptedNameUsageID] => -100000
                [parentNameUsageID] => -79407
                [scientificName] => Frescocyathus nagagreboensis Barta-Calmus, 1969
                [taxonRank] => species
                [source] => gbif:4943435
                [taxonomicStatus] => accepted
                [canonicalName] => Frescocyathus nagagreboensis
                [scientificNameAuthorship] => Barta-Calmus, 1969
                [scientificNameID] => 
                [taxonRemarks] => 
                [namePublishedIn] => 
                [furtherInformationURL] => https://www.gbif-uat.org/species/4943435
                [datasetID] => 6cfd67d6-4f9b-400b-8549-1933ac27936f
                [EOLid] => 
                [EOLidAnnotations] => 
                [Landmark] => 
            */
            if(isset($this->retired_old_DH_taxonID[$rec['taxonID']])) continue;
            else {
                /* start writing */
                $save = array();
                foreach($fields as $head) $save[] = $rec[$head];
                fwrite($WRITE, implode("\t", $save)."\n");
            }
        }
        fclose($WRITE);
        /* append to MySQL table */
        self::append_to_MySQL_table($table, $file_append);
    }
    function append_to_MySQL_table($table, $file_append)
    {
        echo "\nSaving [$table] records to MySQL...\n";
        if(filesize($file_append)) {
            //truncate first
            $sql = "TRUNCATE TABLE DWH.".$table.";";
            if($result = $this->mysqli->query($sql)) echo "\nTable truncated [$table] OK.\n";
            //load data to a blank table
            $sql = "LOAD data local infile '".$file_append."' into table DWH.".$table.";";
            if($result = $this->mysqli->query($sql)) echo "\nSaved table [$table] to MySQL\n";
        }
        else echo "\nNothing to save.\n";
    }
    // */
    private function source_is_in_listof_sources($source_str, $sources_list)
    {
        $sources = self::get_all_source_abbreviations($source_str);
        foreach($sources as $source) {
            if(in_array($source, $sources_list)) return true;
        }
        return false;
    }
    function get_all_source_identifiers($source)
    {
        $tmp = explode(",", $source);
        return array_map('trim', $tmp);
    }
    private function get_EOL_id($source_ids)
    {
        foreach($source_ids as $source_id) {
            if(!$source_id) continue;
            if($val = self::query_EOL_id($source_id)) return $val;
        }
    }
    private function query_EOL_id($source_id, $sql = false) //param $source_id is from new_DH
    {
        if($source_id) {
            $source_id_4sql = str_replace("'", "\'", $source_id);
            $sql = "SELECT m.EOL_id, o.taxonID FROM DWH.taxonID_source_ids o JOIN DWH.EOLid_map m ON o.taxonID = m.smasher_id WHERE o.source_id = '".$source_id_4sql."'";
        }
        $result = $this->mysqli->query($sql);
        while($result && $row=$result->fetch_assoc()) {
            if($source_id) {
                $this->retired_old_DH_taxonID[$row['taxonID']] = ''; //primarily used in step 1
                return $row['EOL_id'];
            }
            elseif($sql) {
                if(isset($this->used_EOLids)) { //limit EOLid assignment
                    if(isset($this->used_EOLids[$row['EOL_id']])) return false;
                    else return $row;
                }
                else return $row;
            }
        }
        return false;
    }
    private function get_all_source_abbreviations($sourceinfo)
    {
        $tmp = explode(",", $sourceinfo);
        foreach($tmp as $t) {
            $tmp2 = explode(":", $t);
            $final[$tmp2[0]] = '';
        }
        $final = array_keys($final);
        return array_map('trim', $final);
    }
    //==========================================================================end step 1
    function create_append_text()
    {
        $file_append = $this->main_path."/append_taxonID_source_id_2mysql.txt"; $WRITE = fopen($file_append, "w"); //will overwrite existing
        $i = 0;
        foreach(new FileIterator($this->file['old DH']) as $line_number => $line) {
            $i++; if(($i % 200000) == 0) echo "\n".number_format($i)." ";
            $row = explode("\t", $line);
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
            // print_r($rec); exit("\nstopx old_DH\n");
            /*Array(
                [taxonID] => -100000
                [acceptedNameUsageID] => -100000
                [parentNameUsageID] => -79407
                [scientificName] => Frescocyathus nagagreboensis Barta-Calmus, 1969
                [taxonRank] => species
                [source] => gbif:4943435
                [taxonomicStatus] => accepted
                [canonicalName] => Frescocyathus nagagreboensis
                [scientificNameAuthorship] => Barta-Calmus, 1969
                [scientificNameID] => 
                [taxonRemarks] => 
                [namePublishedIn] => 
                [furtherInformationURL] => https://www.gbif-uat.org/species/4943435
                [datasetID] => 6cfd67d6-4f9b-400b-8549-1933ac27936f
                [EOLid] => 
                [EOLidAnnotations] => 
                [Landmark] => 
            */
            $source_ids = self::get_all_source_identifiers($rec['source']);
            foreach($source_ids as $source_id) {
                $arr = array();
                $arr = array($rec['taxonID'], $source_id);
                fwrite($WRITE, implode("\t", $arr)."\n");
            }
            // if($i > 10) break; //debug only
        }
        fclose($WRITE);
        /* append to MySQL table */
        echo "\nSaving taxonID_source_ids records to MySQL...\n";
        if(filesize($file_append)) {
            $sql = "LOAD data local infile '".$file_append."' into table DWH.taxonID_source_ids;";
            if($result = $this->mysqli->query($sql)) echo "\nSaved OK to MySQL\n";
        }
        else echo "\nNothing to save.\n";
    }
}
?>