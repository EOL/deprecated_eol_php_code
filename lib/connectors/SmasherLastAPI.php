<?php
namespace php_active_record;
/* */
class SmasherLastAPI
{
    function __construct()
    {
        /* copied template
        $this->resource_id = $resource_id;
        $this->download_options = array('cache' => 1, 'download_wait_time' => 500000, 'timeout' => 10800, 'expire_seconds' => 60*60*1);
        $this->FishBase_collaborators = 'https://www.fishbase.de/Collaborators/CollaboratorsTopicList.php';
        */
    }
    function sheet1_Move_DH2_taxa_to_new_parent() //https://docs.google.com/spreadsheets/d/1D-AYca8hk3WCgAoslL15DvrJD4XD7NXT0d_tPdKxxVQ/edit#gid=0
    {   /* Sheet1: Move DH2 taxa to new parent:
        These are branches that got misplaced during the smasher run, so we need to move them to the correct parent.
        1. For each uid, change the parent_uid to the value in the new_parent_uid column.
        2. Then check the higherClassification for each modified taxon to make sure it corresponds to the path in the new_higherClassification column. 
        If there are any that don’t match, please give me a list of those taxa. It means that I’ve messed up the mapping and need to revisit it.
        */
        // step1
        // /*
        require_library('connectors/GoogleClientAPI');
        $func = new GoogleClientAPI(); //get_declared_classes(); will give you how to access all available classes
        $params['spreadsheetID'] = '1D-AYca8hk3WCgAoslL15DvrJD4XD7NXT0d_tPdKxxVQ';
        $params['range']         = 'Move DH2 taxa to new parent!A1:J394'; //where "A" is the starting column, "C" is the ending column, and "1" is the starting row.
        $arr = $func->access_google_sheet($params);
        //start massage array
        foreach($arr as $item) $info[$item[0]] = array('parent_uid' => $item[1], 'name' => $item[2], 'rank' => $item[3], 'sourceinfo' => $item[4]
        , 'uniqname' => $item[5], 'flags' => $item[6], 'new_parent_uid' => $item[7], 'new_parent_name' => $item[8], 'new_higherClassification' => $item[9]);
        // print_r($info); exit;
        // */
        /* sample $info [-159303] => Array(
                   [parent_uid] => -61672
                   [name] => Apochela
                   [rank] => order
                   [sourceinfo] => COL:a21adb9006dab4241631d279b9f68fa7
                   [uniqname] => 
                   [flags] => unplaced
                   [new_parent_uid] => -159304
                   [new_parent_name] => Eutardigrada
                   [new_higherClassification] => Life|Cellular Organisms|Eukaryota|Opisthokonta|Metazoa|Bilateria|Protostomia|Ecdysozoa|Tardigrada|Eutardigrada)
        */
        
        // /*
        $source = "/Volumes/AKiTiO4/d_w_h/last_smasher/test/taxonomy.tsv"; //results_2021_02_24.zip
        $destination = "/Volumes/AKiTiO4/d_w_h/last_smasher/test/final_taxonomy.tsv";
        $WRITE = Functions::file_open($destination, "w");
        $i = 0;
        foreach(new FileIterator($source) as $line => $row) { $i++;
            $rec = explode("\t|\t", $row);
            if($i == 1) {
                $fields = $rec;
                fwrite($WRITE, implode("\t", $fields) . "\n");
                continue;
            }
            else {
                $rek = array(); $k = 0;
                foreach($fields as $fld) {
                    if($fld) $rek[$fld] = @$rec[$k];
                    $k++;
                }
                $rek = array_map('trim', $rek);
            }
            // print_r($rek); exit;
            /*Array(
                [uid] => 4038af35-41da-469e-8806-40e60241bb58
                [parent_uid] => 
                [name] => Life
                [rank] => no rank
                [sourceinfo] => trunk:4038af35-41da-469e-8806-40e60241bb58,NCBI:1
                [uniqname] => 
                [flags] => 
            )*/
            $uid = $rek['uid'];
            if($sheet = @$info[$uid]) {
                // print_r($sheet); exit("[$uid]\n");
                /*Array(
                    [parent_uid] => c5a57415-49ef-4142-bf8c-c0459c602003
                    [name] => Cyanidiales
                    [rank] => order
                    [sourceinfo] => MIP:Cyanidiales,NCBI:265318
                    [uniqname] => 
                    [flags] => sibling_higher
                    [new_parent_uid] => -7570
                    [new_parent_name] => Bangiophyceae
                    [new_higherClassification] => Life|Cellular Organisms|Eukaryota|Archaeplastida|Rhodophyta|Bangiophyceae
                ) [-7557]
                1. For each uid, change the parent_uid to the value in the new_parent_uid column.
                */
                $rek['parent_uid'] = $sheet['new_parent_uid'];
            }
            //saving
            fwrite($WRITE, implode("\t", $rek) . "\n");
        }
        fclose($WRITE);
        // */
        $out = shell_exec("wc -l ".$source); echo "\nsource: $out\n";
        $out = shell_exec("wc -l ".$destination); echo "\ndestination: $out\n";
    }
    function sheet2_Merge_DH2_taxa()
    {   /*
        keep_uid	keep_name	            merge_uid	merge_name
        -937508	    Megachirella	        -873321	    Megachirella
        -2143159	Chacodelphys formosa	-2143193	Gracilinanus formosus
        
        Sheet2: Merge DH2 taxa:
        These are synonyms that failed to merge during the smasher run, so we need to merge them manually.
        1. For the taxa whose uid matches a value in the merge_uid column, delete the merge_uid taxon 
        and change the parent_uid of each of its children to the keep_uid.
        2. Add the sourceinfo value from the merge_uid taxon to the sourceinfo value of the keep_uid taxon.

        Eli: 
        first loop  => all parent_uid that = to merge_uid, replace parent_uid to keep_uid
                    => $merge_uid_sourceinfo[$merge_uid] = xxx
                    => $uid_will_update_sourceinfo[$keep_uid] = merge_uid
        2nd loop    => delete all rows where uid is = to merge_uid
                    => if found uid_will_update_sourceinfo then append merge_uid sourceinfo to current sourceinfo
        */
        
        require_library('connectors/GoogleClientAPI');
        $func = new GoogleClientAPI(); //get_declared_classes(); will give you how to access all available classes
        $params['spreadsheetID'] = '1D-AYca8hk3WCgAoslL15DvrJD4XD7NXT0d_tPdKxxVQ';
        $params['range']         = 'Merge DH2 taxa!A1:D2200'; //where "A" is the starting column, "C" is the ending column, and "1" is the starting row.
        $arr = $func->access_google_sheet($params);
        //start massage array
        foreach($arr as $item) {
            $rec = array('keep_uid' => $item[0], 'keep_name' => $item[1], 'merge_uid' => $item[2], 'merge_name' => $item[3]);
            // print_r($rec); exit("\nend3\n");
            /*Array(
                [keep_uid] => -838980
                [keep_name] => Xenellidae
                [merge_uid] => -726732
                [merge_name] => Xennellidae
            )*/
            $all_merge_uids[$rec['merge_uid']] = $rec;
            $uid_will_update_sourceinfo[$rec['keep_uid']] = $rec['merge_uid'];
            $all_keep_uids[$rec['keep_uid']] = $rec;
        }
        
        /* first loop */
        $source = "/Volumes/AKiTiO4/d_w_h/last_smasher/test/final_taxonomy.tsv";
        $i = 0;
        foreach(new FileIterator($source) as $line => $row) { $i++;
            $rec = explode("\t", $row);
            if($i == 1) {
                $fields = $rec;
                continue;
            }
            else {
                $rek = array(); $k = 0;
                foreach($fields as $fld) {
                    if($fld) $rek[$fld] = @$rec[$k];
                    $k++;
                }
                $rek = array_map('trim', $rek);
            }
            // print_r($rek); exit("\nend1\n");
            /*Array(
                [uid] => 4038af35-41da-469e-8806-40e60241bb58
                [parent_uid] => 
                [name] => Life
                [rank] => no rank
                [sourceinfo] => trunk:4038af35-41da-469e-8806-40e60241bb58,NCBI:1
                [uniqname] => 
                [flags] => 
            )
            Eli: 
            first loop  => all parent_uid that = to merge_uid, replace parent_uid to keep_uid
                        => $merge_uid_sourceinfo[$merge_uid] = xxx
                        => $uid_will_update_sourceinfo[$keep_uid] = merge_uid
            2nd loop    => delete all rows where uid is = to merge_uid
                        => if found uid_will_update_sourceinfo then append merge_uid sourceinfo to current sourceinfo
            */
            /* has berring below, not here.
            $parent_uid = $rek['parent_uid'];
            if($val = @$all_merge_uids[$parent_uid]) $rek['parent_uid'] = $val['keep_uid'];
            */
            
            $uid = $rek['uid'];
            if($val = @$all_merge_uids[$uid]) $merge_uid_sourceinfo[$uid] = $rek['sourceinfo'];
            if($val = @$all_keep_uids[$uid]) $uid_will_update_sourceinfo[$uid] = $val['merge_uid'];
        }
        /* end first loop */
        /* 2nd loop */
        $source      = "/Volumes/AKiTiO4/d_w_h/last_smasher/test/final_taxonomy.tsv";
        $destination = "/Volumes/AKiTiO4/d_w_h/last_smasher/test/final_taxonomy_2.tsv";
        $WRITE = Functions::file_open($destination, "w");
        $i = 0;
        foreach(new FileIterator($source) as $line => $row) { $i++;
            $rec = explode("\t", $row);
            if($i == 1) {
                $fields = $rec;
                fwrite($WRITE, implode("\t", $fields) . "\n");
                continue;
            }
            else {
                $rek = array(); $k = 0;
                foreach($fields as $fld) {
                    if($fld) $rek[$fld] = @$rec[$k];
                    $k++;
                }
                $rek = array_map('trim', $rek);
            }
            // print_r($rek); exit("\nend4\n");
            /*Array(
                [uid] => 4038af35-41da-469e-8806-40e60241bb58
                [parent_uid] => 
                [name] => Life
                [rank] => no rank
                [sourceinfo] => trunk:4038af35-41da-469e-8806-40e60241bb58,NCBI:1
                [uniqname] => 
                [flags] => 
            )
            Eli: 
            first loop  => all parent_uid that = to merge_uid, replace parent_uid to keep_uid
                        => $merge_uid_sourceinfo[$merge_uid] = xxx
                        => $uid_will_update_sourceinfo[$keep_uid] = merge_uid
            2nd loop    => delete all rows where uid is = to merge_uid
                        => if found uid_will_update_sourceinfo then append merge_uid sourceinfo to current sourceinfo
            */
            
            $parent_uid = $rek['parent_uid'];
            if($val = @$all_merge_uids[$parent_uid]) $rek['parent_uid'] = $val['keep_uid'];
            
            $uid = $rek['uid'];
            if($val = @$all_merge_uids[$uid]) continue;
            
            if($merge_uid = @$uid_will_update_sourceinfo[$uid]) {
                $rek['sourceinfo'] .= ",".$merge_uid_sourceinfo[$merge_uid];
            }
            
            //saving
            fwrite($WRITE, implode("\t", $rek) . "\n");
        }
        fclose($WRITE);
        /* end 2nd loop */
        $out = shell_exec("wc -l ".$source); echo "\nsource: $out\n";
        $out = shell_exec("wc -l ".$destination); echo "\ndestination: $out\n";
    }
    function sheet3_Split_DH2_taxa()
    {
        require_library('connectors/GoogleClientAPI');
        $func = new GoogleClientAPI(); //get_declared_classes(); will give you how to access all available classes
        $params['spreadsheetID'] = '1D-AYca8hk3WCgAoslL15DvrJD4XD7NXT0d_tPdKxxVQ';
        $params['range']         = 'Split DH2 taxa!A1:L9'; //where "A" is the starting column, "C" is the ending column, and "1" is the starting row.
        $arr = $func->access_google_sheet($params);
        //start massage array
        $i = 0;
        foreach($arr as $item) { $i++;
            // $rec = array('keep_uid' => $item[0], 'keep_name' => $item[1], 'merge_uid' => $item[2], 'merge_name' => $item[3]);
            // print_r($rec); exit("\nend4\n");
            // print_r($item);
            if($i == 1) $fields = $item;
            else {
                $rek = array(); $k = 0;
                foreach($fields as $fld) {
                    if($fld) $rek[$fld] = @$item[$k];
                    $k++;
                }
                @$ctr++;
                $rek['new_uid'] = "NEW_".$ctr;
                $rek = array_map('trim', $rek);
                // print_r($rek); exit;
                /*Array(
                    [uid] => -2069538
                    [parent_uid] => -1985654
                    [name] => Jubula
                    [rank] => genus
                    [sourceinfo] => IOC:3b16d65785cdea776b5dcc4fb59ad9c6,ITIS:15232
                    [uniqname] => 
                    [flags] => 
                    [higherClassification] => Life|Cellular Organisms|Eukaryota|Opisthokonta|Metazoa|Bilateria|Deuterostomia|Chordata|Vertebrata|Gnathostomata|Osteichthyes|Sarcopterygii|Tetrapoda|Amniota|Reptilia|Aves|Ornithurae|Neornithes|Neognathae|Neoaves|landbirds|Strigiformes|Strigidae|
                    [new_taxon] => ITIS:15232
                    [new_parent] => -491283
                    [new_higherClassification] => Life|Cellular Organisms|Eukaryota|Archaeplastida|Chloroplastida|Streptophyta|Embryophytes|Marchantiophyta|Jungermanniopsida|Porellales|Jubulineae|Jubulaceae
                    [move_children] => -2107775,-2107773,-2136797,-2136798,-2107774,-2107776,-2136799,-2136801,-2136802,-2136800,-2136804,-2136805,-2136803,-2107777
                )*/
                $info[$rek['uid']] = $rek;
                
                if($val = $rek['move_children']) {
                    $arr = explode(",", $val);
                    foreach($arr as $uid) $uids_with_new_parent[$uid] = $rek['new_uid'];
                }
            }
        }
        // print_r($info); exit;
        $source      = "/Volumes/AKiTiO4/d_w_h/last_smasher/test/final_taxonomy_2.tsv";
        $destination = "/Volumes/AKiTiO4/d_w_h/last_smasher/test/final_taxonomy_3.tsv";
        $WRITE = Functions::file_open($destination, "w");
        $i = 0;
        foreach(new FileIterator($source) as $line => $row) { $i++;
            $rec = explode("\t", $row);
            if($i == 1) {
                $fields = $rec;
                fwrite($WRITE, implode("\t", $fields) . "\n");
                continue;
            }
            else {
                $rek = array(); $k = 0;
                foreach($fields as $fld) {
                    if($fld) $rek[$fld] = @$rec[$k];
                    $k++;
                }
                $rek = array_map('trim', $rek);
            }
            // print_r($rek); exit("\nend4\n");
            $orig = $rek;
            /*Array(
                [uid] => 4038af35-41da-469e-8806-40e60241bb58
                [parent_uid] => 
                [name] => Life
                [rank] => no rank
                [sourceinfo] => trunk:4038af35-41da-469e-8806-40e60241bb58,NCBI:1
                [uniqname] => 
                [flags] => 
            )
            Sheet3: Split DH2 taxa
            These are taxa that should not have merged during the smasher run, so we need to split them into separate taxa. Luckily, this is a pretty short list.
            1. For each uid, remove the value from the new_taxon column from the sourceinfo value of the original taxon.
            2. Then create a new taxon (you can just make up a new uid, we’ll change the taxonIDs later anyway) 
                for the source taxon listed in the new_taxon column
            3. The new taxon should have the name & rank from the source file and the value from the new_taxon column in the sourceinfo column.
            4. Put the value from the new_parent column in the parent_uid column of the new taxon.
            5. Check the higherClassification of the new taxon to make sure it corresponds to the path in the new_higherClassification column. 
                Let me know if anything doesn't check out.
            6. The move_children column has a list of children of the original taxon which need to be moved to the new taxon: 
                For each uid listed here, change their parent_uid to the uid of the newly created taxon.
            */
            $uid = $rek['uid'];
            
            if($new_parent = @$uids_with_new_parent[$uid]) $rek['parent_uid'] = $new_parent; //#6 Sheet3
            
            if($sheet = @$info[$uid]) {
                // print_r($sheet); exit("\nend5\n");
                /*Array(
                    [uid] => 4e8d582b-61f3-4888-83f5-73746fd58e15
                    [parent_uid] => 0237d3e4-a0f9-4ac3-8c14-56bc8be94e42
                    [name] => Cepolidae
                    [rank] => family
                    [sourceinfo] => trunk:4e8d582b-61f3-4888-83f5-73746fd58e15,MOL:Cepolidae,COL:9972b706c30f9a91c6f70bd65abfce04
                    [uniqname] => 
                    [flags] => 
                    [higherClassification] => Life|Cellular Organisms|Eukaryota|Opisthokonta|Metazoa|Bilateria|Deuterostomia|Chordata|Vertebrata|Gnathostomata|Osteichthyes|Actinopterygii|Neopterygii|Teleostei|Euteleostei|Neoteleostei|Acanthopterygii|Priacanthiformes|
                    [new_taxon] => MOL:Cepolidae
                    [new_parent] => -517402
                    [new_higherClassification] => Life|Cellular Organisms|Eukaryota|Opisthokonta|Metazoa|Bilateria|Protostomia|Spiralia|Mollusca|Gastropoda|Heterobranchia|Stylommatophora
                    [move_children] => -1142283,-1142284,-1142285
                    [new_uid] => NEW_1
                )*/
                // /*1. For each uid, remove the value from the new_taxon column from the sourceinfo value of the original taxon.
                $sourceinfo = $sheet['sourceinfo'];
                if($sourceinfo != $rek['sourceinfo']) exit("\nInvestigate 001\n");
                $sourceinfo_arr = explode(",", $sourceinfo); //print_r($sourceinfo_arr);
                $sourceinfo_arr = array_diff($sourceinfo_arr, array($sheet['new_taxon'])); //print_r($sourceinfo_arr);
                // exit("\n".$sheet['new_taxon']."\n");
                $rek['sourceinfo'] = implode(",", $sourceinfo_arr);
                fwrite($WRITE, implode("\t", $rek) . "\n"); //saving
                // */
                /*
                2. Then create a new taxon (you can just make up a new uid, we’ll change the taxonIDs later anyway) 
                    for the source taxon listed in the new_taxon column
                3. The new taxon should have the name & rank from the source file and the value from the new_taxon column in the sourceinfo column.
                4. Put the value from the new_parent column in the parent_uid column of the new taxon.
                5. Check the higherClassification of the new taxon to make sure it corresponds to the path in the new_higherClassification column.
                    Let me know if anything doesn't check out.
                6. The move_children column has a list of children of the original taxon which need to be moved to the new taxon: 
                    For each uid listed here, change their parent_uid to the uid of the newly created taxon.
                */
                $rek = array();
                $rek = Array(
                    "uid" => $sheet['new_uid'],
                    "parent_uid" => $sheet['new_parent'],
                    "name" => $orig['name'],
                    "rank" => $orig['rank'],
                    "sourceinfo" => $sheet['new_taxon'],
                    "uniqname" => '',
                    "flags" => '');
                fwrite($WRITE, implode("\t", $rek) . "\n"); //saving
            }
            else {
                fwrite($WRITE, implode("\t", $rek) . "\n"); //saving
            }
            
        }
        fclose($WRITE);
        $out = shell_exec("wc -l ".$source); echo "\nsource: $out\n";
        $out = shell_exec("wc -l ".$destination); echo "\ndestination: $out\n";
    }


    /*
    function get_contributor_mappings($resource_id = false, $download_options = array())
    {
        if(!$download_options) $download_options = $this->download_options;
        // $download_options['expire_seconds'] = 0;
        self::initialize();
        if(!$resource_id) $resource_id = $this->resource_id;
        if($url = $this->mappings_url[$resource_id]) {}
        else exit("\nUndefined contributor mapping [$resource_id]\n");
        $local = Functions::save_remote_file_to_local($url, $download_options);
        $i = 0;
        foreach(new FileIterator($local) as $line_number => $line) {
            $line = explode("\t", $line); $i++; if(($i % 200000) == 0) echo "\n".number_format($i);
            if($i == 1) $fields = $line;
            else {
                if(!$line[0]) break;
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k]; $k++;
                }
                $rec = array_map('trim', $rec);
                // print_r($rec); exit;
                $final[$rec['Label']] = $rec['URI'];
            }
        }
        unlink($local); // print_r($final);
        return $final;
    }
    function get_collab_name_and_ID_from_FishBase()
    {
        $options = $this->download_options;
        $options['expire_seconds'] = 60*60*24*30; //1 month
        $html = Functions::lookup_with_cache($this->FishBase_collaborators, $options);
        if(Functions::is_production()) $path = "/extra/other_files/contributor_mappings/";
        else                           $path = "/Volumes/AKiTiO4/other_files/contributor_mappings/";
        if(!is_dir($path)) mkdir($path);
        $file = $path.'FishBase_contributors.tsv';
        $handle = fopen($file, "w");
        fwrite($handle, implode("\t", array('Label','URI')) . "\n");
        if(preg_match_all("/CollaboratorSummary.php\?id\=(.*?)<\/a>/ims", $html, $a)) { // print_r($a[1]);
            foreach($a[1] as $tmp) { //2290">Chae, Byung-Soo
                $arr = explode('">', $tmp);
                $id = $arr[0];
                $label = $arr[1];
                $tmp = explode(",", $label);
                $tmp = array_map('trim', $tmp);
                $label = trim(@$tmp[1]." ".$tmp[0]); //to make it "Eli E. Agbayani"
                $label = Functions::remove_whitespace($label);
                $uri = "https://www.fishbase.de/collaborators/CollaboratorSummary.php?id=$id";
                if(!isset($final[$label])) {
                    fwrite($handle, implode("\t", array($label,$uri)) . "\n");
                    $final[$label] = '';
                }
            }
        }
        fclose($handle);
    }
    */
}
?>