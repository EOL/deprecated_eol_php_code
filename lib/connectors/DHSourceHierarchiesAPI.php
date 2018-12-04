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
        
        if(Functions::is_production()) {
            $this->smasher_download_options = array(
                'cache_path'         => '/extra/eol_cache_smasher/',
                'download_wait_time' => 1000000, 'timeout' => 600, 'download_attempts' => 1, 'delay_in_minutes' => 0, 'expire_seconds' => false);
            $this->main_path = "/extra/eli_dwh/";
        }
        else {
            $this->smasher_download_options = array(
                // 'cache_path'         => '/Volumes/AKiTiO4/eol_cache_smasher/',
                'cache_path'         => '/Volumes/Thunderbolt4/z backup of AKiTiO4/eol_cache_smasher/',
                'download_wait_time' => 1000000, 'timeout' => 600, 'download_attempts' => 1, 'delay_in_minutes' => 0, 'expire_seconds' => false);
            $this->main_path = "/Volumes/AKiTiO4/d_w_h/dynamic_working_hierarchy-master/";
            $this->main_path = "/Volumes/AKiTiO4/d_w_h/eli_dwh/"; //old - initial runs
            $this->main_path = "/Volumes/AKiTiO4/d_w_h/eli_dwh2/"; //new - TRAM-800
        }
        /* Functions::lookup_with_cache($this->gnparser.urlencode($rec['scientificName']), $this->smasher_download_options); */
        
        $this->debug = array();
        $this->taxonomy_header = array("uid", "parent_uid", "name", "rank", "sourceinfo"); //('uid	|	parent_uid	|	name	|	rank	|	sourceinfo	|	' + '\n')
        $this->synonym_header = array("uid", "name", "type", "rank");                      //('uid	|	name	|	type	|	rank	|	' + '\n')

        // /* new list
        $this->sh['EET']['source']          = $this->main_path."/eolearthwormpatch/";
        $this->sh['EET']['has_syn']         = false;
        $this->sh['EET']['run_gnparse']     = true;
        
        $this->sh['ASW']['source']          = $this->main_path."/amphibianspeciesoftheworld/";
        $this->sh['ASW']['has_syn']         = false; //has syn but we don't want them
        $this->sh['ASW']['run_gnparse']     = true;
        
        $this->sh['ictv']['source']         = $this->main_path."/ICTV-virus_taxonomy-with-higherClassification/";
        $this->sh['ictv']['has_syn']        = false;
        $this->sh['ictv']['run_gnparse']    = false;

        $this->sh['CLP']['source']          = $this->main_path."/Catalogue_of_Life_Protists_DH/";
        $this->sh['CLP']['has_syn']         = false;
        $this->sh['CLP']['run_gnparse']     = true;
        
        $this->sh['trunk']['source']        = $this->main_path."/dynamichierarchytrunk2018-11-21/";
        $this->sh['trunk']['has_syn']       = false;
        $this->sh['trunk']['run_gnparse']   = false;

        $this->sh['ERE']['source']          = $this->main_path."/eoldynamichierarchyerebidaepatch/";
        $this->sh['ERE']['has_syn']         = false;
        $this->sh['ERE']['run_gnparse']     = false;
        
        $this->sh['IOC']['source']          = $this->main_path."/ioc-birdlist/";
        $this->sh['IOC']['has_syn']         = false;
        $this->sh['IOC']['run_gnparse']     = true;

        $this->sh['COL']['source']          = $this->main_path."/Catalogue_of_Life_DH/";
        $this->sh['COL']['has_syn']         = true;
        $this->sh['COL']['run_gnparse']     = true;
        
        $this->sh['BOM']['source']          = $this->main_path."/kitchingetal2018/";
        $this->sh['BOM']['has_syn']         = true; //?????
        $this->sh['BOM']['run_gnparse']     = true;
        
        $this->sh['NCBI']['source']         = $this->main_path."/NCBI_Taxonomy_Harvest_DH/";
        $this->sh['NCBI']['has_syn']        = true;
        $this->sh['NCBI']['run_gnparse']    = false; //has specific field for just canonical name

        $this->sh['ONY']['source']          = $this->main_path."/oliveira2012onychophora/";
        $this->sh['ONY']['has_syn']         = false;
        $this->sh['ONY']['run_gnparse']     = true;
        
        $this->sh['ODO']['source']          = $this->main_path."/worldodonata/";
        $this->sh['ODO']['has_syn']         = false; //has syn but we don't want them
        $this->sh['ODO']['run_gnparse']     = true;
        
        $this->sh['WOR']['source']          = $this->main_path."/WoRMS_DH/";
        $this->sh['WOR']['has_syn']         = true;
        $this->sh['WOR']['run_gnparse']     = true;
        // */
        /* old list
        $this->sh['WOR']['source']        = $this->main_path."/worms_v5/";
        $this->sh['IOC']['source'] = $this->main_path."/ioc-birdlist_v3/";
        $this->sh['trunk']['source']        = $this->main_path."/trunk_20180521/";
        $this->sh['COL']['source']          = $this->main_path."/col_v1/";
        $this->sh['ictv']['source']         = $this->main_path."/ictv_v3/";
        $this->sh['ictv']['run_gnparse']    = false; //
        $this->sh['ODO']['source']      = $this->main_path."/odonata_v2/";
        $this->sh['ONY']['source']  = $this->main_path."/onychophora_v3/";
        $this->sh['EET']['source']   = $this->main_path."/earthworms_v3/";
        $this->sh['pbdb']['source']         = $this->main_path."/pbdb_v1/";
        $this->sh['pbdb']['run_gnparse']    = false; //has separate field for 'scientificNameAuthorship'
        */
        /* old
        //row_terminator was instroduced for ncbi
        //this was just Eli's initiative. May wait for Katja's instructions here...
        $this->sh['ncbi']['source']         = $this->main_path."/ncbi_v1/";
        $this->sh['ncbi']['run_gnparse']    = false; //has specific field for just canonical name
        $this->sh['ncbi']['iterator_options'] = array('row_terminator' => "\t|\n");
        */
    }
    public function start($what)
    {
        self::get_problematic_names(); exit("\n-end-\n");
        /*
        $json = Functions::lookup_with_cache($this->gnparser.urlencode('Notoscolex wellingtonensis (Spencer, 1895)'), $this->smasher_download_options);
        exit("\n".$json."\n");
        */
        /*
        $sciname = "Gadus morhua Eli 1972";
        $canonical = self::gnsparse_canonical($sciname, 'api');
        // print_r(json_decode($json, true));
        echo "\n[$canonical]\n";
        $canonical = self::gnsparse_canonical($sciname, 'cache');
        // print_r(json_decode($json, true));
        echo "\n[$canonical]\n";
        exit("\nstopx\n");
        */
        /*
        $sciname = "Gadus morhua Eli 1972";
        $json = Functions::lookup_with_cache($this->gnparser.urlencode($sciname), $this->smasher_download_options);
        print_r(json_decode($json, true));
        $json = self::get_json_from_cache($sciname);
        print_r(json_decode($json, true));
        exit;
        */
        /*
        $cmd = 'gnparser name "Notoscolex imparicystis (Jamieson, 1973)"';
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
        /* CoL divided into smaller chunks
        self::save_2local_gnparsed_file($what, "xaa_gnparsed.txt");
        self::save_2local_gnparsed_file($what, "xab_gnparsed.txt");
        self::save_2local_gnparsed_file($what, "xac_gnparsed.txt");
        self::save_2local_gnparsed_file($what, "xad_gnparsed.txt");
        self::save_2local_gnparsed_file($what, "xae_gnparsed.txt");
        self::save_2local_gnparsed_file($what, "xaf_gnparsed.txt");
        self::save_2local_gnparsed_file($what, "xag_gnparsed.txt");
        self::save_2local_gnparsed_file($what, "xah_gnparsed.txt");
        exit;
        */
        // self::parent_id_check($what); exit;
        
        $meta_xml_path = $this->sh[$what]['source']."meta.xml";
        $meta = self::analyze_meta_xml($meta_xml_path);
        if($meta == "No core entry in meta.xml") $meta = self::analyze_eol_meta_xml($meta_xml_path);
        $meta['what'] = $what;
        print_r($meta); //exit;
        
        $with_authorship = false;
        if(@$this->sh[$what]['run_gnparse'] === false) {}
        else { //normal
            if(self::need_2run_gnparser_YN($meta)) {
                $with_authorship = true;
                /* wise move before. That is when using the old gnparser version. The new doesn't have a \n line separator between json records.
                self::run_file_with_gnparser($meta);
                self::save_2local_gnparsed_file($what);
                */
            }
        }
        self::process_taxon_file($meta, $with_authorship);
        self::parent_id_check($what);
        self::show_totals($what);
        if($this->sh[$what]['run_gnparse'] != $with_authorship) echo "\nInvestigate the need to run gnparser [$what]\n";
        else                                                    echo "\n-OK-\n";
    }
    private function get_problematic_names() //sheet found here: https://eol-jira.bibalex.org/browse/TRAM-800
    {
        require_library('connectors/GoogleClientAPI');
        $func = new GoogleClientAPI(); //get_declared_classes(); will give you how to access all available classes
        $params['spreadsheetID'] = '1A08xM14uDjsrs-R5BXqZZrbI_LiDNKeO6IfmpHHc6wg';
        $params['range']         = 'gnparser failures!C2:D1000'; //where "A" is the starting column, "C" is the ending column, and "1" is the starting row.
        $arr = $func->access_google_sheet($params);
        //start massage array
        foreach($arr as $item) $final[$item[0]] = $item[1];
        print_r($final); exit;
        return $final;
    }
    private function show_totals($what)
    {
        $filenames = array('taxonomy.tsv','taxon.tab');
        foreach($filenames as $filename) {
            $total = shell_exec("wc -l < ".escapeshellarg($this->sh[$what]['source'].$filename));
            $total = trim($total);  echo "\n$filename: [$total]\n";
        }
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
                if(!$field) continue;
                $rec[$field] = $tmp[$k];
                $k++;
            }
            // echo "\n".count($tmp)."\n"; print_r($tmp);
            // print_r($rec); exit; //use to test if field - value is OK ==================================================================
            if($val = self::gnsparse_canonical($rec['scientificName'], "cache")) {
                if($val != $rec['scientificName']) return true;
            }
            if($i >= 15) break;
        }
        return false;
    }
    private function process_taxon_file($meta, $with_authorship)
    {
        if($with_authorship) echo "\nWith authorship\n";
        else                 echo "\nWithout authorship\n";
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
            
            /* old
            if($what == 'ncbi') $tmp = explode("\t|\t", $row);
            else                $tmp = explode("\t", $row);
            */
                                $tmp = explode("\t", $row);
            
            $rec = array(); $k = 0;
            foreach($meta['fields'] as $field) {
                if(!$field) continue;
                $rec[$field] = $tmp[$k];
                $k++;
            }
            // echo "\n".count($tmp)."\n"; print_r($tmp);
            // print_r($rec); exit("\ncheck first [$with_authorship]\n"); //use to test if field - value is OK
            if(($i % 5000) == 0) echo "\n".number_format($i)."\n";
            // echo "\n".number_format($i)."\n";
            //--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
            if(in_array($what, array('WOR', 'NCBI', 'BOM', 'COL', 'trunk', 'ODO', 'ONY', 'pbdb', 
                                     'ERE', 'CLP', 'ASW', 'IOC', 'ictv', 'EET'))) {
                /*  [index] => 1
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
                
                if($rec['scientificName'] == "Cataladrilus (Cataladrilus) Qiu and Bouche, 1998") {
                    print_r($rec); exit("\ndebugging...\n");
                }
                
                if($what == "NCBI") {
                    if(in_array($rec['taxonomicStatus'], array("in-part", "authority", "misspelling", "equivalent name", "genbank synonym", "misnomer", "teleomorph"))) continue;
                }
                elseif($what == "COL") {
                    if(in_array($rec['taxonomicStatus'], array("ambiguous synonym", "misapplied name"))) continue;
                }
                
                $t = array();
                $t['parent_id']     = $rec['parentNameUsageID'];    //row[4]
                if($with_authorship) $t['name'] = self::gnsparse_canonical($rec['scientificName'], 'cache'); //row[8]
                else                 $t['name'] = $rec['scientificName'];
                $t['taxon_id']      = $rec['taxonID'];              //row[9]
                $t['accepted_id']   = @$rec['acceptedNameUsageID'];  //row[7]
                $t['rank']          = ($val = @$rec['taxonRank']) ? $val: "no rank"; //row[2]
                $t['source']        = '';

                if($this->sh[$what]['has_syn']) {
                    if(($t['accepted_id'] != $t['taxon_id']) && $t['accepted_id'] != "") {
                        self::write2file("syn", $fn_syn, $t);
                        $has_synonym = true;
                    }
                    elseif(($t['accepted_id'] == $t['taxon_id']) || $t['accepted_id'] == "") self::write2file("tax", $fn_tax, $t);
                }
                elseif(($t['accepted_id'] == $t['taxon_id']) || $t['accepted_id'] == "") self::write2file("tax", $fn_tax, $t);
            }
            //--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
            /*
            if(in_array($what, array('ERE', 'CLP', 'ASW', 'IOC', 'ictv', 'EET'))) { //headers changed from version: ioc-birdlist_v2 to ioc-birdlist_v3
                    // [taxonID] => 09af091e166bfa45493c6242ebf16a7c
                    // [scientificName] => Celeus elegans leotaudi Hellmayr, 1906
                    // [taxonRank] => subspecies
                    // [parentNameUsageID] => d6edba5dd4d993cbab690c2df8fc937f
                    // [taxonRemarks] => 
                    // [canonicalName] => Celeus elegans leotaudi
                    // [source] => http://www.worldbirdnames.org/bow/woodpeckers/
                    // [scientificNameAuthorship] => Hellmayr, 1906
                    // out_file_t.write(taxon_id + '\t|\t' + parent_id + '\t|\t' + name + '\t|\t' + rank + '\t|\t' + source + '\t|\t' + '\n')
                $t = array();
                $t['parent_id'] = $rec['parentNameUsageID'];
                if($with_authorship) $t['name'] = self::gnsparse_canonical($rec['scientificName'], 'cache');
                else                 $t['name'] = $rec['scientificName'];
                $t['taxon_id']  = $rec['taxonID'];
                $t['rank']      = ($val = @$rec['taxonRank']) ? $val: "no rank";
                $t['source']    = '';
                self::write2file("tax", $fn_tax, $t);
            }*/
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
            //--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
            if(in_array($what, array('COL'))) {
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
 
        /* Works OK during older version of gnparser. The later version doesn't have a line separator (\n) between json record.
        //convert entire file (names) to gnparser version
        $cmd = "gnparser file --input ".$this->sh[$what]['source']."name_only.txt --output ".$this->sh[$what]['source']."name_only_gnparsed.txt";
        $out = shell_exec($cmd);
        echo "\n$out\n";
        */
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
            if(!file_exists($cache_path) || filesize($cache_path) == 0) {
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
        $cmd = 'gnparser name -f json-compact "'.$name.'"';
        $json = shell_exec($cmd);
        if($json) {
            if($FILE = Functions::file_open($cache_path, 'w+')) {
                fwrite($FILE, $json);
                fclose($FILE);
            }
            //just to check if you can now get the canonical
            if($obj = json_decode($json)) {
                if($ret = @$obj->canonical_name->value)     echo " ---> OK [$ret]";
                elseif($ret = @$obj->canonicalName->value)  echo " ---> OK [$ret]";
                else                                        echo " ---> FAIL";
            }
        }
        return $json;
    }
    
    private function write2file($ext, $fn, $t)
    {
        if($ext == "syn")     fwrite($fn, $t['accepted_id'] . "\t|\t" . $t['name'] . "\t|\t" . 'synonym' . "\t|\t" . "\t|\t" . "\n");
        elseif($ext == "tax") fwrite($fn, $t['taxon_id'] . "\t|\t" . $t['parent_id'] . "\t|\t" . $t['name'] . "\t|\t" . $t['rank'] . "\t|\t" . $t['source'] . "\t|\t" . "\n");
    }
    private function get_canonical_via_api($sciname, $options)
    {
        $json = Functions::lookup_with_cache($this->gnparser.urlencode($sciname), $options);
        if($obj = json_decode($json)) {
            if($ret = @$obj->namesJson[0]->canonical_name->value) return $ret;
        }
    }
    private function gnsparse_canonical($sciname, $method)
    {
        $sciname = str_replace('"', "", $sciname);
        
        if($sciname == "all") return "all";
        elseif($sciname == "root") return "root";
        elseif($sciname == "not Bacteria Haeckel 1894") return "not Bacteria";
        // elseif($sciname == "unplaced extinct Onychophora") return "unplaced extinct Onychophora";
        // elseif($sciname == "[Cellvibrio] gilvus") return "[Cellvibrio] gilvus";
        // elseif($sciname == "unplaced Cryptophyceae") return "unplaced Cryptophyceae";

        //force
        if($sciname == "Ichthyoidei- Eichwald, 1831") $sciname = "Ichthyoidei Eichwald, 1831";
        elseif($sciname == "Raniadae- Smith, 1831") $sciname = "Raniadae Smith, 1831";
        elseif($sciname == "prokaryote") $sciname = "Prokaryote";
        elseif($sciname == "prokaryotes") $sciname = "Prokaryotes";
        elseif($sciname == "Amblyomma (Cernyomma) hirtum. Camicas et al., 1998") $sciname = "Amblyomma (Cernyomma) hirtum Camicas et al., 1998";
        elseif($sciname == "Cryptops (Cryptops) vector Chamberlin 1939") $sciname = "Cryptops (Cryptops) vector";
        
        if($method == "api") {
            if($canonical = self::get_canonical_via_api($sciname, $this->smasher_download_options)) return $canonical;
        }
        elseif($method == "cache") {
            $json = self::get_json_from_cache($sciname);
            if($obj = json_decode($json)) {
                if($ret = @$obj->canonical_name->value) return $ret;
                elseif($ret = @$obj->canonicalName->value) return $ret;
                else { //the gnparser code was updated due to bug. So some names has be be re-run using cmdline OR API with expire_seconds = 0

                    $options = $this->smasher_download_options; $options['expire_seconds'] = 0;
                    $json = self::get_json_from_cache($sciname, $options);
                    if($obj = json_decode($json)) {
                        if($ret = @$obj->canonical_name->value) return $ret;
                        elseif($ret = @$obj->canonicalName->value) return $ret;
                        else {

                            return $obj->verbatim; //un-successfull
                        
                            /* workin OK but no need to call API
                            print_r($obj); exit("\n".$obj->verbatim."\nInvestigate before use API($sciname)\n");
                            $options = $this->smasher_download_options; $options['expire_seconds'] = 0;
                            if($canonical = self::get_canonical_via_api($sciname, $options)) return $canonical;
                            */
                            
                        }
                    }
                }
            }
        }
        echo("\nInvestigate cannot get canonical name [$sciname][$method]\n");
    }
    public function analyze_eol_meta_xml($meta_xml_path, $row_type = false)
    {
        if(!$row_type) $row_type = "http://rs.tdwg.org/dwc/terms/Taxon";
        if(file_exists($meta_xml_path)) {
            $xml_string = file_get_contents($meta_xml_path);
            $xml = simplexml_load_string($xml_string);
            
            if(!isset($xml->table)) {
                if(isset($xml->core)) $xml_table = $xml->core; //e.g. meta.xml from WoRMS http://www.marinespecies.org/export/eol/WoRMS2EoL.zip
            }
            else                      $xml_table = $xml->table;
            
            foreach($xml_table as $tbl) {
                if($tbl['rowType'] == $row_type) {
                    if(in_array($tbl['ignoreHeaderLines'], array(1, true))) $ignoreHeaderLines = true;
                    else                                                    $ignoreHeaderLines = false;
                    $fields = array();
                    foreach($tbl->field as $f) {
                        $term = (string) $f['term'][0];
                        $uris[] = $term;
                        $fields[] = pathinfo($term, PATHINFO_FILENAME);
                    }
                    $file = (string) $tbl->files->location;
                    return array('fields' => $fields, 'taxon_file' => $file, 'file' => $file, 'ignoreHeaderLines' => $ignoreHeaderLines);
                }
                else {}
            }
            exit("\nInvestigate undefined row_type [$row_type]\n");
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