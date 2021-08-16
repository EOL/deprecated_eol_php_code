<?php
namespace php_active_record;
/* */
class SmasherLastAPI
{
    function __construct($folder)
    {
        if($folder) {
            $this->resource_id = $folder;
            $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
            $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        }
        $this->path['trunk'] = '/Volumes/AKiTiO4/d_w_h/2021_02/dhtrunk/taxon.txt';
        $this->path['ictv'] = '/Volumes/AKiTiO4/d_w_h/2021_02/ICTV-virus_taxonomy-with-higherClassification/taxon.tab';
        $this->path['WOR'] = '/Volumes/AKiTiO4/d_w_h/2021_02/WoRMS_DH/taxon.tab';
        $this->path['COL'] = '/Volumes/AKiTiO4/d_w_h/2021_02/Catalogue_of_Life_DH_2019/taxon.tab';
        $this->path['ANN'] = '/Volumes/AKiTiO4/d_w_h/2021_02/eolannelidapatch/taxon.txt';
        $this->path['MIP'] = '/Volumes/AKiTiO4/d_w_h/2021_02/eolmicrobespatch/taxa.txt';
        $this->path['NCBI'] = '/Volumes/AKiTiO4/d_w_h/2021_02/NCBI_Taxonomy_Harvest_DH/taxon.tab';
        $this->path['dino'] = '/Volumes/AKiTiO4/d_w_h/2021_02/eoldinosauriapatch/taxa.txt';
        $this->path['IOC'] = '/Volumes/AKiTiO4/d_w_h/2021_02/ioc-birdlist/taxon.tab';
        $this->path['ODO'] = '/Volumes/AKiTiO4/d_w_h/2021_02/worldodonatalist/taxa.txt';
        $this->path['BOM'] = '/Volumes/AKiTiO4/d_w_h/2021_02/kitchingetal2018/taxa.txt';
        $this->path['SPR'] = '/Volumes/AKiTiO4/d_w_h/2021_02/Collembola_DH/taxon.tab';
        $this->path['ITIS'] = '/Volumes/AKiTiO4/d_w_h/2021_02/itis_2020-12-01/taxon.tab';
        $this->path['MOL'] = '/Volumes/AKiTiO4/d_w_h/2021_02/eolmolluscapatch/taxa.txt';
        $this->path['LIZ'] = '/Volumes/AKiTiO4/d_w_h/2021_02/eollizardspatch/EOLlizardPatch.txt';
        $this->path['MAM'] = '/Volumes/AKiTiO4/d_w_h/2021_02/eolmammalpatch/taxa.txt';
        $this->path['ONY'] = '/Volumes/AKiTiO4/d_w_h/2021_02/onychophora/taxa.txt';
        $this->path['TRI'] = '/Volumes/AKiTiO4/d_w_h/2021_02/eoltrilobitespatch/taxa.txt';
        $this->path['VSP'] = '/Volumes/AKiTiO4/d_w_h/2021_02/eolvespoideapatch/taxa.txt';
        $this->path['ERE'] = '/Volumes/AKiTiO4/d_w_h/2021_02/eoldynamichierarchyerebidaepatch/taxon.txt';
        $this->path['COC'] = '/Volumes/AKiTiO4/d_w_h/2021_02/eolcoccinelloideapatch/taxa.txt';
        $this->path['CRU'] = '/Volumes/AKiTiO4/d_w_h/2021_02/eolcrustaceapatch/taxa.txt';
        $this->path['COL_2'] = '/Volumes/AKiTiO4/web/cp/COL/2019-annual/taxa.txt';                  // Catalogue_of_Life_DH_2019
        $this->path['SPR_2'] = '/Volumes/AKiTiO4/web/cp/COL/2020-08-01-archive-complete/taxa.txt';  // Collembola_DH
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
        $parent_ids2 = self::get_uids_from_sheet4("dead-end branches2!A1:H13"); //print_r($parent_ids2); exit;
        $parent_ids = array_merge($parent_ids, $parent_ids2);
        
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
        echo "\nparents from sheet: ".count($parent_ids)."\n";
        // */
        /* during dev. only
        // $parent_ids = array('-159349', '374d5b3e-09c9-4ca0-a2fe-38f454c76d1f'); //forced value
        $parent_ids = array('-159349'); //forced value
        $parent_id_name['-159349'] = 'Rhabdocoela';
        // $parent_id_name['374d5b3e-09c9-4ca0-a2fe-38f454c76d1f'] = 'Squamata';
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
        if(in_array('incertae_sedis', $arr)) return true;
        return false;
    }
    function C_Fetch_metadata()
    {
        self::build_source_taxa_records();
        self::build_source_taxa_records_COL_SPR('COL_2');
        self::build_source_taxa_records_COL_SPR('SPR_2');
        self::parse_source_Smasher_file();
    }
    function parse_source_Smasher_file()
    {
        $source = "/Volumes/AKiTiO4/d_w_h/last_smasher/TRAM_993/final_taxonomy_8.tsv"; $i = 0;
        foreach(new FileIterator($source) as $line => $row) { $i++; if(($i % 200000) == 0) echo "\n".number_format($i);
            $rec = explode("\t", $row);
            if($i == 1) {
                $fields = $rec;
                continue;
            }
            else {
                // if($i < 10900) continue; //dev only
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
            
            $rec = array();
            /*http://rs.tdwg.org/dwc/terms/taxonID
            For now, keep the smasher identifiers. We will turn those into real identifiers once DH 2.1 is fully assembled.*/
            $rec['taxonID'] = $rek['uid'];
            
            /*http://rs.tdwg.org/dwc/terms/acceptedNameUsageID
            Blank for taxa from the smasher taxonomy*/
            $rec['acceptedNameUsageID'] = '';
            
            /*http://rs.tdwg.org/dwc/terms/parentNameUsageID
            From the smasher file*/
            $rec['parentNameUsageID'] = $rek['parent_uid'];
            
            /*
            http://rs.tdwg.org/dwc/terms/scientificName --- Fetch the scientificName field from the source file
            http://rs.tdwg.org/dwc/terms/taxonRank      --- Fetch the taxonRank value from the source file
            */
            
            // if(substr($rec['taxonID'],0,13) == 'unclassified_') {}
            // else { //the rest
            // }

            $ret_SI = self::parse_sourceinfo($rek['sourceinfo']); //print_r($ret_SI); exit;
            $source_name = $ret_SI['source_name'];
            /*Array(
                [source_name] => MIP
                [taxon_id] => Glaucocystis-duplex
            )*/
            // if($taxonID == '6f8a846c-9528-42dc-85e4-55527bf9b8d5') {
            //     print_r($ret_SI); //exit("\n[$taxonID]\n");
            // }
            
            /* during dev only
            $this->debug['first source'][$ret_SI['source_name']] = '';
            // continue;
            */
            
            /* obsolete, too long to process
            $ret = self::fetch_from_source('scientificName', $rek['sourceinfo']);
            */
            if($ret_SI['taxon_id']) {
                if($ret = $this->recs[$source_name][$ret_SI['taxon_id']]) {
                    // = array($rec['scientificName'], $taxonRank, $taxonRemarks, $datasetID, $furtherInformationURL);
                    $scientificName = $ret[0];
                    $taxonRank = $ret[1];
                    $taxonRemarks = $ret[2];
                    $datasetID = $ret[3];
                    $furtherInformationURL = $ret[4];
                }
                else { //should not go here...
                    print_r($rek); print_r($ret_SI);
                    exit("\nrec not found A:\n");
                }
            }
            else {
                /* Metadata for new container taxa (see below): 
                    Create a unique ID, we’ll change it later. 
                    The canonicalName value should be the same as scientificName, 
                    the taxonRank should be blank, 
                    the datasetID should be trunk. */
                if(substr($rek['uid'],0,13) == 'unclassified_') {
                    $scientificName = $rek['name'];
                    $taxonRank = '';
                    $taxonRemarks = '';
                    $datasetID = 'trunk';
                    $furtherInformationURL = '';
                }
                else {
                    if($rek['uid']) { //valid to investigate more...
                        print_r($rek); print_r($ret_SI);
                        exit("\nrec not found B:\n");
                    }
                    else continue; //ok to ignore
                }
            }
            
            $rec['scientificName']  = $scientificName;
            $rec['taxonRank']       = $taxonRank;
            $rec['taxonRemarks']    = $taxonRemarks;
            $rec['taxonRemarks'] = self::format_taxonRemarks($rec['taxonRemarks'], $rek['flags']);
            /*
            http://rs.tdwg.org/dwc/terms/taxonRemarks
            Fetch the taxonRemarks value from the source file for those sources that have them: IOC, ODO, BOM, SPR, ITIS, WOR, COL.
            Also, if smasher has flagged a taxon as incertae_sedis (but not incertae_sedis_inherited) 
                please append “incertae sedis” to the taxonRemarks field. 
            If there is already content in that field (fetched from the source file), 
                separate the “incertae sedis” entry from the other content with a semicolon 
                unless that content already ends in a semicolon or a period.
            */
            
            /*http://rs.gbif.org/terms/1.0/canonicalName
            Please copy the value of the smasher name field into the canonicalName field of the DH file.*/
            $rec['canonicalName'] = $rek['name'];
            
            /*http://rs.tdwg.org/dwc/terms/taxonomicStatus
            Use “accepted” as the value for all taxa in the smasher taxonomy.*/
            $rec['taxonomicStatus'] = 'accepted';
            
            /*http://rs.tdwg.org/dwc/terms/datasetID
            Use our data set acronyms (trunk, ictv, MAM, NCBI, etc.) 
                for the following data sets: trunk,ictv, IOC, MAM, LIZ, ODO, BOM, ERE, COC, VSP, ONY, ITIS, NCBI, WOR, CRU, MOL

            The following data sets have line by line dataset or datasetID values, please use those for the DH datasetID values: dino, ANN, MIP

            For TRI, please use pbdb as the datasetID value

            For COL & SPR, we want to use the datasetID value from the original COL data files. 
                These are not in the SPR or COL for DH files, so you have to fetch them from the original COL export files. 
                Also, please use COL- as a prescript for these IDs. For example, if the COL datasetID is 5, our datasetID would be COL-5. 
                If the COL datasetID is Species 2000 or if there is no datasetID available, simply use COL as the datasetID for both COL 
                    and SPR derived taxa.*/

            $rec['datasetID'] = $datasetID;
            if(in_array($ret_SI['source_name'], array("trunk", "ictv", "IOC", "MAM", "LIZ", "ODO", "BOM", "ERE", "COC", "VSP", "ONY", "ITIS", "NCBI", "WOR", "CRU", "MOL"))) {
                                                                                    $rec['datasetID'] = $ret_SI['source_name'];
            }
            elseif(in_array($ret_SI['source_name'], array("dino", "ANN", "MIP")))   $rec['datasetID'] = $datasetID;
            elseif($ret_SI['source_name'] == 'TRI')                                 $rec['datasetID'] = 'pbdb';
            elseif(in_array($ret_SI['source_name'], array("COL", "SPR"))) {
                if($val = $this->recs[$source_name."_2"][$ret_SI['taxon_id']]) $rec['datasetID'] = $val;
                else {
                    print_r($rek); print_r($ret_SI);
                    exit("\ncol spr wrong\n");
                }
            }
            
            // Catalogue_of_Life_DH_2019   --- /Volumes/AKiTiO4/web/cp/COL/2019-annual/taxa.txt 
            // Collembola_DH               --- /Volumes/AKiTiO4/web/cp/COL/2020-08-01-archive-complete/taxa.txt 
            
            
            /*http://purl.org/dc/terms/source
            This should be the entire sourceinfo value from the smasher file.*/
            $rec['source'] = $rek['sourceinfo'];
            
            /*http://rs.tdwg.org/ac/terms/furtherInformationURL
            Fetch the furtherInformationURL values from the source file 
                for those sources that have this field: dino, ODO, BOM, ANN, TRI, ITIS, MIP, NCBI, WOR
            Fetch the value from the “source” field and put it in the furtherInformationURL field of the DH file 
                for the following sources: ictv, IOC */
            $rec['furtherInformationURL'] = $furtherInformationURL;

            /* To be added later
            http://eol.org/schema/EOLid
            http://eol.org/schema/EOLidAnnotations
            http://eol.org/schema/Landmark */
            $rec['EOLid'] = '';
            $rec['EOLidAnnotations'] = '';
            $rec['Landmark'] = '';
            
            // print_r($rec); //exit;
            // /*
            $tax = new \eol_schema\Taxon();
            $tax->taxonID = $rec['taxonID'];
            $tax->scientificName = $rec['scientificName'];
            $tax->canonicalName = $rec['canonicalName'];
            $tax->parentNameUsageID = $rec['parentNameUsageID'];
            $tax->acceptedNameUsageID = $rec['acceptedNameUsageID'];
            $tax->taxonRank = $rec['taxonRank'];
            $tax->taxonomicStatus = $rec['taxonomicStatus'];
            $tax->source = $rec['source'];
            $tax->furtherInformationURL = $rec['furtherInformationURL'];
            $tax->taxonRemarks = $rec['taxonRemarks'];
            $tax->datasetID = $rec['datasetID'];
            $tax->EOLid = $rec['EOLid'];
            $tax->EOLidAnnotations = $rec['EOLidAnnotations'];
            // $tax->higherClassification = $rec['higherClassification'];
            $tax->Landmark = $rec['Landmark'];
            $this->archive_builder->write_object_to_file($tax);
            // */
            // if($i == 2000) break;
        }
        // print_r($this->debug);
        // exit("\nstop muna...\n");
        $this->archive_builder->finalize(true);
    }
    private function fetch_from_source($sought_field, $sourceinfo)
    {
        $ret = self::parse_sourceinfo($sourceinfo); // print_r($ret); exit;
        /*Array(
            [source_name] => trunk
            [taxon_id] => 4038af35-41da-469e-8806-40e60241bb58
        )*/
        $source_name = $ret['source_name'];
        $taxon_id = $ret['taxon_id'];
        
        $dir_path_taxa_file = $this->path[$source_name];
        
        if(!isset($dir_path_taxa_file)) exit("\nsource_name not yet initialized [$source_name]\n");
        return self::get_field_value_from_source($sought_field, $dir_path_taxa_file, $taxon_id, $source_name);
    }
    function parse_sourceinfo($sourceinfo)
    {   // e.g. trunk:4038af35-41da-469e-8806-40e60241bb58,NCBI:1 | ictv:ICTV:201902639
        $arr = explode(",", $sourceinfo);
        $choice = $arr[0];
        $choice = explode(":", $choice);
        $source_name = $choice[0];
        array_shift($choice);
        $taxon_id = implode(":", $choice);
        return array('source_name' => $source_name, 'taxon_id' => $taxon_id);
    }
    function get_field_value_from_source($sought_field, $txtfile, $taxon_id, $source_name)
    {   // echo "\nReading [$txtfile]...\n";
        $i = 0;
        foreach(new FileIterator($txtfile) as $line_number => $line) {
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
            $rec = array_map('trim', $rec); //print_r($rec); exit("\nstopx\n");
            /*Array(
                [taxonID] => 4038af35-41da-469e-8806-40e60241bb58
                [taxonRank] => no rank
                [canonicalName] => Life
                [scientificNameAuthorship] => 
                [scientificName] => Life
                [taxonomicStatus] => accepted
                [parentNameUsageID] => 
                [acceptedNameUsageID] => 
                [higherClassification] => 
            )*/
            
            if($rec['taxonID'] == $taxon_id) {
                $ret = array();
                $ret['scientificName']  = $rec['scientificName'];
                $ret['taxonRank']       = $rec['taxonRank'];

                $ret['taxonRemarks']    = '';
                if(in_array($source_name, array('IOC', 'ODO', 'BOM', 'SPR', 'ITIS', 'WOR', 'COL'))) {
                    $ret['taxonRemarks'] = $rec['taxonRemarks'];
                }

                $ret['datasetID'] = '';
                if(in_array($source_name, array('ANN', 'MIP'))) {
                    $ret['datasetID'] = $rec['dataSet'];
                }
                if(in_array($source_name, array('dino'))) {
                    $ret['datasetID'] = $rec['datasetID'];
                }

                /* Fetch the furtherInformationURL values from the source file 
                    for those sources that have this field: dino, ODO, BOM, ANN, TRI, ITIS, MIP, NCBI, WOR
                Fetch the value from the “source” field and put it in the furtherInformationURL field of the DH file 
                    for the following sources: ictv, IOC */
                $ret['furtherInformationURL'] = '';
                if(in_array($source_name, array('dino', 'ODO', 'BOM', 'ANN', 'TRI', 'ITIS', 'MIP', 'NCBI', 'WOR'))) {
                                                                 $ret['furtherInformationURL'] = $rec['furtherInformationURL'];
                }
                if(in_array($source_name, array('ictv', 'IOC'))) $ret['furtherInformationURL'] = $rec['source'];
                return $ret;
            }
            
            /*
            if($rank = $rec['taxonRank']) {
                if($rank == 'no rank') $rank = '';
                elseif($rank == 'varietas') $rank = 'variety';
                elseif($rank == 'forma.') $rank = 'form';
            }
            $tax = new \eol_schema\Taxon();
            $tax->taxonID = $rec['taxonID'];
            $tax->scientificName = $rec['scientificName'];
            $tax->canonicalName = $rec['canonicalName'];
            $tax->parentNameUsageID = $rec['parentNameUsageID'];
            $tax->acceptedNameUsageID = $rec['acceptedNameUsageID'];
            $tax->taxonRank = $rec['taxonRank'];
            $tax->taxonomicStatus = $rec['taxonomicStatus'];
            $tax->source = $rec['source'];
            $tax->furtherInformationURL = $rec['furtherInformationURL'];
            $tax->taxonRemarks = $rec['taxonRemarks'];
            $tax->datasetID = $rec['datasetID'];
            $tax->EOLid = $rec['EOLid'];
            $tax->EOLidAnnotations = $rec['EOLidAnnotations'];
            $tax->higherClassification = $rec['higherClassification'];
            $tax->Landmark = $rec['Landmark'];
            $this->archive_builder->write_object_to_file($tax);
            // if($i == 5) break;
            */
        }
        exit("\nDid not get anything: [$sought_field], [$txtfile], [$taxon_id]\n");
    }
    private function format_taxonRemarks($rem, $flags)
    {   /*  Also, if smasher has flagged a taxon as incertae_sedis (but not incertae_sedis_inherited) 
                please append “incertae sedis” to the taxonRemarks field. 
            If there is already content in that field (fetched from the source file), 
                separate the “incertae sedis” entry from the other content with a semicolon 
                unless that content already ends in a semicolon or a period. */
        if(self::is_flag('incertae_sedis', $flags) && !self::is_flag('incertae_sedis_inherited', $flags)) {
            if(substr($rem, -1) == ";" || substr($rem, -1) == ".") $rem .= " incertae sedis";
            elseif($rem) $rem .= "; incertae sedis";
            else $rem = "incertae sedis";
        }
        return $rem;
    }
    private function is_flag($needle, $flags)
    {
        $words = explode(",", $flags);
        foreach($words as $word) {
            if($word == $needle) return true;
        }
        return false;
    }
    function build_source_taxa_records()
    {   
        $sources = array("trunk", "ictv", "MIP", "NCBI", "ITIS", "COL", "WOR", "MOL", "LIZ", "dino", "IOC", "MAM", "ONY", "TRI", "ODO", "VSP", "BOM", "ERE", "COC", "SPR", "CRU", "ANN");
        foreach($sources as $source) { echo "\ncaching $source...\n";
            self::save_taxa_records($source);
        }
    }
    private function save_taxa_records($source_name)
    {
        $txtfile = $this->path[$source_name];
        $i = 0;
        foreach(new FileIterator($txtfile) as $line_number => $line) {
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
            $rec = array_map('trim', $rec); //print_r($rec); //exit("\nstopx\n");
            $taxonID = $rec['taxonID'];
            
            $taxonRank = '';
            if(in_array($source_name, array('dino', 'ONY')))   $taxonRank = $rec['rank'];
            else                                        $taxonRank = $rec['taxonRank'];
            
            $taxonRemarks = '';
            if(in_array($source_name, array('IOC', 'ODO', 'BOM', 'SPR', 'ITIS', 'WOR', 'COL'))) $taxonRemarks = $rec['taxonRemarks'];
            
            $datasetID = '';
            if(in_array($source_name, array('ANN', 'MIP'))) $datasetID = $rec['dataSet'];
            if(in_array($source_name, array('dino')))       $datasetID = $rec['datasetID'];
            
            $furtherInformationURL = '';
            if(in_array($source_name, array('dino', 'ODO', 'BOM', 'ANN', 'TRI', 'ITIS', 'MIP', 'NCBI', 'WOR'))) $furtherInformationURL = $rec['furtherInformationURL'];
            if(in_array($source_name, array('ictv', 'IOC')))                                                    $furtherInformationURL = $rec['source'];
            
            $this->recs[$source_name][$taxonID] = array($rec['scientificName'], $taxonRank, $taxonRemarks, $datasetID, $furtherInformationURL);
        }
    }
    // Catalogue_of_Life_DH_2019   --- /Volumes/AKiTiO4/web/cp/COL/2019-annual/taxa.txt 
    // Collembola_DH               --- /Volumes/AKiTiO4/web/cp/COL/2020-08-01-archive-complete/taxa.txt 
    // COL & SPR
    function build_source_taxa_records_COL_SPR($source_name)
    {
        $path['COL_2'] = '/Volumes/AKiTiO4/web/cp/COL/2019-annual/';                  // Catalogue_of_Life_DH_2019
        $path['SPR_2'] = '/Volumes/AKiTiO4/web/cp/COL/2020-08-01-archive-complete/';  // Collembola_DH
        $path = $path[$source_name];

        require_library('connectors/DHSourceHierarchiesAPI');
        $func = new DHSourceHierarchiesAPI();        
        $meta = $func->analyze_eol_meta_xml($path.'meta.xml', 'http://rs.tdwg.org/dwc/terms/Taxon'); //2nd param $row_type is rowType in meta.xml // print_r($meta);
        echo "\nCaching $source_name...";
        $final = array(); $i = 0;
        foreach(new FileIterator($path.$meta['taxon_file'], false, true, @$this->dwc['iterator_options']) as $line => $row) { //2nd and 3rd param; false and true respectively are default values
            $i++; if(($i % 500000) == 0) echo "\n count:[".number_format($i)."] ";
            if($meta['ignoreHeaderLines'] && $i == 1) continue;
            if(!$row) continue;
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta['fields'] as $field) {
                $rec[$field] = $tmp[$k];
                $k++;
            }
            $rec = array_map('trim', $rec); // print_r($rec); exit("\nelix\n");
            /*( [taxonID] => 1001
                [identifier] => 40c1c4c8925fb02ce99db87c0221a6f6
                [datasetID] => 18
                
            For COL & SPR, we want to use the datasetID value from the original COL data files. 
                These are not in the SPR or COL for DH files, so you have to fetch them from the original COL export files. 
                Also, please use COL- as a prescript for these IDs. For example, if the COL datasetID is 5, our datasetID would be COL-5. 
                If the COL datasetID is Species 2000 or if there is no datasetID available, simply use COL as the datasetID for both COL 
                    and SPR derived taxa.    
            */
            $datasetID = (string) $rec['datasetID'];
            if($datasetID == 'Species 2000') $datasetID = 'COL';
            if(!$datasetID) $datasetID = 'COL';
            else {
                if($datasetID == 'COL') {}
                else $datasetID = 'COL-'.$datasetID;
            }
            $this->recs[$source_name][$rec['identifier']] = $datasetID;
        }
    }
}
?>