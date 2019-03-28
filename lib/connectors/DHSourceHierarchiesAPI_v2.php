<?php
namespace php_active_record;
/* connector: [dwh_v2.php] */
class DHSourceHierarchiesAPI_v2
{
    function __construct($folder = "") //sometimes blank bec. e.g. used in ---> private function get_meta_info()
    {
        $this->mysqli =& $GLOBALS['db_connection'];
        $this->resource_id = $folder;
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));

        $this->gnparser = "http://parser.globalnames.org/api?q=";
        if(Functions::is_production()) {
            $this->smasher_download_options = array(
                'cache_path'         => '/extra/eol_cache_smasher/',
                'download_wait_time' => 250000, 'timeout' => 600, 'download_attempts' => 1, 'delay_in_minutes' => 0, 'expire_seconds' => false); //false
            $this->main_path = "/extra/d_w_h/2018_12/"; //download_wait_time is 1/4 of a second -> 1000000/4
        }
        else {
            $this->smasher_download_options = array(
                'cache_path'         => '/Volumes/AKiTiO4/eol_cache_smasher/', //new, started from blank
                // 'cache_path'         => '/Volumes/Thunderbolt4/z backup of AKiTiO4/eol_cache_smasher/',
                'download_wait_time' => 250000, 'timeout' => 600, 'download_attempts' => 1, 'delay_in_minutes' => 0, 'expire_seconds' => false); //false
            $this->main_path = "/Volumes/AKiTiO4/d_w_h/dynamic_working_hierarchy-master/"; //still during Anne's time. I never used it. Just for testing in the earlier days.
            $this->main_path = "/Volumes/AKiTiO4/d_w_h/2018_06/"; //old - initial runs
            $this->main_path = "/Volumes/AKiTiO4/d_w_h/2018_12/"; //new - TRAM-800 - 1st Smasher run
            $this->main_path = "/Volumes/AKiTiO4/d_w_h/2019_03/"; //new - TRAM-805 - 2nd Smasher run
        }
        /* Functions::lookup_with_cache($this->gnparser.urlencode($rec['scientificName']), $this->smasher_download_options); */
        
        $this->debug = array();
        $this->taxonomy_header = array("uid", "parent_uid", "name", "rank", "sourceinfo"); //('uid	|	parent_uid	|	name	|	rank	|	sourceinfo	|	' + '\n')
        $this->synonym_header = array("uid", "name", "type", "rank");                      //('uid	|	name	|	type	|	rank	|	' + '\n')


/*paste these in terminal
php update_resources/connectors/dwh_v2.php _ EET
php update_resources/connectors/dwh_v2.php _ ASW
php update_resources/connectors/dwh_v2.php _ ictv
php update_resources/connectors/dwh_v2.php _ CLP
php update_resources/connectors/dwh_v2.php _ trunk
php update_resources/connectors/dwh_v2.php _ ERE
php update_resources/connectors/dwh_v2.php _ IOC
php update_resources/connectors/dwh_v2.php _ BOM
php update_resources/connectors/dwh_v2.php _ NCBI
php update_resources/connectors/dwh_v2.php _ ONY
php update_resources/connectors/dwh_v2.php _ ODO
php update_resources/connectors/dwh_v2.php _ WOR
php update_resources/connectors/dwh_v2.php _ COL
php update_resources/connectors/dwh_v2.php _ COC
php update_resources/connectors/dwh_v2.php _ VSP

*/
        //for testing
        $this->sh['xxx']['source']          = $this->main_path."/xxx/";
        $this->sh['xxx']['has_syn']         = false;
        $this->sh['xxx']['run_gnparse']     = true;

        // /* 2 new from March 2019 run ---------------------------------------------------------------------------------------------------

        $this->sh['COC']['source']          = $this->main_path."/eolcoccinelloideapatch/";
        $this->sh['COC']['has_syn']         = false;
        $this->sh['COC']['run_gnparse']     = true;

        $this->sh['VSP']['source']          = $this->main_path."/eolvespoideapatch/";
        $this->sh['VSP']['has_syn']         = false;
        $this->sh['VSP']['run_gnparse']     = true;

        // /* new list ---------------------------------------------------------------------------------------------------
        $this->sh['EET']['source']          = $this->main_path."/eolearthwormpatch/";
        $this->sh['EET']['has_syn']         = false;
        $this->sh['EET']['run_gnparse']     = true;

        $this->sh['ASW']['source']          = $this->main_path."/amphibianspeciesoftheworld/";
        $this->sh['ASW']['has_syn']         = false; //has syn but we don't want them
        $this->sh['ASW']['run_gnparse']     = true;

        $this->sh['ictv']['source']         = $this->main_path."/ICTV-virus_taxonomy-with-higherClassification/";
        $this->sh['ictv']['has_syn']        = false;
        $this->sh['ictv']['run_gnparse']    = false;

        $this->sh['CLP']['source']          = $this->main_path."/Catalogue_of_Life_Protists_DH_20Feb2019/";
        $this->sh['CLP']['has_syn']         = false;
        $this->sh['CLP']['run_gnparse']     = true;

        $this->sh['trunk']['source']        = $this->main_path."/dhtrunk25mar201920190326022449/";
        $this->sh['trunk']['has_syn']       = false;
        $this->sh['trunk']['run_gnparse']   = false;

        $this->sh['ERE']['source']          = $this->main_path."/eoldynamichierarchyerebidaepatch/";
        $this->sh['ERE']['has_syn']         = false;
        $this->sh['ERE']['run_gnparse']     = false;

        $this->sh['IOC']['source']          = $this->main_path."/ioc-birdlist/";
        $this->sh['IOC']['has_syn']         = false;
        $this->sh['IOC']['run_gnparse']     = true;

        $this->sh['COL']['source']          = $this->main_path."/Catalogue_of_Life_DH_20Feb2019/";
        $this->sh['COL']['has_syn']         = false; //false based from: https://eol-jira.bibalex.org/browse/TRAM-800?focusedCommentId=63045&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-63045
        $this->sh['COL']['run_gnparse']     = true;

        $this->sh['BOM']['source']          = $this->main_path."/kitchingetal2018/";
        $this->sh['BOM']['has_syn']         = true;
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
        // --------------------------------------------------------------------------------------------------- */
        $this->taxonomy_header_tmp = array("name", "uid", "parent_uid", "rank");
        $this->synonym_header_tmp = array("name", "uid", "accepted_x_id", "type");
        
        /* old
        //row_terminator was instroduced for ncbi
        //this was just Eli's initiative. May wait for Katja's instructions here...
        $this->sh['ncbi']['source']         = $this->main_path."/ncbi_v1/";
        $this->sh['ncbi']['run_gnparse']    = false; //has specific field for just canonical name
        $this->sh['ncbi']['iterator_options'] = array('row_terminator' => "\t|\n");
        */
    }
    public function test($what)
    {
        /*
        $this->what = $what;
        $string = "Malmopsylla† karatavica Bekker-Migdisova, 1985";
        //$string = '“montereina” greeleyi (MacFarland, 1909)';
        // $string = "V latipennis Baehr, 2006";
        $string = "Curcuma vitellina Škornick. & H.Ð.Tran";

        $string = "Bolivina suЬincrassata Khalilov, 1956";
        //$string = "Bolivina suЬincrassata var. caucasica Khalilov, 1956";
        //$string = "Bolivina suЬincrassata var. costata Khalilov, 1956";
        //$string = "Bolivina dilataЬilis Khalilov, 1956";
        
        echo "\norig: $string";
        $string = str_replace("†","",$string);
        $string = Functions::conv_to_utf8($string);
        echo "\nutf8: $string";
        echo "\ngnparser canonical: ".self::gnsparse_canonical($string, 'cache');
        $c = Functions::canonical_form($string);
        exit("\ncanonical: $c\n");
        */
        /*
        gnparser file -f json-compact --input test.txt --output test_gnparsed.txt
        self::save_2local_gnparsed_file_new($what, "test_gnparsed.txt"); exit("\n-end test-\n");

        gnparser file -f simple --input test.txt --output test_gnparsed.txt

        gnparser file -f json-compact --input xaa.txt --output xaa_gnparsed.txt
        gnparser file -f json-compact --input xab.txt --output xab_gnparsed.txt
        gnparser file -f json-compact --input xac.txt --output xac_gnparsed.txt
        gnparser file -f json-compact --input xad.txt --output xad_gnparsed.txt
        gnparser file -f json-compact --input xae.txt --output xae_gnparsed.txt
        gnparser file -f json-compact --input xaf.txt --output xaf_gnparsed.txt
        gnparser file -f json-compact --input xag.txt --output xag_gnparsed.txt
        gnparser file -f json-compact --input xah.txt --output xah_gnparsed.txt

        self::save_2local_gnparsed_file_new($what, "xaa_gnparsed.txt"); exit("\n-end xaa_gnparsed-\n");
        self::save_2local_gnparsed_file_new($what, "xab_gnparsed.txt"); exit("\n-end xab_gnparsed-\n");
        self::save_2local_gnparsed_file_new($what, "xac_gnparsed.txt"); exit("\n-end xac_gnparsed-\n");
        self::save_2local_gnparsed_file_new($what, "xad_gnparsed.txt"); exit("\n-end xad_gnparsed-\n");
        self::save_2local_gnparsed_file_new($what, "xae_gnparsed.txt"); exit("\n-end xae_gnparsed-\n");
        self::save_2local_gnparsed_file_new($what, "xaf_gnparsed.txt"); exit("\n-end xaf_gnparsed-\n");
        self::save_2local_gnparsed_file_new($what, "xag_gnparsed.txt"); exit("\n-end xag_gnparsed-\n");
        self::save_2local_gnparsed_file_new($what, "xah_gnparsed.txt"); exit("\n-end xah_gnparsed-\n");
        */

        // self::parent_id_check($what); exit;
        
        // /* get uuid
        $str = "unplaced extinct Onychophora\n";
        $str = "Pseudostaffella bareпtsevensis Solovieva, 1984
        Profusulinella оblопgа Potievskaya, 1964
        Kanmeraia amdeгmensis Solovieva, 1984";
        $str = "V sinister Liebherr, 2005
        V vanemdeni Liebherr, 2005
        Mesochra incertae sedis
        Mesochra incertae sedis lybica Blanchard & Richard, 1891
        'gammarus' heteroclitus Viviani, 1805
        'gammarus'
        V";
        $str = "Asaccus kurdistanensis Rastegar-pouyani,nilson & Faizi, 2006
        Polydrosus van volxemi Dalla Torre & van Emden, 1936
        Hilipus de geeri Boheman, C.H. in Schönherr, C.J., 1836";
        $arr = explode("\n", $str); $arr = array_map('trim', $arr);
        $arr = array_unique($arr);  foreach($arr as $a) $final[$a] = '';
        print_r($final); self::scan_resource_file($meta, $final); exit("\n");
        // */
    }
    private function get_meta($what)
    {
        $meta_xml_path = $this->sh[$what]['source']."meta.xml";
        $meta = self::analyze_meta_xml($meta_xml_path);
        if($meta == "No core entry in meta.xml") $meta = self::analyze_eol_meta_xml($meta_xml_path);
        $meta['what'] = $what;
        print_r($meta); //exit;
        return $meta;
    }
    public function start($what, $special_task = false)
    {
        $this->sh[$what]['destin'] = $this->main_path."/zDestination/$what/";
        if(!is_dir($this->sh[$what]['destin'])) mkdir($this->sh[$what]['destin']);
        /*===================================starts here=====================================================================*/
        $this->what = $what;

        $meta = self::get_meta($what);

        /* this is now obsolete. Based on the new scheme from a newer ticket TRAM-803: 
        https://eol-jira.bibalex.org/browse/TRAM-803?focusedCommentId=63324&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-63324
        The new process is added in DWH_CoL_API_20Feb2019.php - under function fix_CLP_taxa_with_not_assigned_entries_V2().
        if($special_task == "CLP_adjustment") {
            self::fix_CLP_taxa_with_not_assigned_entries($meta);
            exit("\n-end fix [$what]-\n");
        }
        */

        $this->problematic_names = array();
        // /* get problematic names from Google sheet
        $this->problematic_names = self::get_problematic_names();   //UN-COMMENT IN REAL OPERATION
        // print_r($this->problematic_names); exit;
        // */

        // /* utility write all names. This has now become the only sustainable approach especially for big resources like COL, since it has 3,620,095 rows
        self::utility_write_all_names($meta);
        if($undefined_parents = self::parent_id_check($what)) {
            self::remove_undefined_parents_and_their_descendants($meta, $undefined_parents, 'taxonomy');
            self::parent_id_check($what);
        }
        if($undefined_accepted_ids = self::parent_id_check_synonyms($what)) {
            self::remove_undefined_parents_and_their_descendants($meta, $undefined_accepted_ids, 'synonym');
            self::parent_id_check_synonyms($what);
        }
        if($this->debug) print_r($this->debug);
        exit("\n-end write all names [$what]-\n"); //works OK
        
        // Then start caching... No longer used. OBSOLETE
        // self::run_TSV_file_with_gnparser_new("COL_ALL_NAMES_2_gnparsed.txt", $what); exit("\nCaching TSV for [$what] done!\n");
        // */

        /* this is one-time run for every dataset - all 13 datasets ============================================================= OBSOLETE ever since utility_write_all_names()
        self::run_file_with_gnparser_new($meta);    exit("\nCaching for [$what] done!\n"); //is used for blank slate, meaning new cache path or new gnparser version.
        self::run_file_with_gnparser_new_v2($meta); exit("\nCaching for [$what] done!\n"); //is used to get names left behind from above. Only processes names, where cache doesn't exist yet
        ========================================================================================================================= */
        
        $with_authorship = false;
        if($this->sh[$what]['run_gnparse'] == false) {}
        else { //normal
            if(self::need_2run_gnparser_YN($meta)) {
                $with_authorship = true;
                /* wise move before. That is when using the old gnparser version. The new doesn't have a \n line separator between json records.
                self::run_file_with_gnparser($meta);
                self::save_2local_gnparsed_file($what);
                */
            }
        }
        
        /* 5. Duplicate taxa --- utility generating duplicates report for Katja ==========================================================================================
        // WOR has a bunch of species and subspecific taxa that have the same canonical form but different authors. These are mostly foraminiferans and a few diatoms. 
        // I'm not sure what to do about these. Clearly, they can't all be accepted names, but WOR still has them as such. I don't quite remember how we handled these 
        // in previous smasher runs. If smasher can't handle these apparent duplicate taxa, we could consider cleaning them up by keeping the one with the oldest date and 
        // removing the ones with the more recent data, along with their children.
        // self::check_for_duplicate_canonicals($meta, $with_authorship); exit("\n-end checking for duplicates [$what]-\n");
        self::check_for_duplicate_canonicals_new($meta, "taxonomy"); exit("\n-end checking for duplicates (new) [$what]-\n");
        ================================================================================================================================================================= */
        //initialize this report file
        $path = $this->sh[$what]['source']."../zFailures/$what".".txt"; if(file_exists($path)) unlink($path);
        
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
        $params['spreadsheetID'] = '1A08xM14uDjsrs-R5BXqZZrbI_LiDNKeO6IfmpHHc6wg'; //same spreadsheet for ver 1.0 and ver 1.1
        $params['range']         = 'gnparser failures!B2:D1000'; //where "A" is the starting column, "C" is the ending column, and "1" is the starting row.
        $arr = $func->access_google_sheet($params);
        //start massage array
        foreach($arr as $item) $final[$item[0]] = $item[2];
        return $final;
    }
    private function show_totals($what)
    {
        $filenames = array('taxonomy.tsv', 'synonym.tsv', 'taxon.tab', 'taxa.txt');
        foreach($filenames as $filename) {
            $file = $this->sh[$what]['destin'].$filename;
            if(file_exists($file)) {
                $total = shell_exec("wc -l < ".escapeshellarg($file));
                $total = trim($total);  echo "\n$filename: [$total]\n";
            }
        }
    }
    private function get_ctr_value($what)
    {
        $directory = $this->sh[$what]['destin'];
        $filecount = 0;
        $files = glob($directory . "taxonomy_*_gnparsed.txt"); //taxonomy_1_gnparsed.txt
        if($files) $filecount = count($files);
        return $filecount;
    }
    private function print_duplicates($what, $test, $postfix)
    {
        // print_r($test);
        echo "\n==============================\n";
        echo "\nPrinting duplicates [$postfix]: ".count($test)."\n";
        echo "\n==============================\n";
        $path = $this->sh[$what]['source']."../zFailures/$what".$postfix;
        $FILE = Functions::file_open($path, 'w');
        foreach($test as $canon => $origs) {
            if(count($origs) > 1) {
                $k = 0;
                foreach($origs as $orig) {
                    // if($canon != $orig && $canon) {
                        fwrite($FILE, $canon."\t".$orig."\n");
                        $k++;
                    // }
                }
                fwrite($FILE, "\n");
                if($k == 1) fwrite($FILE, "***\n");
            }
        }
        fclose($FILE);
        //just to clean-up, delete zero size files
        $path = $this->sh[$what]['source']."../zFailures/$what".$postfix;
        if(file_exists($path)) {
            if(!filesize($path)) {
                echo "\nNo duplicates for [$postfix] [$what]\n"; unlink($path);
            }
        }
    }
    private function is_record_valid($what, $rec)
    {
        if($what == "NCBI") {
            if(in_array($rec['taxonomicStatus'], array("in-part", "authority", "misspelling", "equivalent name", "genbank synonym", "misnomer", "teleomorph"))) return false;
        }
        elseif($what == "COL") {
            if(in_array($rec['taxonomicStatus'], array("synonym", "ambiguous synonym", "misapplied name"))) return false;
        }
        elseif($what == "ASW") {
            if(in_array($rec['taxonomicStatus'], array("invalid"))) return false;
        }
        elseif($what == "ODO") {
            if(in_array($rec['taxonomicStatus'], array("synonym"))) return false;
        }
        return true;
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
    private function clean_rank($rank)
    {
        $rank = strtolower($rank);
        if($rank == "subsp.")       $rank = "subspecies";
        elseif($rank == "var.")     $rank = "variety";
        elseif($rank == "f.")       $rank = "form";
        elseif($rank == "varietas") $rank = "variety";
        elseif($rank == "forma")    $rank = "form";
        return $rank;
    }
    private function parent_id_check($what)
    {
        echo "\n-------------------------------\nStarts parent_id check...\n"; $undefined_parents = array();
        $uids = self::get_uids_from_taxonomy_tsv($what);
        echo "\nuids: ".count($uids)."\n"; $i = 0; $undefined_parents = array();
        foreach(new FileIterator($this->sh[$what]['destin'].'taxonomy.tsv') as $line => $row) {
            $i++; if($i == 1) continue;
            $rec = explode("\t|\t", $row);
            if($parent_uid = @$rec[1]) {
                if(!isset($uids[$parent_uid])) $undefined_parents[$parent_uid] = '';
            }
        }
        echo "\nUndefined parents: ".count($undefined_parents)."\n";
        if($undefined_parents) {
            // echo "\nUndefined parents for [$what]:\n"; print_r($undefined_parents);
        }
        return $undefined_parents;
    }
    private function parent_id_check_synonyms($what)
    {
        if(!file_exists($this->sh[$what]['destin'].'synonym.tsv')) return array();
        echo "\n-------------------------------\nStarts accepted_id check synonyms...\n"; $undefined_accepted_ids = array();
        $uids = self::get_uids_from_taxonomy_tsv($what);
        echo "\nuids: ".count($uids)."\n"; $i = 0; $undefined_parents = array();
        foreach(new FileIterator($this->sh[$what]['destin'].'synonym.tsv') as $line => $row) {
            $i++; if($i == 1) continue;
            $rec = explode("\t|\t", $row);
            if($accepted_id = @$rec[0]) {
                if(!isset($uids[$accepted_id])) $undefined_accepted_ids[$accepted_id] = '';
            }
        }
        echo "\nUndefined accepted ids: ".count($undefined_accepted_ids)."\n";
        if($undefined_accepted_ids) {
            // echo "\nUndefined accepted ids for [$what]:\n"; print_r($undefined_accepted_ids);
        }
        return $undefined_accepted_ids;
    }
    /*
    private function run_file_with_gnparser_new($meta) //creates name_only.txt and converts it to name_only_gnparsed.txt using gnparser. gnparser converts entire file
    {
        $xname = "name_only1";
        $m = 3620095/10; //for CoL
        $what = $meta['what']; $i = 0;
        echo "\nRunning gnparser...\n";
        $WRITE = fopen($this->sh[$what]['source'].$xname.".txt", "w"); //will overwrite existing
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
            // print_r($rec); //exit; //use to test if field - value is OK
            
            // A good way to pinpoint the row count - works OK
            // if($rec['scientificName'] == "Euchilofulvius carinatus (Poppius, 1913)") exit("\n---[$i]---\n");
            // else continue;
            
            
            // breakdown when caching:
            // $cont = false;
            // // if($i >=  1    && $i < $m)   $cont = true;
            // // if($i >=  $m   && $i < $m*2) $cont = true;
            // // if($i >=  $m*2 && $i < $m*3) $cont = true;
            // // if($i >=  $m*3 && $i < $m*4) $cont = true;
            // if(!$cont) continue;
            
            
            if(!self::is_record_valid($what, $rec)) continue; //main criteria filter
            if($val = @$rec['scientificName']) fwrite($WRITE, $val."\n");
            if(($i % 1000) == 0) {
                echo "\nmain count:[".number_format($i)."]\n";
                fclose($WRITE);
                $cmd = "gnparser file -f json-compact --input ".$this->sh[$what]['source'].$xname.".txt --output ".$this->sh[$what]['source'].$xname."_gnparsed.txt";
                $out = shell_exec($cmd); echo "\n$out\n";
                self::save_2local_gnparsed_file_new($what, $xname."_gnparsed.txt");
                $WRITE = fopen($this->sh[$what]['source'].$xname.".txt", "w"); //will overwrite existing
            }
        }
        //last batch
        fclose($WRITE);
        $cmd = "gnparser file -f json-compact --input ".$this->sh[$what]['source'].$xname.".txt --output ".$this->sh[$what]['source'].$xname."_gnparsed.txt";
        $out = shell_exec($cmd); echo "\n$out\n";
        self::save_2local_gnparsed_file_new($what, $xname."_gnparsed.txt");
    }
    */
    /*
    private function run_file_with_gnparser_new_v2($meta) //
    {
        $xname = "name_onlyx2";
        $what = $meta['what']; $i = 0; $saved = 0;
        echo "\nRunning gnparser...\n";
        $WRITE = fopen($this->sh[$what]['source'].$xname.".txt", "w"); //will overwrite existing
        foreach(new FileIterator($this->sh[$what]['source'].$meta['taxon_file']) as $line => $row) {
            $i++;
            if(($i % 5000) == 0) echo "\n --->:[".number_format($i)."]"; //stopped at 1,645,000 for COL
            if($meta['ignoreHeaderLines'] && $i == 1) continue;
            if(!$row) continue;
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta['fields'] as $field) {
                if(!$field) continue;
                $rec[$field] = $tmp[$k];
                $k++;
            }
            // print_r($rec); //exit; //use to test if field - value is OK
            // breakdown when caching:
            // $cont = false;
            // if($i >=  931834 && $i < 4000000) $cont = true;
            // if(!$cont) continue;
            //
            if(!self::is_record_valid($what, $rec)) continue; //main criteria filter
            if($val = @$rec['scientificName']) {
                if(!self::cache_exists($val)) {
                    fwrite($WRITE, $val."\n");
                    $saved++;
                }
            }
            if($saved == 1000) {
                echo "\nmain countx:[".number_format($i)."]\n";
                fclose($WRITE);
                $cmd = "gnparser file -f json-compact --input ".$this->sh[$what]['source'].$xname.".txt --output ".$this->sh[$what]['source'].$xname."_gnparsed.txt";
                $out = shell_exec($cmd); echo "\n$out\n";
                self::save_2local_gnparsed_file_new($what, $xname."_gnparsed.txt");
                $WRITE = fopen($this->sh[$what]['source'].$xname.".txt", "w"); //will overwrite existing
                $saved = 0;
            }
        }
        //last batch
        fclose($WRITE);
        if($saved) {
            $cmd = "gnparser file -f json-compact --input ".$this->sh[$what]['source'].$xname.".txt --output ".$this->sh[$what]['source'].$xname."_gnparsed.txt";
            $out = shell_exec($cmd); echo "\n$out\n";
            self::save_2local_gnparsed_file_new($what, $xname."_gnparsed.txt");
        }
    }
    */
    /*
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
        // Works OK during older version of gnparser. The later version doesn't have a line separator (\n) between json record.
        // //convert entire file (names) to gnparser version
        // $cmd = "gnparser file --input ".$this->sh[$what]['source']."name_only.txt --output ".$this->sh[$what]['source']."name_only_gnparsed.txt";
        // $out = shell_exec($cmd);
        // echo "\n$out\n";
    }
    */
    /*
    private function save_2local_gnparsed_file_new($what, $filename = false) //for latest gnparser
    {
        $big_json = file_get_contents($this->sh[$what]['source'].$filename);
        $arrs = json_decode($big_json, true);
        $i = 0;
        foreach($arrs as $arr) {
            $i++; if($i == 1) continue;
            $json = json_encode($arr);
            // echo "\n$json\n"; continue;
            //copied below -------------------------------------- start
            $name = $arr['verbatim'];
            if(($i % 500) == 0) echo "\n".number_format($i)." $name - ";
            //now check if json already cached. Ignore if it does and save/cache it if it doesn't
            $options['cache_path'] = $this->smasher_download_options['cache_path'];
            $md5 = md5($name);
            $cache1 = substr($md5, 0, 2);
            $cache2 = substr($md5, 2, 2);
            if(!file_exists($options['cache_path'] . $cache1)) mkdir($options['cache_path'] . $cache1);
            if(!file_exists($options['cache_path'] . "$cache1/$cache2")) mkdir($options['cache_path'] . "$cache1/$cache2");
            $cache_path = $options['cache_path'] . "$cache1/$cache2/$md5.json";
            if(!file_exists($cache_path) || filesize($cache_path) == 0) {
                if(($i % 500) == 0) echo " - saving...";
                if($FILE = Functions::file_open($cache_path, 'w')) {
                    fwrite($FILE, $json);
                    fclose($FILE);
                }
            }
            else if(($i % 500) == 0) echo " - already saved/cached";
            //copied below -------------------------------------- end
        }
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
        if(file_exists($this->sh[$what]['source'].'name_only_gnparsed.txt')) Functions::file_rename($this->sh[$what]['source'].'name_only_gnparsed.txt', $this->sh[$what]['source'].'name_only_gnparsed_DONE.txt');
    }
    */
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
    
    private function get_canonical_via_api($sciname, $options)
    {
        $json = Functions::lookup_with_cache($this->gnparser.urlencode($sciname), $options);
        if($obj = json_decode($json)) {
            if($ret = @$obj->namesJson[0]->canonical_name->value) return $ret;
        }
    }
    private function gnsparse_canonical($sciname, $method, $download_options = array())
    {
        if(!$download_options) $download_options = $this->smasher_download_options;

        $sciname = self::fix_sciname($sciname); //just to make-the-same approach as utility_write_all_names()
        
        /*
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
        */
        if($method == "api") {
            if($canonical = self::get_canonical_via_api($sciname, $this->smasher_download_options)) return $canonical;
        }
        elseif($method == "cache") {
            $json = self::get_json_from_cache($sciname, $download_options);
            if($obj = json_decode($json)) {
                if($ret = @$obj->canonical_name->value) return $ret;
                elseif($ret = @$obj->canonicalName->value) return $ret;
                else { //the gnparser code was updated due to bug. So some names has be be re-run using cmdline OR API with expire_seconds = 0

                    self::write_gnparser_failures($this->what, $obj->verbatim);
                    return $obj->verbatim; //un-successfull
                }
            }
        }
        echo("\nInvestigate cannot get canonical name [$sciname][$method]\n");
    }
    private function write_gnparser_failures($what, $name, $postfix = "")
    {
        if($this->sh[$what]['run_gnparse'] == false) return;
        
        $path = $this->sh[$what]['source']."../zFailures/$what".$postfix.".txt";
        if($FILE = Functions::file_open($path, 'a')) {
            // echo "\nadded name failures [$what]: [$name]\n"; //good debug
            fwrite($FILE, $name."\n");
            fclose($FILE);
        }
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
    //========================================================================================start fixing undefined parents
    private function get_taxID_nodes_info($meta)
    {
        $what = $meta['what']; $i = 0;
        foreach(new FileIterator($this->sh[$what]['destin'].'taxonomy.tsv') as $line => $row) {
            $i++; 
            if($i == 1) $fields = explode("\t|\t", $row);
            else {
                if(!$row) continue;
                $tmp = explode("\t|\t", $row);
                $rec = array(); $k = 0;
                foreach($fields as $field) {
                    $rec[$field] = $tmp[$k];
                    $k++;
                }
                $rec = array_map('trim', $rec);
                // print_r($rec); exit;
                /*Array(
                    [uid] => Bombycoidea
                    [parent_uid] => 
                    [name] => Bombycoidea
                    [rank] => superfamily
                    [sourceinfo] => 
                    [] => 
                )*/
                $final[$rec['uid']] = array("pID" => $rec['parent_uid'], 'r' => $rec['rank']);
            }
        }
        return $final;
    }
    private function get_ancestry_of_taxID($tax_id, $taxID_info)
    {   /* Array(
                [Ganisa-plana-yunnanensis] => Array
                    (
                        [pID] => Ganisa-plana
                        [r] => subspecies
                    )
        )*/
        $final = array();
        $final[] = $tax_id;
        while($parent_id = @$taxID_info[$tax_id]['pID']) {
            if(!in_array($parent_id, $final)) $final[] = $parent_id;
            else {
                if($parent_id == 1) return $final;
                else {
                    print_r($final);
                    exit("\nInvestigate $parent_id already in array.\n");
                }
            }
            $tax_id = $parent_id;
        }
        return $final;
    }
    private function an_id_from_ancestry_is_part_of_a_removed_branch($ancestry, $removed_branches)
    {
        foreach($ancestry as $id) {
            /* use isset() instead
            if(in_array($id, $removed_branches)) return true;
            */
            if(isset($removed_branches[$id])) return true;
        }
        return false;
    }
    //=========================================================================== start adjusting taxon.tab with those 'not assigned' entries
    private function get_taxID_nodes_info_from_taxon_tab($meta)
    {
        $what = $meta['what']; $i = 0;
        foreach(new FileIterator($this->sh[$what]['source'].$meta['taxon_file']) as $line => $row) {
            $i++; if(($i % 100000) == 0) echo "\n".number_format($i);
            if($meta['ignoreHeaderLines'] && $i == 1) continue;
            if(!$row) continue;
            $row = Functions::conv_to_utf8($row); //possibly to fix special chars
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta['fields'] as $field) {
                if(!$field) continue;
                $rec[$field] = $tmp[$k];
                $k++;
            }
            // print_r($rec); exit; //use to test if field - value is OK
            /*Array(
                [taxonID] => 10145025
                [furtherInformationURL] => http://www.catalogueoflife.org/annual-checklist/2015/details/species/id/fc0886d15759a01525b1469534189bb5
                [acceptedNameUsageID] => 
                [parentNameUsageID] => 43060173
                [scientificName] => Bryometopus alekperovi Foissner, 1998
                [taxonRank] => species
                [taxonomicStatus] => accepted name
            )*/
            $final[$rec['taxonID']] = array("pID" => $rec['parentNameUsageID'], 'n' => $rec['scientificName'], 'r' => $rec['taxonRank'], 's' => $rec['taxonomicStatus']);
        }
        return $final;
    }
    private function is_there_not_assigned_in_ancestry($ancestry)
    {
        // print_r($ancestry);
        /* just for debug
        foreach($ancestry as $taxon_id) {
            $sci = $this->taxID_info[$taxon_id]['n'];
            echo "\n$taxon_id -- $sci";
        }
        */
        array_shift($ancestry); //remove first element of array, bec first element of $ancestry is the taxon in question.
        // print_r($ancestry);
        foreach($ancestry as $taxon_id) {
            $sci = $this->taxID_info[$taxon_id]['n'];
            if(stripos($sci, "not assigned") !== false) return true; //string is found
        }
        return false;
    }
    private function get_valid_parent_from_ancestry($ancestry, $taxonID)
    {
        foreach($ancestry as $taxon_id) {
            if($taxon_id == $taxonID) continue; //exclude the first record
            $sci = $this->taxID_info[$taxon_id]['n'];
            if(stripos($sci, "not assigned") !== false) {} //string is found
            else return $taxon_id;
        }
        exit("\nInvestigate no valid parent for taxon_id = [$taxonID]\n");
    }
    public function fix_CLP_taxa_with_not_assigned_entries($meta)
    {
        $this->taxID_info = self::get_taxID_nodes_info_from_taxon_tab($meta); echo "\ntaxID_info (".$meta['taxon_file'].") total rows: ".count($this->taxID_info)."\n";
        // print_r($taxID_info);
        $what = $meta['what']; $i = 0;
        $WRITE = fopen($this->sh[$what]['source'].$meta['taxon_file'].".txt", "w"); //e.g. new taxon.tab will be taxon.tab.txt
        foreach(new FileIterator($this->sh[$what]['source'].$meta['taxon_file']) as $line => $row) {
            $i++; if(($i % 100000) == 0) echo "\n".number_format($i);
            if($meta['ignoreHeaderLines'] && $i == 1) {
                fwrite($WRITE, $row."\n");
                continue;
            }
            if(!$row) continue;
            $row = Functions::conv_to_utf8($row); //possibly to fix special chars
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta['fields'] as $field) {
                if(!$field) continue;
                $rec[$field] = $tmp[$k];
                $k++;
            }
            // print_r($rec); exit; //use to test if field - value is OK
            $taxonID = $rec['taxonID'];
            // if($taxonID == 42990672) { //42990672 //42998538
                // print_r($rec);
                $ancestry = self::get_ancestry_of_taxID($rec['taxonID'], $this->taxID_info); // print_r($ancestry);
                if(self::name_is_not_assigned($rec['scientificName'])) continue; //ignore e.g. "Order not assigned" or "Family not assigned"
                elseif(self::is_there_not_assigned_in_ancestry($ancestry)) {
                    $valid_parent = self::get_valid_parent_from_ancestry($ancestry, $taxonID);
                    echo "\nvalid parent is $valid_parent\n";
                    echo "\nold row: $row\n";
                    $rec['parentNameUsageID'] = $valid_parent;
                    $new_row = implode("\t", $rec);
                    echo "\nnew row: $new_row\n";
                    fwrite($WRITE, $new_row."\n");
                }
                else fwrite($WRITE, $row."\n"); //regular row
                // exit;
            // }
        }
        fclose($WRITE);
        $txtfile_o = $this->sh[$what]['source'].$meta['taxon_file'];        $old = self::get_total_rows($txtfile_o); echo "\nOld taxon.tab: [$old]\n";
        $txtfile_n = $this->sh[$what]['source'].$meta['taxon_file'].".txt"; $new = self::get_total_rows($txtfile_n); echo "\nNew taxon.tab.txt: [$new]\n";
    }
    private function name_is_not_assigned($str)
    {
        if(stripos($str, "not assigned") !== false) return true;
        return false;
    }
    //=========================================================================== end adjusting taxon.tab with those 'not assigned' entries
    private function remove_undefined_parents_and_their_descendants($meta, $undefined_parents, $pre)
    {
        $taxID_info = self::get_taxID_nodes_info($meta); echo "\ntaxID_info (taxonomy.tsv) total rows: ".count($taxID_info)."\n";
        $what = $meta['what']; $i = 0; $removed = 0;
        
        $fn_tax = fopen($this->sh[$what]['destin'].$pre.".tsv.txt", "w"); //will overwrite existing
        fwrite($fn_tax, implode("\t|\t", $this->{$pre."_header"})."\t|\t"."\n");
        
        foreach(new FileIterator($this->sh[$what]['destin'].$pre.'.tsv') as $line => $row) {
            $i++; 
            if($i == 1) {
                $fields = explode("\t|\t", $row);
                $fields = array_filter($fields);
                // print_r($fields);
            }
            else {
                $tmp = explode("\t|\t", $row);
                if(!$row) continue;
                $rec = array(); $k = 0;
                foreach($fields as $field) {
                    $rec[$field] = $tmp[$k];
                    $k++;
                }
                $rec = array_map('trim', $rec);
                // print_r($rec); exit;
                // if($pre == "synonym") {
                //     print_r($rec); exit;
                // }
                /*Array( --- taxonomy
                    [uid] => Bombycoidea
                    [parent_uid] => 
                    [name] => Bombycoidea
                    [rank] => superfamily
                    [sourceinfo] => 
                    [] => 
                )
                Array( --- synonym
                    [uid] => Apatelodidae
                    [name] => Zanolidae
                    [type] => synonym
                    [rank] => 
                )*/
                $ancestry = self::get_ancestry_of_taxID($rec['uid'], $taxID_info);
                if(self::an_id_from_ancestry_is_part_of_a_removed_branch($ancestry, $undefined_parents)) {
                    $removed++;
                    // echo "\nto be removed:"; print_r($rec); print_r($ancestry);
                    continue;
                }
                else fwrite($fn_tax, $row."\n");
            }
        }
        fclose($fn_tax);
        echo "\nTotal removed due to undefined ids: [$removed]\n"; // print_r($undefined_parents);
        
        $txtfile_o = $this->sh[$what]['destin'].$pre.".tsv";     $old = self::get_total_rows($txtfile_o); echo "\n$pre.tsv [$old]\n";
        $txtfile_n = $this->sh[$what]['destin'].$pre.".tsv.txt"; $new = self::get_total_rows($txtfile_n); echo "\n$pre.tsv.txt [$new]\n";
        if($new < $old) {
            unlink($txtfile_o);
            Functions::file_rename($txtfile_n, $txtfile_o);
        }
        $txtfile_o = $this->sh[$what]['destin'].$pre.".tsv";     $old = self::get_total_rows($txtfile_o); echo "\n$pre.tsv [$old]\n";
    }
    //========================================================================================end fixing undefined parents
    private function utility_write_all_names($meta)
    {
        $Taxa2Remove = array();
        $Taxa2Remove = self::Taxa2Remove_from_DH_Resources();
        $Taxa2Remove_resources = array_keys($Taxa2Remove);
        $what = $meta['what']; $i = 0; $ctr = 1;
        //initialize this report file
        $path = $this->sh[$what]['source']."../zFailures/$what"."_failures.txt"; if(file_exists($path)) unlink($path);

        // print_r($Taxa2Remove); print_r($Taxa2Remove_resources); exit("\n[$what]\n");

        $fn_tax = fopen($this->sh[$what]['destin']."taxonomy_".$ctr.".txt", "w"); //will overwrite existing
        $fn_syn = fopen($this->sh[$what]['destin']."synonym_".$ctr.".txt", "w"); //will overwrite existing
        fwrite($fn_tax, implode("\t", $this->taxonomy_header_tmp)."\n");
        fwrite($fn_syn, implode("\t", $this->synonym_header_tmp) ."\n");

        $fn_tax_part = fopen($this->sh[$what]['destin']."taxonomy_part_".$ctr.".txt", "w"); //will overwrite existing
        $fn_syn_part = fopen($this->sh[$what]['destin']."synonym_part_".$ctr.".txt", "w"); //will overwrite existing
        fwrite($fn_tax_part, implode("\t", array("name"))."\n");
        fwrite($fn_syn_part, implode("\t", array("name")) ."\n");
        
        $file = $this->sh[$what]['source'].$meta['taxon_file'];
        
        /* obsolete in 2nd Smasher run
        if(in_array($what, array("COL", "CLP"))) $file .= ".txt"; //these 2 resources are fixed by fix_CLP_taxa_with_not_assigned_entries()
        */
        
        foreach(new FileIterator($file) as $line => $row) {
            $i++; if(($i % 100000) == 0) echo "\n".number_format($i);
            if($meta['ignoreHeaderLines'] && $i == 1) continue;
            if(!$row) continue;
            $row = Functions::conv_to_utf8($row); //possibly to fix special chars
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta['fields'] as $field) {
                if(!$field) continue;
                $rec[$field] = $tmp[$k];
                $k++;
            }
            // print_r($rec); //exit; //use to test if field - value is OK
            // if($rec['taxonID'] == "Sphingonaepiopsis-Genus-Group") exit;
            
            // $this->debug[$rec['taxonomicStatus']] = ''; //for debug only
            
            //=======================================================================================
            if(!self::is_record_valid($what, $rec)) continue; //main criteria filter
            if(in_array($what, $Taxa2Remove_resources)) {
                if(isset($Taxa2Remove[$what][$rec['taxonID']])) continue;
            }
            $t = array();
            $t['parent_id']     = $rec['parentNameUsageID'];
            $t['name']          = self::fix_sciname($rec['scientificName']);
            $t['taxon_id']      = $rec['taxonID'];
            $t['accepted_id']   = @$rec['acceptedNameUsageID'];
            $t['rank']          = ($val = @$rec['taxonRank']) ? self::clean_rank($val): "no rank";
            $t['source']        = '';
            if($this->sh[$what]['has_syn']) {
                if(($t['accepted_id'] != $t['taxon_id']) && $t['accepted_id'] != "") {
                    if(self::is_name_synonym($what, $rec)) {
                        self::write2file_tmp("syn", $fn_syn, $t);
                        self::write2file_tmp("syn_part", $fn_syn_part, $t);
                        $has_synonym = true;
                    }
                }
                elseif( (
                            ($t['accepted_id'] == $t['taxon_id']) || $t['accepted_id'] == ""
                        ) && @$rec['taxonomicStatus'] != "synonym"
                      ) {
                    if(self::is_name_valid($what, $rec)) {
                        self::write2file_tmp("tax", $fn_tax, $t);
                        self::write2file_tmp("tax_part", $fn_tax_part, $t);
                    }
                }
            }
            elseif(($t['accepted_id'] == $t['taxon_id']) || $t['accepted_id'] == "") {
                if(self::is_name_valid($what, $rec)) {
                    self::write2file_tmp("tax", $fn_tax, $t);
                    self::write2file_tmp("tax_part", $fn_tax_part, $t);
                }
            }
            //=======================================================================================
            if(($i % 200000) == 0) { //500000 orig
                fclose($fn_tax); fclose($fn_tax_part);
                fclose($fn_syn); fclose($fn_syn_part);
                $total_rows = self::get_total_rows($this->sh[$what]['destin']."taxonomy_part_".$ctr.".txt"); echo "\ntaxonomy_part_".$ctr.".txt -> $total_rows\n";
                if($total_rows > 500000) exit("\ngnparser cannot process more than 500K. Reduce batch process further. Current is 200K.\n");
                
                echo "\nrunning gnparser to taxonomy_".$ctr.".txt\n";
                $cmd = "gnparser file -f simple --input ".$this->sh[$what]['destin']."taxonomy_part_".$ctr.".txt --output ".$this->sh[$what]['destin']."taxonomy_part_".$ctr."_gnparsed.txt";
                $out = shell_exec($cmd); echo "\n$out\n";
                echo "\nrunning gnparser to synonym_".$ctr.".txt\n";
                $cmd = "gnparser file -f simple --input ".$this->sh[$what]['destin']."synonym_part_".$ctr.".txt --output ".$this->sh[$what]['destin']."synonym_part_".$ctr."_gnparsed.txt";
                $out = shell_exec($cmd); echo "\n$out\n";
                
                $ctr++;
                $fn_tax = fopen($this->sh[$what]['destin']."taxonomy_".$ctr.".txt", "w"); //will overwrite existing
                $fn_syn = fopen($this->sh[$what]['destin']."synonym_".$ctr.".txt", "w"); //will overwrite existing
                fwrite($fn_tax, implode("\t", $this->taxonomy_header_tmp)."\n");
                fwrite($fn_syn, implode("\t", $this->synonym_header_tmp) ."\n");
                
                $fn_tax_part = fopen($this->sh[$what]['destin']."taxonomy_part_".$ctr.".txt", "w"); //will overwrite existing
                $fn_syn_part = fopen($this->sh[$what]['destin']."synonym_part_".$ctr.".txt", "w"); //will overwrite existing
                fwrite($fn_tax_part, implode("\t", array("name"))."\n");
                fwrite($fn_syn_part, implode("\t", array("name")) ."\n");
            }
        }
        fclose($fn_tax); fclose($fn_tax_part);
        fclose($fn_syn); fclose($fn_syn_part);
        $total_rows = self::get_total_rows($this->sh[$what]['destin']."taxonomy_part_".$ctr.".txt"); echo "\ntaxonomy_part_".$ctr.".txt -> $total_rows\n";

        //last batch
        echo "\nrunning gnparser to taxonomy_".$ctr.".txt\n";
        $cmd = "gnparser file -f simple --input ".$this->sh[$what]['destin']."taxonomy_part_".$ctr.".txt --output ".$this->sh[$what]['destin']."taxonomy_part_".$ctr."_gnparsed.txt";
        $out = shell_exec($cmd); echo "\n$out\n";
        echo "\nrunning gnparser to synonym_".$ctr.".txt\n";
        $cmd = "gnparser file -f simple --input ".$this->sh[$what]['destin']."synonym_part_".$ctr.".txt --output ".$this->sh[$what]['destin']."synonym_part_".$ctr."_gnparsed.txt";
        $out = shell_exec($cmd); echo "\n$out\n";
        
        //now we then create the final taxonomy.tsv by looping to all taxonomy_?.txt
        $meta['ctr'] = $ctr;
        $ret = self::build_final_taxonomy_tsv($meta, "taxonomy");  
        if($this->sh[$what]['run_gnparse'] == true) self::print_duplicates($what, $ret, "_duplicates.txt");
        $ret = self::build_final_taxonomy_tsv($meta, "synonym");   
        if($this->sh[$what]['run_gnparse'] == true) self::print_duplicates($what, $ret, "_duplicates_syn.txt");

        if($what == "BOM") self::special_case_for_BOM($meta, 'synonym');

        //clean-up
        $txtfile = $this->sh[$what]['destin']."synonym.tsv";
        $total_rows = self::get_total_rows($txtfile);
        if($total_rows <= 1) unlink($txtfile);
    }
    private function special_case_for_BOM($meta, $pre)
    {   /* special case for BOM where we need to remove from synonym.tsv those duplicates (same canonical but different authorities BOM_duplicates_syn.txt). 
           That is taxonomicStatus:synonym. */
        //step 1: get all canonicals from BOM_duplicates_syn.txt
        $canonicals = array();
        $path = $this->sh['BOM']['source']."../zFailures/BOM_duplicates_syn.txt";
        foreach(new FileIterator($path) as $line => $row) {
            if(!$row) continue;
            $arr = explode("\t", $row);
            $canonicals[$arr[0]] = '';
        }
        print_r($canonicals);
        
        //step 2: remove from .txt where canonicals from step 1.
        $what = $meta['what']; $i = 0; $removed = 0;
        $fn_tax = fopen($this->sh[$what]['destin'].$pre.".tsv.txt", "w"); //will overwrite existing. Same temp files used elsewhere but not related. Just a temp file.
        fwrite($fn_tax, implode("\t|\t", $this->{$pre."_header"})."\t|\t"."\n");
        foreach(new FileIterator($this->sh[$what]['destin'].$pre.'.tsv') as $line => $row) {
            $i++; 
            if($i == 1) {
                $fields = explode("\t|\t", $row);
                $fields = array_filter($fields);
                // print_r($fields);
            }
            else {
                $tmp = explode("\t|\t", $row);
                if(!$row) continue;
                $rec = array(); $k = 0;
                foreach($fields as $field) {
                    $rec[$field] = $tmp[$k];
                    $k++;
                }
                $rec = array_map('trim', $rec);
                // print_r($rec); exit;
                /*Array( --- synonym
                    [uid] => Apatelodidae
                    [name] => Zanolidae
                    [type] => synonym
                    [rank] => 
                )*/
                if(isset($canonicals[$rec['name']])) {
                    $removed++;
                    // echo "\nto be removed:"; print_r($rec); print_r($ancestry);
                    continue;
                }
                else fwrite($fn_tax, $row."\n");
            }
        }
        fclose($fn_tax);
        echo "\nTotal removed from special step: [$removed]\n";
        $txtfile_o = $this->sh[$what]['destin'].$pre.".tsv";     $old = self::get_total_rows($txtfile_o); echo "\n$pre.tsv [$old]\n";
        $txtfile_n = $this->sh[$what]['destin'].$pre.".tsv.txt"; $new = self::get_total_rows($txtfile_n); echo "\n$pre.tsv.txt [$new]\n";
        if($new < $old) {
            unlink($txtfile_o);
            Functions::file_rename($txtfile_n, $txtfile_o);
        }
        $txtfile_o = $this->sh[$what]['destin'].$pre.".tsv";     $old = self::get_total_rows($txtfile_o); echo "\n$pre.tsv [$old]\n";
        //step 3: rename BOM_duplicates_syn.txt to show it was already processed
        $path = $this->sh['BOM']['source']."../zFailures/BOM_duplicates_syn.txt";
        Functions::file_rename($path, $path.".proc"); //.proc for processed already
    }
    private function is_name_valid($what, $rec)
    {
        if   ($what == "trunk") { if(in_array($rec['taxonomicStatus'], array("accepted"))) return true; }
        elseif($what == "ictv") return true;
        elseif($what == "IOC") return true;
        elseif($what == "ASW")  { if(in_array($rec['taxonomicStatus'], array("valid"))) return true; }
        elseif($what == "ODO")  { if(in_array($rec['taxonomicStatus'], array("valid"))) return true; }
        elseif($what == "BOM")  { 
            if(!$rec['taxonomicStatus']) return false;
            if(in_array($rec['taxonomicStatus'], array("valid"))) return true; 
        }
        elseif($what == "ERE")  { if(in_array($rec['taxonomicStatus'], array("accepted"))) return true; }
        elseif($what == "ONY")  { if(in_array($rec['taxonomicStatus'], array("accepted"))) return true; }
        elseif($what == "EET")  { if(in_array($rec['taxonomicStatus'], array("accepted"))) return true; }
        elseif($what == "NCBI") { if(in_array($rec['taxonomicStatus'], array("accepted"))) return true; }
        elseif($what == "WOR")  { if(in_array($rec['taxonomicStatus'], array("accepted"))) return true; }
        elseif($what == "CLP") {
            if(!$rec['taxonomicStatus']) return true;
            elseif(in_array($rec['taxonomicStatus'], array("accepted name", "provisionally accepted name", ""))) return true;
        }
        elseif($what == "COL") {
            if(!$rec['taxonomicStatus']) return true;
            elseif(in_array($rec['taxonomicStatus'], array("accepted name", "provisionally accepted name"))) return true;
        }
        // print_r($rec);
        // $this->debug[$rec['taxonID']] = ''; //for debug only
        exit("\nUndefined resource here [$what]\n");
    }
    private function is_name_synonym($what, $rec)
    {
        if    ($what == "BOM")  { if(in_array($rec['taxonomicStatus'], array("synonym"))) return true; }
        elseif($what == "NCBI") { if(in_array($rec['taxonomicStatus'], array("synonym"))) return true; }
        elseif($what == "WOR")  { if(in_array($rec['taxonomicStatus'], array("synonym"))) return true; }
        return false;
    }
    private function get_canonicals_from_gnparser_generated_file($meta, $pre, $cur_ctr)
    {
        $what = $meta['what'];
        $txtfile = $this->sh[$what]['destin'].$pre."_part_".$cur_ctr."_gnparsed.txt"; echo "\nreading [$txtfile]\n";
        $i = 0; $final = array(); $withAuthor = array();
        foreach(new FileIterator($txtfile) as $line_number => $line) {
            $i++;
            $arr = explode("\t", $line);
            if($i > 1) {
                $final[] = $arr[2]; //canonicalName
                $withAuthor[] = $arr[1];
            }
        }
        echo "\ncanonicals $pre: ".count($final)."\n";
        echo "\nwith author $pre: ".count($withAuthor)."\n";
        // echo "\n-".$final[0];
        // echo "\n-".$final[1];
        // echo "\n-".$final[2];
        // echo "\n-".$final[7540];
        // echo "\n-".$final[7541];
        // exit("\n");
        return array($final, $withAuthor);
    }
    private function build_final_taxonomy_tsv($meta, $pre)
    {
        $ctr = $meta['ctr']; $what = $meta['what'];
        $fn_tax = fopen($this->sh[$what]['destin'].$pre.".tsv", "w"); //will overwrite existing
        fwrite($fn_tax, implode("\t|\t", $this->{$pre."_header"})."\t|\t"."\n");
        $test = array();
        for ($c = 1; $c <= $ctr; $c++) {
            $ret = self::get_canonicals_from_gnparser_generated_file($meta, $pre, $c);
            $canonicals = $ret[0];
            $withAuthor = $ret[1];
            
            $txtfile = $this->sh[$what]['destin'].$pre."_".$c.".txt"; echo "\nprocessing [$txtfile]\n";

            //just for progress indicator
            $total_rows = self::get_total_rows($txtfile);
            echo "\nTotal rows: [".number_format($total_rows-1)."]\n"; //minus 1 bec. first row is header
            $modulo = self::get_modulo($total_rows);
            
            $i = 0;
            foreach(new FileIterator($txtfile) as $line_number => $line) {
                $i++; if(($i % $modulo) == 0) echo "\n $pre $c of $ctr - ".number_format($i)." ";
                if($i == 1) $line = strtolower($line);
                $row = explode("\t", $line); // print_r($row);
                if($i == 1) {
                    $fields = $row;
                    // print_r($fields);
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
                
                if($pre == "synonym") {
                    // print_r($rec); 
                    // exit("\nstopx\n");
                }
                // print_r($rec); exit("\nstopx\n");
                /*Array( --- taxonomy
                    [name] => Erebidae
                    [uid] => Erebidae
                    [parent_uid] => 
                    [rank] => family
                )
                Array( --- synonym
                    [name] => Zanolidae McDunnough 1938
                    [uid] => Zanolidae
                    [accepted_x_id] => Apatelodidae
                    [type] => synonym
                )*/
                $canon = $canonicals[$i-2];
                /*this is to fix this issue: Notes on data set preprocessing: #2. gnparser https://docs.google.com/spreadsheets/d/1A08xM14uDjsrs-R5BXqZZrbI_LiDNKeO6IfmpHHc6wg/edit?usp=sharing#gid=789044618 */
                if($val = @$this->problematic_names[$rec['uid']]) $canon = $val;
                else {
                    if(!$canon) {
                        self::write_gnparser_failures($what, $rec['name'], "_failures");
                        $canon = $withAuthor[$i-2]; //if failure, will get the original string as the canonical.
                    }
                }
                // echo "\n[$canon] - [".$rec['name']."]\n"; //good debug
                
                if($this->sh[$what]['run_gnparse'] == false) $canon = $withAuthor[$i-2]; //e.g. NCBI should not compute for any canonical, no gnparser activity here.

                $t = array();
                $t['parent_id']     = @$rec['parent_uid'];      //only for taxonomy
                $t['name']          = $canon;                   //for both
                $t['taxon_id']      = $rec['uid'];              //only for taxonomy
                $t['accepted_id']   = @$rec['accepted_x_id'];   //only for synonym
                $t['rank']          = @$rec['rank'];            //only for taxonomy
                $t['source']        = '';
                
                $test[$t['name']][] = $withAuthor[$i-2]; //for computing duplicates
                
                if($pre == "taxonomy") self::write2file("tax", $fn_tax, $t);
                else                   self::write2file("syn", $fn_tax, $t); //originally fn_syn, from above
            }
            echo "\n[$c] - Incrementing count for checking duplicates [$pre]: ".count($test)."\n";
        }
        return $test;
    }
    private function write2file($ext, $fn, $t)
    {
        if($ext == "syn")     fwrite($fn, $t['accepted_id'] . "\t|\t" . $t['name'] . "\t|\t" . 'synonym' . "\t|\t" . "\t|\t" . "\n");
        elseif($ext == "tax") fwrite($fn, $t['taxon_id'] . "\t|\t" . $t['parent_id'] . "\t|\t" . $t['name'] . "\t|\t" . $t['rank'] . "\t|\t" . $t['source'] . "\t|\t" . "\n");
    }
    private function write2file_tmp($ext, $fn, $t)
    {
        // if($ext == "syn")     fwrite($fn, $t['name'] . "\t" . $t['accepted_id'] . "\t" . 'synonym' . "\n");
        if($ext == "syn")     fwrite($fn, $t['name'] . "\t" . $t['taxon_id'] . "\t" . $t['accepted_id'] . "\t" . 'synonym' . "\n");
        elseif($ext == "tax") fwrite($fn, $t['name'] . "\t" . $t['taxon_id'] . "\t" . $t['parent_id'] . "\t" . $t['rank'] . "\n");
        if(in_array($ext, array("tax_part", "syn_part"))) fwrite($fn, $t['name'] . "\n");
    }
    /*
    private function run_TSV_file_with_gnparser_new($file, $what)
    {
        $i = 0;
        foreach(new FileIterator($this->sh[$what]['source'].$file) as $line => $row) {
            $i++;
            if(!$row) continue;
            $arr = explode("\t", $row);
            // if(($i % 10000) == 0) echo "\n".number_format($i);
            echo " -".number_format($i)."- ";
            // Array(
            //     [0] => 77f24f37-c0ee-5d53-b21b-56a9c1c2e25b
            //     [1] => Caulanthus crassicaulis var. glaber M.E. Jones   -   verbatim
            //     [2] => Caulanthus crassicaulis glaber                   -   canonicalName->value
            //     [3] => Caulanthus crassicaulis var. glaber              -   canonicalName->valueRanked
            //     [4] => M. E. Jones
            //     [5] => 
            //     [6] => 1
            // )
            $verbatim = $arr[1];
            if(!self::cache_exists($verbatim)) {
                echo "\n$verbatim -> no rec";
                self::gnsparse_canonical($verbatim, 'cache');
            }
        }
    }
    */
    private function cache_exists($name, $options = array())
    {
        if(!isset($options['cache_path'])) $options['cache_path'] = $this->smasher_download_options['cache_path'];
        $md5 = md5($name);
        $cache1 = substr($md5, 0, 2);
        $cache2 = substr($md5, 2, 2);
        $cache_path = $options['cache_path'] . "$cache1/$cache2/$md5.json";
        if(file_exists($cache_path)) return true;
        else                         return false;
    }
    private function get_total_rows($file)
    {
        /* source: https://stackoverflow.com/questions/3137094/how-to-count-lines-in-a-document */
        $total = shell_exec("wc -l < ".escapeshellarg($file));
        $total = trim($total);
        return $total;
    }
    private function get_modulo($total_rows)
    {
        if($total_rows >= 500000) $modulo = 100000;
        elseif($total_rows >= 100000 && $total_rows < 500000) $modulo = 50000;
        elseif($total_rows >= 50000 && $total_rows < 100000) $modulo = 10000;
        else $modulo = 5000;
        return $modulo;
    }
    private function fix_with_period($str)
    {   /* this:    Aloencyrtus angustifrons. (Annecke, 1964) 
           becomes: Aloencyrtus angustifrons (Annecke, 1964) */
        $str = trim($str);
        $arr = explode(" ", $str);
        if(count($arr) > 3) {
            if(substr($arr[1], -1) == ".") {
                $arr[1] = substr($arr[1], 0, -1); //remove last char
                $str = implode(" ", $arr);
            }
        }
        return $str;
    }
    private function fix_sciname($str)
    {
        $str = str_replace('"', "", $str);
        $str = str_replace(",,", ",", $str); //e.g. Matsucoccus sinensis Chen,, 1937
        //from COL ======================================================================================================= start
        $str = str_ireplace("Curtitoma georg?ssiani", "Curtitoma georgossiani", $str);
        $str = str_ireplace("Leptochiton ?ommandorensis", "Leptochiton commandorensis", $str);
        $str = str_ireplace("Leptoplana lutea?", "Leptoplana lutea", $str);
        $str = str_ireplace("Echinus e?culentus", "Echinus esculentus", $str);
        $str = str_ireplace("Cucumaria croceo?da", "Cucumaria croceoida", $str);
        $str = str_ireplace("Cossonus lacupros?", "Cossonus lacupros", $str);
        $str = str_ireplace("Heterolaophonte islandi?a", "Heterolaophonte islandica", $str);
        $str = str_ireplace("Paradiscogaster ?indersi", "Paradiscogaster flindersi", $str);
        $str = str_ireplace("Dendronotus kalikal?", "Dendronotus kalikal", $str);

        /* convert from: "Ontholestes rosti Bernhauer,ms" to: "Ontholestes rosti Bernhauer,(ms)" */
        $str = str_ireplace("Bernhauer,ms", "Bernhauer,(ms)", $str);
        $str = str_ireplace("Scheerpeltz,ms", "Scheerpeltz,(ms)", $str);    //Ocypus	Ocypus schaeferi Scheerpeltz,ms
        $str = str_ireplace("Smetana,ms", "Smetana,(ms)", $str);            //Platydracus	Platydracus juang Smetana,ms
        
        if(stripos($str, " pro syn.") !== false)    $str = str_ireplace(" pro syn.", "", $str); //e.g. Aristida coerulescens pro	Aristida coerulescens Hochst. ex Steud., pro syn.
        if(stripos($str, "“montereina”") !== false) $str = str_ireplace("“montereina”", "Montereina", $str); //e.g. “montereina” greeleyi (MacFarland, 1909)
        //from COL ======================================================================================================= end

        //with (.) period
        $str = self::fix_with_period($str); // convert from: "Amblyomma hirtum."  to: "Amblyomma hirtum"
        
        $str = str_ireplace("?kornick", "Škornick", $str);
        $str = str_ireplace("?erný", "Černý", $str);
        $str = str_ireplace("?tyroký", "Čtyroký", $str);
        $str = str_ireplace("†", "", $str); //remove dagger
        return $str;
    }
    private function scan_resource_file($meta, $final) //a utility
    {
        $what = $meta['what']; $i = 0; $ctr = 1;
        foreach(new FileIterator($this->sh[$what]['source'].$meta['taxon_file']) as $line => $row) {
            $i++; if(($i % 100000) == 0) echo "\n".number_format($i);
            if($meta['ignoreHeaderLines'] && $i == 1) continue;
            if(!$row) continue;
            $row = Functions::conv_to_utf8($row); //possibly to fix special chars
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta['fields'] as $field) {
                if(!$field) continue;
                $rec[$field] = $tmp[$k];
                $k++;
            }
            // print_r($rec); exit; //use to test if field - value is OK
            //=======================================================================================
            if(!self::is_record_valid($what, $rec)) continue; //main criteria filter
            /* COL
            Array(
                [taxonID] => 328843
                [furtherInformationURL] => http://www.catalogueoflife.org/annual-checklist/2015/details/species/id/b928b50927a32e8d9075eee882eef492/synonym/912c3bee1ce61534d3a4daf2598266c5
                [acceptedNameUsageID] => 328840
                [parentNameUsageID] => 
                [scientificName] => Pulvinaria maskelli novemarticulata Green, 1915
                [taxonRank] => infraspecies
                [taxonomicStatus] => synonym
            )
            Array( WOR
                [taxonID] => 6
                [furtherInformationURL] => http://www.marinespecies.org/aphia.php?p=taxdetails&id=6
                [acceptedNameUsageID] => 
                [parentNameUsageID] => 
                [scientificName] => Bacteria
                [taxonRank] => kingdom
                [taxonomicStatus] => accepted
                [taxonRemarks] => 
            )*/
            $sciname = $rec['scientificName'];
            if(isset($final[$sciname])) $final[$sciname] = $rec['taxonID'];
        }
        print_r($final); echo "\n";
        foreach($final as $sci => $taxon_id) echo $taxon_id."\t".$sci."\n";
        echo "\n-end-\n";
    }
    public function compare_results()
    {
        // print_r($this->sh['WOR']['source'])
        $sets = array_keys($this->sh);
        print_r($sets);
        foreach($sets as $what) {
            $txtfile = $this->sh[$what]['destin']."taxonomy.tsv";
            $total_rows = self::get_total_rows($txtfile);
            echo "\nTotal $what: [".number_format($total_rows)."]\n";

            $txtfile = $this->sh[$what]['destin']."taxonomy orig.tsv";
            $total_rows = self::get_total_rows($txtfile);
            echo "\nTotal $what old: [".number_format($total_rows)."]\n";
        }
    }
    
    public function clean_up_destination_folder()
    {
        $hierarchies = self::get_order_of_hierarchies();
        print_r($hierarchies);
        foreach($hierarchies as $what) {
            $this->sh[$what]['destin'] = $this->main_path."/zDestination/$what/";
            $files = $this->sh[$what]['destin']."*.txt";
            echo "\n\n$what";
            foreach (glob($files) as $filename) {
                echo "\n - $filename";
                unlink($filename);
            }
        }
    }
    private function get_order_of_hierarchies()
    {
        require_library('connectors/GoogleClientAPI');
        $func = new GoogleClientAPI(); //get_declared_classes(); will give you how to access all available classes
        $params['spreadsheetID'] = '1A08xM14uDjsrs-R5BXqZZrbI_LiDNKeO6IfmpHHc6wg'; //same spreadsheet for ver 1.0 and ver 1.1
        $params['range']         = 'source data sets!C2:C50'; //where "A" is the starting column, "C" is the ending column, and "1" is the starting row.
        $arr = $func->access_google_sheet($params);
        foreach($arr as $item) $final[] = $item[0];
        // print_r($final); //good debug to see perfect order of hierarchies
        return $final;
    }
    private function priority_list_resources()
    {
        $final = self::get_order_of_hierarchies();
        $i = 0;
        foreach($final as $hierarchy) {
            $i++; echo "# $i. $hierarchy\n";
            $this->sh[$hierarchy]['destin'] = $this->main_path."/zDestination/$hierarchy/";
        }
        /*
        trunk = Taxonomy.getTaxonomy('t/tax/trunk_20170614/', 'trunk')
        ictv = Taxonomy.getTaxonomy('t/tax/ictv_v2/', 'ictv')
        */
        $str = "#use this to load the taxonomies\n\n";
        foreach($final as $h) {
            $folder = str_replace($this->main_path, "", $this->sh[$h]['destin']);
            $folder = str_replace("zDestination/", "", $folder);
            // echo "\n".$this->sh[$h]['destin'];
            // echo "\n".$this->main_path;
            $str .= "$h = Taxonomy.getTaxonomy('t/tax_2018_12".$folder."', '".$h."')\n";
        }
        echo "\n$str\n";
        return $final;
    }
    
    public function syn_integrity_check()
    {
        $syn_ids_from_spreadsheet = self::get_syn_ids_from_spreadsheet();
        /*[IOC] => Array(
                    [0] => 6168a5808fb28ee5581c52a1994b97ab
                    [1] => 2b4e5e944dabbf41930904d59ab8feb3
                )
        */
        // exit("\n");
        $hierarchies = self::get_order_of_hierarchies();
        print_r($hierarchies);
        $undefined_ids = array();
        foreach($hierarchies as $what) {
            $this->sh[$what]['destin'] = $this->main_path."/zDestination/$what/";
            $uids = self::get_uids_from_taxonomy_tsv($what);
            echo "\n $what uids: ".count($uids)."\n";
            // print_r($uids); exit;
            foreach($syn_ids_from_spreadsheet[$what] as $id) {
                if(!isset($uids[$id])) $undefined_ids[$what][$id] = '';
            }
        }
        print_r($undefined_ids);
        // echo "\n".count($undefined_ids['COL'])."\n";
    }
    
    private function get_syn_ids_from_spreadsheet()
    {
        require_library('connectors/GoogleClientAPI');
        $func = new GoogleClientAPI(); //get_declared_classes(); will give you how to access all available classes
        $params['spreadsheetID'] = '1XreJW9AMKTmK13B32AhiCVc7ZTerNOH6Ck_BJ2d4Qng'; //same for ver 1.0 and ver 1.1
        //left side
        $params['range']         = 'Updated_Sheet1!A2:B1000'; //where "A" is the starting column, "C" is the ending column, and "1" is the starting row.
        $arr = $func->access_google_sheet($params);
        foreach($arr as $item) $final[$item[0]][] = $item[1];
        // print_r($final['WOR']); echo "\n".count($final['WOR'])."\n";
        //right side
        $params['range']         = 'Updated_Sheet1!D2:E1000'; //where "A" is the starting column, "C" is the ending column, and "1" is the starting row.
        $arr = $func->access_google_sheet($params);
        foreach($arr as $item) $final[$item[0]][] = $item[1];
        // print_r($final['WOR']); echo "\n".count($final['WOR'])."\n";
        // print_r($final);
        return $final;
    }
    
    
    private function phython_file_start()
    {
        $str  = "import sys, os, csv\n\n";
        $str .= "from org.opentreeoflife.taxa import Taxonomy, SourceTaxonomy, Taxon\n";
        $str .= "from org.opentreeoflife.smasher import UnionTaxonomy\n\n";
        $str .= "dwh = UnionTaxonomy.newTaxonomy('dwh')\n\n";
        $str .= "#Use this to tell smasher what separation file to use\n";
        $str .= "dwh.setSkeleton(Taxonomy.getTaxonomy('tax/separation/', 'separation'))\n\n";
        return $str;
    }
    public function generate_python_file()
    {
        echo self::phython_file_start();
        $hierarchies = self::priority_list_resources();
        require_library('connectors/GoogleClientAPI');
        $func = new GoogleClientAPI(); //get_declared_classes(); will give you how to access all available classes
        $params['spreadsheetID'] = '1XreJW9AMKTmK13B32AhiCVc7ZTerNOH6Ck_BJ2d4Qng'; //same for ver 1.0 and ver 1.1
        $params['range']         = 'Updated_Sheet1!A2:F1000'; //where "A" is the starting column, "C" is the ending column, and "1" is the starting row.
        $arr = $func->access_google_sheet($params);
        //start massage array
        /* PriorityHierarchy	taxonID	scientificName	SynonymHierarchy	taxonID	scientificName 
           $item[0]                     $item[2]        $item[3]                    $item[5]);  */
        foreach($arr as $item) {
            $item = array_map('trim', $item);
            $final[$item[3]][] = array("PriorityH" => $item[0], "Priority_sci" => $item[2], "SynonymH" => $item[3], "Synonym_sci" => $item[5]); // print_r($final);
        }
        $str = "#Use this to absorb one taxonomy into another\n\n";
        foreach($hierarchies as $hierarchy) { //synonym portion
            $str .= "alignment = dwh.alignment($hierarchy)\n";
            if($val = @$final[$hierarchy]) {
                foreach($val as $rec) {
                    $str .= 'alignment.same('.$hierarchy.'.taxon("'.$rec['Synonym_sci'].'"), dwh.taxon("'.$rec['Priority_sci'].'"))'."\n";
                }
            }
            $str .= "dwh.align(alignment)\n";
            $str .= "dwh.merge(alignment)\n\n";
            $str .= "for root in Taxonomy.roots(dwh):\n";
            $str .= "	print root\n\n";
        }
        $str .= "dwh.dump('test/')\n"; //last line from build_dwh.py
        echo $str;
        /* $final array
        [COL] => Array(
                    [94] => Array(
                             [PriorityH] => trunk
                             [Priority_sci] => Scalidophora
                             [SynonymH] => COL
                             [Synonym_sci] => Cephalorhyncha
                         )
        alignment = dwh.alignment(wor)
        alignment.same(wor.taxon('Cephalorhyncha'), dwh.taxon('Scalidophora'))
        alignment.same(wor.taxon('Codonosiga'), dwh.taxon('Codosiga'))
        */
    }
    private function get_uids_from_taxonomy_tsv($what, $withNames = false)
    {
        $i = 0;
        foreach(new FileIterator($this->sh[$what]['destin'].'taxonomy.tsv') as $line => $row) {
            $i++; if($i == 1) continue;
            $rec = explode("\t|\t", $row);
            if($withNames)  $uids[$rec[0]] = $rec[2];
            else            $uids[$rec[0]] = '';
        }
        return $uids;
    }
    private function Taxa2Remove_from_DH_Resources()
    {
        require_library('connectors/GoogleClientAPI');
        $func = new GoogleClientAPI(); //get_declared_classes(); will give you how to access all available classes
        $params['spreadsheetID'] = '1fK4QzdExFRY16Du8nSAY2sJf8NeykkFB8fD0DyKu2nM';
        $params['range']         = 'Sheet1!A2:C2000'; //where "A" is the starting column, "C" is the ending column, and "1" is the starting row.
        $arr = $func->access_google_sheet($params);
        //start massage array
        foreach($arr as $item) $final[$item[0]][$item[1]] = '';
        return $final;
    }
    
    public function save_all_ids_from_all_hierarchies_2MySQL()
    {
        $file = $this->main_path."/zFiles/write2mysql.txt"; $WRITE = fopen($file, "w"); //will overwrite existing
        $hierarchies = self::get_order_of_hierarchies(); print_r($hierarchies);
        // $hierarchies = array("CLP");
        foreach($hierarchies as $what) {
            $meta = self::get_meta($what);
            $file = $this->sh[$what]['source'].$meta['taxon_file']; $i = 0;
            foreach(new FileIterator($file) as $line => $row) {
                $i++; if(($i % 100000) == 0) echo "\n".number_format($i);
                if($meta['ignoreHeaderLines'] && $i == 1) continue;
                if(!$row) continue;
                $row = Functions::conv_to_utf8($row); //possibly to fix special chars
                $tmp = explode("\t", $row);
                $rec = array(); $k = 0;
                foreach($meta['fields'] as $field) {
                    if(!$field) continue;
                    $rec[$field] = $tmp[$k];
                    $k++;
                }
                // print_r($rec); exit("\n-elix-\n");
                /*Array(
                    [taxonID] => Erebidae
                    [scientificName] => Erebidae
                    [parentNameUsageID] => 
                    [taxonRank] => family
                    [taxonomicStatus] => accepted
                )*/
                $arr = array($what, $rec['taxonID'], $rec['scientificName']);
                fwrite($WRITE, implode("\t", $arr)."\n");
            }
            echo "\n$what -> $i\n";
        }
        fclose($WRITE);
    }
    private function generate_synonym_extension($resource_id)
    {
        $path = $this->main_path."/zresults_".$resource_id; 
        $txtfile = $path.'/synonyms.tsv'; $i = 0;
        foreach(new FileIterator($txtfile) as $line_number => $line) {
            $i++; if(($i % 100) == 0) echo "\n".number_format($i)." ";
            if($i == 1) $line = strtolower($line);
            $row = explode("\t|\t", $line); // print_r($row);
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
            // print_r($rec); exit("\nstopx\n");
            /*Array(
                [name] => root
                [uid] => 3488a150-bbcb-44cd-b7cf-af758ef8686e
                [type] => synonym
                [uniqname] => root (synonym for Life)
                [sourceinfo] => NCBI:1
            )*/
            $arr = explode(",", $rec['sourceinfo']);
            $tmp = $arr[0];
            $tmp = explode(":", $tmp);
            
            $synonym = new \eol_schema\Taxon();
            $synonym->taxonID               = $tmp[1];
            $synonym->scientificName        = self::get_orig_sciname_from_mysql($rec);
            $synonym->canonicalName         = $rec["name"];
            $synonym->acceptedNameUsageID   = $rec["uid"];
            $synonym->taxonomicStatus       = "synonym";
            $synonym->taxonRemarks          = $rec['sourceinfo'];
            $this->archive_builder->write_object_to_file($synonym);
        }
    }
    public function generate_dwca($resource_id)
    {
        $path = $this->main_path."/zresults_".$resource_id; 
        $txtfile = $path.'/taxonomy.tsv'; $i = 0;
        foreach(new FileIterator($txtfile) as $line_number => $line) {
            $i++; if(($i % 25000) == 0) echo "\n".number_format($i)." ";
            if($i == 1) $line = strtolower($line);
            $row = explode("\t|\t", $line); // print_r($row);
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
            // print_r($rec); exit("\nstopx\n");
            /*Array(
                [uid] => 3488a150-bbcb-44cd-b7cf-af758ef8686e
                [parent_uid] => 
                [name] => Life
                [rank] => clade
                [sourceinfo] => trunk:309e36b5-c6be-4663-80fc-e52b9002d574,NCBI:1
                [uniqname] => 
                [flags] => 
            )*/
            $taxon = new \eol_schema\Taxon();
            $taxon->taxonID             = $rec['uid'];
            $taxon->scientificName      = self::get_orig_sciname_from_mysql($rec);
            $taxon->canonicalName       = $rec['name'];
            $taxon->parentNameUsageID   = $rec["parent_uid"];
            $taxon->taxonRank           = $rec["rank"];
            $taxon->taxonRemarks        = $rec['sourceinfo'];
            $this->archive_builder->write_object_to_file($taxon);
            // if($i >= 1000) break; //debug only
        }
        self::generate_synonym_extension($resource_id);
        $this->archive_builder->finalize(true);
    }
    private function separate_what_and_taxon_id($haystack)
    {
        // $haystack = "ASW:v-Diasporus-sapo-Batista-Köhler-Mebert-Hertz-and-Vesely-2016-Zool.-J.-Linn.-Soc.-178:-274.";
        $replace = "_elix_";
        $needle = ":";
        $pos = strpos($haystack, $needle);
        if ($pos !== false) {
            $new = substr_replace($haystack, $replace, $pos, strlen($needle));
            // echo "\n$haystack";
            // echo "\n$new";
            $arr = explode($replace, $new);
            $what = $arr[0];
            $taxon_id = $arr[1];
            return array($what, $taxon_id);
        }
        return false;
    }
    private function get_orig_sciname_from_mysql($rec)
    {
        $arr = explode(",", $rec['sourceinfo']);
        $tmp = $arr[0];
        $arr = self::separate_what_and_taxon_id($tmp);
        if(!$arr) {
            print_r($rec);
            exit("\nInvestigate sourceinfo\n");
        }
        $what = $arr[0];
        $taxon_id = $arr[1];

        $taxon_id = str_replace("'", "\'", $taxon_id);

        if($val = self::query_orig_sciname($what, $taxon_id)) return $val;
        
        echo "\nstart 2nd try:\n";
        $taxon_id = urldecode($taxon_id);

        if($val = self::query_orig_sciname($what, $taxon_id)) return $val;
        
        echo "\nNot found --> what: [$what]  taxon_id: [$taxon_id]";
        return "";
    }
    private function query_orig_sciname($what, $taxon_id)
    {
        $sql = "SELECT t.sciname from DWH.ids_scinames t where t.what = '$what' and t.taxon_id = '$taxon_id'";
        $result = $this->mysqli->query($sql);
        while($result && $row=$result->fetch_assoc()) return $row['sciname'];
        return false;
    }
}
?>