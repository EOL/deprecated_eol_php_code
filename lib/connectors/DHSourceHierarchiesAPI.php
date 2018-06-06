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
        
gnparser file --input xaa.txt --output xaa_gnparsed.txt
gnparser file --input xab.txt --output xab_gnparsed.txt
gnparser file --input xac.txt --output xac_gnparsed.txt
gnparser file --input xad.txt --output xad_gnparsed.txt
gnparser file --input xae.txt --output xae_gnparsed.txt
gnparser file --input xaf.txt --output xaf_gnparsed.txt
gnparser file --input xag.txt --output xag_gnparsed.txt
gnparser file --input xah.txt --output xah_gnparsed.txt
        
        */
        $this->AphiaRecordByAphiaID_download_options = array('download_wait_time' => 1000000, 'timeout' => 1200, 'download_attempts' => 2, 'delay_in_minutes' => 1, 'resource_id' => 26, 'expire_seconds' => false);
        $this->webservice['AphiaRecordByAphiaID'] = "http://www.marinespecies.org/rest/AphiaRecordByAphiaID/";

        $this->gnparser = "http://parser.globalnames.org/api?q=";
        
        if(Functions::is_production()) {
            $this->smasher_download_options = array(
                'cache_path'         => '/extra/eol_cache_smasher/',
                'download_wait_time' => 1000000, 'timeout' => 600, 'download_attempts' => 1, 'delay_in_minutes' => 0, 'expire_seconds' => false);
            $this->main_path = "/extra/eli_dwh/";
        }
        else {
            $this->smasher_download_options = array(
                'cache_path'         => '/Volumes/AKiTiO4/eol_cache_smasher/',
                'download_wait_time' => 1000000, 'timeout' => 600, 'download_attempts' => 1, 'delay_in_minutes' => 0, 'expire_seconds' => false);
            $this->main_path = "/Volumes/AKiTiO4/d_w_h/dynamic_working_hierarchy-master/";
            $this->main_path = "/Volumes/AKiTiO4/d_w_h/eli_dwh/";
        }
        /* Functions::lookup_with_cache($this->gnparser.urlencode($rec['scientificName']), $this->smasher_download_options); */
        
        $this->debug = array();
        $this->taxonomy_header = array("uid", "parent_uid", "name", "rank", "sourceinfo"); //('uid	|	parent_uid	|	name	|	rank	|	sourceinfo	|	' + '\n')
        $this->synonym_header = array("uid", "name", "type", "rank");                      //('uid	|	name	|	type	|	rank	|	' + '\n')
        
        $this->sh['worms']['source']        = $this->main_path."/worms_v5/";
        $this->sh['ioc-birdlist']['source'] = $this->main_path."/ioc-birdlist_v3/";
        $this->sh['trunk']['source']        = $this->main_path."/trunk_20180521/";
        $this->sh['col']['source']          = $this->main_path."/col_v1/";
        $this->sh['ictv']['source']         = $this->main_path."/ictv_v3/";
        $this->sh['ictv']['run_gnparse']    = false; //
        $this->sh['odonata']['source']      = $this->main_path."/odonata_v2/";
        $this->sh['onychophora']['source']  = $this->main_path."/onychophora_v3/";
        $this->sh['earthworms']['source']   = $this->main_path."/earthworms_v3/";
        $this->sh['pbdb']['source']         = $this->main_path."/pbdb_v1/";
        $this->sh['pbdb']['run_gnparse']    = false; //has separate field for 'scientificNameAuthorship'

        //row_terminator was instroduced for ncbi
        //this was just Eli's initiative. May wait for Katja's instructions here...
        $this->sh['ncbi']['source']         = $this->main_path."/ncbi_v1/";
        $this->sh['ncbi']['run_gnparse']    = false; //has specific field for just canonical name
        $this->sh['ncbi']['iterator_options'] = array('row_terminator' => "\t|\n");
        
    }
    
    public function start($what)
    {
        /*
        $cmd = 'gnparser name "Gadus morhua Eli & Cha, 1972"';
        $json = shell_exec($cmd);
        print_r(json_decode($json, true));
        exit;
        
        gnparser file --input xaa.txt --output xaa_gnparsed.txt
        gnparser file --input xab.txt --output xab_gnparsed.txt
        gnparser file --input xac.txt --output xac_gnparsed.txt
        gnparser file --input xad.txt --output xad_gnparsed.txt
        gnparser file --input xae.txt --output xae_gnparsed.txt
        gnparser file --input xaf.txt --output xaf_gnparsed.txt
        gnparser file --input xag.txt --output xag_gnparsed.txt
        gnparser file --input xah.txt --output xah_gnparsed.txt
        ftp://ftp.ncbi.nlm.nih.gov/pub/taxonomy/
        */
        // /* CoL divided into smaller chunks
        // self::save_2local_gnparsed_file($what, "xaa_gnparsed.txt");
        // self::save_2local_gnparsed_file($what, "xab_gnparsed.txt");
        // self::save_2local_gnparsed_file($what, "xac_gnparsed.txt");
        // self::save_2local_gnparsed_file($what, "xad_gnparsed.txt");
        // self::save_2local_gnparsed_file($what, "xae_gnparsed.txt");
        // self::save_2local_gnparsed_file($what, "xaf_gnparsed.txt");
        // self::save_2local_gnparsed_file($what, "xag_gnparsed.txt");
        // self::save_2local_gnparsed_file($what, "xah_gnparsed.txt");
        // exit;
        // */
        // self::parent_id_check($what);
        // exit;
        
        $meta_xml_path = $this->sh[$what]['source']."meta.xml";
        $meta = self::analyze_meta_xml($meta_xml_path);
        if($meta == "No core entry in meta.xml") $meta = self::analyze_eol_meta_xml($meta_xml_path);
        $meta['what'] = $what;
        // print_r($meta); exit;
        
        $with_authorship = false;
        if(@$this->sh[$what]['run_gnparse'] === false) {}
        else { //normal
            if(self::need_2run_gnparser_YN($meta)) {
                $with_authorship = true;
                self::run_file_with_gnparser($meta);
                self::save_2local_gnparsed_file($what);
            }
        }
        self::process_taxon_file($meta, $with_authorship);
        self::parent_id_check($what);
    }
    private function need_2run_gnparser_YN($meta)
    {
        $what = $meta['what']; $i = 0;
        foreach(new FileIterator($this->sh[$what]['source'].$meta['taxon_file']) as $line => $row) {
            $i++;
            if($meta['ignoreHeaderLines'] && $i == 1) continue;
            if(!$row) continue;
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta['fields'] as $field) {
                $rec[$field] = $tmp[$k];
                $k++;
            }
            // echo "\n".count($tmp)."\n"; print_r($tmp);
            // print_r($rec); exit; //use to test if field - value is OK
            if(self::gnsparse_canonical($rec['scientificName'], "api") != $rec['scientificName']) return true;
            if($i >= 15) break;
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
        $i = 0;
        $m = 3765285/10; //for CoL
        
        foreach(new FileIterator($this->sh[$what]['source'].$meta['taxon_file'], false, true, @$this->sh[$what]['iterator_options']) as $line => $row) { //2nd and 3rd param; false and true respectively are default values
            $i++;
            if($meta['ignoreHeaderLines'] && $i == 1) continue;
            if(!$row) continue;
            
            if($what == 'ncbi') $tmp = explode("\t|\t", $row);
            else                $tmp = explode("\t", $row);
            
            $rec = array(); $k = 0;
            foreach($meta['fields'] as $field) {
                $rec[$field] = $tmp[$k];
                $k++;
            }
            // echo "\n".count($tmp)."\n"; print_r($tmp);
            // print_r($rec); exit; //use to test if field - value is OK
            if(($i % 5000) == 0) echo "\n".number_format($i)."\n";
            // echo "\n".number_format($i)."\n";
            //--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
            if(in_array($what, array('trunk', 'odonata', 'onychophora', 'pbdb'))) {
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
                if($with_authorship) $t['name'] = self::gnsparse_canonical($rec['scientificName'], 'cache'); //row[8]
                else                 $t['name'] = $rec['scientificName'];
                $t['taxon_id']      = $rec['taxonID'];              //row[9]
                $t['accepted_id']   = $rec['acceptedNameUsageID'];  //row[7]
                $t['rank']          = ($val = @$rec['taxonRank']) ? $val: "no rank"; //row[2]
                $t['source']        = '';
                if(($t['accepted_id'] != $t['taxon_id']) && $t['accepted_id'] != "") {
                    self::write2file("syn", $fn_syn, $t);
                    $has_synonym = true;
                }
                elseif(($t['accepted_id'] == $t['taxon_id']) || $t['accepted_id'] == "") self::write2file("tax", $fn_tax, $t);
            }
            //--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
            if(in_array($what, array('ioc-birdlist', 'ictv', 'earthworms'))) { //headers changed from version: ioc-birdlist_v2 to ioc-birdlist_v3
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
                
                // status = row[6]
                // parent_id = row[3]
                // accepted_id = row[2]
                // 
                
                $t = array();
                $t['parent_id'] = $rec['parentNameUsageID'];
                if($with_authorship) $t['name'] = self::gnsparse_canonical($rec['scientificName'], 'cache');
                else                 $t['name'] = $rec['scientificName'];
                $t['taxon_id']  = $rec['taxonID'];
                $t['rank']      = ($val = @$rec['taxonRank']) ? $val: "no rank";
                $t['source']    = '';
                self::write2file("tax", $fn_tax, $t);
            }
            //--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
            if(in_array($what, array('ictv'))) {
                /*
                parent_id = row[2]
                name = row[3]
                taxon_id = row[0]
                rank = row[5].lower()
                source = ''
                out_file.write(taxon_id + '\t|\t' + parent_id + '\t|\t' + name + '\t|\t' + rank + '\t|\t' + source + '\t|\t' + '\n')
                    [0] => ICTV:Sobemovirus
                    [1] => 
                    [2] => ICTV:unplaced Viruses
                    [3] => Sobemovirus
                    [4] => Viruses|unplaced
                    [5] => genus

                    [taxonID] => ICTV:Sobemovirus
                    [source] => 
                    [parentNameUsageID] => ICTV:unplaced Viruses
                    [scientificName] => Sobemovirus
                    [higherClassification] => Viruses|unplaced
                    [taxonRank] => genus
                */
            }
            
            //--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
            if(in_array($what, array('worms'))) {}
            if(in_array($what, array('ncbi'))) {
                /* Array(
                    [taxonID] => 3830
                    [scientificName] => Crotalaria pallida
                    [xxx] => 
                    [taxonomicStatus] => scientific name OR synonym
                )
                */
                $t = array();
                $t['parent_id']     = '';
                if($with_authorship) $t['name'] = self::gnsparse_canonical($rec['scientificName'], 'cache');
                else                 $t['name'] = $rec['scientificName'];
                $t['taxon_id']      = $rec['taxonID'];
                $t['accepted_id']   = '';
                $t['rank']          = ($val = @$rec['taxonRank']) ? $val: "no rank";
                $t['source']        = '';
                if($rec['taxonomicStatus'] == "synonym") {
                    self::write2file("syn", $fn_syn, $t);
                    $has_synonym = true;
                }
                elseif($rec['taxonomicStatus'] == "scientific name") self::write2file("tax", $fn_tax, $t);
            }
            
            //--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
            if(in_array($what, array('col'))) {
                /* breakdown when caching:
                $cont = false;
                // if($i >=  1    && $i < $m) $cont = true;
                // if($i >=  $m   && $i < $m*2) $cont = true;
                // if($i >=  $m*2 && $i < $m*3) $cont = true;
                // if($i >=  $m*3 && $i < $m*4) $cont = true;
                // if($i >=  $m*4 && $i < $m*5) $cont = true;
                // if($i >=  $m*5 && $i < $m*6) $cont = true;
                // if($i >=  $m*6 && $i < $m*7) $cont = true;
                // if($i >=  $m*7 && $i < $m*8) $cont = true;
                // if($i >=  $m*8 && $i < $m*9) $cont = true;
                // if($i >=  $m*9 && $i < $m*10) $cont = true;
                if(!$cont) continue;
                */
            }
            //--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
            //--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
            //--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
        }
        fclose($fn_tax);
        fclose($fn_syn);
        if(!$has_synonym) unlink($this->sh[$what]['source']."synonym.tsv");
    }
    private function parent_id_check($what)
    {
        echo "\nStarts parent_id check...\n";
        $i = 0;
        foreach(new FileIterator($this->sh[$what]['source'].'taxonomy.tsv') as $line => $row) {
            $i++; if($i == 1) continue;
            $rec = explode("\t|\t", $row);
            $uids[$rec[0]] = '';
        }
        echo "\nuids: ".count($uids)."\n";
        $i = 0; $undefined_parents = array();
        foreach(new FileIterator($this->sh[$what]['source'].'taxonomy.tsv') as $line => $row) {
            $i++; if($i == 1) continue;
            $rec = explode("\t|\t", $row);
            if($parent_uid = @$rec[1]) {
                // echo " [$parent_uid]";
                if(!isset($uids[$parent_uid])) $undefined_parents[$parent_uid] = '';
            }
        }
        echo "\nUndefined parents: ".count($undefined_parents)."\n";
    }
    private function run_file_with_gnparser($meta) //creates name_only.txt and converts it to name_only_gnparsed.txt using gnparser. gnparser converts entire file
    {
        $what = $meta['what']; $i = 0;
        if(file_exists($this->sh[$what]['source'].'name_only_gnparsed_DONE.txt')) {
            echo "\nAll names for [$what] has already been cached.\n";
            return;
        }
        echo "\nRunning gnparser...\n";
        $WRITE = fopen($this->sh[$what]['source']."name_only.txt", "w"); //will overwrite existing
        foreach(new FileIterator($this->sh[$what]['source'].$meta['taxon_file']) as $line => $row) {
            $i++;
            if($meta['ignoreHeaderLines'] && $i == 1) continue;
            if(!$row) continue;
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta['fields'] as $field) {
                $rec[$field] = $tmp[$k];
                $k++;
            }
            // echo "\n".count($tmp)."\n"; print_r($tmp);
            // print_r($rec); //exit; //use to test if field - value is OK
            if($val = @$rec['scientificName']) fwrite($WRITE, $val."\n");
        }
        fclose($WRITE);
        //convert entire file (names) to gnparser version
        $cmd = "gnparser file --input ".$this->sh[$what]['source']."name_only.txt --output ".$this->sh[$what]['source']."name_only_gnparsed.txt";
        $out = shell_exec($cmd);
        echo "\n$out\n";
    }
    private function save_2local_gnparsed_file($what, $filename = false)
    {
        if(file_exists($this->sh[$what]['source'].'name_only_gnparsed_DONE.txt')) {
            echo "\nAll names for [$what] has already been cached.\n";
            return;
        }
        $i = 0;
        if(!$filename) $filename = "name_only_gnparsed.txt";
        foreach(new FileIterator($this->sh[$what]['source'].$filename) as $line => $json) {
            $i++; if($i == 1) continue;
            // echo "\n$json\n";
            $arr = json_decode($json, true);
            // print_r($arr); exit;
            $name = $arr['verbatim'];
            if(($i % 1000) == 0) echo "\n".number_format($i)." $name - ";
            //now check if json already cached. Ignore if it does and save/cache it if it doesn't
            $options['cache_path'] = $this->smasher_download_options['cache_path'];
            $md5 = md5($name);
            $cache1 = substr($md5, 0, 2);
            $cache2 = substr($md5, 2, 2);
            if(!file_exists($options['cache_path'] . $cache1)) mkdir($options['cache_path'] . $cache1);
            if(!file_exists($options['cache_path'] . "$cache1/$cache2")) mkdir($options['cache_path'] . "$cache1/$cache2");
            $cache_path = $options['cache_path'] . "$cache1/$cache2/$md5.json";
            if(!file_exists($cache_path)) {
                if(($i % 1000) == 0) echo " - saving...";
                if($FILE = Functions::file_open($cache_path, 'w')) {
                    fwrite($FILE, $json);
                    fclose($FILE);
                }
            }
            else if(($i % 1000) == 0) echo " - already saved/cached";
        }
        Functions::file_rename($this->sh[$what]['source'].'name_only_gnparsed.txt', $this->sh[$what]['source'].'name_only_gnparsed_DONE.txt');
    }
    private function get_json_from_cache($name, $options = array()) //json generated by gnparser
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
            // echo "\nRetrieving cache ($name)...\n"; //good debug
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
    private function gnsparse_canonical($sciname, $method)
    {
        if($method == "api")       $json = Functions::lookup_with_cache($this->gnparser.urlencode($sciname), $this->smasher_download_options);
        elseif($method == "cache") $json = self::get_json_from_cache($sciname);
        if($obj = json_decode($json)) {
            if($ret = @$obj->namesJson[0]->canonical_name->value) return $ret;
        }
        exit("\nInvestigate cannot get canonical name [$sciname][$method]\n");
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