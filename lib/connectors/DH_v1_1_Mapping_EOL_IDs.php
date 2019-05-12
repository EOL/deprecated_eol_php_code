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
    //==========================================================================start step 2
    function step_2()
    {
        /* 2.1 get list of used EOL_ids */
        $used_EOLids = self::get_used_EOLids($this->main_path."/new_DH_after_step1.txt");
        // print_r($used_EOLids); 
        echo "\n".count($used_EOLids)."\n";
        
        /*
        ------------sent email
        Hi Katja, as I was investigating results of step 1. Match EOLid based on source identifiers.
        I have these five records in new DH:
        EOL-000000085511	trunk:06e1feb1-fc37-4596-a605-46601d3f74a9,NCBI:33634,WOR:368898		EOL-000000025792	Stramenopiles 	clade		trunk	Stramenopiles
        EOL-000000085560	trunk:6660e13d-acb7-451b-9659-46ed72a8cc47,WOR:345465		EOL-000000085512	Ochrophyta 	phylum		trunk	Ochrophyta
        EOL-000000085561	trunk:608e5df3-4b08-4d6c-a3df-3fbe9534da68,WOR:576884		EOL-000000085560	Diatomista 	clade		trunk	Diatomista
        EOL-000000095234	trunk:513e4f24-6a52-4700-9f44-af7af734d516,WOR:449151		EOL-000000085511	Bigyra 	clade		trunk	Bigyra
        EOL-000000095335	trunk:b6259274-728a-4b38-a135-f7286fdc5917,WOR:582466		EOL-000000095234	Opalozoa 	phylum		trunk	Opalozoa
        All five ended up with just one EOLid => 2912001
        Why? because we have this one record in old DH:
        -7957	-7957	-7958	Stramenopiles	clade	trunk:1850f573-8fd1-4d14-8794-df5ed1c294ec,WOR:368898,WOR:449151,WOR:345465,WOR:582466,WOR:576884,WOR:588641,WOR:591205,WOR:591209,WOR:588643	accepted							trunk	2912001	multiple;	1
        Where all five WOR:XXXXXX shared identifiers from new DH is linked to this one record in old DH.
        What do you suggest to do here.
        Thanks,
        Eli
        
        more investigation from old DH:
        -23684	-23684	-7957	Ochrophyta	phylum	gbif:98	accepted	Ochrophyta					https://www.gbif-uat.org/species/98	7ddf754f-d193-4cc9-b351-99906754a03b	3402	multiple; canonical;	
        -23677	-23677	-7957	Bigyra	phylum	gbif:8158183	accepted	Bigyra					https://www.gbif-uat.org/species/8158183	7ddf754f-d193-4cc9-b351-99906754a03b	13047845	multiple;	
        */
    }
    private function get_used_EOLids($file)
    {
        $i = 0;
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
            
            if($val = $rec['EOLid']) {
                if(isset($final[$val])) print_r($rec);
                else $final[$val] = '';
            }
        }
        return array_keys($final);
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
            
            $source_ids = self::get_all_source_identifiers($rec['source']);
            // /* MySQL option
            if($EOL_id = self::get_EOL_id($source_ids)) {
                echo "\nwith EOL_id [$EOL_id]\n";
                $rec['EOLid'] = $EOL_id;
                @$this->debug['totals']['matched EOLid count']++;
            }
            else { //No EOL_id
                if(self::source_is_in_listof_sources($rec['source'], array('ictv', 'IOC', 'ODO'))) {
                    $rec['EOLidAnnotations'] = 'unmatched';
                    @$this->debug['totals']['unmatched count']++;
                }
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
        Functions::start_print_debug($this->debug, $this->resource_id);
    }
    private function source_is_in_listof_sources($source_str, $sources_list)
    {
        $sources = self::get_all_source_abbreviations($source_str);
        foreach($sources as $source) {
            if(in_array($source, $sources_list)) return true;
        }
        return false;
    }
    private function get_all_source_identifiers($source)
    {
        $tmp = explode(",", $source);
        return array_map('trim', $tmp);
    }
    private function get_EOL_id($source_ids)
    {
        foreach($source_ids as $source_id) {
            if($val = self::query_EOL_id($source_id)) return $val;
        }
    }
    private function query_EOL_id($source_id) //param $source_id is from new_DH
    {
        $sql = "SELECT m.EOL_id FROM DWH.taxonID_source_ids o JOIN DWH.EOLid_map m ON o.taxonId = m.smasher_id WHERE o.source_id = '".$source_id."'";
        $result = $this->mysqli->query($sql);
        while($result && $row=$result->fetch_assoc()) return $row['EOL_id'];
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