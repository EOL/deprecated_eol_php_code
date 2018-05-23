<?php
namespace php_active_record;
/* connector: [dwh.php] */

class DHSourceHierarchiesAPI
{
    function __construct()
    {
        /*
        $this->resource_id = $folder;
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        if(Functions::is_production()) {}
        */
        $this->AphiaRecordByAphiaID_download_options = array('download_wait_time' => 1000000, 'timeout' => 1200, 'download_attempts' => 2, 'delay_in_minutes' => 1, 'resource_id' => 26, 'expire_seconds' => false);
        $this->webservice['AphiaRecordByAphiaID'] = "http://www.marinespecies.org/rest/AphiaRecordByAphiaID/";

        $this->gnparser = "http://parser.globalnames.org/api?q=";
        $this->smasher_download_options = array(
            'cache_path'         => '/Volumes/AKiTiO4/eol_cache_smasher/',
            'download_wait_time' => 1000000, 'timeout' => 600, 'download_attempts' => 1, 'delay_in_minutes' => 0, 'expire_seconds' => false);
        /* Functions::lookup_with_cache($this->gnparser.urlencode($rec['scientificName']), $this->smasher_download_options); */
        
        $this->debug = array();
        $this->taxonomy_header = array("uid", "parent_uid", "name", "rank", "sourceinfo"); //('uid	|	parent_uid	|	name	|	rank	|	sourceinfo	|	' + '\n')
        $this->synonym_header = array("uid", "name", "type", "rank");                      //('uid	|	name	|	type	|	rank	|	' + '\n')
        $this->main_path = "/Volumes/AKiTiO4/d_w_h/dynamic_working_hierarchy-master/";
        
        $this->sh['worms']['source']        = $this->main_path."/worms_v5/";
        $this->sh['ioc-birdlist']['source'] = $this->main_path."/ioc-birdlist_v3/";
        $this->sh['trunk']['source']        = $this->main_path."/trunk_20180521/";
        $this->sh['amphibia']['source']     = $this->main_path."/amphibia_v2/";
        $this->sh['spiders']['source']      = $this->main_path."/spiders_v2/";
        $this->sh['col']['source']          = $this->main_path."/col_v1/";
        
    }
    
    public function start($what)
    {
        /*
        $cmd = 'gnparser name "Gadus morhua Eli & Cha, 1972"';
        $json = shell_exec($cmd);
        print_r(json_decode($json, true));
        exit;
        */
        
        self::save_2local_gnparsed_file($what);
        exit;
        
        $meta_xml_path = $this->sh[$what]['source']."meta.xml";
        $meta = self::analyze_meta_xml($meta_xml_path);
        if($meta == "No core entry in meta.xml") $meta = self::analyze_eol_meta_xml($meta_xml_path);
        $meta['what'] = $what;
        // print_r($meta); exit;
        
        $with_authorship = false;
        if(self::need_2run_gnparser_YN($meta)) {
            $with_authorship = true;
            self::run_file_with_gnparser($what);
            self::save_2local_gnparsed_file($what);
        }
        self::process_taxon_file($meta, $with_authorship);
    }
    private function need_2run_gnparser_YN($meta)
    {
        foreach(new FileIterator($this->sh[$what]['source'].$meta['taxon_file']) as $line => $row) {
            $i++;
            if($meta['ignoreHeaderLines'] && $i == 1) continue;
            if(!$row) continue;
            $tmp = explode("\t", $row);
            // echo "\n".count($tmp)."\n"; print_r($tmp);
            $rec = array(); $k = 0;
            foreach($meta['fields'] as $field) {
                $rec[$field] = $tmp[$k];
                $k++;
            }
            // print_r($rec); //exit; //use to test if field - value is OK
            if(self::gnsparse_canonical($rec['scientificName']) != $rec['scientificName']) return true;
        }
        return false;
    }
    private function process_taxon_file($meta, $with_authorship)
    {
        $what = $meta['what']; $has_synonym = false;
        $fn_tax = fopen($this->sh[$what]['source']."taxonomy.tsv", "w"); //will overwrite existing
        $fn_syn = fopen($this->sh[$what]['source']."synonym.tsv", "w"); //will overwrite existing
        fwrite($fn_tax, implode("\t|\t", $this->taxonomy_header)."\t|\t"."\n");
        fwrite($fn_syn, implode("\t|\t", $this->synonym_header)."\t|\t"."\n");
        $i = 0; $run_gnparser = false;
        $m = 3765285/10; //for CoL
        foreach(new FileIterator($this->sh[$what]['source'].$meta['taxon_file']) as $line => $row) {
            $i++;
            if($meta['ignoreHeaderLines'] && $i == 1) continue;
            if(!$row) continue;
            $tmp = explode("\t", $row);
            // echo "\n".count($tmp)."\n"; print_r($tmp);
            $rec = array(); $k = 0;
            foreach($meta['fields'] as $field) {
                $rec[$field] = $tmp[$k];
                $k++;
            }
            // print_r($rec); //exit; //use to test if field - value is OK
            if(($i % 5000) == 0) echo "\n".number_format($i)."\n";
            // echo "\n".number_format($i)."\n";
            //--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
            if(in_array($what, array('trunk'))) {
                /*
                    [0] => 1
                    [1] => accepted
                    [2] => superfamily
                    [3] => dd18e3cf-04ba-4b0d-8349-1dd4b7ac5000
                    [4] => 324b4a02-700b-4ae2-9dbd-65570f42f83c
                    [5] => 
                    [6] => life,cellular organisms,Eukaryota,Opisthokonta,Metazoa,Bilateria,Protostomia,Ecdysozoa,Panarthropoda,Arthropoda,Chelicerata,Arachnida,Acari,Acariformes,Trombidiformes,Prostigmata,Anystina,Parasitengona
                    [7] => 00016d53-eae4-494c-8f79-3e9ddcd5e634
                    [8] => Arrenuroidea
                    [9] => 00016d53-eae4-494c-8f79-3e9ddcd5e634
                    [index] => 1
                    [taxonomicStatus] => accepted
                    [taxonRank] => superfamily
                    [datasetID] => dd18e3cf-04ba-4b0d-8349-1dd4b7ac5000
                    [parentNameUsageID] => 324b4a02-700b-4ae2-9dbd-65570f42f83c
                    [scientificNameAuthorship] => 
                    [higherClassification] => life,cellular organisms,Eukaryota,Opisthokonta,Metazoa,Bilateria,Protostomia,Ecdysozoa,Panarthropoda,Arthropoda,Chelicerata,Arachnida,Acari,Acariformes,Trombidiformes,Prostigmata,Anystina,Parasitengona
                    [acceptedNameUsageID] => 00016d53-eae4-494c-8f79-3e9ddcd5e634
                    [scientificName] => Arrenuroidea
                    [taxonID] => 00016d53-eae4-494c-8f79-3e9ddcd5e634
                    if accepted_id != taxon_id:
                        print('synonym found')
                        out_file_s.write(accepted_id + '\t|\t' + name + '\t|\t' + 'synonym' + '\t|\t' + '\t|\t' + '\n')
                    else:
                        out_file_t.write(taxon_id + '\t|\t' + parent_id + '\t|\t' + name + '\t|\t' + rank + '\t|\t' + source + '\t|\t' + '\n')
                */
                $t = array();
                $t['parent_id']     = $rec['parentNameUsageID'];    //row[4]
                if($with_authorship) $t['name'] = self::get_json_with_cache($rec['scientificName']); //row[8]
                else                 $t['name'] = $rec['scientificName'];
                $t['taxon_id']      = $rec['taxonID'];              //row[9]
                $t['accepted_id']   = $rec['acceptedNameUsageID'];  //row[7]
                $t['rank']          = ($val = @$rec['taxonRank']) ? $val: "no rank"; //row[2]
                $t['source']        = '';
                if($t['accepted_id'] != $t['taxon_id']) {
                    self::write2file("syn", $fn_syn, $t);
                    $has_synonym = true;
                }
                else self::write2file("tax", $fn_tax, $t);
            }
            //--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
            if(in_array($what, array('ioc-birdlist'))) { //headers changed from version: ioc-birdlist_v2 to ioc-birdlist_v3
                /*
                    [0] => 09af091e166bfa45493c6242ebf16a7c
                    [1] => Celeus elegans leotaudi Hellmayr, 1906
                    [2] => subspecies
                    [3] => d6edba5dd4d993cbab690c2df8fc937f
                    [4] => 
                    [5] => Celeus elegans leotaudi
                    [6] => http://www.worldbirdnames.org/bow/woodpeckers/
                    [7] => Hellmayr, 1906
                    [taxonID] => 09af091e166bfa45493c6242ebf16a7c
                    [scientificName] => Celeus elegans leotaudi Hellmayr, 1906
                    [taxonRank] => subspecies
                    [parentNameUsageID] => d6edba5dd4d993cbab690c2df8fc937f
                    [taxonRemarks] => 
                    [canonicalName] => Celeus elegans leotaudi
                    [source] => http://www.worldbirdnames.org/bow/woodpeckers/
                    [scientificNameAuthorship] => Hellmayr, 1906
                    out_file_t.write(taxon_id + '\t|\t' + parent_id + '\t|\t' + name + '\t|\t' + rank + '\t|\t' + source + '\t|\t' + '\n')
                */
                $t = array();
                $t['parent_id'] = $rec['parentNameUsageID'];
                if($with_authorship) $t['name'] = self::get_json_with_cache($rec['scientificName']); //row[8]
                else                 $t['name'] = $rec['scientificName'];
                $t['taxon_id']  = $rec['taxonID'];
                $t['rank']      = ($val = @$rec['taxonRank']) ? $val: "no rank";
                $t['source']    = '';
                self::write2file("tax", $fn_tax, $t);
            }
            //--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
            if(in_array($what, array('worms'))) {
            }
            if(in_array($what, array('col'))) {
                /* breakdown when caching:
                $cont = false;
                // if($i >=  1    && $i < $m) $cont = true;
                // if($i >=  $m   && $i < $m*2) $cont = true;
                // if($i >=  $m*2 && $i < $m*3) $cont = true;
                // if($i >=  $m*3 && $i < $m*4) $cont = true;
                // if($i >=  $m*4 && $i < $m*5) $cont = true;
                // if($i >=  $m*5 && $i < $m*6) $cont = true;
                if($i >=  $m*6 && $i < $m*7) $cont = true;
                // if($i >=  $m*7 && $i < $m*8) $cont = true;
                // if($i >=  $m*8 && $i < $m*9) $cont = true;
                // if($i >=  $m*9 && $i < $m*10) $cont = true;
                if(!$cont) continue;
                */
            }
            //--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
            //--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
            //--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
            //--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
            /* no longer used
            if(!$run_gnparser) {
                if(self::gnsparse_canonical($t['name']) != $t['name']) $run_gnparser = true;
            }
            */
        }
        fclose($fn_tax);
        fclose($fn_syn);
        if(!$has_synonym) unlink($this->sh[$what]['source']."synonym.tsv");

        /* no longer used
        if($run_gnparser) {
            self::run_file_with_gnparser($what);
            self::save_2local_gnparsed_file($what);
        }
        else              echo "\nNo need to run gnparser()\n";
        */
        
        self::parent_id_check($what);
    }
    private function parent_id_check($what)
    {
    }
    private function run_file_with_gnparser($what) //working OK but not used
    {
        echo "\nRunning gnparser...\n";
        $WRITE = fopen($this->sh[$what]['source']."name_only.txt", "w"); //will overwrite existing
        foreach(new FileIterator($this->sh[$what]['source'].$meta['taxon_file']) as $line => $row) {
            $i++;
            if($meta['ignoreHeaderLines'] && $i == 1) continue;
            if(!$row) continue;
            $tmp = explode("\t", $row);
            // echo "\n".count($tmp)."\n"; print_r($tmp);
            $rec = array(); $k = 0;
            foreach($meta['fields'] as $field) {
                $rec[$field] = $tmp[$k];
                $k++;
            }
            // print_r($rec); //exit; //use to test if field - value is OK
            if($val = @$rec['scientificName']) fwrite($WRITE, $val."\n");
        }
        fclose($WRITE);
        //convert entire file (names) to gnparser version
        $cmd = "gnparser file --input ".$this->sh[$what]['source']."name_only.txt --output ".$this->sh[$what]['source']."name_only_gnparsed.txt";
        $out = shell_exec($cmd);
        echo "\n$out\n";
    }
    private function save_2local_gnparsed_file($what)
    {
        $i = 0;
        foreach(new FileIterator($this->sh[$what]['source'].'name_only_gnparsed.txt') as $line => $json) {
            $i++; if($i == 1) continue;
            // echo "\n$json\n";
            $arr = json_decode($json, true);
            // print_r($arr); exit;
            $name = $arr['verbatim'];
            echo "\n$i. $name";
            
            //now check if json already cached. Ignore if it does and save/cache it if it doesn't
            $options['cache_path'] = $this->smasher_download_options['cache_path'];
            $md5 = md5($name);
            $cache1 = substr($md5, 0, 2);
            $cache2 = substr($md5, 2, 2);
            if(!file_exists($options['cache_path'] . $cache1)) mkdir($options['cache_path'] . $cache1);
            if(!file_exists($options['cache_path'] . "$cache1/$cache2")) mkdir($options['cache_path'] . "$cache1/$cache2");
            $cache_path = $options['cache_path'] . "$cache1/$cache2/$md5.json";
            if(!file_exists($cache_path)) {
                echo " - saving...";
                if($FILE = Functions::file_open($cache_path, 'w')) {
                    fwrite($FILE, $json);
                    fclose($FILE);
                }
            }
            else echo " - already saved/cached";
        }
    }
    /*
    private function replace_sciname_with_gnparser_canonical($what)
    {
        Functions::file_rename($this->sh[$what]['source'].'taxonomy.tsv', $this->sh[$what]['source'].'taxonomy_tmp.tsv');
        $WRITE = fopen($this->sh[$what]['source']."taxonomy.tsv", "w"); $i = 0;
        foreach(new FileIterator($this->sh[$what]['source'].'taxonomy_tmp.tsv') as $line => $row) {
            $i++; if($i == 1) continue;
            $tmp = explode("\t|\t", $row);
            // print_r($tmp); exit;
            if($name = @$tmp[2]) {
                $json = self::get_json_with_cache($name);
                echo "\n$i. [$json]\n";
            }
        }
    }
    */
    private function get_json_with_cache($name, $options = array()) //json generated by gnparser
    {
        // download_wait_time
        if(!isset($options['expire_seconds'])) $options['expire_seconds'] = false;
        if(!isset($options['cache_path'])) $options['cache_path'] = $this->smasher_download_options['cache_path'];
        $md5 = md5($name);
        $cache1 = substr($md5, 0, 2);
        $cache2 = substr($md5, 2, 2);
        if(!file_exists($options['cache_path'] . $cache1)) mkdir($options['cache_path'] . $cache1);
        if(!file_exists($options['cache_path'] . "$cache1/$cache2")) mkdir($options['cache_path'] . "$cache1/$cache2");
        $cache_path = $options['cache_path'] . "$cache1/$cache2/$md5.json";
        if(file_exists($cache_path)) {
            echo "\nRetrieving cache ($name)...\n";
            $file_contents = file_get_contents($cache_path);
            $cache_is_valid = true;
            if(($file_contents && $cache_is_valid) || (strval($file_contents) == "0" && $cache_is_valid)) {
                $file_age_in_seconds = time() - filemtime($cache_path);
                if($file_age_in_seconds < $options['expire_seconds']) return $file_contents;
                if($options['expire_seconds'] === false) return $file_contents;
            }
            @unlink($cache_path);
        }
        //generate json
        echo "\nGenerating cache json for the first time ($name)...\n";
        $cmd = 'gnparser name "'.$name.'"';
        $json = shell_exec($cmd);
        if($FILE = Functions::file_open($cache_path, 'w+')) {
            fwrite($FILE, $json);
            fclose($FILE);
        }
        return $json;
    }
    
    private function write2file($ext, $fn, $t)
    {
        if($ext == "syn")     fwrite($fn, $t['accepted_id'] . "\t|\t" . $t['name'] . "\t|\t" . 'synonym' . "\t|\t" . "\t|\t" . "\n");
        elseif($ext == "tax") fwrite($fn, $t['taxon_id'] . "\t|\t" . $t['parent_id'] . "\t|\t" . $t['name'] . "\t|\t" . $t['rank'] . "\t|\t" . $t['source'] . "\t|\t" . "\n");
    }
    private function gnsparse_canonical($sciname)
    {
        $json = Functions::lookup_with_cache($this->gnparser.urlencode($sciname), $this->smasher_download_options);
        if($obj = json_decode($json)) return @$obj->namesJson[0]->canonical_name->value;
    }
    private function analyze_eol_meta_xml($meta_xml_path)
    {
        if(file_exists($meta_xml_path)) {
            $xml_string = file_get_contents($meta_xml_path);
            $xml = simplexml_load_string($xml_string);
            foreach($xml->table as $tbl) {
                if($tbl['rowType'] == "http://rs.tdwg.org/dwc/terms/Taxon") {
                    if(in_array($tbl['ignoreHeaderLines'], array(1, true))) $ignoreHeaderLines = true;
                    else                                                    $ignoreHeaderLines = false;
                    $fields = array();
                    foreach($tbl->field as $f) {
                        $term = (string) $f['term'][0];
                        $uris[] = $term;
                        $fields[] = pathinfo($term, PATHINFO_FILENAME);
                    }
                    $file = (string) $tbl->files->location;
                    return array('fields' => $fields, 'taxon_file' => $file, 'ignoreHeaderLines' => $ignoreHeaderLines);
                }
            }
        }
        else {
            echo "\nNo meta.xml present. Will use first-row header from taxon file\n";
        }
        exit("\nInvestigate 02.\n");
    }
    private function analyze_meta_xml($meta_xml_path)
    {
        if(file_exists($meta_xml_path)) {
            $xml_string = file_get_contents($meta_xml_path);
            $xml = simplexml_load_string($xml_string);
            // print_r($xml->core);
            if(!isset($xml->core)) {
                echo "\nNo core entry in meta.xml\n";
                return "No core entry in meta.xml";
            }
            if(in_array($xml->core['ignoreHeaderLines'], array(1, true))) $ignoreHeaderLines = true;
            else                                                          $ignoreHeaderLines = false;
            $fields = array();
            if($xml->core['index'] == 0) $fields[] = "index";
            if($xml->core->field[0]['index'] == 0) $fields = array(); //this will ignore <id index="0" />
            foreach($xml->core->field as $f) {
                $term = (string) $f['term'][0];
                $uris[] = $term;
                $fields[] = pathinfo($term, PATHINFO_FILENAME);
            }
            $file = (string) $xml->core->files->location;
            return array('fields' => $fields, 'taxon_file' => $file, 'ignoreHeaderLines' => $ignoreHeaderLines);
        }
        else {
            echo "\nNo meta.xml present. Will use first-row header from taxon file\n";
        }
        exit("\nInvestigate 01.\n");
    }
}
?>