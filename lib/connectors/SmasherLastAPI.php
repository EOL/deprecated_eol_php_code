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
        , 'uniqname' => $item[5], 'flags' => $item[6], 'new_parent_uid' => $item[7], 'new_parent_name' => $item[8], 'new_higherClassification' => $item[9], );
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
                    if($fld) $rek[$fld] = $rec[$k];
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
        
        // step2
        
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