<?php
namespace php_active_record;
/* connector: [] - first used in TRAM-809 - minting of synonyms
*/
class DH_minting_synonyms
{
    function __construct($folder)
    {
        $this->resource_id = $folder;
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->taxon_ids = array();
        $this->debug = array();
        if(Functions::is_production()) {
            $this->download_options = array(
                'cache_path'         => '/extra/eol_cache_smasher/',
                'download_wait_time' => 250000, 'timeout' => 600, 'download_attempts' => 1, 'delay_in_minutes' => 0, 'expire_seconds' => false);
            $this->main_path = "/extra/other_files/DWH/TRAM-809/"; //download_wait_time is 1/4 of a second -> 1000000/4
        }
        else {
            $this->download_options = array(
                'cache_path'         => '/Volumes/AKiTiO4/eol_cache_smasher/',
                'download_wait_time' => 250000, 'timeout' => 600, 'download_attempts' => 1, 'delay_in_minutes' => 0, 'expire_seconds' => false);
            $this->main_path = "/Volumes/AKiTiO4/d_w_h/TRAM-809/";
        }
        $this->write_fields = array('taxonID', 'source', 'furtherInformationURL', 'parentNameUsageID', 'scientificName', 'taxonRank', 'taxonRemarks', 
                                    'datasetID', 'canonicalName', 'EOLid', 'EOLidAnnotations', 'higherClassification', 'taxonomicStatus', 'acceptedNameUsageID');
    }
    //===================================================start minting
    function mint_synonym_ids()
    {
        $file_append = $this->main_path."/minted_recs_syn_transaction.txt"; $WRITE = fopen($file_append, "w"); //will overwrite existing
        $this->mysqli =& $GLOBALS['db_connection'];
        /* step 1: get max minted_id value */
        $max_id = self::get_max_minted_id();
        if(!$max_id) $max_id = 'SYN-000000000000';
        $incremental = str_replace('SYN-','',$max_id);
        $this->incremental = intval($incremental);
        echo("\nmax minted ID: [$max_id]\n");
        echo("\nincrement starts with: [$this->incremental]\n");
        
        /* step 2: loop synonym file, check if each name exists already. If yes, get minted_id from table. If no, increment id and assign. */
        $sourcef = $this->main_path.'/synonyms_deduplicated.txt';
        $i = 0;
        foreach(new FileIterator($sourcef) as $line_number => $line) {
            $i++; if(($i % 200000) == 0) echo "\n".number_format($i)." ";
            $row = explode("\t", $line);
            if($i == 1) {
                $fields = $row;
                $fields = array_filter($fields); print_r($fields);
                continue;
            }
            else {
                /* will exclude manually curated synonyms added from the Google sheet
                if(!@$row[0]) continue;
                */
                $k = 0; $rec = array();
                foreach($fields as $fld) {
                    $rec[$fld] = @$row[$k];
                    $k++;
                }
            }
            $rec = array_map('trim', $rec);
            // print_r($rec); //exit("\nstopx\n");
            /*Array(
                [taxonID] => 1_1
                [source] => NCBI
                [furtherInformationURL] => https://www.ncbi.nlm.nih.gov/Taxonomy/Browser/wwwtax.cgi?id=1
                [parentNameUsageID] => 
                [scientificName] => all
                [taxonRank] => no rank
                [taxonRemarks] => 
                [datasetID] => NCBI
                [canonicalName] => 
                [EOLid] => 
                [EOLidAnnotations] => 
                [higherClassification] => 
                [taxonomicStatus] => synonym
                [acceptedNameUsageID] => EOL-000000000001
            )*/
            if(!$rec['scientificName']) continue; //seems a blank row might crept in
            $minted_id = self::search_minted_record($rec['taxonID'], $rec['acceptedNameUsageID'], $rec['scientificName'], $rec['taxonRank'], $rec['taxonomicStatus'], $rec['datasetID']);
            if(!$minted_id) { //new name --- will be assigned with newly minted ID
                $this->incremental++;
                $minted_id = self::format_minted_id();
                $arr = array($minted_id, $rec['taxonID'], $rec['acceptedNameUsageID'], $rec['scientificName'], $rec['taxonRank'], $rec['taxonomicStatus'], $rec['datasetID']);
                fwrite($WRITE, implode("\t", $arr)."\n");
            }
            else echo "\nRecord already exists [$minted_id]\n";
            
            $old_id_minted_id_info[$rec['taxonID']] = $minted_id; //to be used below
            
            // if($i > 8) break; //debug only
        }
        fclose($WRITE);
        
        /* step 3: append to MySQL table */
        echo "\nSaving minted records to MySQL...\n";
        if(filesize($file_append)) {
            $sql = "LOAD data local infile '".$file_append."' into table DWH.minted_records_synonyms;";
            if($result = $this->mysqli->query($sql)) echo "\nSaved OK to MySQL\n";
        }
        else echo "\nNothing to save.\n";
        
        // /* step 4: loop again synonyms_deduplicated.txt and generate synonyms_minted.txt, now using minted_id for uid and accepted_uid
        $file_taxonomy = $this->main_path."/synonyms_minted.txt"; $WRITE2 = fopen($file_taxonomy, "w"); //will overwrite existing
        // $fields = array('uid','accepted_uid','name','rank','sourceinfo','uniqname','flags'); //not used here...
        fwrite($WRITE2, implode("\t", $this->write_fields)."\n");
        echo "\nGenerating $file_taxonomy\n";
        
        $sourcef = $this->main_path.'/synonyms_deduplicated.txt';
        $i = 0;
        foreach(new FileIterator($sourcef) as $line_number => $line) {
            $i++; if(($i % 200000) == 0) echo "\n".number_format($i)." ";
            $row = explode("\t", $line);
            if($i == 1) {
                $fields = $row;
                $fields = array_filter($fields); print_r($fields);
                continue;
            }
            else {
                /* will exclude manually curated synonyms added from the Google sheet
                if(!@$row[0]) continue;
                */
                $k = 0; $rec = array();
                foreach($fields as $fld) {
                    $rec[$fld] = @$row[$k];
                    $k++;
                }
            }
            $rec = array_map('trim', $rec);
            // print_r($rec); exit("\nstopx\n");
            /*Array(
                [taxonID] => 1_1
                [source] => NCBI
                [furtherInformationURL] => https://www.ncbi.nlm.nih.gov/Taxonomy/Browser/wwwtax.cgi?id=1
                [parentNameUsageID] => 
                [scientificName] => all
                [taxonRank] => no rank
                [taxonRemarks] => 
                [datasetID] => NCBI
                [canonicalName] => 
                [EOLid] => 
                [EOLidAnnotations] => 
                [higherClassification] => 
                [taxonomicStatus] => synonym
                [acceptedNameUsageID] => EOL-000000000001
            )*/
            
            if(!$rec['taxonID']) continue;
            
            // for taxonomy file for DwCA -------------------------
            if($minted_id = $old_id_minted_id_info[$rec['taxonID']]) {}
            else exit("\nInvestigate no minted taxonID...\n");

            $rec['taxonID'] = $minted_id;
            self::write_report($rec, $fields, $WRITE2);
            // -------------------------
            
            // if($i > 8) break; //debug only
        }
        fclose($WRITE2);
        // */
    }
    private function write_report($save_rec, $fields, $fileH)
    {
        $arr = array();
        foreach($fields as $f) $arr[] = @$save_rec[$f];
        // print_r($arr);
        fwrite($fileH, implode("\t", $arr)."\n");
    }
    private function get_max_minted_id()
    {
        $sql = "SELECT max(m.minted_id) as max_id from DWH.minted_records_synonyms m;";
        $result = $this->mysqli->query($sql);
        while($result && $row=$result->fetch_assoc()) return $row['max_id'];
        return false;
    }
    private function search_minted_record($uid, $accepted_uid, $sciname, $rank, $taxon_status, $datasetID)
    {
        $sciname = str_replace("'", "\'", $sciname);
        $uid = str_replace("'", "\'", $uid);
        $sql = "SELECT m.minted_id from DWH.minted_records_synonyms m WHERE 
        m.uid = '$uid' 
        and m.accepted_uid = '$accepted_uid' 
        and m.sciname = '$sciname' 
        and m.rank = '$rank' 
        and m.taxon_status = '$taxon_status' 
        and m.datasetID = '$datasetID';";
        $result = $this->mysqli->query($sql);
        while($result && $row=$result->fetch_assoc()) return $row['minted_id'];
        return false;
    }
    private function format_minted_id()
    {
        return "SYN-".Functions::format_number_with_leading_zeros($this->incremental, 12);
    }
    //=====================================================end minting
}
?>
