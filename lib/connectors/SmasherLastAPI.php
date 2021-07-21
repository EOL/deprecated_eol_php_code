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
    function sheet4_Delete_DH2_taxa()
    {
        /* Sheet4: Delete DH2 taxa: These are mostly duplicates and other things that snuck in that I should have caught earlier.
        Please delete all of these taxa and their children. Some of the children got moved to a new parent.
        To avoid deleting these, please delete taxa only after completing the previous modifications.
        */
        $source      = "/Volumes/AKiTiO4/d_w_h/last_smasher/test/final_taxonomy_3.tsv";
        $destination = "/Volumes/AKiTiO4/d_w_h/last_smasher/test/final_taxonomy_4.tsv"; //final version

        $parent_ids = self::get_uids_from_sheet4("Delete DH2 taxa!A1:I131");
        // $parent_ids = array('-941485'); //this will come from the sheet4     //-992881           forced value
        // $parent_ids = array('-1596984'); //this will come from the sheet4    //has 2 children    forced value
        
        $this->parentID_taxonID = self::get_ids($source);
        
        require_library('connectors/PaleoDBAPI_v2');
        $func = new PaleoDBAPI_v2("");
        $descendant_ids = $func->get_all_descendants_of_these_parents($parent_ids, $this->parentID_taxonID);
        print_r($descendant_ids); 
        $exclude_uids = array_merge($parent_ids, $descendant_ids);
        echo "\nparent_ids: ".count($parent_ids)."\n";
        echo "\ndescendant_ids: ".count($descendant_ids)."\n";
        echo "\nexclude_uids: ".count($exclude_uids)."\n";
        
        /* start final deletion */
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
            )*/
            if(in_array($rek['uid'], $exclude_uids)) continue;
            fwrite($WRITE, implode("\t", $rek) . "\n"); //saving
        }
        fclose($WRITE);
        $out = shell_exec("wc -l ".$source); echo "\nsource: $out\n";
        $out = shell_exec("wc -l ".$destination); echo "\ndestination: $out\n";
    }
    private function get_uids_from_sheet4($range, $ret = 'normal')
    {
        require_library('connectors/GoogleClientAPI');
        $func = new GoogleClientAPI(); //get_declared_classes(); will give you how to access all available classes
        $params['spreadsheetID'] = '1D-AYca8hk3WCgAoslL15DvrJD4XD7NXT0d_tPdKxxVQ';
        $params['range']         = $range; //where "A" is the starting column, "C" is the ending column, and "1" is the starting row.
        $arr = $func->access_google_sheet($params);
        //start massage array
        $i = 0;
        foreach($arr as $item) { $i++;
            if($i > 1) $final[$item[0]] = $item[2];
        }
        // print_r($final); echo "\n".count($final)."\n";
        if($ret == 'normal') return array_keys($final);
        elseif($ret == 'special') return $final;
    }
    private function get_ids($source)
    {
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
            // print_r($rek); exit("\nend5\n");
            /*Array(
                [uid] => 4038af35-41da-469e-8806-40e60241bb58
                [parent_uid] => 
                [name] => Life
                [rank] => no rank
                [sourceinfo] => trunk:4038af35-41da-469e-8806-40e60241bb58,NCBI:1
                [uniqname] => 
                [flags] => 
            )*/
            $parent_id = @$rek["parent_uid"];
            $taxon_id = @$rek["uid"];
            if($parent_id && $taxon_id) $final[$parent_id][] = $taxon_id;
        }
        return $final;
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
    function July7_num_1_2()
    {
        $source      = "/Volumes/AKiTiO4/d_w_h/last_smasher/test/final_taxonomy_4.tsv";
        $destination = "/Volumes/AKiTiO4/d_w_h/last_smasher/test/final_taxonomy_5.tsv"; //final version
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
            // print_r($rek); exit("\n-end Jul7_num1-\n");
            /*Array(
                [uid] => 4038af35-41da-469e-8806-40e60241bb58
                [parent_uid] => 
                [name] => Life
                [rank] => no rank
                [sourceinfo] => trunk:4038af35-41da-469e-8806-40e60241bb58,NCBI:1
                [uniqname] => 
                [flags] => 
            )*/
            /* 1. Fordonia: Somehow the parent for one of the genera in the split got messed up here.
            The MAM:Fordonia should keep its original parent. Here’s the correct record for this genus:
            -1986500 -1592527 Fordonia genus MAM:Fordonia
            And this species should be moved to be the child of the new taxon like this:
            -2071721 NEW_3 Fordonia leucobalia species ITIS:700427 */
            if($rek['uid'] == '-1986500') $rek['parent_uid'] = '-1592527';
            if($rek['uid'] == '-2071721') $rek['parent_uid'] = 'NEW_3';

            /* 2. A few of the new taxa have the wrong rank. They should get their rank from the original resource file.
            Here are the records with the correct ranks:
            NEW_8 -159304 Parachela order COL:786d46027cb9420635375ae171e42a2e
            NEW_4 -61648 Penicillaria order WOR:366466
            NEW_7 -256841 Bdelloidea order COL:3103df03b02e432090b3a99ff216dc50 */
            if($rek['uid'] == 'NEW_8') $rek['rank'] = 'order';
            if($rek['uid'] == 'NEW_4') $rek['rank'] = 'order';
            if($rek['uid'] == 'NEW_7') $rek['rank'] = 'order';
            
            /*Also, I found a couple more taxa where we need to update the parent. Here are the corrected records:
            -62063 -62065 Amphifila genus MIP:Amphifila sibling_higher
            83e8b925-7c7d-46c4-a718-9dd7b8f0e29b 881f7dd4-99b6-49d3-a442-cabb77c4bfb5 Derodontiformia series trunk:83e8b925-7c7d-46c4-a718-9dd7b8f0e29b */
            if($rek['uid'] == '-62063') $rek['parent_uid'] = '-62065';
            if($rek['uid'] == '83e8b925-7c7d-46c4-a718-9dd7b8f0e29b') $rek['parent_uid'] = '881f7dd4-99b6-49d3-a442-cabb77c4bfb5';
            
            fwrite($WRITE, implode("\t", $rek) . "\n"); //saving
        }
        fclose($WRITE);
        $out = shell_exec("wc -l ".$source); echo "\nsource: $out\n";
        $out = shell_exec("wc -l ".$destination); echo "\ndestination: $out\n";
    }
    function July7_num_2_delete()
    {
        /*And there are a bunch more duplicates and dead-end branches to delete in the Delete #2 sheet of 
        the Fix DH2 smasher doc: https://docs.google.com/spreadsheets/d/1D-AYca8hk3WCgAoslL15DvrJD4XD7NXT0d_tPdKxxVQ/edit#gid=1514419992*/
        $source      = "/Volumes/AKiTiO4/d_w_h/last_smasher/test/final_taxonomy_5.tsv";
        $destination = "/Volumes/AKiTiO4/d_w_h/last_smasher/test/final_taxonomy_6.tsv"; //final version, supposedly

        $parent_ids = self::get_uids_from_sheet4("Delete #2!A1:I734");
        // $parent_ids = array('xxx');            forced value
        
        $this->parentID_taxonID = self::get_ids($source);
        
        require_library('connectors/PaleoDBAPI_v2');
        $func = new PaleoDBAPI_v2("");
        $descendant_ids = $func->get_all_descendants_of_these_parents($parent_ids, $this->parentID_taxonID);
        print_r($descendant_ids); 
        $exclude_uids = array_merge($parent_ids, $descendant_ids);
        echo "\nparent_ids: ".count($parent_ids)."\n";
        echo "\ndescendant_ids: ".count($descendant_ids)."\n";
        echo "\nexclude_uids: ".count($exclude_uids)."\n";
        
        /* start final deletion */
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
            /**/
            if(in_array($rek['uid'], $exclude_uids)) continue;
            fwrite($WRITE, implode("\t", $rek) . "\n"); //saving
        }
        fclose($WRITE);
        $out = shell_exec("wc -l ".$source); echo "\nsource: $out\n";
        $out = shell_exec("wc -l ".$destination); echo "\ndestination: $out\n";
    }
    /* ------------------------------ START TRAM-993 ------------------------------ */
    function A_Clean_up_deadend_branches()
    {
        /* A. Clean up deadend branches
        ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        There’s a new ‘Remove dead-end branches’ worksheet in the Fix DH2 smasher workbook: https://docs.google.com/spreadsheets/d/1D-AYca8hk3WCgAoslL15DvrJD4XD7NXT0d_tPdKxxVQ/edit#gid=798495355
        Please remove all of these taxa from the taxonomy file. They are all childless. */
        $source      = "/Volumes/AKiTiO4/d_w_h/last_smasher/TRAM_993/final_taxonomy_6.tsv";
        $destination = "/Volumes/AKiTiO4/d_w_h/last_smasher/TRAM_993/final_taxonomy_7.tsv";
        // /* orig
        $parent_ids = self::get_uids_from_sheet4("Remove dead-end branches!A1:H731"); // print_r($parent_ids); exit;
        // $parent_ids = array('xxx');            forced value
        $this->parentID_taxonID = self::get_ids($source); //print_r($this->parentID_taxonID); exit;
        require_library('connectors/PaleoDBAPI_v2');
        $func = new PaleoDBAPI_v2("");
        $descendant_ids = $func->get_all_descendants_of_these_parents($parent_ids, $this->parentID_taxonID); // echo "\ndescendant_ids: "; print_r($descendant_ids);
        $exclude_uids = array_merge($parent_ids, $descendant_ids);
        echo "\nparent_ids: ".count($parent_ids)."\n";
        echo "\ndescendant_ids: ".count($descendant_ids)."\n";
        echo "\nexclude_uids: ".count($exclude_uids)."\n";
        // */
        /* debug
        $parent_ids = array();
        $descendant_ids = array();
        $exclude_uids = array();
        */
        /* start final deletion */
        $WRITE = Functions::file_open($destination, "w");
        $i = 0;
        foreach(new FileIterator($source) as $line => $row) { $i++; if(($i % 200000) == 0) echo "\n".number_format($i);
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
            )*/
            if(in_array($rek['uid'], $exclude_uids)) continue;
            
            if(stripos($rek['flags'], "was_container") !== false) { //string is found
                @$was_container++;
                continue;
            }
            if($rek['uid']) fwrite($WRITE, implode("\t", $rek) . "\n"); //saving
        }
        fclose($WRITE);
        $out = shell_exec("wc -l ".$source); echo "\nsource: $out\n";
        $out = shell_exec("wc -l ".$destination); echo "\ndestination: $out\n";
        echo "\n was_container: [$was_container]\n";
    }
    function B2_Create_new_containers_for_incertae_sedis()
    {   /*2. Create new containers for incertae_sedis taxa
        There’s a new sheet called “containers” in the Fix DH2 smasher doc: 
        For each of the taxa in this list, create a new child and call it "unclassified name-of-parent". 
        Then find all the children of that parent (the original taxon from the list) and move all those children 
        into the unclassified branch that have an incertae_sedis flag. Important: don’t move taxa that have only an incertae_sedis_inherited flag, 
        but do move taxa that have both an incertae_sedis flag and an incertae_sedis_inherited flag.
        Example:
        taxon: -159349 ba6ce1ac-9f2d-4166-b536-a81c3f9e418d Rhabdocoela order COL:8860fcb2e22a083696e895c0ec08b306
        Create a new child of -159349 and name it “unclassified Rhabdocoela.”
        Rhabdocoela has 47 children (assuming -256639 was removed in the previous step). 
        Of these 34 have the incertae_sedis_inherited flag only -> leave those alone. 
        The other 13 have both flags: incertae_sedis,incertae_sedis_inherited. 
        These taxa should have their parent changed to the “unclassified Rhabdocoela” node.
        
        Metadata for new container taxa (see below): Create a unique ID, we’ll change it later. 
        The canonicalName value should be the same as scientificName, 
        the taxonRank should be blank, the datasetID should be trunk.
        */
        $source      = "/Volumes/AKiTiO4/d_w_h/last_smasher/TRAM_993/final_taxonomy_7.tsv";
        $destination = "/Volumes/AKiTiO4/d_w_h/last_smasher/TRAM_993/final_taxonomy_8.tsv";

        // /* orig
        $ret = self::get_uids_from_sheet4("containers!A1:G129", 'special'); 
        $parent_ids = array_keys($ret); //print_r($parent_ids);
        $parent_id_name = $ret;         //print_r($parent_id_name); exit; //e.g. Array([891fa241-de7e-419d-9272-fba02e073fb2] => Ascomycota)
        // */
        /* during dev. only
        $parent_ids = array('-159349', '374d5b3e-09c9-4ca0-a2fe-38f454c76d1f'); //forced value
        $parent_ids = array('-159349'); //forced value
        $parent_id_name['-159349'] = 'Rhabdocoela';
        $parent_id_name['374d5b3e-09c9-4ca0-a2fe-38f454c76d1f'] = 'Squamata';
        */
        
        $this->parentID_taxonID = self::get_ids($source); //print_r($this->parentID_taxonID); exit; //used when getting children

        /* step 1: loop to parent_ids */
        // foreach($parent_ids as $parent_id) {
        foreach($parent_id_name as $parent_id => $name) {
            if($children = @$this->parentID_taxonID[$parent_id]) { // print_r($children);
                echo "\n children of $parent_id: ".count($children)."\n";
                foreach($children as $child) $all_children[$child] = $name; //$parent_id;
            }
        }
        // print_r($all_children); exit("\n".count($all_children)."\n");

        /* step 2: loop taxonomy file */
        $WRITE = Functions::file_open($destination, "w");
        $i = 0;
        foreach(new FileIterator($source) as $line => $row) { $i++; if(($i % 200000) == 0) echo "\n".number_format($i);
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
            )*/
            $uid = $rek['uid'];
            if($parent_name = @$all_children[$uid]) {
                if(self::has_both_incertae_flags($rek['flags'])) $rek['parent_uid'] = "unclassified_".$parent_name;
            }
            
            if($rek['uid']) fwrite($WRITE, implode("\t", $rek) . "\n"); //saving
        }
        
        //add the new containers
        foreach($parent_id_name as $parent_id => $name) {
            $save = array();
            $save = Array(
                "uid" => 'unclassified_'.$name,
                "parent_uid" => $parent_id,
                "name" => "unclassified $name",
                "rank" => '',
                "sourceinfo" => '',
                "uniqname" => '',
                "flags" => '');
            fwrite($WRITE, implode("\t", $save) . "\n"); //saving
        }
        
        fclose($WRITE);
        $out = shell_exec("wc -l ".$source); echo "\nsource: $out\n";
        $out = shell_exec("wc -l ".$destination); echo "\ndestination: $out\n";
    }
    private function has_both_incertae_flags($flag)
    {   // incertae_sedis,incertae_sedis_inherited
        $arr = explode(",", $flag);
        $arr = array_map('trim', $arr);
        if(in_array('incertae_sedis', $arr) && in_array('incertae_sedis_inherited', $arr)) return true;
        return false;
    }
}
?>