<?php
namespace php_active_record;
/* connector: [dwh_postproc_TRAM_808.php] - TRAM-808
*/
class DH_v1_1_mapping_EOL_IDs
{
    function __construct($folder)
    {
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
    /* steps for step 1:
    step. manually put JRice's eolpageids.csv to MySQL as well. Created eolpageids.csv.tsv, replace 'comma' with 'tab'.
            $ mysql -u root -p --local-infile DWH;
            to load from txt file:
            mysql> load data local infile '/Volumes/AKiTiO4/d_w_h/TRAM-808/eolpageids.csv.tsv' into table EOLid_map;
    step. run create_append_text(); --> creates [append_taxonID_source_id_2mysql.txt]
          then saves it to MySQL table [taxonID_source_ids]
    step. run step_1()
    */
    function create_append_text()
    {
        $file_append = $this->main_path."/append_taxonID_source_id_2mysql.txt"; $WRITE = fopen($file_append, "w"); //will overwrite existing
        $i = 0;
        foreach(new FileIterator($this->file['old DH']) as $line_number => $line) {
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
            // print_r($rec); exit("\nstopx old_DH\n");
            /*Array(
                [taxonid] => -100000
                [acceptednameusageid] => -100000
                [parentnameusageid] => -79407
                [scientificname] => Frescocyathus nagagreboensis Barta-Calmus, 1969
                [taxonrank] => species
                [source] => gbif:4943435
                [taxonomicstatus] => accepted
                [canonicalname] => Frescocyathus nagagreboensis
                [scientificnameauthorship] => Barta-Calmus, 1969
                [scientificnameid] => 
                [taxonremarks] => 
                [namepublishedin] => 
                [furtherinformationurl] => https://www.gbif-uat.org/species/4943435
                [datasetid] => 6cfd67d6-4f9b-400b-8549-1933ac27936f
                [eolid] => 
                [eolidannotations] => 
                [landmark] => 
            */
            $source_ids = self::get_all_source_identifiers($rec['source']);
            foreach($source_ids as $source_id) {
                $arr = array();
                $arr = array($rec['taxonid'], $source_id);
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
    function start_tram_808()
    {
        /* steps for step 1:
        3. run step_1()
        */
        self::step_1(); //1. Match EOLid based on source identifiers
        Functions::start_print_debug($this->debug, $this->resource_id);
    }
    private function step_1() //1. Match EOLid based on source identifiers
    {   //loop new DH
        $i = 0;
        foreach(new FileIterator($this->file['new DH']) as $line_number => $line) {
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
            print_r($rec); //exit("\nstopx\n");
            /*Array(
                [taxonid] => EOL-000000008199
                [source] => NCBI:683737
                [furtherinformationurl] => https://www.ncbi.nlm.nih.gov/Taxonomy/Browser/wwwtax.cgi?id=683737
                [parentnameusageid] => EOL-000000007969
                [scientificname] => Paenibacillus uliginis
                [taxonrank] => species
                [taxonremarks] => 
                [datasetid] => NCBI
                [canonicalname] => Paenibacillus uliginis
            */
            $source_ids = self::get_all_source_identifiers($rec['source']);
            /*
            if($EOL_id = self::loop_old_DH_get_EOL_id($source_ids)) {
                echo "\nwith EOL_id [$EOL_id]\n";
                $this->debug[$EOL_id] = json_encode($rec);
            }
            else echo "\nNo EOL_id\n";
            */
            
            // /* MySQL option
            if($EOL_id = self::get_EOL_id($source_ids)) {
                echo "\nwith EOL_id [$EOL_id]\n";
                $this->debug[$EOL_id] = json_encode($rec);
            }
            else echo "\nNo EOL_id\n";
            // */
            if($i > 10) break; //debug only
        }
    }
    /*
    private function loop_old_DH_get_EOL_id($source_ids)
    {
        foreach($source_ids as $source_id) {
            if($taxon_id = self::get_taxonid_from_old_DH($source_id)) {
                if($EOL_id = self::query_eol_id($taxon_id)) return $EOL_id;
            }
        }
    }
    private function query_eol_id($taxon_id)
    {
        $sql = "select m.* from EOLid_map m where m.smasher_id = '".$taxon_id."'";
        $result = $this->mysqli->query($sql);
        while($result && $row=$result->fetch_assoc()) return $row['EOL_id'];
        return false;
    }
    private function get_taxonid_from_old_DH($source_id)
    {
        $i = 0;
        foreach(new FileIterator($this->file['old DH']) as $line_number => $line) {
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
            // print_r($rec); exit("\nstopx old_DH\n");
            $source_ids = self::get_all_source_identifiers($rec['source']);
            if(in_array($source_id, $source_ids)) return $rec['taxonid'];
        }
        return false;
    }
    */
    private function get_all_source_identifiers($source)
    {
        $tmp = explode(",", $source);
        return array_map('trim', $tmp);
    }
    // /*
    private function get_EOL_id($source_ids)
    {
        foreach($source_ids as $id) {
            if($val = self::query_EOL_id($id)) return $val;
        }
    }
    private function query_EOL_id($id)
    {
        $sql = "SELECT m.EOL_id, o.taxonID from DWH.taxonID_source_ids o join DWH.EOLid_map m ON o.taxonId = m.smasher_id where o.source_id = '".$id."'";
        $result = $this->mysqli->query($sql);
        while($result && $row=$result->fetch_assoc()) return $row['EOL_id'];
        return false;
    }
    // */
}
?>