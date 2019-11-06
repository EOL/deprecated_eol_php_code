<?php
namespace php_active_record;
// connector: [wikidata.php for wikimedia]
//            [wikipedia.php for wikipedia in diff. languages]
// require_once DOC_ROOT . '/vendor/JsonCollectionParser-master/src/Parser.php';
require_library('connectors/WikipediaRegionalAPI');
/*
https://en.wikipedia.org/wiki/List_of_Wikipedias
commons dump: https://dumps.wikimedia.org/commonswiki/

wget -c https://dumps.wikimedia.org/commonswiki/latest/commonswiki-latest-pages-articles.xml.bz2
wget -c https://dumps.wikimedia.org/wikidatawiki/entities/latest-all.json.gz

used api for commons:
https://commons.wikimedia.org/wiki/Commons:API/MediaWiki
others:
https://tools.wmflabs.org/magnus-toolserver/commonsapi.php
https://commons.wikimedia.org/wiki/Commons:Commons_API
using page id -> https://commons.wikimedia.org/?curid=29447337

Oct 28, 2018. A good read: https://topicseed.com/blog/importing-wikidata-dumps#decompress-the-archive
*/
/*
[root@eol-archive wikidata]# ls -lt
total 1090321376
-rw------- 1 root root  31499459297 Oct 24 11:16 latest-all.json.bz2
-rw------- 1 root root 666341712265 Oct 24 11:16 latest-all.json.from_bz2
-rw-r--r-- 1 root root  14760668958 Oct  1 04:03 latest-all-taxon.json
drwxr-xr-x 2 root root          112 Oct  1 03:04 wikimedia
-rw-r--r-- 1 root root 360658889565 Sep 25 07:49 latest-all.json.bak
-rw-r--r-- 1 root root  43228343586 Sep 25 07:49 latest-all.json.gz
drwxr-xr-x 2 root root           10 Nov  6  2017 wikipedia
[root@eol-archive wikidata]# 
*/
/* For testing one image to write to DwCA for Wikimedia. Follow the 3 asterisk ***. Un-comment these block of codes. Worked OK. Works also now for Wikipedia */
class WikiDataAPI extends WikipediaAPI
{
    function __construct($folder, $lang, $what = "wikipedia", $langs_with_multiple_connectors = array())
    {
        $this->what = $what;
        $this->resource_id = $folder;
        $this->language_code = $lang;
        $this->langs_with_multiple_connectors = $langs_with_multiple_connectors;
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->taxon_ids = array();
        $this->object_ids = array();
        $this->debug = array();
        $this->download_options = array('expire_seconds' => 60*60*24*25*2, 'download_wait_time' => 1000000, 'timeout' => 10800, 'download_attempts' => 1, 'delay_in_minutes' => 1);
        // $this->download_options['expire_seconds'] = false; //just temporary, comment in normal operation
        if(!Functions::is_production()) $this->download_options['expire_seconds'] = 60*60*24*5; //during development

        if(Functions::is_production()) {
            $this->path['raw_dump']         = "/extra/dumps/wikidata/latest-all.json";
            $this->path['wiki_data_json']   = "/extra/dumps/wikidata/latest-all-taxon.json";
            $this->path['commons']          = "/extra/dumps/wikidata/wikimedia/commonswiki-latest-pages-articles.xml";
            $this->path['wikimedia_cache']  = "/extra/wikimedia_cache/";
            $this->download_options['cache_path'] = "/extra/eol_cache_wiki_regions/";
        }
        else {
            $this->path['raw_dump']         = "/Volumes/Thunderbolt4/wikidata/latest-all.json";       //from https://dumps.wikimedia.org/wikidatawiki/entities/latest-all.json.gz
            $this->path['wiki_data_json']   = "/Volumes/Thunderbolt4/wikidata/latest-all-taxon.json"; //an all_taxon dump generated from raw [latest-all.json.gz]
            $this->path['commons']          = "/Volumes/Thunderbolt4/wikidata/wikimedia/commonswiki-latest-pages-articles.xml"; //from http://dumps.wikimedia.org/commonswiki/latest/commonswiki-latest-pages-articles.xml.bz2
            $this->path['wikimedia_cache']  = "/Volumes/Thunderbolt4/wikimedia_cache/";
            $this->download_options['cache_path'] = "/Volumes/Thunderbolt4/eol_cache_wiki_regions/";
        }
        
        if($this->what == "wikipedia") { //80 - wikipedia-en | 957 - wikipedia-de
            $lang_1st_batch = array('80','wikipedia-es','wikipedia-it','957','wikipedia-fr','wikipedia-zh','wikipedia-ru','wikipedia-pt','wikipedia-ja','wikipedia-ko','wikipedia-nl');
            if(!in_array($this->resource_id, $lang_1st_batch)) $this->download_options['resource_id'] = $this->resource_id;
            if($this->language_code == "sv") $this->download_options['expire_seconds'] = 60*60*24*30*6; //6 months expiration
        }
        
        // $this->property['taxon name'] = "P225";
        // $this->property['taxon rank'] = "P105";

        $this->trans['editors']['en'] = "Wikipedia authors and editors";
        $this->trans['editors']['de'] = "Wikipedia Autoren und Herausgeber";
        $this->trans['editors']['es'] = "Autores y editores de Wikipedia";
        
        $this->passed_already = false; //use to create a fake meta.xml
        
        $this->save_all_filenames = false; //use to save all media filenames to text file; normal operation is false; => not being used since a lookup is still needed
        
        $this->license['public domain']   = "http://creativecommons.org/licenses/publicdomain/";
        $this->license['by']              = "http://creativecommons.org/licenses/by/3.0/";
        $this->license['by-nc']           = "http://creativecommons.org/licenses/by-nc/3.0/";
        $this->license['by-sa']           = "http://creativecommons.org/licenses/by-sa/3.0/";
        $this->license['by-nc-sa']        = "http://creativecommons.org/licenses/by-nc-sa/3.0/";
        $this->license['no restrictions'] = "No known copyright restrictions";
        
        $this->count['greater_equal_2995'] = 0;
        $this->count['less_than_2995'] = 0;
        
        $this->exact_map_categories = array('Distribution maps', 'Distributional maps', 'Biogeographical maps', 'NASA World Wind');
        $this->substrs_map_categories_right = array(' distribution map', ' distributional map', ' biogeographical map', ' range map');
        $this->substrs_map_categories_left  = array('Distribution maps ', 'Distributional maps ', 'Biogeographical maps ', 'SVG maps ', 'Maps of ', 'Maps from ', 'Maps by ');
        
        $this->excluded_pageids = array('75038714');
    }
    function save_all_media_filenames($task, $range_from, $range_to, $actual_task = false) //one of pre-requisite steps | only for wikimedia
    {   
        // $GLOBALS['ENV_DEBUG'] = true; //debug only --- elixAug2 troubleshooting a problem.
        //initialize:
        $txtfile = CONTENT_RESOURCE_LOCAL_PATH . "wikimedia_filenames_" . date("Y_m") . ".txt";
        if(!($f = Functions::file_open($txtfile, "w"))) return;
        fclose($f);
        echo "\n-Filename created OK\n";
        
        $this->save_all_filenames = true; //use to save all media filenames to text file
        if($actual_task) {
            self::parse_wiki_data_json($task, $range_from, $range_to);
            //log this task finished
            $txtfile = CONTENT_RESOURCE_LOCAL_PATH . "wikimedia_filenames_status_" . date("Y_m") . ".txt";
            if(!($f = Functions::file_open($txtfile, "a"))) return;
            fwrite($f, "$actual_task DONE"."\n"); fclose($f); echo "\n-$actual_task DONE\n";
            return true; //so it can run and test final step if ready
        }
        else { //means finalize file
            if(self::finalize_media_filenames_ready("wikimedia_filenames_status_")) {
                self::parse_wiki_data_json($task, false, false);
                //truncate for next run
                $txtfile = CONTENT_RESOURCE_LOCAL_PATH . "wikimedia_filenames_status_" . date("Y_m") . ".txt";
                if(!($f = Functions::file_open($txtfile, "w"))) return;
                fwrite($f, "Truncated now."."\n"); fclose($f); 
                return true; //so it can run next step...
            }
            else {
                echo "\n\n ---Cannot finalize media filenames yet.---\n\n";
                return false;
            }
        }
    }
    private function finalize_media_filenames_ready($status) //e.g. "wikimedia_filenames_status_" or "wikimedia_generation_status_" or "wikipedia_generation_status_"
    {
        $txtfile = CONTENT_RESOURCE_LOCAL_PATH . "$status" . date("Y_m") . ".txt";
        if(!file_exists($txtfile)) return false;
        $contents = file_get_contents($txtfile);
        for($i=1; $i<=6; $i++) {
            if(stripos($contents, $i."of6 DONE") !== false) {} //string is found
            else return false;
        }
        return true;
    }
    function test($filename = false)
    {
        /* [file in question] => -----                              Nov 25, 2018
            [File:Virgin%27s_bower_(Clematis_terniflora).jpg] => 
            [File:Narcissus rupicola distrib.jpg] => 
            [File:Rosa_glauca_inflorescence_(32).jpg] =>            no real agent
            [File:Alnus_acuminata_4.jpg] =>                         no real agent
        */
        // $filename = "Aa_species.jpg"; //force assignment Aa_species.jpg
        $arr = self::process_file($filename); //case-sensitive filename param
        print_r($arr);
        /* Note: then search for 'good debug' below. Two options: coming from API or dump. Then continue to investigate... */
    }
    private function test_agent_value($a, $final)
    {
        $fields = array('name', 'role', 'homepage');
        $print_error = false;
        echo "\n";
        foreach($fields as $fld) {
            if(@$a[$fld] == $final[$fld]) echo "\nOK $fld";
            else {
                echo "\nerror $fld"; $print_error = true;
            }
        }
        if($print_error) {
            echo "\n"; print_r($a); print_r($final);
        }
    }
    function run_test()
    {
        echo "\nRun tests...";
        $a = array(); $a['name'] = "#if:94187100@N00|[http://flickr.com/photos/94187100@N00 Hernán García Crespo]|#if:|[2 Hernán García Crespo]|Hernán García Crespo #if:|from location";
        $a = self::clean_agent_rec($a); // print_r($a); 
        self::test_agent_value($a, array('name' => "Hernán García Crespo", 'role' => "creator", 'homepage' => "http://flickr.com/photos/94187100@N00"));

        $a = array(); $a['name'] = "Author assumed|[[User:McLeod|McLeod]]";
        $a = self::clean_agent_rec($a); //print_r($a); exit;
        self::test_agent_value($a, array('name' => "McLeod", 'role' => "", 'homepage' => ""));
        
        $a = array(); $a['name'] = "User:Charly Morlock/crédito";
        $a = self::clean_agent_rec($a); //print_r($a); exit;
        self::test_agent_value($a, array('name' => "Charly Morlock", 'role' => "", 'homepage' => ""));

        $a = array(); $a['name'] = "User:Raymond/author";
        $a = self::clean_agent_rec($a); //print_r($a); exit;
        self::test_agent_value($a, array('name' => "Raymond", 'role' => "", 'homepage' => ""));

        $a = array(); $a['name'] = "user:JoJan";
        $a = self::clean_agent_rec($a); //print_r($a); exit("\n");
        self::test_agent_value($a, array('name' => "JoJan", 'role' => "", 'homepage' => ""));

        $a = array(); $a['role'] = "photographer";
        $a['name'] = 'Eli E. Agbayani';
        self::test_agent_value($a, array('name' => "Eli E. Agbayani", 'role' => "photographer", 'homepage' => ""));

        // self::test_agent_value($a, array('name' => "xxx", 'role' => "yyy", 'homepage' => "zzz"));
        // self::test_agent_value($a, array('name' => "xxx", 'role' => "yyy", 'homepage' => "zzz"));
        
        $arr[] = array('filename' => 'Aa_species.jpg',              'name' => "Eric in SF",    'condition' => 'eq', 'role' => 'creator');
        $arr[] = array('filename' => 'Abies_grandis_needles.jpg',   'name' => "Sten Porse",    'condition' => 'eq');
        $arr[] = array('filename' => 'Indian_-_Rama_Destroys_Ogress_-_Walters_W888.jpg',   'name' => "Walters Art Museum", 'condition' => 'eq');
        $arr[] = array('filename' => 'Salix_sericea_NRCS-2.jpg',        'name' => "Nonenmac",              'condition' => 'eq');
        $arr[] = array('filename' => 'Caligula_by_A_Yakovlev_1911.jpg', 'name' => "Alexander Yakovlev",    'condition' => 'eq');
        $arr[] = array('filename' => 'Megalophaedusa_martensi_02.jpg',  'name' => "Takahashi",             'condition' => 'eq', 'index' => 1);
        $arr[] = array('filename' => 'Elgaria_multicarinata_08416.JPG', 'name' => "Walter Siegmund ©2006 Walter Siegmund", 'condition' => 'eq', 'index' => 1);
        $arr[] = array('filename' => 'Alexander_yakovlev,_autoritratto,_1917.JPG', 'name' => "Sailko", 'condition' => 'eq', 'role' => 'creator', 'homepage' => 'https://commons.wikimedia.org/wiki/User:Sailko');
        $arr[] = array('filename' => 'Alexandr_Yakovlev_(self-portrait,_1917,_GTG).jpg', 'name' => "Alexandre Jacovleff", 'condition' => 'eq', 'role' => 'creator', 'homepage' => 'https://en.wikipedia.org/wiki/en:Alexandre_Jacovleff');
        $arr[] = array('filename' => 'España_y_Portugal.jpg', 'name' => "Jacques Descloitres, MODIS Rapid Response Team, NASA/GSFC", 'condition' => 'eq', 'role' => 'creator');
        $arr[] = array('filename' => 'Okinawa_Churaumi_Aquarium.jpg', 'name' => "Derek Mawhinney", 'condition' => 'eq', 'role' => 'creator');
        $arr[] = array('filename' => 'Indian_-_Rama_Destroys_Ogress_-_Walters_W888.jpg', 'name' => "Walters Art Museum", 'condition' => 'eq', 'role' => 'source');
        $arr[] = array('filename' => '1PRT.png', 'name' => "Jmol, Jmol Development Team", 'condition' => 'eq', 'role' => 'source', 'index' => 1);
        $arr[] = array('filename' => 'Red-breasted_Parakeet.jpg', 'name' => "Flickr user NatureAtYourBackyard . Photo uploaded to commons by user ltshears", 'condition' => 'eq', 'role' => 'creator', 'index' => 0, 'homepage' => 'https://www.flickr.com/photos/64684201@N00/');
        $arr[] = array('filename' => 'Red-breasted_Parakeet.jpg', 'name' => "Johnny Wee. (Thanks for a million views.) (64684201@N00)", 'condition' => 'eq', 'role' => 'source', 'index' => 1, 'homepage' => 'https://www.flickr.com/photos/64684201@N00/291506502/');
        $arr[] = array('filename' => 'Whales_are_Paraphyletic.png', 'name' => "Ian Alexander", 'condition' => 'eq', 'role' => 'creator', 'homepage' => 'https://commons.wikimedia.org/wiki/User:Chiswick_Chap');
        $arr[] = array('filename' => 'Narcissus_assoanus_distrib.jpg', 'name' => "Cillas;España_y_Portugal.jpg: Jacques Descloitres, MODIS Rapid Response Team, NASA/GSFC", 'condition' => 'eq', 'role' => 'creator', 'index' => 0, 'homepage' => 'https://commons.wikimedia.org/wiki/File:Espa%C3%B1a_y_Portugal.jpg');
        $arr[] = array('filename' => 'Narcissus_assoanus_distrib.jpg', 'name' => "Se ha trabajado con datos propios sobre la imagen existente en Commons: España_y_Portugal.jpg", 'condition' => 'eq', 'role' => 'source', 'index' => 1, 'homepage' => 'https://commons.wikimedia.org/wiki/File:Espa%C3%B1a_y_Portugal.jpg');
        $arr[] = array('filename' => 'Japanese_Kolonok.jpg', 'name' => "Conifer",    'condition' => 'eq', 'role' => 'creator', 'homepage' => 'https://www.flickr.com/photos/conifer/');
        $arr[] = array('filename' => 'Saguinus_nigricollis_3.jpg', 'name' => "Felipe Neira", 'condition' => 'eq', 'role' => 'creator', 'index' => 0, 'homepage' => 'https://www.flickr.com/photos/11923391@N00/');
        $arr[] = array('filename' => 'Saguinus_nigricollis_3.jpg', 'name' => "Flickr user ID ipecuador", 'condition' => 'eq', 'role' => 'source', 'index' => 1, 'homepage' => 'https://www.flickr.com/photos/ipecuador/233502258/in/dateposted/');
        $arr[] = array('filename' => 'Blue_Shepherd_ja_leijona.jpg',              'name' => "Korkeasaaren kirja, a book published in 1951, photos thus in the public domain.",    'condition' => 'eq', 'role' => 'source');
        $arr[] = array('filename' => 'Inclusion_bodies.jpg', 'name' => "{{NCI Visuals Online|2252}} (since removed)",    'condition' => 'eq', 'role' => 'source', 'homepage' => 'https://en.wikipedia.org/wiki/National_Cancer_Institute');
        $arr[] = array('filename' => 'Feh-painting.jpg', 'name' => "Brehms Tierleben, Small Edition 1927",    'condition' => 'eq', 'role' => 'source', 'homepage' => 'https://en.wikipedia.org/wiki/Brehms_Tierleben');
        $arr[] = array('filename' => 'Alitta_virens_pharynx_(dorsal).jpg', 'name' => "Flickr user ID a_semenov", 'condition' => 'eq', 'role' => 'source', 'index' => 1, 'homepage' => 'https://www.flickr.com/photos/a_semenov/3459795279/sizes/o/in/photostream/');
        $arr[] = array('filename' => 'Chicory-m.jpg', 'name' => "marya", 'condition' => 'eq', 'role' => 'creator', 'index' => 0, 'homepage' => 'https://www.flickr.com/photos/35237093637@N01');
        $arr[] = array('filename' => 'Chicory-m.jpg', 'name' => "Flickr image ID 1718209", 'condition' => 'eq', 'role' => 'source', 'index' => 1, 'homepage' => 'https://flickr.com/photos/35237093637@N01/1718209');
        $arr[] = array('filename' => 'Age-Spatial-and-Temporal-Variations-in-Hospital-Admissions-with-Malaria-in-Kilifi-County-Kenya-A-25-pmed.1002047.s013.ogv', 'name' => "S1 Video from journal (PLOS Medicine). DOI (10.1371/journal.pmed.1002047).", 'condition' => 'eq', 'role' => 'source', 'index' => 1, 'homepage' => 'https://www.ncbi.nlm.nih.gov/pmc/articles/PMC4924798/bin/pmed.1002047.s013.mp4');
        $arr[] = array('filename' => 'Sea_spider_(Pantopoda_or_pycnogonids).webm', 'name' => "Denise King", 'condition' => 'eq', 'role' => 'creator', 'index' => 0, 'homepage' => 'https://vimeo.com/growthanddk');
        $arr[] = array('filename' => 'Sea_spider_(Pantopoda_or_pycnogonids).webm', 'name' => "Vimeo video 136560584", 'condition' => 'eq', 'role' => 'source', 'index' => 1, 'homepage' => 'https://vimeo.com/136560584');
        $arr[] = array('filename' => 'Tordalke01.jpg', 'name' => "T.Müller",    'condition' => 'eq', 'role' => 'creator');
        $arr[] = array('filename' => 'Llaca.jpg', 'name' => "Yamil Hussein E.", 'condition' => 'eq', 'role' => 'creator', 'index' => 0, 'homepage' => 'https://commons.wikimedia.org/w/index.php?title=User:Yamilhussein&action=edit&redlink=1');
        $arr[] = array('filename' => 'Llaca.jpg', 'name' => "http://www.jacobita.cl/", 'condition' => 'eq', 'role' => 'source', 'index' => 1, 'homepage' => 'http://www.jacobita.cl/');

        echo "\n\nNext...".count($arr);
        // $arr[] = array('filename' => 'xxx',   'name' => "yyy",    'condition' => 'eq');
        // $arr[] = array('filename' => 'xxx',   'name' => "yyy",    'condition' => 'eq');
        // $arr[] = array('filename' => 'xxx',   'name' => "yyy",    'condition' => 'eq');
        $i = 0;
        foreach($arr as $a) { $i++;
            if(!@$a['index']) $a['index'] = 0;
            $arr = self::process_file($a['filename']);
            if(!isset($arr['Artist'])) echo "\n$i filename not found!";
            else { //start test proper
                $param = array('condition' => $a['condition']);
                if(isset($a['name']))     $param['name']     = $arr['Artist'][$a['index']]['name'];
                if(isset($a['role']))     $param['role']     = $arr['Artist'][$a['index']]['role'];
                if(isset($a['homepage'])) $param['homepage'] = $arr['Artist'][$a['index']]['homepage'];
                echo "\n$i. ".(self::validate_test($param, $a) ? 'OK' : "error: $a[filename]");
            }
        }
    }
    private function validate_test($param, $a)
    {
        if($param['condition'] == 'eq') {
            if(isset($param['name'])) {
                if($param['name'] == $a['name']) {}
                else return false;
            }
            if(isset($param['role'])) {
                if($param['role'] == $a['role']) {}
                else return false;
                /*debug
                if($param['role'] == "creatorx") {}
                else return false;
                */
            }
            if(isset($param['homepage'])) {
                if($param['homepage'] == $a['homepage']) {}
                else return false;
            }
        }
        return true;
    }
    function generate_resource($task = false, $range_from = false, $range_to = false, $actual_task = false)
    {   /* VERY IMPORTANT - everytime we get a fresh new wikidata dump. The raw dump has all categories not just taxa.
        This utility will create an all-taxon dump, which our connector will use.
        self::create_all_taxon_dump(); //a utility that generates an all-taxon dump, generates overnight 
        exit; 
        */
        
        /* testing
        // $arr = self::process_file("Dark_Blue_Tiger_-_tirumala_septentrionis_02614.jpg");
        // $arr = self::process_file("Prairie_Dog_(Cynomys_sp.),_Auchingarrich_Wildlife_Centre_-_geograph.org.uk_-_1246985.jpg");
        // [file in question] => Array
        //     (
        //         [File:] => Aix_sponsa_dis.PNG
        //         [File:] => Aix_sponsa_dis1.PNG Aix sponsa dis1.PNG | Alnus acuminata 4.jpg
        //         [File:] => 
        //     )
        
        $arr = self::process_file("Haworthia_arachnoidea_-_cobweb_aloe.jpg"); //File:Przewalski 26-9-2004-2.jpg //Virgin's bower (Clematis terniflora).jpg
        print_r($arr);
        exit("\n-Finished testing-\n");
        */
        
        // if($task == 'taxon_wiki_per_language_stats') $this->download_options['expire_seconds'] = false; NO NEED ANYMORE AS IT DOESN'T MAKE API CALLS ANYMORE.
        
        if(!@$this->trans['editors'][$this->language_code]) {
            $func = new WikipediaRegionalAPI($this->resource_id, $this->language_code);
            $this->trans['editors'][$this->language_code] = $func->translate_source_target_lang("Wikipedia authors and editors", "en", $this->language_code);
        }
        
        if($task != 'taxon_wiki_per_language_stats') self::initialize_files();
        if    ($this->what == "wikipedia") $what_generation_status = "wikipedia_generation_status_".$this->language_code."_";
        elseif($this->what == "wikimedia") $what_generation_status = "wikimedia_generation_status_";

        if(
            ($this->what == "wikimedia") || (
                                                $this->what == "wikipedia" && in_array($this->language_code, $this->langs_with_multiple_connectors)
                                            )
          ) { //orig
            //start new block ---------------------------------------
            // if(false) { // *** used for wikipedia only - when developing, so to process just one taxon e.g. en, es, de, it
            if($actual_task) { //un-comment in real operation
                self::parse_wiki_data_json($task, $range_from, $range_to);
                //log this task finished
                $txtfile = CONTENT_RESOURCE_LOCAL_PATH . $what_generation_status . date("Y_m") . ".txt";
                if(!($f = Functions::file_open($txtfile, "a"))) return;
                fwrite($f, "$actual_task DONE"."\n"); fclose($f); echo "\n-$actual_task DONE\n";
                return array(true, false); //so it can run and test final step if ready
            }
            else { //means finalize file
                // if(true) { //use this when developing*** wikimedia & wikipedia --- for 'en' and now 'es' -> those with multiple jobs
                if(self::finalize_media_filenames_ready($what_generation_status) || $task == "generate_resource_force" || $task == "taxon_wiki_per_language_stats") { //un-comment in real operation
                    self::parse_wiki_data_json($task, false, false);
                    //truncate for next run
                    $txtfile = CONTENT_RESOURCE_LOCAL_PATH . $what_generation_status . date("Y_m") . ".txt";
                    if(!($f = Functions::file_open($txtfile, "w"))) return;
                    fwrite($f, "Truncated now."."\n"); fclose($f); 
                    /* no more {return true;} here... bec it still has steps below */
                }
                else {
                    echo "\n\n ---Cannot finalize generate resource yet.---\n\n";
                    return array(false, false);
                }
            }
            //end new block ---------------------------------------
        }
        else self::parse_wiki_data_json(); //for non-English wikipedia --- orig
        
        self::add_parent_entries(); //not sure if we need it but gives added value to taxonomy
        $this->archive_builder->finalize(TRUE);

        //start ============================================================= needed adjustments
        if($this->what == "wikipedia") {
            unlink(CONTENT_RESOURCE_LOCAL_PATH . $this->resource_id . "_working" . "/media_resource.tab");  //remove generated orig test media_resource.tab
            Functions::file_rename($this->media_extension, CONTENT_RESOURCE_LOCAL_PATH . $this->resource_id . "_working" . "/media_resource.tab");  //rename .eli to .tab

            //mimic the compression in $this->archive_builder->finalize()
            $info = pathinfo(CONTENT_RESOURCE_LOCAL_PATH . $this->resource_id . "_working");
            $temporary_tarball_path = \php_active_record\temp_filepath();
            $final_tarball_path = $info['dirname'] ."/". $info['basename'] .".tar.gz";
            shell_exec("tar -czf $temporary_tarball_path --directory=". $info['dirname'] ."/". $info['basename'] ." .");
            @unlink($final_tarball_path);
            if(copy($temporary_tarball_path, $final_tarball_path))
              unlink($temporary_tarball_path);
        }
        //end =============================================================
        
        self::delete_TEMP_FILE_PATH();
        echo "\n----start debug array\n";
        // print_r($this->debug); //exit; No need to display this since it is written to file anyway below. I think...
        echo "\n----end debug array\n";
        
        //write to file $this->debug contents
        $f = Functions::file_open(CONTENT_RESOURCE_LOCAL_PATH."/".$this->what."_debug_".date("Y-m-d H").".txt", "w");
        $index = array_keys($this->debug);
        foreach($index as $i) {
            fwrite($f, "\n$i ---"."\n");
            if(is_array($this->debug[$i])) {
                foreach(array_keys($this->debug[$i]) as $row) fwrite($f, "$row"."\n");
            }
            else fwrite($f, $this->debug[$i]."\n");
        }
        fclose($f);
        
        print_r($this->count); //just a debug print of values
        
        // if(($this->what == "wikimedia") || ($this->what == "wikipedia" && $this->language_code == "en")) return array(true, true);
        return array(true, true); //all that reaches this point will return true true
    }
    private function delete_TEMP_FILE_PATH()
    {
        if(unlink($this->TEMP_FILE_PATH)) echo "\nFile deleted OK [$this->TEMP_FILE_PATH]\n";
        else                              echo "\nERROR: Failed to delete [$this->TEMP_FILE_PATH]\n";
    }
    private function initialize_files()
    {   /* orig. worked well but it goes to /tmp/ folder. We need to put it in /extra/ in eol-archive
        $this->TEMP_FILE_PATH = temp_filepath(); 
        */
        
        //creates a temp file
        $this->TEMP_FILE_PATH = CONTENT_RESOURCE_LOCAL_PATH."/wikipedia_".$this->language_code."_".date("Y-m-d_H_s").".tmp";
        if(!($f = Functions::file_open($this->TEMP_FILE_PATH, "w"))) return;
        fclose($f);
        
        /*
        <field index="0" term="http://purl.org/dc/terms/identifier"/>
        <field index="1" term="http://rs.tdwg.org/dwc/terms/taxonID"/>
        <field index="2" term="http://purl.org/dc/terms/type"/>
        <field index="3" term="http://purl.org/dc/terms/format"/>
        <field index="4" term="http://iptc.org/std/Iptc4xmpExt/1.0/xmlns/CVterm"/>
        <field index="5" term="http://purl.org/dc/terms/title"/>
        <field index="6" term="http://purl.org/dc/terms/description"/>
        <field index="7" term="http://rs.tdwg.org/ac/terms/furtherInformationURL"/>
        <field index="8" term="http://purl.org/dc/terms/language"/>
        <field index="9" term="http://ns.adobe.com/xap/1.0/rights/UsageTerms"/>
        <field index="10" term="http://ns.adobe.com/xap/1.0/rights/Owner"/>
        */
        if($this->what == "wikipedia") {
            $this->media_cols = "identifier,taxonID,type,format,CVterm,title,description,furtherInformationURL,language,UsageTerms,Owner";
            $this->media_cols = explode(",", $this->media_cols);
            $this->media_extension = CONTENT_RESOURCE_LOCAL_PATH . $this->resource_id . "_working" . "/media_resource.eli";
            if(!($f = Functions::file_open($this->media_extension, "w"))) return;
            fwrite($f, implode("\t", $this->media_cols)."\n");
            fclose($f);
        }
    }
    private function add_parent_entries()
    {
        echo "\n\nStart add parent entries...\n\n";
        foreach(new FileIterator($this->TEMP_FILE_PATH) as $line_number => $row) {
            $arr = json_decode($row, true);
            while(@$arr['parent']) {
                //first record
                $rec = array();
                $rec['id']          = $arr['id'];
                $rec['taxon_name']  = $arr['taxon_name'];
                $rec['rank']        = $arr['rank'];
                $rec['parent_id']   = @$arr['parent']['id'];
                self::create_parent_taxon($rec);
                $arr = @$arr['parent']; //trigger a loop
                if(!@$arr['parent']) {  //if true, then get the last record
                    $rec = array();
                    $rec['id']          = $arr['id'];
                    $rec['taxon_name']  = $arr['taxon_name'];
                    $rec['rank']        = $arr['rank'];
                    $rec['parent_id']   = @$arr['parent']['id'];
                    self::create_parent_taxon($rec);
                }
            }
        }
    }
    private function create_parent_taxon($rec)
    {
        if(!@$rec['taxon_name']) return;
        $t = new \eol_schema\Taxon();
        $t->taxonID                 = $rec['id'];
        $t->scientificName          = $rec['taxon_name'];
        $t->taxonRank               = $rec['rank'];
        $t->parentNameUsageID       = @$rec['parent_id'];
        $t->source                  = "https://www.wikidata.org/wiki/".$t->taxonID;
        if(!isset($this->taxon_ids[$t->taxonID])) {
            $this->taxon_ids[$t->taxonID] = '';
            $this->archive_builder->write_object_to_file($t);
        }
        /*
        [id] => Q25833
        [taxon_name] => Eutheria
        [rank] => infraclass
        [parent_id] => Q130942
        */
    }
    function open_json_files_generated_above() //called from wikidata_test.php
    {
        for($z = 921904; $z <= 921910; $z++) { echo "\nprocessing $z ";
            $json_file = CONTENT_RESOURCE_LOCAL_PATH."/$z".".json";
            if(file_exists($json_file)) { echo " exists...";
                $json = file_get_contents($json_file);
                $arr = json_decode($json); // print_r($arr);
                if(is_object($arr)) { echo " 111 ";
                    $rek = array();
                     $rek['taxon_id'] = trim((string) $arr->id);
                     if($rek['taxon'] = self::get_taxon_name($arr)) { //old working param is $arr->claims
                         echo " 222 ";
                         if($rek['sitelinks'] = self::get_taxon_sitelinks_by_lang($arr->sitelinks)) { //if true then create DwCA for it
                             echo " 333 ";
                             // print_r($rek['sitelinks']); exit; good debug
                             $rek['rank'] = self::get_taxon_rank($arr->claims); echo " 444 ";
                             $rek['author'] = self::get_authorship($arr->claims); echo " 555 ";
                             $rek['author_yr'] = self::get_authorship_date($arr->claims); echo " 777 ";
                             $rek['parent'] = self::get_taxon_parent($arr->claims, $rek['taxon_id']); echo " 888 ";
                             print_r($rek);
                             continue;
                             debug("\n $this->language_code ".$rek['taxon_id']." - ");
                         }
                     }
                     else $j++;
                }
                else echo " --- not object";
            }
        }
    }
    function investigate_latest_all_taxon_json() //called from wikidata_test.php
    { //copied from a template - below 
        $k = 0;
        foreach(new FileIterator($this->path['wiki_data_json']) as $line_number => $row) {
            $k++; if(($k % 1000) == 0) echo " ".number_format($k)." ";
            if(stripos($row, "Q16521") !== false) { //string is found -- "taxon"
                /* remove the last char which is "," a comma */
                $row = substr($row,0,strlen($row)-1); //removes last char which is "," a comma
                debug("\n$k. size: ".strlen($row)."\n"); //elixAug2
                if($k >= 921904 && $k <= 921910) { //investigate the problem $row
                    $f = Functions::file_open(CONTENT_RESOURCE_LOCAL_PATH."/$k".".json", "w"); //creates json files for further investigation - open_json_files_generated_above()
                    fwrite($f, $row); fclose($f); continue;
                }
                else continue;
            } //end of taxon wiki
        } //main loop
    }
    private function parse_wiki_data_json($task = false, $range_from = false, $range_to = false)
    {
        $exit_now = false; //only used during debug
        $actual = 0; $i = 0; $j = 0;
        $k = 0; $m = 250000; //only for breakdown when caching
        foreach(new FileIterator($this->path['wiki_data_json']) as $line_number => $row) {
            $k++; if(($k % 1000) == 0) echo " ".number_format($k)." ";
            if(in_array($task, array("save_all_media_filenames", "generate_resource")) && $range_from && $range_to) {
                $cont = false;
                if($k >= $range_from && $k < $range_to) $cont = true;
                if(!$cont) continue;
            }

            /* this can be used to investigate rows OR this case exclude rows
            if($k >= 921904 && $k <= 921910) continue; //elixAug2 ---> the actual fix is the infinite loop in get_taxon_parent()
            */
            /* the problem was this one:
            921904. size: 2173
            Segmentation fault (core dumped)
            Build step 'Execute shell' marked build as failure
            Finished: FAILURE
            */

            /* good way to limit foreach loop
            if($k >= 1 && $k < 10) $cont = true;
            else break;
            */

            /* breakdown when caching:
            $cont = false;
            // if($k >=  1    && $k < $m) $cont = true;
            // if($k >=  $m   && $k < $m*2) $cont = true;
            // if($k >=  $m*2 && $k < $m*3) $cont = true;
            // if($k >=  $m*3 && $k < $m*4) $cont = true;
            // if($k >=  $m*4 && $k < $m*5) $cont = true;
            // if($k >=  $m*5 && $k < $m*6) $cont = true;
            // if($k >=  $m*6 && $k < $m*7) $cont = true;
            // if($k >=  $m*7 && $k < $m*8) $cont = true;
            // if($k >=  $m*8 && $k < $m*9) $cont = true;

            // these 3 have many pages, but just a stub page with under-construction feel
            // if($k >= 1132112 && $k < $m*5) $cont = true; // nl
            // if($k >= 601476 && $k < $m*5) $cont = true; // sv
            // if($k >= 1154430 && $k < $m*5) $cont = true; // vi

            if($k >= 1 && $k < 50) $cont = true;   //wikimedia total taxa = 2,208,086
            else break;
            
            // if($k >= 1000000) $cont = true;   //wikimedia total taxa = 2,208,086
            
            if(!$cont) continue;
            */

            if(stripos($row, "Q16521") !== false) { //string is found -- "taxon"
                /* remove the last char which is "," a comma */
                $row = substr($row,0,strlen($row)-1); //removes last char which is "," a comma

                // debug("\n$k. size: ".strlen($row)."\n"); //elixAug2
                $arr = json_decode($row);
                $Q_id = $arr->id;

                /* for debug start ====================== Q4589415 - en with blank taxon name | Q5113 - jap with erroneous desc | ko Q8222313 has invalid parent | Q132634
                $arr = self::get_object('Q6707390');
                $arr = $arr->entities->Q6707390;
                for debug end ======================== */
                
                /* force taxon in wikipedia & wikimedia. when developing. ***
                $arr = self::get_object('Q140'); $arr = $arr->entities->Q140; //Panthera leo
                // $arr = self::get_object('Q199788'); $arr = $arr->entities->Q199788; //Gadus morhua (No Indonesian - id)
                // $arr = self::get_object('Q26843'); $arr = $arr->entities->Q26843; //Orca
                // $arr = self::get_object('Q1819782'); $arr = $arr->entities->Q1819782; //Pacific halibut - Hippoglossus stenolepis
                // $arr = self::get_object('Q739525'); $arr = $arr->entities->Q739525; //Vulpes pallida -- Pale fox
                // $arr = self::get_object('Q465261'); $arr = $arr->entities->Q465261; //Chanos chanos
                // $arr = self::get_object('Q311170'); $arr = $arr->entities->Q311170; //Oreochromis niloticus
                // $arr = self::get_object('Q33609'); $arr = $arr->entities->Q33609; //Polar bear - Ursus maritimus
                // $arr = self::get_object('Q25314'); $arr = $arr->entities->Q25314; //Angiosperms DATA-1803
                // $arr = self::get_object('Q83310'); $arr = $arr->entities->Q83310; //Mus musculus - house mouse
                // $arr = self::get_object('Q729'); $arr = $arr->entities->Q729; //Animalia
                // $arr = self::get_object('Q756'); $arr = $arr->entities->Q756; //Plantae
                // $arr = self::get_object('Q80005'); $arr = $arr->entities->Q80005; //ferns
                // $arr = self::get_object('Q81666'); $arr = $arr->entities->Q81666; //Acacia
                // $arr = self::get_object('Q19939'); $arr = $arr->entities->Q19939; //tiger - Panthera tigris
                // $arr = self::get_object('Q127216'); $arr = $arr->entities->Q127216; //Bald Eagle
                // $arr = self::get_object('Q171497'); $arr = $arr->entities->Q171497; //sunflower
                */
                
                // /* taxon_wiki_per_language_stats
                if($task == 'taxon_wiki_per_language_stats') {
                    /* 1st version: OK but triggers API calls. Better to use what the json dump gives.
                    $arr = self::get_object($Q_id);
                    $arr = $arr->entities->$Q_id;
                    */
                    if($val = @$arr->sitelinks) {
                        self::taxon_wiki_per_language_stats((array) $val);
                        // if($k >= 10) break; //debug only
                    }
                    continue;
                }
                // */
                
                /* print_r($arr->claims->P935); exit; */
                
                if(is_object($arr)) {
                    $rek = array();
                     // /*
                     $rek['taxon_id'] = trim((string) $arr->id);
                     if($rek['taxon'] = self::get_taxon_name($arr)) { //old working param is $arr->claims
                         // /* normal operation ==========================
                         if($rek['sitelinks'] = self::get_taxon_sitelinks_by_lang($arr->sitelinks)) { //if true then create DwCA for it
                             // print_r($rek['sitelinks']); exit; good debug
                             $i++; 
                             $rek['rank'] = self::get_taxon_rank($arr->claims);
                             $rek['author'] = self::get_authorship($arr->claims);
                             $rek['author_yr'] = self::get_authorship_date($arr->claims);
                             $rek['parent'] = self::get_taxon_parent($arr->claims, $rek['taxon_id']);
                             
                             if($this->what == "wikimedia") $rek['vernaculars'] = self::get_vernacular_names($arr->claims, $rek, $arr); //this is where vernaculars are added

                             $rek['com_gallery'] = self::get_commons_gallery($arr->claims); //P935
                             $rek['com_category'] = self::get_commons_category($arr->claims); //P373
                             debug("\n $this->language_code ".$rek['taxon_id']." - ");
                             if($this->what == "wikipedia") $rek = self::get_other_info($rek); //uncomment in normal operation
                             if($this->what == "wikimedia") {
                                 if($url = @$rek['com_category'])   $rek['obj_category'] = self::get_commons_info($url);
                                 if($url = @$rek['com_gallery'])    $rek['obj_gallery'] = self::get_commons_info($url);
                                 // print_r($rek['obj_gallery']); exit;
                                 if($range_maps = self::get_range_map($arr->claims)) {
                                     if(@$rek['obj_gallery']) $rek['obj_gallery'] = array_merge($range_maps, $rek['obj_gallery']);
                                     else                     $rek['obj_gallery'] = $range_maps;
                                 }
                                 /* eli's debug
                                 if($a = @$rek['obj_category']) {}//print_r($a);
                                 if($b = @$rek['obj_gallery']) {}//print_r($b);
                                 if($a || $b)
                                 {
                                     print_r($rek);
                                     $exit_now = true;
                                     // exit("\nmeron commons\n");
                                 }
                                 */ //eli's debug end
                             }
                             
                             if($rek['taxon_id']) {
                                 $ret = self::create_archive($rek);
                                 if($ret) {
                                     self::save_ancestry_to_temp($rek['parent']);
                                 }
                                 // if(!@$rek['other']['comprehensive_desc']) { print_r($rek); exit("\ninvestigate\n"); }
                                 // print_r($rek);
                                 // break; //debug - process just 1 rec
                                 
                                 /*
                                 $actual++; echo " [$actual] ";
                                 if($actual >= 5000) break;   //debug - used only on batch of 5000 articles per language
                                 */
                             }
                         }
                         // else echo "\nNo sitelinks\n"; //debug only
                         // print_r($rek); //exit("\nstop muna\n");
                         // if($i >= 20) break; //debug
                         // ===============================*/ //end normal operation
                         
                         /* utility: this is to count how many articles per language ==============
                         if($arr = self::get_taxon_sitelinks($arr->sitelinks)) {
                             foreach($arr as $a) {
                                 $str = str_replace("wiki", "", $a->site);
                                 $this->debug[$str]++;
                             }
                             // if($j > 100) break; //debug
                         }
                         ==========================================================================*/
                     }
                     else $j++;
                     // */
                }
                else {
                    echo "\n[$row]\n";
                    echo("\n --Investigate not ok-- \n"); //previously this is exit()
                }
                
                // break; //debug get first taxon wiki only //use this when developing*** wikimedia and wikipedia
                // if($k > 10) break; //10000
                // if($exit_now) break;
                
            } //end of taxon wiki
            else $j++; //non-taxon wiki
            // if($exit_now) break;
            
        } //main loop
        echo "\ntotal taxon wikis = [$i]\n";
        echo "\ntotal non-taxon wikis = [$j]\n";
        
        if($task == 'taxon_wiki_per_language_stats') {
            $a = self::eli_sort($this->count_taxon_wiki_per_lang);                        
            // print_r($a);
            //write to text:
            /*[52] => Array(
                        [language] => kk
                        [count] => 4290
                    )
            */
            $txtfile = CONTENT_RESOURCE_LOCAL_PATH . "taxon_wiki_per_language_count_" . date("Y_m_d") . ".txt";
            $handle = fopen($txtfile, "w");
            $arr = array('language', 'count');
            fwrite($handle, implode("\t", $arr) . "\n");
            foreach($a as $rec) {
                $arr = array($rec['language'], $rec['count']);
                fwrite($handle, implode("\t", $arr) . "\n");
            }
            fclose($handle);
            exit("\n-end stats-\n");
        }
    }
    private function save_ancestry_to_temp($ancestry)
    {
        $id = $ancestry['id'];
        if(!isset($this->saved_ids[$id])) {
            $this->saved_ids[$id] = '';
            if(isset($this->TEMP_FILE_PATH)) { //bec step 5 in Jenkins -> Wikimedia Commons: [Project 05 Finalize media filenames] doesn't need this step.
                if(!($f = Functions::file_open($this->TEMP_FILE_PATH, "a"))) return;
                fwrite($f, json_encode($ancestry)."\n");
                fclose($f);
            }
        }
    }
    private function create_archive($rec)
    {
        if($this->what == "wikimedia") {
            if(!@$rec['obj_gallery'] && !@$rec['obj_category']) return;
        }
        if($this->what == "wikipedia") {
            if(!trim(@$rec['other']['comprehensive_desc'])) return;
        }
        
        if(!@$rec['taxon']) return;
        $t = new \eol_schema\Taxon();
        $t->taxonID                  = $rec['taxon_id'];
        $t->scientificName           = $rec['taxon'];
        if($t->scientificNameAuthorship = $rec['author']) {
            if($year = $rec['author_yr']) {
                //+1831-01-01T00:00:00Z
                $year = substr($year,1,4);
                $t->scientificNameAuthorship .= ", $year";
            }
        }
        
        $t->taxonRank                = $rec['rank'];
        $t->parentNameUsageID        = $rec['parent']['id'];
        
        if($val = @$rec['other']['permalink']) $t->source = $val;
        else                                   $t->source = "https://www.wikidata.org/wiki/".$t->taxonID;

        if(!isset($this->taxon_ids[$t->taxonID])) {
            $this->taxon_ids[$t->taxonID] = '';
            $this->archive_builder->write_object_to_file($t);
        }

        if($val = @$rec['vernaculars']) self::add_vernaculars($val, $rec['taxon_id']);

        // if($rec['taxon_id'] == "Q5113" && $this->language_code == "ja") return; //debug force
        // if($rec['taxon_id'] == "Q5113") return; //Aves is problematic...debug force

        //start media objects
        $media = array();
        
        if($this->what == "wikipedia") {
            if($description = trim(@$rec['other']['comprehensive_desc'])) {
                // Comprehensive Description
                $media['identifier']             = md5($rec['taxon_id']."Comprehensive Description");
                $media['title']                  = $rec['other']['title'];
                $media['description']            = $description;
                $media['CVterm']                 = 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Description';
                // below here is same for the next text object
                $media['taxonID']                = $t->taxonID;
                $media['type']                   = "http://purl.org/dc/dcmitype/Text";
                $media['format']                 = "text/html";
                $media['language']               = $this->language_code;
                $media['Owner']                  = $this->trans['editors'][$this->language_code];
                $media['UsageTerms']             = 'http://creativecommons.org/licenses/by-sa/3.0/';
                $media['furtherInformationURL'] = $rec['other']['permalink'];
                self::create_wikipedia_object($media);
                
                // Brief Summary
                $media['identifier']             = md5($rec['taxon_id']."Brief Summary");
                $media['title']                  = $rec['other']['title'] . ': Brief Summary';
                $media['description']            = $rec['other']['brief_summary'];
                $media['CVterm']                 = 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#TaxonBiology';
                if($media['description']) self::create_wikipedia_object($media);
            }
        }
        
        if($this->what == "wikimedia") {
            if($commons = @$rec['obj_gallery'])     self::create_commons_objects($commons, $t);
            if($commons = @$rec['obj_category'])    self::create_commons_objects($commons, $t);
        }

        /* // Brief Summary - works well for 'de'
        $media['identifier']             = md5($rec['permalink']."Brief Summary");
        $media['title']                  = $rec['title'] . ': Brief Summary';
        $media['description']            = $rec['brief_desc'];
        $media['CVterm']                 = 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#TaxonBiology';
        if($media['description']) self::create_wikipedia_object($media);
        */
        return true;
    }
    private function create_commons_objects($commons, $t)
    {
        foreach($commons as $com) {
            $formatted_license = self::format_license(@$com['LicenseUrl'], @$com['LicenseShortName']);
            if(!self::valid_license_YN($formatted_license)) $this->debug['invalid_LicenseUrl'][$formatted_license] = '';
            else {
                /*
                [pageid] => 56279236
                [timestamp] => 2017-03-23T23:20:37Z
                [ImageDescription] => Summary <table cellpadding="4"> <tr> <td lang="en">DescriptionAPI</td> <td> English: Simplified cladogram showing that the whales are paraphyletic with respect to the dolphins and porpoises. The clade Cetacea includes all these animals. </td> </tr> <tr> <td lang="en">Date</td> <td lang="en">14 February 2017</td> </tr> <tr> <td lang="en">Source</td> <td>This file was derived from <a href="https://commons.wikimedia.org/wiki/File:Whales_are_Paraphyletic.png" title="File:Whales are Paraphyletic.png">Whales are Paraphyletic.png</a>: <a href="https://commons.wikimedia.org/wiki/File:Whales_are_Paraphyletic.png" ></a><br /></td> </tr> <tr> <td lang="en">Author</td> <td> Original: <a href="https://commons.wikimedia.org/wiki/User:Chiswick_Chap" title="User:Chiswick Chap">Chiswick Chap</a> Vectorisation: <a href="https://commons.wikimedia.org/wiki/User:CheChe" title="User:CheChe">CheChe</a> </td> </tr> </table> <br /> <table > <tr> <td></td> <td>This is a <i><a href="https://en.wikipedia.org/wiki/Image_editing" title="w:Image editing">retouched picture</a></i>, which means that it has been digitally altered from its original version. The original can be viewed here: <a href="https://commons.wikimedia.org/wiki/File:Whales_are_Paraphyletic.png" title="File:Whales are Paraphyletic.png">Whales are Paraphyletic.png</a>. Modifications made by <a href="https://commons.wikimedia.org/wiki/User:CheChe" title="User:CheChe">CheChe</a>. </td> </tr> </table> Licensing <table cellspacing="8" cellpadding="0" > <tr> <td> <table lang="en"> <tr> <td rowspan="3"><br /> </td> <td lang="en">This file is licensed under the <a href="https://en.wikipedia.org/wiki/en:Creative_Commons" title="w:en:Creative Commons">Creative Commons</a> <a rel="nofollow" href="http://creativecommons.org/licenses/by-sa/4.0/deed.en">Attribution-Share Alike 4.0 International</a> license.</td> <td rowspan="3"></td> </tr> <tr> <td></td> </tr> <tr lang="en"> <td> http://creativecommons.org/licenses/by-sa/4.0 CC BY-SA 4.0 Creative Commons Attribution-Share Alike 4.0 truetrue </td> </tr> </table> </td> </tr> </table>
                [LicenseShortName] => self|cc-by-sa-4.0
                [LicenseUrl] => http://creativecommons.org/licenses/by-sa/4.0/deed.en
                [title] => Whales are Paraphyletic.svg
                [other] => Array (
                        [date] => 2017-02-14
                        [author] => *Original: [[User:Chiswick Chap|Chiswick Chap]]
                        [source] => {{derived from|Whales are Paraphyletic.png|display=50}}
                        [permission] => 
                    )
                [date] => 2017-02-14
                [Artist] => Array (
                        [0] => Array (
                                [name] => Chiswick Chap
                                [homepage] => https://commons.wikimedia.org/wiki/User:Chiswick_Chap
                            )
                    )
                [fromx] => dump
                [source_url] => https://commons.wikimedia.org/wiki/File:Whales_are_Paraphyletic.svg
                [media_url] => https://upload.wikimedia.org/wikipedia/commons/3/30/Whales_are_Paraphyletic.svg
                */

                // /*
                $media = array();
                $media['identifier']             = $com['pageid'];
                $media['title']                  = $com['title'];
                $media['description']            = $com['ImageDescription'];
                // $media['CVterm']                 = ''; not applicable - EOL subject
                // below here is same for the next text object
                $media['taxonID']                = $t->taxonID;
                $media['format']                 = Functions::get_mimetype($com['media_url']);
                $media['subtype'] = @$com['eol_type'];
                // if($com['media_url'] == "https://upload.wikimedia.org/wikipedia/commons/0/07/Opilion_stalking_lavender_sunset_September.jpeg") {
                //     print_r($com); exit;
                // }
                
                if(!$media['format']) {
                    $this->debug['undefined media ext. excluded'][pathinfo($com['media_url'], PATHINFO_EXTENSION)] = '';
                    continue;
                }
                $media['type']                   = Functions::get_datatype_given_mimetype($media['format']);
                
                // if(!$media['type']) $this->debug['undefined DataType 1'][@$media['format']] = '';
                // if(!$media['type']) $this->debug['undefined DataType 2'][@$com['media_url']] = '';
                
                $media['language']               = $this->language_code;
                $media['Owner']                  = '';
                $media['UsageTerms']             = $formatted_license; //$com['LicenseUrl']; //license
                $media['furtherInformationURL']  = $com['source_url'];
                $media['accessURI']              = $com['media_url'];

                /* obsolete. we're now adding the agent with role 'source' before writing to DwCA
                // print_r($com);
                //new start https://eol-jira.bibalex.org/browse/DATA-1784 - if there is 'source' make it an agent with role='source'.
                if($source_wiki = @$com['other']['source']) {
                    if($source_wiki != "{{own}}") {
                        $source_html = self::convert_wiki_2_html($source_wiki);
                        // echo "\n[$source_html]\n"; exit;
                        if($source_agent = self::prepare_html_as_source_agent($source_html)) $com['Artist'][] = $source_agent;
                    }
                }
                //new end
                // print_r($com);
                */
                
                $role = Functions::get_role_given_datatype($media['type']);
                if($agent_ids = self::gen_agent_ids($com['Artist'], strtolower($role))) $media['agentID'] = implode("; ", $agent_ids);

                if(!@$media['agentID']) {
                    echo "\n-------start investigate--------Undefined index: agentID---\n";
                    print_r($com);
                    echo "\n-------end investigate--------Undefined index: agentID---\n";
                    // print_r($media);
                    $this->debug['file in question'][pathinfo($media['furtherInformationURL'], PATHINFO_BASENAME)] = '';
                }

                $media = self::last_quality_check($media); //removes /n and /t inside values. May revisit this as it may not be the sol'n for 2 rows with wrong no. of columns.
                
                // new start https://eol-jira.bibalex.org/browse/DATA-1784 - if agent is creator and license is PD, make that agent Owner in media object.
                if(@$com['Artist'][0]['role'] == 'creator' && $media['UsageTerms'] != $this->license['public domain']) {
                    if($val = $com['Artist'][0]['name']) $media['Owner'] = $val;
                }
                // print_r($media);
                // new end
                
                $mr = new \eol_schema\MediaResource(); //for Wikimedia objects only
                $mr->taxonID                = $media['taxonID'];
                $mr->identifier             = $media['identifier'];
                $mr->type                   = $media['type'];
                $mr->format                 = $media['format'];
                $mr->subtype                = $media['subtype'];
                $mr->language               = $media['language'];
                $mr->UsageTerms             = $media['UsageTerms'];
                // $mr->CVterm                 = $media['CVterm'];
                $mr->description            = $media['description'];
                /* debug only
                echo "\n=========================\n";
                echo "[".$mr->description."]";
                echo "\n=========================\n";
                */
                $mr->accessURI              = $media["accessURI"];
                $mr->furtherInformationURL  = $media['furtherInformationURL'];
                $mr->title                  = $media['title'];
                $mr->Owner                  = $media['Owner'];
                $mr->agentID                = @$media['agentID'];
                
                if(!isset($this->object_ids[$mr->identifier])) {
                    $this->object_ids[$mr->identifier] = '';
                    $this->archive_builder->write_object_to_file($mr);
                }
                // */
            }
        }
    }
    private function prepare_html_as_source_agent($html)
    {   /* 
        $source_wiki e.g. value:
        {{derived from|Whales are Paraphyletic.png|display=50}}
    
        $source_html e.g. value:
        This file was derived from: <a href="https://commons.wikimedia.org/wiki/File:Whales_are_Paraphyletic.png" title="File:Whales are Paraphyletic.png">Whales are Paraphyletic.png</a>: 
                                    <a href="https://commons.wikimedia.org/wiki/File:Whales_are_Paraphyletic.png" ></a><br />
        another e.g.:
        wiki:
        Photograped by [https://sites.google.com/site/thebrockeninglory/ Brocken Inaglory] in [[:en::en:Northern California|Northern California]]
        html:
        Photograped by <a rel="nofollow" href="https://sites.google.com/site/thebrockeninglory/">Brocken Inaglory</a> in <a href="https://en.wikipedia.org/wiki/en:Northern_California" title="en:en:Northern California">Northern California</a>
        */
        // echo "\n\n".$html."\n\n";
        $final = array();
        // if(preg_match("/title=\"(.*?)\"/ims", $html, $arr)) $final['name'] = $arr[1];
        if(preg_match("/\>(.*?)<\/a>/ims", $html, $arr)) $final['name'] = $arr[1];
        if(preg_match("/<a href=\"(.*?)\"/ims", $html, $arr)) $final['homepage'] = $arr[1];
        if(@$final['name']) {
            $final['role'] = 'source';
            return $final;
        }
    }
    private function last_quality_check($media)
    {
        $fields = array_keys($media);
        foreach($fields as $field) {
            $media[$field] = str_replace("\t", " ", $media[$field]);
            $media[$field] = str_replace("\n", "<br>", $media[$field]);
        }
        return $media;
    }
    private function fix_agent_name($a)
    {
        $name = trim(@$a['name']);
        $name = strip_tags($name);
        $name = str_replace(array("\t", "\n"), "", $name);
        $name = str_replace(array("'''", "''"), "'", $name);
        if(preg_match("/\#if\:\|\[2 (.*?)\]/ims", $name, $arr)) { /* #if:94187100@N00|[http://flickr.com/photos/94187100@N00 Hernán García Crespo]|#if:|[2 Hernán García Crespo]|Hernán García Crespo #if:|from location */
            if(preg_match("/\[http\:\/\/flickr\.com\/photos\/(.*?)\]/ims", $name, $arr2)) $a['homepage'] = "http://flickr.com/photos/".$arr2[1];
            $name = trim($arr[1]);
            $a['role'] = 'creator';
        }
        elseif(preg_match("/\[User\:(.*?)\]/ims", $name, $arr)) { /* Author assumed|[[User:McLeod|McLeod]] */
            $name = $arr[1];
            $arr = explode("|", $name);
            if(@$arr[0] == @$arr[1]) $name = trim($arr[0]);
        }
        elseif(strtolower(substr($name,0,5)) == "user:") {
            $name = str_ireplace("user:", "", $name);
            $arr = explode("/", $name);
            $name = trim($arr[0]);
        }
        $a['name'] = $name;
        return $a;
    }
    private function clean_agent_rec($a)
    {
        $role = trim(@$a['role']);
        $role = str_replace(array("\t", "\n", "|"), "", $role);
        $a['role'] = $role;
        
        $homepage = trim(@$a['homepage']);
        $homepage = str_replace(array("\t", "\n"), "", $homepage);
        $a['homepage'] = $homepage;
        
        if(is_array(@$a['name'])) {
            echo "\n---start---\n";
            echo "this var should always be string, not array, investigate:";
            print_r($a);
            echo "\n---end---\n";
            return false;
        }
        
        if(trim(@$a['name'])) {
            $a = self::fix_agent_name($a);
            // /* start debug mode... normally comment this. Uncomment only during investigation -------------
            $keys = array_keys($a);
            foreach($keys as $key) {
                if(is_array($a[$key])) {
                    echo "\n--------investigate agent----start---found array-----\n";
                    print_r($a);
                    echo "\n--------investigate agent----end-----found array-----\n";
                }
            }
            // ----------------------------------------------------------------------------------------------- */
            if($a = array_map('trim', $a)) {
                /* fix homepage format e.g. "http://flickr.com/photos/94187100@N00 Hernán García Crespo" */
                if($homepage = @$a['homepage']) {
                    $tmp = explode(" ", $homepage);
                    $a['homepage'] = $tmp[0];
                }
                return $a;
            }
            else {
                echo "\n--------investigate agent----start--------\n";
                print_r($a);
                echo "\n--------investigate agent----end--------\n";
            }
        }
        return false;
    }
    private function gen_agent_ids($artists, $role)
    {   /* $artists must not be:
        Array (
            [name] => Wikigraphists
            [homepage] => https://en.wikipedia.org/wiki/Wikipedia:Graphics_Lab
            [role] => creator
        )
        but rather:
        Array(
            0 => Array (
                [name] => Wikigraphists
                [homepage] => https://en.wikipedia.org/wiki/Wikipedia:Graphics_Lab
                [role] => creator
            )
        )*/
        if(isset($artists['name'])) {
            $temp = $artists;
            $artists = array();
            $artists[] = $temp;
        }
        
        $agent_ids = array();
        if(!@$artists) return array();
        foreach($artists as $a) {
            if($a = self::clean_agent_rec($a)) {}
            else continue;
            if(!$a['name']) continue;
            $r = new \eol_schema\Agent();
            $r->term_name       = $a['name'];
            $r->agentRole       = ($val = @$a['role']) ? (string) $val : $role;
            $r->agentRole = trim(str_replace("|", "", $r->agentRole));
            /* to capture erroneous artist entries
            if(strlen($r->agentRole) == 1)
            {
                print_r($artists);
                exit("\nagent role is just 1 char\n");
            }
            */
            $r->term_homepage   = self::format_homepage(@$a['homepage']);
            $r->identifier      = md5("$r->term_name|$r->agentRole");
            $agent_ids[] = $r->identifier;
            if(!isset($this->agent_ids[$r->identifier])) {
               $this->agent_ids[$r->identifier] = '';
               $this->archive_builder->write_object_to_file($r);
            }
        }
        return $agent_ids;
    }
    private function format_homepage($url)
    {
        if    (substr($url,0,5) == "File:") return "https://commons.wikimedia.org/wiki/".$url;
        elseif(substr($url,0,5) == "User:") return "https://wikipedia.org/wiki/".$url;
        return $url;
    }
    private function get_commons_info($url)
    {
        $final = array();
        // <a href="/wiki/File:A_hand-book_to_the_primates_(Plate_XL)_(5589462024).jpg"
        // <a href="/wiki/File:Irrawaddy_Dolphin.jpg"
        debug("\nelix:[$url]\n");
        $options = $this->download_options;
        if($html = Functions::lookup_with_cache($url, $options)) { //preferably monthly cache expires. This gets filenames from page-gallery & page-category
            if(preg_match_all("/<a href=\"\/wiki\/File:(.*?)\"/ims", $html, $arr)) {
                $files = array_values(array_unique($arr[1]));
                // print_r($files); //exit;
                
                //for utility use only, will not pass here on normal operation =========================== start
                if($this->save_all_filenames) { 
                    self::save_filenames_2file($files);
                    return;
                }
                //for utility use only, will not pass here on normal operation =========================== end
                
                $limit = 0;
                foreach($files as $file) { // https://commons.wikimedia.org/wiki/File:Eyes_of_gorilla.jpg
                    $rek = self::process_file($file);
                    if($rek == "continue") continue;
                    if(!$rek) continue;
                    /* debug only -- use when u want to generate DwCA with just one media       //use this when developing*** wikimedia only
                    $rek = self::process_file("Tanzanie_Lionne.jpg");    //??? wrong license
                    // $rek = self::process_file("Frazer´s_dolphin_group.jpg");    //no artist
                    // $rek = self::process_file("Haworthia_arachnoidea_-_cobweb_aloe.jpg");    //no artist
                    // $rek = self::process_file("Aa_species.jpg");
                    // $rek = self::process_file("Whales_are_Paraphyletic.svg");
                    // $rek = self::process_file("Whales_are_Paraphyletic.png");
                    // $rek = self::process_file("Red_stingray2.jpg"); //8680729                //invalid license
                    // $rek = self::process_file("Soft-shell_crab_on_ice.jpg"); //10964578
                    // $rek = self::process_file("Slifkin.jpg"); //11930268
                    // $rek = self::process_file("Clone_war_of_sea_anemones_3.jpg"); //18645958
                    $final[] = $rek;
                    break; //debug
                    */
                    // print_r($rek); //exit;
                    if($rek['pageid']) {
                        $final[] = $rek;
                        $limit++;
                    }
                    // if($limit >= 35) break; //no. of images to get
                }
                // exit("\n cha222 \n");
            }
        }
        // print_r($final);exit;
        return $final;
    }
    private function process_file($file) //e.g. Abhandlungen_aus_dem_Gebiete_der_Zoologie_und_vergleichenden_Anatomie_(1841)_(16095238834).jpg
    {
        $rek = array();
        // if(false) //will force to use API data - debug only
        if($filename = self::has_cache_data($file)) { //Eyes_of_gorilla.jpg - used in normal operation -- get media info from commons
        // if(false) { //this is when debugging... force use api instead of json.
            debug("\nused cache data");
            $rek = self::get_media_metadata_from_json($filename, $file);
            if($rek == "protected") return false; //"continue";
            if(!$rek) {
                // echo "\njust used api data instead";
                /*
                if(!in_array($file, array("The_marine_mammals_of_the_north-western_coast_of_North_America,_described_and_illustrated;_together_with_an_account_of_the_American_whale-fishery_(1874)_(14598172619).jpg", 
                "The_marine_mammals_of_the_north-western_coast_of_North_America_described_and_illustrated_(microform)_-_together_with_an_account_of_the_American_whale-fishery_(1874)_(20624848441).jpg"))) exit("\n111 [$file] 222\n");
                */
                $rek = self::get_media_metadata_from_api($file);
            }
            // print_r($rek); exit;
        }
        else {
            debug("\nused api data");
            $rek = self::get_media_metadata_from_api($file);
        }
        if(!$rek) return false;
        
        $rek['source_url']  = "https://commons.wikimedia.org/wiki/File:".$file;
        $rek['media_url']   = self::get_media_url($file);
        if($val = @$rek['Artist']) $rek['Artist'] = self::format_artist($val);
        $rek['ImageDescription'] = Functions::remove_this_last_char_from_str($rek['ImageDescription'], "|");
        
        //will capture in report source of various invalid data (to check afterwards) but will not stop process.
        if(!self::url_is_valid($rek['source_url'])) {
            $this->debug['invalid source_url'][$rek['pageid']] = '';
            $rek['source_url'] = '';
        }
        if(!self::url_is_valid($rek['media_url'])) {
            $this->debug['invalid media_url'][$rek['pageid']] = '';
            return false;
        }

        /* not the proper place here...
        if(!self::valid_license_YN($rek['LicenseUrl'])) {
            $rek['LicenseUrl'] = self::format_license($rek['LicenseUrl']);
            if(!self::valid_license_YN($rek['LicenseUrl'])) {
                print_r($rek); exit("\nstop muna tayo\n");
                $this->debug['invalid license pageid is'][$rek['pageid']] = '';
                return false;
            }
        }
        */
        
        // if(!self::lang_is_valid())
        // print_r($rek); exit("\nice\n");
        /* ditox
        URI: http://purl.org/dc/terms/language
        Message: Language should use standardized ISO 639 language codes
        Line Value:  Burma creeper, Chinese honeysuckle, Rangoon creeper (English) 
        */
        return $rek;
    }
    private function url_is_valid($url)
    {
        $url = trim($url);
        if(substr($url,0,7) == "http://") return true;
        if(substr($url,0,8) == "https://") return true;
        return false;
    }
    private function lang_is_valid($lang)
    {
        $lang = trim($lang);
        if(strlen($lang) <= 3) return true;
        else                   return false;
    }
    private function format_artist($str)
    {
        if(is_array($str)) return $str;
        $str = trim($str);
        // [Artist] => [[User:Chiswick Chap|Ian Alexander]]
        if(preg_match("/\[\[User:(.*?)\]\]/ims", $str, $a)) {
            $arr = explode("|", $a[1]);
            $arr = array_unique($arr);
            $final = array();
            foreach($arr as $t) $final[] = array('name' => $t, 'homepage' => "https://commons.wikimedia.org/wiki/User:".str_replace(" ", "_", $t));
            if($final) return $final;
        }
        
        //[Artist] => <a rel="nofollow" class="external text" href="https://www.flickr.com/people/126377022@N07">Internet Archive Book Images</a>
        if(substr($str,0,3) == "<a " && substr_count($str, '</a>') == 1) {
            $temp = array();
            if(preg_match("/>(.*?)<\/a>/ims", $str, $a))    $temp['name'] = $a[1];
            if(preg_match("/href=\"(.*?)\"/ims", $str, $a)) $temp['homepage'] = $a[1];
            if($temp) {
                $final[] = $temp;
                return $final;
            }
        }
        
        //[Artist] => <span lang="en">Anonymous</span>
        if(substr($str,0,6) == "<span " && substr_count($str, '</span>') == 1) {
            return array(array('name' => strip_tags($str)));
        }
        return $str;
    }
    private function has_cache_data($file)
    {
        if($filename = self::taxon_media($file)) {
            if(filesize($filename) > 0) return $filename;
        }
        return false;
    }
    private function wiki_protected($wiki)
    {
        if(stripos($wiki, "{{Mprotected}}") !== false) return true; //string is found
        if(stripos($wiki, "Wiktionary-logo") !== false) return true; //string is found
        if(stripos($wiki, "Wikispecies-logo") !== false) return true; //string is found
        return false;
    }
    private function un_tabular_the_description($desc) //new Nov 5, 2018
    {   // exit("\n$desc\n");
        $desc = str_replace("Summary <table", "<table", $desc);
        $desc = str_replace("DescriptionAPI</td>", "Description</td>", $desc);
        $desc = str_replace("</td> <td", ":</td> <td", $desc);
        $desc = str_replace("</tr>", ". ", $desc);
        $desc = strip_tags($desc, "<a>");
        $desc = Functions::remove_whitespace($desc);
        $desc = str_replace(" .", ".", $desc);
        $desc = str_replace("..", ".", $desc);
        return $desc;
    }
    private function get_media_metadata_from_json($filename, $title)
    {
        $json = file_get_contents($filename);
        $json = Functions::delete_all_between("<nowiki>", "<\/nowiki>", $json); //to fix this: https://commons.wikimedia.org/wiki/File:Abies_grandis_needles.jpg DATA-1798
        $json = self::clean_html($json);
        $dump_arr = json_decode($json, true);
        $rek = array();
        $rek['pageid'] = $dump_arr['id'];
        if(in_array($rek['pageid'], $this->excluded_pageids)) return false;
        
        /* debug mode
        if($rek['pageid'] == "9163872") { //9163872 10584787 36373984
            print_r($dump_arr); exit("\n-stop-\n");
        }
        */
        $rek['timestamp'] = $dump_arr['revision']['timestamp'];
        $wiki = $dump_arr['revision']['text'];
        if(self::wiki_protected($wiki)) return "protected";
        //================================================================ ImageDescription
        if($rek['ImageDescription'] = self::convert_wiki_2_html($wiki)) {
            // print("\n".$rek['ImageDescription']."\n\n");
            $rek['ImageDescription'] = self::un_tabular_the_description($rek['ImageDescription']);
            // exit("\n".$rek['ImageDescription']."\nelix2\n");
        }
        else return false;
        //================================================================ LicenseShortName
        // == {{int:license-header}} ==
        // {{Flickr-no known copyright restrictions}}
        if(preg_match("/== \{\{int:license-header\}\} ==(.*?)\}\}/ims", $wiki, $a) ||
           preg_match("/==\{\{int:license-header\}\}==(.*?)\}\}/ims", $wiki, $a)) {
            $tmp = trim(str_replace("{", "", $a[1]));
            $rek['LicenseShortName'] = $tmp;
        }
        // else echo "\n----111----\n"; //means no license
        //================================================================ LicenseUrl
        //  -- http://creativecommons.org/licenses/by-sa/3.0 
        if(preg_match("/http:\/\/creativecommons.org\/licenses\/(.*?)\"/ims", $rek['ImageDescription'], $a)) {
            $rek['LicenseUrl'] = "http://creativecommons.org/licenses/" . $a[1];
        }
        elseif(stripos($rek['ImageDescription'], "licensed with PD-self") !== false) $rek['LicenseUrl'] = $this->license['public domain']; //string is found
        // else echo "\n----222----\n"; //means no license
        //================================================================ title
        if($rek['title'] = self::get_title_from_ImageDescription($rek['ImageDescription'])) {}
        else $rek['title'] = str_replace("_", " ", $title);

        //================================================================ other metadata
        /*
        |date=1841
        |author=Schlegel, H. (Hermann), 1804-1884
        |source=https://www.flickr.com/photos/internetarchivebookimages/16095238834/
        |permission={{User:Fæ/Flickr API}}
        */
        
        $wiki = str_ireplace(array("author=|", "author= |", "Author = |"), "", $wiki);
        $wiki = str_ireplace(array("photographer=|", "photographer= |", "photographer = |"), "", $wiki);
        $wiki = str_ireplace(array("artist=|", "artist= |", "artist = |"), "", $wiki);
        $wiki = str_ireplace(array("date=|", "date= |", "date = |"), "", $wiki);
        $wiki = str_ireplace(array("author = |", "author= |", "author = |"), "", $wiki);
        
        if(preg_match("/\|date\=(.*?)\\\n/ims", $wiki, $a)) $rek['other']['date'] = trim($a[1]);
        else {
            $temp = Functions::remove_whitespace($wiki);
            if(preg_match("/\|date \=(.*?)\\\n/ims", $temp, $a)) $rek['other']['date'] = trim($a[1]);
            elseif(preg_match("/\| date \=(.*?)\\\n/ims", $temp, $a)) $rek['other']['date'] = trim($a[1]);
        }
        if(preg_match("/\|author\=(.*?)\\\n/ims", $wiki, $a)) $rek['other']['author'] = trim($a[1]);
        else {
            $temp = Functions::remove_whitespace($wiki);
            $temp = str_replace("\t", "", $temp);
            if    (preg_match("/\|author \=(.*?)\\\n/ims", $temp, $a)) $rek['other']['author'] = trim($a[1]);
            elseif(preg_match("/\| author \=(.*?)\\\n/ims", $temp, $a)) $rek['other']['author'] = trim($a[1]);
            elseif(preg_match("/\|author\=(.*?)\\\n/ims", $temp, $a)) $rek['other']['author'] = trim($a[1]);

            elseif(preg_match("/\|photographer \=(.*?)\\\n/ims", $temp, $a)) $rek['other']['author'] = trim($a[1]);
            elseif(preg_match("/\| photographer \=(.*?)\\\n/ims", $temp, $a)) $rek['other']['author'] = trim($a[1]);
            elseif(preg_match("/\|photographer\=(.*?)\\\n/ims", $temp, $a)) $rek['other']['author'] = trim($a[1]);
            
            elseif(preg_match("/\|artist \=(.*?)\\\n/ims", $temp, $a)) $rek['other']['author'] = trim($a[1]);
            elseif(preg_match("/\| artist \=(.*?)\\\n/ims", $temp, $a)) $rek['other']['author'] = trim($a[1]);
            elseif(preg_match("/\|artist\=(.*?)\\\n/ims", $temp, $a)) $rek['other']['author'] = trim($a[1]);

            elseif(preg_match("/Photo by (.*?)\\\n/ims", $temp, $a)) $rek['other']['author'] = trim($a[1]);
            elseif(preg_match("/ Photo by (.*?)\\\n/ims", $temp, $a)) $rek['other']['author'] = trim($a[1]);

            // else exit("\n$temp\nelix\n");
            /*
            |Author		= [http://www.flickr.com/people/46788399@N00 Gilles Gonthier]
            |Author= [http://www.flickr.com/people/46788399@N00 Gilles Gonthier]
            |photographer       = [[flickruser:29797746@N08|Dave & Margie Hill / Kleerup]]
            */
        }
        
        /* special case for: https://commons.wikimedia.org/wiki/File:Age-Spatial-and-Temporal-Variations-in-Hospital-Admissions-with-Malaria-in-Kilifi-County-Kenya-A-25-pmed.1002047.s013.ogv
        | doi = 10.1371/journal.pmed.1002047
        | journal = PLOS Medicine
        */
        if(preg_match("/ doi \= (.*?)\\\n/ims", $wiki, $a)) $rek['other']['doi'] = trim($a[1]);
        if(preg_match("/ journal \= (.*?)\\\n/ims", $wiki, $a)) $rek['other']['journal'] = trim($a[1]);
        
        if($val = @$rek['other']['author']) $rek['other']['author'] = Functions::delete_all_between('<!--', '-->', $val);
        
        if(preg_match("/\|source\=(.*?)\\\n/ims", $wiki, $a)) $rek['other']['source'] = trim($a[1]);
        else {
            //start new Nov 6
            $temp = Functions::remove_whitespace($wiki);
            if(preg_match("/\|source \=(.*?)\\\n/ims", $temp, $a)) $rek['other']['source'] = trim($a[1]);
            elseif(preg_match("/\| source \=(.*?)\\\n/ims", $temp, $a)) $rek['other']['source'] = trim($a[1]);
            //end new Nov 6
        }
        if(stripos(@$rek['other']['source'], "own work;") !== false) {  //new Mar 3, 2019
            // exit("\n".$rek['other']['source']."\n");
            $rek['other']['source'] = "";
        }
        if(preg_match("/\|permission\=(.*?)\\\n/ims", $wiki, $a)) $rek['other']['permission'] = $a[1];
        $rek['date'] = @$rek['other']['date'];
        //================================================================ Artist
        $rek['Artist'] = array();
        //start new Nov 6, 2018 e.g. https://commons.wikimedia.org/wiki/File:Clone_war_of_sea_anemones_3.jpg
        if($other_author = trim(@$rek['other']['author'])) {
            // exit("\nother_author: [$other_author]\n");
            if($val = self::make_other_author_an_agent($other_author, @$rek['other']['source'])) $rek['Artist'][] = $val;
        }
        //end new

        //start new Feb 26, 2019 e.g. https://commons.wikimedia.org/wiki/File:Narcissus_assoanus_distrib.jpg
        if($other_source = trim(@$rek['other']['source'])) {
            // exit("\nother_source: [$other_source]\n");
            if($val = self::make_other_source_an_agent($other_source, $rek['other'])) $rek['Artist'][] = $val;
        }
        //end new

        if(!$rek['Artist']) { //became the 1st option. Before was just the 2nd option
            // echo "\nelix went here aaa\n";
            if($val = self::second_option_for_artist_info($dump_arr)) $rek['Artist'][] = $val; //here you'll get like "JarektBot" OR "YaCBot"
        }

        /* orig - commented now. better to just have additional artists here rather than limit to just 1 artist.
        if(!$rek['Artist']) {
            $rek['Artist'] = self::get_artist_from_ImageDescription($rek['ImageDescription']); //get_media_metadata_from_json()
        }
        */
        if($val = self::get_artist_from_ImageDescription($rek['ImageDescription'], $rek['Artist'])) {
            if(isset($val[0])) $rek['Artist'] = array_merge($rek['Artist'], $val);
            else               $rek['Artist'][] = $val;
        }
        if($LicenseShortName = @$rek['LicenseShortName']) {
            if($val = self::get_artist_from_LicenseShortName($LicenseShortName)) $rek['Artist'][] = $val;
        }

        if(!$rek['Artist']) {
            // echo "\nelix went here ccc\n";
            if($val = self::get_artist_from_special_source($wiki, '')) $rek['Artist'][] = $val; //get_media_metadata_from_json()
        }
        // parse this value = "[http://www.panoramio.com/user/6099584?with_photo_id=56065015 Greg N]"
        /*MOVED UP
        //start new Feb 26, 2019 e.g. https://commons.wikimedia.org/wiki/File:Narcissus_assoanus_distrib.jpg
        if($other_source = trim(@$rek['other']['source'])) {
            // exit("\nother_source: [$other_source]\n");
            if($val = self::make_other_source_an_agent($other_source)) $rek['Artist'][] = $val;
        }
        //end new
        */
        // /* ================================ new Oct 7, 2017 -- comment it first...
        if(!$rek['Artist']) $rek['Artist'] = "";
        if(is_array($rek['Artist'])) {
            // echo "\nartist is ARRAY()"; print_r($rek['Artist']); //debug only
        }
        else {
            debug("\nartist is STRING: [".$rek['Artist']."]\n");
            /* //new first option
                [revision] => Array(
                        [id] => 178748754
                        [parentid] => 139462069
                        [timestamp] => 2015-11-10T22:44:04Z
                        [contributor] => Array(
                                [username] => Mariomassone
                                [id] => 412814
            */
            // new Nov 5, 2018. Initially this wasn't the 1st option.
            //possible values --> "[[User:Victuallers]]" "[[User:Tomascastelazo|Tomas Castelazo]]" "*Original: [[User:Chiswick Chap|Chiswick Chap]]"
            if(stripos($rek['Artist'], "[[User:") !== false && stripos($rek['Artist'], "]]") !== false) { //string is found //e.g. *Original: [[User:Chiswick Chap|Chiswick Chap]]
                debug("\nartist value is: ".$rek['Artist']."\n");
                if(preg_match_all("/\[\[(.*?)\]\]/ims", $rek['Artist'], $a)) {
                    unset($rek['Artist']);
                    foreach($a[1] as $t) {
                        $tmp_arr = explode("|", $t); //"[[User:Tomascastelazo|Tomas Castelazo]]" "*Original: [[User:Chiswick Chap|Chiswick Chap]]"
                        if($name = @$tmp_arr[1]) $rek['Artist'][] = array('name' => $name, 'homepage' => "https://commons.wikimedia.org/wiki/".$tmp_arr[0], 'role' => 'creator');
                        else { //"[[User:Victuallers]]"
                            $user = str_ireplace("User:", "", $t);
                            $rek['Artist'][] = array('name' => $user, 'homepage' => "https://commons.wikimedia.org/wiki/User:".$user, 'role' => 'creator');
                        }
                    }
                }
            }
            elseif($val = @$dump_arr['revision']['contributor']['username']) {
                unset($rek['Artist']);
                $rek['Artist'][] = array('name' => $val, 'homepage' => "https://commons.wikimedia.org/wiki/User:".$val, 'role' => 'editor');
            }
            elseif(substr($rek['Artist'],0,5) == "[http") { //[https://sites.google.com/site/thebrockeninglory/ Brocken Inaglory]
                $tmp_arr = explode(" ", $rek['Artist']);
                unset($rek['Artist']);
                $temp = array();
                $temp['homepage'] = trim($tmp_arr[0]);

                $tmp_arr[0] = null;
                $tmp_arr = array_filter($tmp_arr);
                $temp['name'] = implode(" ", $tmp_arr);

                // remove "[" "]"
                $temp['name'] = str_replace(array("[","]"), "", $temp['name']);
                $temp['homepage'] = str_replace(array("[","]"), "", $temp['homepage']);

                //start special
                if(!$temp['name'] && $temp['homepage'] == "https://www.flickr.com/photos/hdport/") $temp['name'] = "Hunter Desportes";
                //end special
                
                if($temp['name']) $rek['Artist'][] = $temp;
                
                //start another special 
                /*[other] => Array (
                         [date] => 2009-03-13
                         [author] => [https://www.flickr.com/photos/sempivirens/]
                         [source] => [https://www.flickr.com/photos/sempivirens/3355235281]
                         [permission] => {{User:FlickreviewR/reviewed-pass-change|Sequoia Hughes|http://flickr.com/photos/29225241@N04/3355235281|2015-01-23 19:50:34|cc-by-2.0|cc-by-sa-2.0}}
                computed homepage is "https://www.flickr.com/photos/sempivirens/" but blank name */
                // print_r($rek['other']); exit;
                if(preg_match("/User\:(.*?)\//ims", @$rek['other']['permission'], $a)) {
                    $rek['Artist'][] = array('name' => $a[1], 'homepage' => "https://commons.wikimedia.org/wiki/User:".$a[1], 'role' => 'source');
                }
                //end another special
                
                //start another special 
                /* [LicenseShortName] => User:FlickreviewR/reviewed-pass|Jon David Nelson|https://flickr.com/photos/65771669@N07/15115751721|2015-12-01 12:50:33|cc-by-2.0| */
                if(preg_match("/User\:(.*?)\//ims", @$rek['LicenseShortName'], $a)) {
                    $rek['Artist'][] = array('name' => $a[1], 'homepage' => "https://commons.wikimedia.org/wiki/User:".$a[1], 'role' => 'source');
                }
                //end another special 
            }
            /* this is covered in elseif() below this
            elseif(substr($rek['Artist'],0,7) == "[[User:") //[[User:Tomascastelazo|Tomas Castelazo]]
            {
                $temp = str_replace(array("[","]"), "", $rek['Artist']);
                $tmp_arr = explode("|", $temp);
                unset($rek['Artist']);
                if($name = @$tmp_arr[1]) $rek['Artist'][] = array('name' => $name, 'homepage' => "https://commons.wikimedia.org/wiki/".$tmp_arr[0]);
            }
            */
            else {
                $name = $rek['Artist'];
                unset($rek['Artist']);
                $rek['Artist'][] = array('name' => $name);
            }
            // else exit("\nInvestiage this artist string\n");
            
            if(is_array($rek['Artist'])) {
                // echo "\nartist is now also ARRAY()\n"; print_r($rek['Artist']);
            }
            else echo "\nSTILL not an array...investigate...\n";
        }
        // ================================ */
        //================================================================ END
        $rek['eol_type'] = self::check_if_dump_image_is_map($dump_arr['revision']['text']);
        $rek['fromx'] = 'dump';
        /* good debug for Artist using dump
        if($rek['pageid'] == "pageid") {
            echo "\n=================investigate dump data===========start\n";
            print_r($dump_arr); print_r($rek);
            echo "\n=================investigate dump data===========end\n";
        }
        */
        return $rek;
    }
    private function make_other_author_an_agent($other_author, $other_source)
    {
        if($other_author == "[[user:]]") return;
        /* e.g. orig wiki value = {{Creator:Marten de Vos}} */
        /* there is a better way to proceed here ---> convert wiki to html then parse, see below
        if(substr($other_author,0,2) == "{{") {
            $name = str_ireplace(array("{", "}", "Creator:"), "", $other_author);
            return array('name' => $name, 'role' => 'creator');
        }
        */
        // $other_author = "{{Creator:Marten de Vos}}"; //force assign
        if(preg_match("/\{\{Creator\:(.*?)\}\}/ims", $other_author, $a)) {
            $creator_name = $a[1];
            $final = array('name' => $creator_name, 'role' => 'creator');
            $html = self::convert_wiki_2_html($other_author);
            if(preg_match("/href=\"(.*?)\"/ims", $html, $a)) $final['homepage'] = $a[1];
            // print_r($final);
            return $final;
        }
        
        if(substr($other_author,0,5) == "[http") {
            /* e.g. $other_author orig value, which is a wiki:
            [https://sites.google.com/site/thebrockeninglory/ Brocken Inaglory]|Cc-by-sa-3.0,2.5,2.0,1.0|GFDL|migration=redundant}}
            e.g. html value is:
            <a rel="nofollow" href="https://sites.google.com/site/thebrockeninglory/">Brocken Inaglory</a>|Cc-by-sa-3.0,2.5,2.0,1.0|GFDL|migration=redundant}}
            */
            $html = self::convert_wiki_2_html($other_author);
            $final = array();
            if(preg_match("/>(.*?)<\/a>/ims", $html, $a)) $final['name'] = $a[1];
            else                                          $final['name'] = str_replace(array("[","]"), "", $other_author); /* orig wiki value = [Jenny (JennyHuang)] */
            if(preg_match("/href=\"(.*?)\"/ims", $html, $a)) $final['homepage'] = $a[1];
            if(@$final['name']) {
                $final['role'] = 'creator'; //creator xxx
                return $final;
            }
        }
        elseif(in_array(strtolower($other_author), array('{{unknown photographer}}', '{{author|unknown}}', '{{unknown|author}}'))) return;
        else { //other cases; may still sub-divide this to different cases when needed
            /* e.g. orig wiki https://commons.wikimedia.org/wiki/File:Narcissus_assoanus_distrib.jpg
            Cillas;[[:File:España_y_Portugal.jpg|España_y_Portugal.jpg]]: Jacques Descloitres, MODIS Rapid Response Team, NASA/GSFC
            converted to html:
            Cillas;<a href="https://commons.wikimedia.org/wiki/File:Espa%C3%B1a_y_Portugal.jpg" title="File:España y Portugal.jpg">España_y_Portugal.jpg</a>: Jacques Descloitres, MODIS Rapid Response Team, NASA/GSFC
            */
            $html = self::convert_wiki_2_html($other_author);
            $final = array();
            if($val = strip_tags($html)) $final['name'] = $val;
            //----------------------
            $final['name'] = self::format_name_special_cases(@$final['name']);
            //----------------------
            if(preg_match("/href=\"(.*?)\"/ims", $html, $a)) $final['homepage'] = $a[1];
            
            //start here ---------- you can add here other ways to customize 'name' 'homepage' etc. for different sources e.g. Vimeo Flickr etc.
            if($val = self::get_vimeo_user_homepage($other_source)) $final['homepage'] = $val; //new Mar 4, 2019
            //end here ----------
            
            if(@$final['name']) {
                $final['role'] = 'creator'; //creator yyy
                return $final;
            }
        }
    }
    private function get_vimeo_user_homepage($url) //e.g. media file = Sea_spider_(Pantopoda_or_pycnogonids).webm
    {
        if(substr($url,0,18) == "https://vimeo.com/" && is_numeric(substr($url, -3))) {
            $options = $this->download_options;
            $options['expire_seconds'] = false; //should always be false
            if($html = Functions::lookup_with_cache($url, $options)) {
                //e.g. "Person","name":"Denise King"
                if(preg_match("/\"Person\"\,\"name\"\:\"(.*?)\"/ims", $html, $a)) {
                    if($name = trim($a[1])) {
                        if(preg_match("/\"".$name."\"\,\"url\"\:\"(.*?)\"/ims", $html, $a)) {
                            if($val = $a[1]) return $val; //will return "https://vimeo.com/growthanddk"
                        }
                    }
                }
            }
        }
        else return false;
    }
    private function format_name_special_cases($name)
    {   /* e.g. value "T.Müller}}" */
        if(stripos($name, "{") !== false) { //string is found
            if(stripos($name, "}") === false) return str_replace("{","",$name); //string is not found
        }
        if(stripos($name, "}") !== false) { //string is found
            if(stripos($name, "{") === false) return str_replace("}","",$name); //string is not found
        }
        return $name;
    }
    private function make_other_source_an_agent($other_source, $rek_other = array())
    {   /* e.g. $other_source "Se ha trabajado con datos propios sobre la imagen existente en Commons: [[:File:España_y_Portugal.jpg|España_y_Portugal.jpg]]" */
        if($other_source == "{{own}}") return;
        if($other_source == "{{Own}}") return;
        if(stripos($other_source, "Flickr") !== false) { //string is found
            //Flickr routine 1
            if($html = trim(self::convert_wiki_2_html($other_source))) {
                // exit("\n[$html]\n");
                $final = array('name' => $other_source, 'role' => 'source');
                if(preg_match("/href=\"(.*?)\"/ims", $html, $a)) {
                    $final['homepage'] = $a[1];
                    // $user_id = "64684201@N00"; or 'conifer'. Latter will give you "Flickr user ID conifer".
                    if($user_id = Functions::get_Flickr_user_id_from_url($final['homepage'])) { //e.g. param "http://flickr.com/photos/64684201@N00/291506502/"
                        // echo("\n[$user_id]\n"); exit;
                        $options = $this->download_options;  $options['expire_seconds'] = false;
                        $final['name'] = self::get_Flickr_user_realname_using_userID($user_id, $options);
                        if($final['name']) return $final;
                    }
                    elseif(preg_match("/flickr\.com\/photos\/(.*?)image description page/ims", $other_source, $a)) {
                        /* orig wiki is e.g. "[http://www.flickr.com/ Flickr.com] - [http://flickr.com/photos/22437367@N00/1102771 image description page]" 
                            OR e.g.          "[https://www.flickr.com/ Flickr.com] - [https://www.flickr.com/photos/35237093637@N01/1718209 image description page]"
                        */
                        $part = trim($a[1]);
                        $tmp = explode("/", $part);
                        if($flickr_image_id = trim(@$tmp[1])) {
                            return array('name' => "Flickr image ID $flickr_image_id", 'role' => 'source', 'homepage' => "https://flickr.com/photos/".$part);
                        }
                    }
                }
            }
        }
        else {
            if($html = trim(self::convert_wiki_2_html($other_source))) {
                $tmp = trim(strip_tags($html));
                if(strlen($tmp) > 300) {
                    if(strlen($other_source) < strlen($tmp)) $tmp = $other_source; //use wiki text, which is shorter.
                }
                //--------------
                $final = self::special_cases_4source_strings($tmp, $rek_other);
                // print_r($final); exit;
                //--------------
                $final['role'] = 'source';
                if(!@$final['homepage']) {
                    if(preg_match("/href=\"(.*?)\"/ims", $html, $a)) $final['homepage'] = $a[1];
                }
                return $final;
            }
        }
    }
    private function special_cases_4source_strings($name, $rek_other)
    {   /* $name value is e.g. "S1 Video from {{Cite journal" */
        if(substr($name, -14) == "{{Cite journal") { //special case for: https://commons.wikimedia.org/wiki/File:Age-Spatial-and-Temporal-Variations-in-Hospital-Admissions-with-Malaria-in-Kilifi-County-Kenya-A-25-pmed.1002047.s013.ogv
            $final = str_ireplace("{{Cite journal", "", $name);
            if($val = @$rek_other['journal']) $final .= "journal ($val). ";
            if($val = @$rek_other['doi']) $final .= "DOI ($val). ";
            $ret['name'] = trim($final);
            return $ret;
        }
        //-------------------------------------------------------------------------------------------------------
        /* e.g. "https://vimeo.com/136560584" --- $name value is a URL form Vimeo */
        if(substr($name,0,18) == "https://vimeo.com/" && is_numeric(substr($name, -3))) {
            if(preg_match("/vimeo\.com\/(.*?)xxx/ims", $name."xxx", $a)) $final['name'] = "Vimeo video ".$a[1];
            $final['homepage'] = $name;
            return $final;
        }
        //-------------------------------------------------------------------------------------------------------
        return array('name' => $name);
    }
    private function second_option_for_artist_info($arr)
    {   /*(
            [title] => File:Brassica oleracea2.jpg
            [ns] => 6
            [id] => 56480
            [revision] => Array(
                    [id] => 141570217
                    [parentid] => 26626799
                    [timestamp] => 2014-12-06T05:29:10Z
                    [contributor] => Array(
                            [username] => JarektBot
                            [id] => 472310
                        )*/
        if($val = @$arr['revision']['contributor']['username']) {
            $a['name'] = $val;
            $a['homepage'] = "https://commons.wikimedia.org/wiki/User:$val";
            $a['role'] = "editor";
            return $a;
        }
        elseif($val = @$arr['revision']['text']) {
            if(stripos($val, "{{Wellcome Images}}") !== false) { //string is found
                return array('name' => "Wellcome Images", 'homepage' => "https://wellcomeimages.org/", 'role' => 'creator');
            }
        }
        return false;
    }
    private function get_artist_from_LicenseShortName($str) //e.g. [LicenseShortName] => PD-self|author=I, [[User:Takahashi|Takahashi]]
    {
        if(preg_match("/author\=I\, \[\[User\:(.*?)\|/ims", $str, $a)) {
            $final['name'] = $a[1];
            $final['homepage'] = "https://commons.wikimedia.org/wiki/User:".$final['name'];
            $final['role'] = 'creator';
            return $final;
        }
    }
    private function get_artist_from_ImageDescription($description, $rek_Artist = array())
    {
        $description = str_ireplace(array("Source: my own file.", "Author: This file is lacking author information."), "", $description);
        // <td lang="en">Author</td> 
        // <td><a href="https://commons.wikimedia.org/wiki/User:Sardaka" title="User:Sardaka">Sardaka</a></td> 
        if(preg_match("/>Author<\/td>(.*?)<\/td>/ims", $description, $a)) { // echo "\nelix 111\n";
            $temp = $a[1];
            $final = array(); $atemp = array();
            if(preg_match("/href=\"(.*?)\"/ims", $temp, $a)) $atemp['homepage'] = trim($a[1]);
            if(preg_match("/\">(.*?)<\/a>/ims", $temp, $a)) $atemp['name'] = trim($a[1]);
            if(@$atemp['name']) {
                $atemp['role'] = 'creator';
                $final[] = $atemp;
                return $final;
            }
            else {
                // echo "\nelix 222\n";
                // <td lang="en">Author</td>
                // <td>Museo Nacional de Chile.</td>
                // echo("\n[@$a[1]]\n");
                if($name = trim(strip_tags($temp))) {
                    $final[] = array('name' => $name, 'role' => 'creator');
                    return $final;
                }
                // else echo "\nelix 333\n";
            }
        }
        /* heavy so commented first, will see how the preview goes in V3 and get back to this
        elseif(stripos($description, "{{Wellcome Images}}") !== false) { //string is found
            return array('name' => "Wellcome Images", 'homepage' => "https://wellcomeimages.org/", 'role' => 'creator');
        }
        */
        elseif(preg_match("/Photographer<\/td>(.*?)<\/td>/ims", $description, $a)) { //<td >Photographer</td> <td>Hans Hillewaert</td>
            // echo "\nelix 333 333\n";
            $temp = $a[1];
            $final = array(); $atemp = array();
            if(preg_match("/href=\"(.*?)\"/ims", $temp, $a)) $atemp['homepage'] = trim($a[1]);
            $atemp['name'] = strip_tags(trim($temp)); //format_artist
            if(@$atemp['name']) {
                $atemp['role'] = 'creator';
                $final[] = $atemp;
                return $final;
            }
        }
        elseif(preg_match("/Photographer:(.*?)\./ims", $description, $a)) { /*Photographer: Hans Hillewaert.*/
            if($val = strip_tags(trim($a[1]))) return array('name' => $val, 'role' => 'photographer');
            else { //e.g. Photographer: <a href="https://commons.wikimedia.org/wiki/User:Biopics" title="User:Biopics">Hans Hillewaert</a>.
                $d = strip_tags($description);
                if(preg_match("/Photographer:(.*?)\./ims", $d, $a)) {
                    $tmp = strip_tags(trim($a[1]));
                    
                    // /* case where $a[1] is equal to:
                    // Walter Siegmund ©2006 Walter Siegmund Licensing I, the copyright holder of this work, hereby publish it under the following licenses: : Permission is granted to copy, distribute and/or modify this document under the terms of the GNU Free Documentation License, Version 1 
                    if(preg_match("/xxx(.*?)Licensing/ims", "xxx".$tmp, $a)) $tmp = $a[1];
                    // */
                    
                    if($val = strip_tags(trim($tmp))) return array('name' => $val, 'role' => 'photographer');
                }
            }
        }
        elseif(preg_match("/Author:(.*?)\./ims", $description, $a)) { /*Author: Kurt Stüber <a rel="nofollow" href="http://www.kurtstueber.de/">[1]</a>.*/
            if($val = trim(strip_tags($a[1]))) {
                if($val == "[[user:]]") return array();
                $other_source = $a[1]; //main assignment
                if(stripos($other_source, "Flickr") !== false) { //string is found
                    //Flickr routine 2
                    if($html = trim(self::convert_wiki_2_html($other_source))) {
                        $final = array('name' => $other_source, 'role' => 'creator');
                        if(preg_match("/href=\"(.*?)\"/ims", $html, $a)) {
                            $final['homepage'] = $a[1];
                            // $user_id = "64684201@N00";
                            if($user_id = Functions::get_Flickr_user_id_from_url($final['homepage'])) {//e.g. param "http://flickr.com/photos/64684201@N00/291506502/"
                                $options = $this->download_options;  $options['expire_seconds'] = false;
                                // if(stripos($user_id, "@N") !== false)
                                $final['name'] = self::get_Flickr_user_realname_using_userID($user_id, $options);
                                if($final['name']) return $final;
                            }
                        }
                    }
                }
                else {
                    if(!$rek_Artist) return array('name' => $val, 'role' => 'creator'); //creator 1 --- suppressed 2nd-class agents
                }
            }
            else {
                $d = strip_tags($description);
                if(preg_match("/Author:(.*?)\./ims", $d, $a)) {
                    if($val = strip_tags(trim($a[1])))  {
                        if(!$rek_Artist) return array('name' => $val, 'role' => 'creator'); //creator 2 --- suppressed 2nd-class agents
                    }
                }
            }
        }

        //added Feb 25, 2019
        elseif(preg_match("/Artist:(.*?)\./ims", $description, $a)) { /*Artist: <a href="https://en.wikipedia.org/wiki/Alexandre_Jacovleff" title="en:Alexandre Jacovleff">Alexandre Jacovleff</a> (1887–1938) <a href="https://commons.wikimedia.org/wiki/Creator:Alexander_Yakovlev" title="Link back to Creator infobox template"></a> <a href="https://www.wikidata.org/wiki/Q593879" title="wikidata:Q593879"></a>. <a href="https://commons.wikimedia.org/wiki/File:Alexandr_Yakovlev_(self-portrait,_1917,_GTG).jpg" ></a>.*/
            if($val = strip_tags(trim($a[1]))) return array('name' => $val, 'role' => 'creator');
            else {
                $d = strip_tags($description);
                if(preg_match("/Artist:(.*?)\./ims", $d, $a)) {
                    if($val = strip_tags(trim($a[1]))) return array('name' => $val, 'role' => 'creator');
                }
            }
        }

        //added Feb 11, 2019
        elseif(preg_match("/Source:(.*?)\./ims", $description, $a)) { /*Source: <a href="https://en.wikipedia.org/wiki/Walters_Art_Museum" title="en:Walters Art Museum">Walters Art Museum</a>: <a href="http://thewalters.org/" rel="nofollow"></a> <a rel="nofollow" href="http://thewalters.org/">Home page</a> <a href="http://art.thewalters.org/detail/37360" rel="nofollow"></a> <a rel="nofollow" href="http://art.thewalters.org/detail/37360">Info about artwork</a>.*/
            $stripped = strip_tags(trim($a[1]));
            if($val = self::is_valid_agent_name($stripped)) return array('name' => $val, 'role' => 'source');
            else {
                $d = strip_tags($description);
                if(preg_match("/Source:(.*?)\./ims", $d, $a)) {
                    if($val = strip_tags(trim($a[1]))) {
                        $tmp = explode(":", $val);
                        if($val = self::is_valid_agent_name(trim($tmp[0]))) return array('name' => $val, 'role' => 'source');
                    }
                }
            }
        }
        else {
            // wiki/User:Bewareofdog" title="en:User:Bewareofdog"
            if(preg_match("/wiki\/User\:(.*?)\"/ims", $description, $a) && !$rek_Artist) { // 2nd condition means that there is already $rek['Artist'], not priority to get from "User:"
                $final[] = array('name' => $a[1], 'homepage' => "https://commons.wikimedia.org/wiki/User:".$a[1], 'role' => 'creator');
                // print_r($final); exit("\n$description\n");
                return $final;
            }
            elseif(preg_match("/Fotograf oder Zeichner\:(.*?)Lizenzstatus/ims", $description, $a)) { //Fotograf oder Zeichner: Goldlocki Lizenzstatus:
                if($val = trim($a[1])) {
                    $final[] = array('name' => $val, 'role' => 'creator');
                    return $final;
                }
            }
            elseif(stripos($description, "Category:Wikigraphists") !== false) { //string is found
                return array('name' => "Wikigraphists", 'homepage' => "https://en.wikipedia.org/wiki/Wikipedia:Graphics_Lab", 'role' => 'creator');
            }
            elseif(stripos($description, "Medicago italica") !== false) { //string is found
                return array('name' => "Medicago italica", 'homepage' => "", 'role' => 'source');
            }
        }
        return false;
    }
    private function is_valid_agent_name($possible_name)
    {
        if(!$possible_name) return false;
        if(stripos($possible_name, "own work") !== false) return false; //string is found
        else return $possible_name;
    }
    private function remove_portions_of_wiki($wiki)
    {
        // =={{Assessment}}==
        $wiki = str_ireplace("=={{Assessment}}==", "", $wiki);
        //{{Assessment }}
        if(preg_match("/\{\{Assessment(.*?)\}\}/ims", $wiki, $a)) $wiki = str_ireplace("{{Assessment" . $a[1] . "}}", "", $wiki);
        // {{User:FlickreviewR }}
        if(preg_match("/\{\{User:FlickreviewR(.*?)\}\}/ims", $wiki, $a)) $wiki = str_ireplace("{{User:FlickreviewR" . $a[1] . "}}", "", $wiki);
        // {{Check categories }}
        if(preg_match("/\{\{Check categories(.*?)\}\}/ims", $wiki, $a)) $wiki = str_ireplace("{{Check categories" . $a[1] . "}}", "", $wiki);
        //===Versions:===
        $wiki = str_ireplace("===Versions:===", "", $wiki);
        /*
        $wiki = str_ireplace("== {{int:license-header}} ==", "", $wiki);
        $wiki = str_ireplace("{{self|cc-by-sa-3.0}}", "", $wiki);
        */
        $wiki = str_ireplace("{{gardenology}}", "", $wiki); //e.g. Gardenology.org-IMG_2825_rbgs11jan.jpg
        return $wiki;
    }
    private function convert_wiki_2_html($wiki)
    {
        $url = "https://www.mediawiki.org/w/api.php?action=parse&contentmodel=wikitext&format=json&text=";
        $url = "https://commons.wikimedia.org/w/api.php?action=parse&contentmodel=wikitext&format=json&text="; //much better API version
        $wiki = self::remove_portions_of_wiki($wiki);
        $count = strlen($wiki);
        debug("\ncount = [$count]\n");
        if($count >= 2995) { //2995 //4054 //6783
            $this->count['greater_equal_2995']++;
            return false;
        }
        else $this->count['less_than_2995']++;
        
        $options = $this->download_options;
        $options['expire_seconds'] = false; //always false bec. you're just converting wiki to html
        if($json = Functions::lookup_with_cache($url.urlencode($wiki), $options)) {
            $arr = json_decode($json, true);
            // echo "\n==========\n";
            // print_r($arr);
            $html = $arr['parse']['text']['*'];
            if(preg_match("/elix(.*?)<!--/ims", "elix".$html, $a)) {
                $html = trim($a[1]);
                $html = str_ireplace('href="//', 'href="http://', $html);
                $html = str_ireplace('href="/', 'href="https://commons.wikimedia.org/', $html);
                $html = self::format_wiki_substr($html);
                $html = str_ireplace("&nbsp;", " ", $html);
                $html = Functions::remove_whitespace($html);
                /*
                //double Template:Information field -> not needed when using the commons.wikimedia.org API
                $temp = '<a href="https://commons.wikimedia.org/w/index.php?title=Template:Information_field&action=edit&redlink=1" class="new" title="Template:Information field (page does not exist)">Template:Information field</a>';
                $html = str_ireplace($temp.$temp, $temp, $html);
                */
                //remove style
                if(preg_match_all("/style=\"(.*?)\"/ims", $html, $a)) {
                    foreach($a[1] as $style) $html = str_ireplace('style="'.$style.'"', "", $html);
                }
                //others
                $html = str_ireplace(" (page does not exist)", "", $html);
                /*
                //Template removal when using API mediawiki.org -> not needed when using the commons.wikimedia.org API
                $html = str_ireplace('<a href="https://commons.wikimedia.org/w/index.php?title=Template:Date-time_separator&action=edit&redlink=1" class="new" title="Template:Date-time separator">Template:Date-time separator</a>', "", $html);
                $html = str_ireplace('<a href="https://commons.wikimedia.org/w/index.php?title=Template:Formatting_error&action=edit&redlink=1" class="new" title="Template:Formatting error">Template:Formatting error</a>', "", $html);
                $html = str_ireplace('<a href="https://commons.wikimedia.org/w/index.php?title=Template:Own&action=edit&redlink=1" class="new" title="Template:Own">Template:Own</a>', "Own work", $html);
                $arr = array("Self", "Location dec", "Geograph");
                foreach($arr as $t) $html = str_ireplace('<a href="https://commons.wikimedia.org/w/index.php?title=Template:'.str_replace(" ", "_", $t).'&action=edit&redlink=1" class="new" title="Template:'.$t.'">Template:'.$t.'</a>', "", $html);
                */
                $html = strip_tags($html, "<table><tr><td><br><a><i>"); //strip_tags
                $html = str_ireplace("([//www.mediawiki.org/w/index.php?title=API&action=purge# purge])", "", $html);
                $html = Functions::remove_whitespace($html);
                $html = str_ireplace('[<a href="https://commons.wikimedia.org/w/index.php?title=API&action=edit&section=1" class="mw-redirect" title="Edit section: Summary">edit</a>]', "", $html);
                $html = str_ireplace('[<a href="https://commons.wikimedia.org/w/index.php?title=API&action=edit&section=2" class="mw-redirect" title="Edit section: Licensing">edit</a>]', "", $html);
                $arr = array("class", "id");
                foreach($arr as $attrib) { //remove class="" id=""
                    if(preg_match_all("/$attrib=\"(.*?)\"/ims", $html, $a)) {
                        foreach($a[1] as $style) $html = str_ireplace($attrib.'="'.$style.'"', "", $html);
                    }
                }
                $html = str_ireplace("<tr >", "<tr>", $html);
                $html = str_ireplace("<td >", "<td>", $html);
                //remove 2 rows before 'License'
                $html = str_ireplace("I, the copyright holder of this work, hereby publish it under the following license:", "", $html);
                $html = str_ireplace("You are free: to share – to copy, distribute and transmit the work to remix – to adapt the work Under the following conditions: attribution – You must attribute the work in the manner specified by the author or licensor (but not in any way that suggests that they endorse you or your use of the work). share alike – If you alter, transform, or build upon this work, you may distribute the resulting work only under the same or similar license to this one.", "", $html);

                $html = Functions::remove_whitespace($html); //always the last step
                //remove Flickr's long licensing portion
                $html = str_ireplace('Licensing <table cellspacing="8" cellpadding="0" lang="en" > <tr> <td><i>This image was taken from <a href="https://commons.wikimedia.org/wiki/Flickr" title="Flickr">Flickr</a>'."'".'s <a rel="nofollow" href="https://flickr.com/commons">The Commons</a>. The uploading organization may have various reasons for determining that no known copyright restrictions exist, such as:<br /></i> The copyright is in the public domain because it has expired; The copyright was injected into the public domain for other reasons, such as failure to adhere to required formalities or conditions; The institution owns the copyright but is not interested in exercising control; or The institution has legal rights sufficient to authorize others to use the work without restrictions. More information can be found at <a rel="nofollow" href="https://flickr.com/commons/usage/">https://flickr.com/commons/usage/</a> Please add additional <a href="https://commons.wikimedia.org/wiki/Commons:Copyright_tags" title="Commons:Copyright tags">copyright tags</a> to this image if more specific information about copyright status can be determined. See <a href="https://commons.wikimedia.org/wiki/Special:MyLanguage/Commons:Licensing" title="Special:MyLanguage/Commons:Licensing">Commons:Licensing</a> for more information.No known copyright restrictionsNo restrictionshttps://www.flickr.com/commons/usage/false </td> </tr> </table>', "", $html);
                //remove {{PD-scan|PD-old-100}} long licensing portion
                $html = str_ireplace('Licensing <table cellspacing="8" cellpadding="0" > <tr> <td>This image is in the <a href="https://en.wikipedia.org/wiki/public_domain" title="w:public domain">public domain</a> because it is a mere mechanical scan or photocopy of a public domain original, or – from the available evidence – is so similar to such a scan or photocopy that no copyright protection can be expected to arise. The original itself is in the public domain for the following reason: <table > <tr> <td>Public domainPublic domainfalsefalse</td> </tr> </table> <table lang="en"> <tr> <td rowspan="2"></td> <td> This work is in the <a href="https://en.wikipedia.org/wiki/public_domain" title="en:public domain">public domain</a> in its country of origin and other countries and areas where the <a href="https://en.wikipedia.org/wiki/List_of_countries%27_copyright_length" title="w:List of countries'."'".' copyright length">copyright term</a> is the author'."'".'s life plus 100 years or less. You must also include a <a href="https://commons.wikimedia.org/wiki/Commons:Copyright_tags#United_States" title="Commons:Copyright tags">United States public domain tag</a> to indicate why this work is in the public domain in the United States. </td> </tr> <tr> <td colspan="2"> <a rel="nofollow" href="https://creativecommons.org/publicdomain/mark/1.0/deed.en">This file has been identified as being free of known restrictions under copyright law, including all related and neighboring rights.</a> </td> </tr> </table> This tag is designed for use where there may be a need to assert that any enhancements (eg brightness, contrast, colour-matching, sharpening) are in themselves insufficiently creative to generate a new copyright. It can be used where it is unknown whether any enhancements have been made, as well as when the enhancements are clear but insufficient. For known raw unenhanced scans you can use an appropriate <a href="https://commons.wikimedia.org/wiki/Template:PD-old" title="Template:PD-old">{{PD-old}}</a> tag instead. For usage, see <a href="https://commons.wikimedia.org/wiki/Commons:When_to_use_the_PD-scan_tag" title="Commons:When to use the PD-scan tag">Commons:When to use the PD-scan tag</a>. Note: This tag applies to scans and photocopies only. For photographs of public domain originals taken from afar, <a href="https://commons.wikimedia.org/wiki/Template:PD-Art" title="Template:PD-Art">{{PD-Art}}</a> may be applicable. See <a href="https://commons.wikimedia.org/wiki/Commons:When_to_use_the_PD-Art_tag" title="Commons:When to use the PD-Art tag">Commons:When to use the PD-Art tag</a>.</td> </tr> </table>', "", $html);
            }
            $html = self::clean_html($html);
            $html = self::more_desc_removed($html);
            return $html;
        }
        return false;
    }
    private function adjust_image_desc($html)
    {
        $html = trim(self::remove_space($html));
        $html = Functions::get_str_up_to_this_chars_only($html, "<b>Text Appearing Before Image: </b>");
        $html = Functions::remove_whitespace($html);
        $html = strip_tags($html,'<a><b><br>');
        return $html;
    }
    private function more_desc_removed($html)
    {
        $findme = '</table> Licensing <table';
        $html = trim($html);
        $pos = stripos($html, $findme);
        // The !== operator can also be used.  Using != would not work as expected because the position of 'a' is 0. The statement (0 != false) evaluates to false.
        if($pos !== false) { //echo "The string '$findme' exists at position $pos";
            $html = substr($html,0,$pos);
            $html .= "</table>";
        }
        else {
            $findme = '</table> Licensing[';
            $html = trim($html);
            $pos = stripos($html, $findme);
            // The !== operator can also be used.  Using != would not work as expected because the position of 'a' is 0. The statement (0 != false) evaluates to false.
            if($pos !== false) { //echo "The string '$findme' exists at position $pos";
                $html = substr($html,0,$pos);
                $html .= "</table>";
            }
        }
        return Functions::remove_whitespace($html);
    }
    private function clean_html($html)
    {
        $html = str_ireplace(array("\n", "\r", "\t", "\o", "\xOB", "\11", "\011"), "", trim($html));
        $html = str_ireplace("</table> |", "</table>", $html);
        $html = str_ireplace(" |||| ", "; ", $html); //was a weird sol'n to an imageDescription with weird chars. But it worked :-)
        return $html;
        // return Functions::remove_whitespace($html);
    }
    /*
    private function last_chance_for_description($str)
    {
        if(preg_match("/\|en =(.*?)\\\n/ims", $str, $a)) {
            // |en = Inflorescence of [[:en:Oregano|Oregano]].
            // Origanum_vulgare_-_harilik_pune.jpg
            if($val = trim($a[1])) return $val;
        }
        if(preg_match("/\|Description=(.*?)\\\n/ims", $str, $a)) {
            if($val = trim($a[1])) return $val;
        }
        if(preg_match("/\|Description (.*?)\\\n/ims", $str, $a)) {
            if($val = trim($a[1])) return $val;
        }
        if(preg_match("/\|description (.*?)\\\n/ims", $str, $a)) {
            if($val = trim($a[1])) return $val;
        }
        if(preg_match("/\| Description (.*?)\\\n/ims", $str, $a)) {
            if($val = trim($a[1])) return $val;
        }
        // if(preg_match("/elix(.*?)\\\n/ims", "elix".$str, $a)) //get first row in the wiki text
        // {
        //     if($val = trim($a[1])) return $val;
        // }
        return false;
    }
    */
    private function get_media_metadata_from_api($file)
    {   //https://commons.wikimedia.org/w/api.php?action=query&prop=imageinfo&iiprop=extmetadata&titles=Image:Gorilla_498.jpg
        $rek = array();
        $options = $this->download_options;
        // $options['expire_seconds'] = false; //preferably monthly cache expires
        $api_call = "https://commons.wikimedia.org/w/api.php?format=json&action=query&prop=imageinfo&iiprop=extmetadata&titles=Image:".$file;
        // echo "\n[$api_call]\n";
        if($json = Functions::lookup_with_cache($api_call, $options)) {
            $json = self::clean_html($json); //new ditox eli
            $arr = json_decode($json, true);
            // print_r($arr); exit;
            if(is_array(@$arr["query"]["pages"])) $arr = array_values($arr["query"]["pages"]); //normal
            else                                  $arr = array();
            $arr = @$arr[0];
            debug("\nresult: " . count($arr) . "\n");
            // print_r($arr); //exit;
            if(!isset($arr['pageid'])) return array();
            $rek['pageid'] = self::format_wiki_substr($arr['pageid']);

            if(in_array($rek['pageid'], $this->excluded_pageids)) return false;

            $rek['ImageDescription'] = self::format_wiki_substr(@$arr['imageinfo'][0]['extmetadata']['ImageDescription']['value']);
            $rek['ImageDescription'] = self::adjust_image_desc($rek['ImageDescription']);

            if($rek['title'] = self::get_title_from_ImageDescription($rek['ImageDescription'])) {}
            else $rek['title'] = self::format_wiki_substr($arr['title']);
            
            /*
            if($rek['pageid'] == "pageid") { //good debug using API
                echo "\n=======investigate api data =========== start\n";
                print_r($arr);
                echo "\n=======investigate api data =========== end\n";
            }
            */
            
            /* NOT to be a rule as an invalid license. see here: https://commons.wikimedia.org/wiki/File:Aa_species.jpg
            if($val = self::format_wiki_substr(@$arr['imageinfo'][0]['extmetadata']['Credit']['value'])) {
                if(stripos($val, "int-own-work") !== false) return false; //string is found ---- invalid license
            }
            */
            //start artist ====================
            /* e.g. file = Cypron-Range_Hypanus_dipterura.svg
            <div class="noresize">
            <a href="//commons.wikimedia.org/wiki/User:The_Emirr/MapLab" title="User:The Emirr/MapLab">
                <img alt="Maplab-logo.svg" src="https://upload.wikimedia.org/wikipedia/commons/thumb/f/f6/Maplab-logo.svg/50px-Maplab-logo.svg.png" width="50" height="15" srcset="https://upload.wikimedia.org/wikipedia/commons/thumb/f/f6/Maplab-logo.svg/75px-Maplab-logo.svg.png 1.5x, https://upload.wikimedia.org/wikipedia/commons/thumb/f/f6/Maplab-logo.svg/100px-Maplab-logo.svg.png 2x" data-file-width="200" data-file-height="60">
            </a>
            </div>
            */
            if($val = self::format_wiki_substr(@$arr['imageinfo'][0]['extmetadata']['Artist']['value'])) {
                $val = str_ireplace("\n", "", $val);
                if(stripos($val, "User:Aktron") !== false) return false; //string is found ---- invalid license
                // User:Sevela.p
                elseif(stripos($val, "Tom Habibi") !== false) $rek['Artist'][] = array('name' => 'Tom Habibi', 'homepage' => 'http://commons.wikimedia.org/wiki/User:Tomhab~commonswiki', 'role' => 'source');

                elseif(preg_match_all("/<li>(.*?)<\/li>/ims", $val, $a)) $rek['Artist'] = self::process_li_separated_artists($a);
                elseif(preg_match("/title=\"User:(.*?)\"/ims", $val, $a)) {
                    $hpage = '';
                    if(preg_match("/href=\"(.*?)\"/ims", $val, $a2)) {
                        $hpage = trim($a2[1]);
                        if(substr($hpage,0,24) == '//commons.wikimedia.org/') $hpage = "https:".$hpage;
                        else                                                  $hpage = trim($a2[1]); //orig
                    }
                    $rek['Artist'][] = array('name' => $a[1], 'role' => 'creator', 'homepage' => $hpage);
                }
                else { //original block
                    $atemp = array();
                    if(preg_match("/href=\"(.*?)\"/ims", $val, $a)) {
                        $hpage = trim($a[1]);
                        if(substr($hpage,0,24) == '//commons.wikimedia.org/') $atemp['homepage'] = "https:".$hpage;
                        else                                                  $atemp['homepage'] = trim($a[1]); //orig
                    }
                    if(preg_match("/\">(.*?)<\/a>/ims", $val, $a)) {
                        if(stripos($val, " by ") !== false) { //string is found
                            $atemp['name'] = strip_tags($val);
                            $atemp['role'] = 'creator';
                        }
                        else {
                            $atemp['name'] = self::remove_role_from_name(strip_tags(trim($a[1]),''));
                            $atemp['role'] = 'creator';
                        }
                    }
                    if(@$atemp['name']) $rek['Artist'][] = $atemp;
                    else                $rek['Artist'][] = array('name' => self::remove_space(strip_tags($val,'')), 'role' => 'creator'); // e.g. <span lang="en">Anonymous</span>

                    if(!$rek['Artist']) {
                        if($temp = self::remove_space(strip_tags($val,''))) {
                            $rek['Artist'][] = array('name' => $temp, 'role' => 'creator');
                        }
                    }

                    if(self::invalid_artist_name_value($rek)) $rek['Artist'] = array();
                }
            }
            if(!@$rek['Artist']) {
                if($val = self::get_artist_from_ImageDescription($rek['ImageDescription'])) {
                    if(isset($val[0])) $rek['Artist'] = array_merge(array(), $val);
                    else               $rek['Artist'][] = $val;
                }
                // echo "\n ice 111\n";
                if(self::invalid_artist_name_value($rek)) $rek['Artist'] = array();
            }
            if(!@$rek['Artist']) {
                if($val = @$arr['imageinfo'][0]['extmetadata']['Credit']['value']) {
                    $val = str_ireplace("\n", "", $val);
                    // echo "\n ice 222\n";
                    $credit_value = strip_tags($val);
                    if(stripos($credit_value, "http://wellcomeimages.org") !== false) $rek['Artist'][] = array('name' => 'Wellcome Images', 'homepage' => 'http://wellcomeimages.org', 'role' => 'source');
                    elseif(stripos($credit_value, "by the British Library") !== false) $rek['Artist'][] = array('name' => 'The British Library', 'homepage' => 'https://www.bl.uk/', 'role' => 'source');
                    elseif(stripos($credit_value, "Iconographia Zoologica") !== false) $rek['Artist'][] = array('name' => 'Iconographia Zoologica', 'homepage' => 'https://commons.wikimedia.org/wiki/Category:Iconographia_Zoologica', 'role' => 'source');
                    else $rek['Artist'][] = array('name' => strip_tags($val) , 'role' => 'source');
                }
                if(self::invalid_artist_name_value($rek)) $rek['Artist'] = array();
            }
            if(!@$rek['Artist']) { //e.g. Files from Wellcome Images
                // echo "\n ice 333\n";
                if($val = self::get_artist_from_special_source(@$arr['imageinfo'][0]['extmetadata']['Categories']['value'], $rek['title'])) $rek['Artist'][] = $val; //get_media_metadata_from_api()
            }
            if(!@$rek['Artist']) {
                // echo "\n ice 444\n";
                if($val = self::get_artist_from_special_source($rek['ImageDescription'])) $rek['Artist'][] = $val; //get_media_metadata_from_api()
            }
            
            if($val = @$rek['Artist']) $rek['Artist'] = self::flickr_lookup_if_needed($val);
            
            //end artist ========================
            
            $rek['LicenseUrl']       = self::format_wiki_substr(@$arr['imageinfo'][0]['extmetadata']['LicenseUrl']['value']);
            $rek['LicenseShortName'] = self::format_wiki_substr(@$arr['imageinfo'][0]['extmetadata']['LicenseShortName']['value']);
            if($val = @$arr['imageinfo'][0]['extmetadata']['DateTime']['value'])             $rek['date'] = self::format_wiki_substr($val);
            elseif($val = @$arr['imageinfo'][0]['extmetadata']['DateTimeOriginal']['value']) $rek['date'] = self::format_wiki_substr($val);
            $rek['eol_type'] = self::check_if_api_image_is_map(@$arr['imageinfo'][0]['extmetadata']['Categories']['value']);
            $rek['fromx'] = 'api'; //object metadata from API;
            /* debug only
            if(!$rek['Artist']) {
                print_r($arr);
                exit("\nwala artist...\n");
            }
            */
            /* debug only
            if($rek['Artist'][0]['name'] == '<span lang="en">Anonymous</span>') {
                print_r($arr);
                exit("\n investigate...\n");
            }
            */
        }
        else echo "\nNot found in API\n";
        return $rek; //$arr
    }
    private function check_if_dump_image_is_map($wiki)
    {   /*
        [[Category:NASA World Wind]]
        [[Category:Canidae distribution maps]]
        [[Category:Animal distribution maps of Africa]]
        [[Category:Pleuronectiformes distribution maps]]
        */
        if(preg_match_all("/\[\[Category\:(.*?)\]\]/ims", $wiki, $a)) {
            $cats = array_map('trim', $a[1]);
            $ret = self::chech_each_category($cats);
            return $ret;
        }
    }
    private function check_if_api_image_is_map($categories)
    {   /*[Categories] => Array
            [value] => Azolla pinnata|Biogeographical maps of India|CC-BY-SA-3.0,2.5,2.0,1.0|GFDL|License migration redundant|Retouched pictures|Self-published work|Uploaded with derivativeFX
        */
        $cats = explode("|", trim($categories));
        $cats = array_map('trim', $cats);
        $ret = self::chech_each_category($cats);
        return $ret;
    }
    private function chech_each_category($categories)
    {
        foreach($categories as $cat) {
            if(in_array($cat, $this->exact_map_categories)) return 'map';
            foreach($this->substrs_map_categories_right as $part) {
                // if(preg_match("/ (.*?)".$part."/ims", $cat, $a)) return 'map';
                if(stripos($cat, $part) !== false) return 'map'; //string is found
            }
            foreach($this->substrs_map_categories_left as $part) {
                // if(preg_match("/".$part."(.*?) /ims", $cat, $a)) return 'map';
                if(stripos($cat, $part) !== false) return 'map'; //string is found
            }
        }
    }
    private function flickr_lookup_if_needed($arr)
    {
        $i = 0;
        foreach($arr as $a) {
            if($name = $a['name']) {
                if(substr($name,0,strlen("http://www.flickr.com/photos/")) == "http://www.flickr.com/photos/") $arr[$i]['name'] = self::realname_Flickr_lookup($a['name']);
                if(substr($name,0,strlen("https://www.flickr.com/photos/")) == "https://www.flickr.com/photos/") $arr[$i]['name'] = self::realname_Flickr_lookup($a['name']);
            }
        }
        return $arr;
    }
    private function realname_Flickr_lookup($url) //from https://www.flickr.com/services/api/
    {
        $options = $this->download_options;
        $options['expire_seconds'] = false; //this can always be false
        $options['delay_in_minutes'] = 0;
        if(preg_match("/photos\/(.*?)xxx/ims", $url."xxx", $a)) {
            $user_id = $a[1];
            $user_id = Functions::remove_this_last_char_from_str($user_id, "/");
            if(stripos($user_id, "@N") !== false) return self::get_Flickr_user_realname_using_userID($user_id, $options); //string is found
            else { //$user_id is a username
                // $user_id = 'dkeats'; //debug only
                $api_call = "https://api.flickr.com/services/rest/?method=flickr.people.findByUsername&api_key=".FLICKR_API_KEY."&username=".$user_id."&format=json&nojsoncallback=1";
                if($json = Functions::lookup_with_cache($api_call, $options)) {
                    $arr = json_decode($json, true);
                    if($user_id = @$arr['user']['id']) {
                        return self::get_Flickr_user_realname_using_userID($user_id, $options);
                    }
                }
            }
        }
        return $url;
    }
    private function get_Flickr_user_realname_using_userID($user_id, $options)
    {
        $api_call = "https://api.flickr.com/services/rest/?method=flickr.people.getInfo&api_key=".FLICKR_API_KEY."&user_id=".$user_id."&format=json&nojsoncallback=1";
        if($json = Functions::lookup_with_cache($api_call, $options)) { //always cache expire false
            $arr = json_decode($json, true);
            if($val = @$arr['person']['realname']['_content']) return "$val ($user_id)";
            elseif($val = @$arr['person']['username']['_content']) return "$val ($user_id)";
            else return "Flickr user ID $user_id";
        }
        else return "Flickr user ID $user_id";
    }
    private function process_li_separated_artists($arr)
    {
        $final = array();
        foreach($arr[1] as $item) {
            if(preg_match("/wiki\/User\:(.*?)\"/ims", $item, $a)) $final[] = array("name" => $a[1], 'homepage' => 'https://commons.wikimedia.org/wiki/User:'.$a[1], 'role' => 'creator');
            else                                                  $final[] = array("name" => self::remove_space(strip_tags($item)), 'role' => 'creator', 'homepagae' => 'media_urlx');
        }
        return $final;
    }
    private function invalid_artist_name_value($rek)
    {
        if(!isset($rek['Artist'][0]['name'])) return false;
        if($rek['Artist'][0]['name'] == "Unknown") return true;
        if(stripos($rek['Artist'][0]['name'], "Unknown author") !== false) return true;
        // if(stripos($rek['Artist'][0]['name'], "Unknown photographer") !== false) return true; --- not needed yet
        if(Functions::get_mimetype($rek['Artist'][0]['name'])) return true; //name should not be an image path
        // elseif(self::url_is_valid($rek['Artist'][0]['name']))  return true; //name should not be a url - DON'T USE THIS, WILL REMAIN COMMENTED, at this point we can accept URL values as it will be resolved later
        return false;
    }
    private function remove_role_from_name($str)
    {
        $str = self::remove_space($str);
        $remove = array("Creator:");
        return str_ireplace($remove, "", $str);
    }
    function remove_space($str)
    {
        $str = str_replace("&nbsp;", " ", $str);
        return Functions::remove_whitespace($str);
    }
    private function get_artist_from_special_source($categories, $title = "") //$categories can be any block of string
    {
        $categories = Functions::remove_whitespace($categories);
        
        if(stripos($categories, "Template Unknown (author)") !== false) { //string is found
            return array('name' => "Wikimedia Commons", 'homepage' => "https://commons.wikimedia.org/wiki/$title", 'role' => 'recorder');
        }
        if(stripos($categories, "Files from Wellcome Images") !== false) { //string is found
            return array('name' => "Wellcome Images", 'homepage' => "https://wellcomeimages.org/", 'role' => 'source');
        }
        elseif(stripos($categories, "{{Wellcome Images}}") !== false) { //string is found
            return array('name' => "Wellcome Images", 'homepage' => "https://wellcomeimages.org/", 'role' => 'source');
        }
        elseif(stripos($categories, "Files with no machine-readable author|Files with no machine-readable source") !== false) { //string is found

            if(stripos($categories, "User:") !== false) { //string is found
                if    (preg_match("/User\:(.*?)\|/ims", $categories, $a))        return array('name' => trim($a[1]), 'homepage' => $title, 'role' => 'creator');
                elseif(preg_match("/User\:(.*?)xxx/ims", $categories."xxx", $a)) return array('name' => trim($a[1]), 'homepage' => $title, 'role' => 'creator');
            }
            else return array('name' => "Wikimedia Commons", 'homepage' => $title, 'role' => 'recorder');

        }
        if(preg_match("/Photographer\:(.*?)\\n/ims", $categories, $a)) { //Photographer: Richard Ling <wikipedia@rling.com>
            return array('name' => trim($a[1]), 'homepage' => $title, 'role' => 'photographer');
        }
        if(preg_match("/Uploader\:(.*?)\\n/ims", $categories, $a)) { //Uploader: [[user:de:Necrophorus|Necrophorus]] 15:30, 8. Sep 2004 (CEST)
            $str = trim($a[1]);
            if($arr = self::parse_str_with_User_enclosed_in_brackets($str)) return $arr;
            else return array('name' => $str, 'homepage' => $title, 'role' => 'source');
        }

        if(preg_match("/Creator\:(.*?)\\n/ims", $categories, $a)) { //:Creator: Harrison, George
            return array('name' => trim($a[1]), 'homepage' => $title, 'role' => 'creator');
        }
        if(preg_match("/Publisher\:(.*?)\\n/ims", $categories, $a)) { //:Publisher: U.S. Fish and Wildlife Service
            return array('name' => trim($a[1]), 'homepage' => $title, 'role' => 'publisher');
        }
        if(preg_match("/Source\:(.*?)\\n/ims", $categories, $a)) { //:Source: WO-EE-4138
            return array('name' => trim($a[1]), 'homepage' => $title, 'role' => 'source');
        }

        if(stripos($categories, "User:The Emirr") !== false && stripos($categories, "Permission ={{Cypron}}") !== false) { //strings are found
            return array('name' => 'The Emirr/MapLab', 'homepage' => 'https://commons.wikimedia.org/wiki/User:The_Emirr/MapLab/Cypron', 'role' => 'creator');
        }
        
        //Images from the CDC Public Health Image Library
        if(stripos($categories, "Public Health Image") !== false) { //strings are found
            return array('name' => 'CDC Public Health Image Library', 'homepage' => 'https://commons.wikimedia.org/wiki/Template:CDC-PHIL', 'role' => 'source');
        }

        if(stripos($categories, "PD US HHS CDC") !== false) { //strings are found
            return array('name' => 'Centers for Disease Control and Prevention', 'homepage' => 'https://en.wikipedia.org/wiki/Centers_for_Disease_Control_and_Prevention', 'role' => 'source');
        }
        
        //last options
        if(preg_match("/Photo by (.*?)\./ims", $categories, $a)) { //from wiki text - description: "...Photo by Gus van Vliet."
            return array('name' => trim($a[1]), 'homepage' => $title, 'role' => 'photographer');
        }
        if(preg_match("/\[\[Category:Photographs by (.*?)\]\]/ims", $categories, $a)) { //from wiki text - description: "...[[Category:Photographs by Ernst Schäfer]]..."
            return array('name' => trim($a[1]), 'homepage' => $title, 'role' => 'photographer');
        }

        if(stripos($categories, "Files from Flickr's 'The Commons'") !== false) { //string is found
            return array('name' => "Flickr's 'The Commons'", 'homepage' => "https://flickr.com/commons", 'role' => 'source');
        }
        //real last option
        if(stripos($categories, "[[Category:Media missing infobox template]]") !== false) { //string is found
            return array('name' => "Wikimedia Commons", 'homepage' => "https://commons.wikimedia.org/wiki/Main_Page", 'role' => 'source');
        }
        return false;
    }
    private function parse_str_with_User_enclosed_in_brackets($str)
    {
        if(stripos($str, "[[User:") !== false && stripos($str, "]]") !== false) { //string is found //e.g. *Original: [[User:Chiswick Chap|Chiswick Chap]]
            if(preg_match("/\[\[(.*?)\]\]/ims", $str, $a)) {
                $tmp_arr = explode("|", $a[1]); //"[[User:Tomascastelazo|Tomas Castelazo]]" "*Original: [[User:Chiswick Chap|Chiswick Chap]]"
                if($name = @$tmp_arr[1]) return array('name' => $name, 'homepage' => "https://commons.wikimedia.org/wiki/".$tmp_arr[0]);
                else { //"[[User:Victuallers]]"
                    $user = str_ireplace("User:", "", $a[1]);
                    return array('name' => $user, 'homepage' => "https://commons.wikimedia.org/wiki/User:".$user);
                }
            }
        }
    }
    private function get_title_from_ImageDescription($desc)
    {
        $desc = strip_tags($desc, "<br>");
        if(preg_match("/Title:(.*?)<br>/ims", $desc, $arr)) return trim($arr[1]);
        return false;
    }
    private function wiki2html($str)
    {
        if(preg_match_all("/\[(.*?)\]/ims", $str, $a)) {
            $divided = array();
            foreach($a[1] as $tmp) {
                $arr = explode(" ", $tmp);
                $url = $arr[0];
                array_shift($arr);
                $link_text = implode(" ", $arr);
                $divided[] = array("url" => $url, "link_text" => $link_text);
            }
            $i = 0;
            foreach($a[1] as $tmp) {
                $str = str_replace("[" . $tmp . "]", "<a href='" . $divided[$i]['url'] . "'>" . $divided[$i]['link_text'] . "</a>", $str);
                $i++;
            }
        }
        return $str;
    }
    private function get_media_url($file)
    {   // $file = "DKoehl_Irrawaddi_Dolphin_jumping.jpg";  // $file = "Lycopodiella_cernua_estróbilos.jpg";    // $file = "Lycopodiella_cernua_estr%C3%B3bilos.jpg";
        $file = urldecode($file);
        $md5 = md5($file);
        $char1 = substr($md5,0,1);
        $char2 = substr($md5,1,1);
        return "https://upload.wikimedia.org/wikipedia/commons/$char1/$char1$char2/" . str_replace(" ", "_", $file);
    }
    private function format_wiki_substr($substr) //https://en.wikipedia.org/wiki/Control_character
    {   
        $substr = Functions::import_decode($substr);
        $substr = Functions::remove_whitespace($substr);
        return str_replace(array("\n", "\t", "\r", chr(9), chr(10), chr(13)), "", $substr);
    }
    private function get_taxon_name($arr)
    {
        $claims = @$arr->claims;
        if($val = @$claims->P225[0]->mainsnak->datavalue->value) return (string) $val;
        elseif(in_array(@$arr->id, array("Q4589415"))) { //special case for a ko & en article
            if($val = @$arr->labels->en->value) return (string) $val;
        }
        /* this introduced new probs, thus commented
        elseif($val = @$arr->labels->en->value) return (string) $val;
        else {
            // print_r($arr);
            // exit("\nno taxon name, pls investigate...\n");
        }
        */
        return false;
    }
    private function get_authorship($claims)
    {
        if($id = @$claims->P225[0]->qualifiers->P405[0]->datavalue->value->id) return self::lookup_value($id);
        return false;
    }
    private function get_authorship_date($claims)
    {
        if($date = @$claims->P225[0]->qualifiers->P574[0]->datavalue->value->time) return (string) $date;
        return false;
    }
    private function get_taxon_rank($claims)
    {
        if($id = (string) @$claims->P105[0]->mainsnak->datavalue->value->id) return self::lookup_value($id);
        return false;
    }
    //----------------------------------------------------------------------------------------------start process of sitelinks as comnames
    /* these were never used. Used 'labels' instead of 'sitelinks'.
    private function remove_non_essential_indeces($indeces)
    {   // echo "\n".count($indeces)."\n";
        $indeces = array_diff($indeces, array('commonswiki','simplewiki','specieswiki')); //subtract from list
        // echo "\n".count($indeces)."\n"; // print_r($indeces);
        $final = array();
        foreach($indeces as $index) {
            if(stripos($index, "wikiquote") !== false) {}  //string is found
            elseif(stripos($index, "wikinews") !== false) {}  //string is found
            else $final[] = $index;
        }
        // echo "\n".count($final)."\n"; print_r($final);
        return $final;
    }
    private function get_comnames_from_sitelinks($sitelinks, $taxon_id)
    {
        $sitelinks = (array) $sitelinks;
        $indeces = array_keys($sitelinks); // print_r($indeces);
        $indeces = self::remove_non_essential_indeces($indeces);
        // print_r($sitelinks);
        $final = array();
        foreach($indeces as $index) {
            $rec = $sitelinks[$index];
            if($url = $rec->url) { //e.g. https://sr.wikipedia.org/wiki/Bakalar
                if(preg_match("/\:\/\/(.*?)\./ims", $url, $arr)) $rec->lang_code = $arr[1];
            }
            elseif($site = $rec->site) { //e.g. [site] => ukwiki
                if(preg_match("/xxx(.*?)wiki/ims", "xxx".$site, $arr)) $rec->lang_code = $arr[1];
            }
            else exit("\nCannot compute language code [$taxon_id]\n");
            $final[] = $rec;
            print_r($rec);
        }
        return $final;
    }
    */
    //----------------------------------------------------------------------------------------------end process of sitelinks as comnames
    //----------------------------------------------------------------------------------------------start process of 'labels' as comnames
    private function get_comnames_from_labels($labels, $sciname)
    {   /* sample $labels
        [ia] => stdClass Object(
                [language] => ia
                [value] => Gadus morhua
        [de-ch] => stdClass Object(
                [language] => de-ch
                [value] => Kabeljau
        */
        // print_r($labels); exit("\nraw labels list\n");
        //step 1: remove those resembling a scientific name
        $final = array();
        $scinames = explode(" ", $sciname); // print_r($scinames); exit("\n[".$scinames[0]."] [".$scinames[1]."]\n");
        foreach($labels as $lang => $label) {
            $is_sciname = false;
            foreach($scinames as $sci) {
                if(stripos($label->value, $sci) !== false) $is_sciname = true;  //string is found
            }
            if(!$is_sciname) {
                if($val = $label->value) {
                    $language = self::comname_manual_adjustment($label->language);
                    $final[$language][] = array('comname' => $val, 'lang' => $language);
                }
            }
        }
        return $final;
    }
    private function comname_manual_adjustment($lang)
    {
        if($lang == 'es-419') return 'es';
        if($lang == 'simple') return 'en';
        return $lang;
    }
    private function reformat_orig_comnames($orig_comnames)
    {
        $final = array();
        foreach($orig_comnames as $rec) {
            if($val = $rec['comname']) {
                $final[$rec['lang']][] = array('comname' => $val, 'lang' => $rec['lang'], 'refs' => $rec['refs']);
            }
        }
        return $final;
    }
    private function get_matched_rec_and_other_recs($comname, $recs)
    {
        // echo "\nsearched: [$comname]\n"; //print_r($recs); exit("\nstopx\n");
        $final = array();
        foreach($recs as $rec) {
            if($comname == $rec['comname']) {
                $rec['isPreferredName'] = true;
                $final[] = $rec;
            }
            else $final[] = $rec;
        }
        return $final;
    }
    //----------------------------------------------------------------------------------------------start process of 'labels' as comnames
    private function get_vernacular_names($claims, $rek, $arr) //main vernaculars routine
    {   /* the original process: until DATA-1803 was requested.
        $orig_comnames   = self::get_comnames_from_taxon_common_name($claims, $rek); //orig using P1843
        return $orig_comnames;
        */
    
        /* debug only. A good way to get all indeces
        $a = (array) $arr; print_r(array_keys($a)); exit;
        */
        $taxon_id = $rek['taxon_id'];
        $sciname = $rek['taxon'];

        /* was never used. Used 'labels' instead of 'sitelinks'.
        $sitelinks = $arr->sitelinks;
        $sitelink_comnames = self::get_comnames_from_sitelinks($sitelinks, $taxon_id); exit;
        */
        
        $labels = $arr->labels;
        $labels_comnames = self::get_comnames_from_labels($labels, $sciname); //print_r($labels_comnames); exit;
        $orig_comnames   = self::get_comnames_from_taxon_common_name($claims, $rek); //orig using P1843
        $orig_comnames   = self::reformat_orig_comnames($orig_comnames); //print_r($orig_comnames); exit;
        
        /* Start of the strenous specs:
        So we'd like to try this; for a given language and taxon:
        *-Collect the "common name" elements and the sitelink element
        *-discard any sitelink element that is similar to the scientific name ("matches the first word" would be a good test, I think)
        1-if there are no common name elements, keep the sitelink element (In this case you could keep the sitelink url as Source), and mark as isPreferredName
        2-if there is no sitelink element, keep all common name elements
        3-if there are both, compare the string of the sitelink with the common name strings
        4-if the sitelink matches none of the common names, discard the sitelink and keep all common names
        5-if the sitelink matches one of the common names, flag that common name as isPreferredName, discard the sitelink, and keep the other common names, unflagged
        6-if the sitelink matches more than one common name, keep one of them at random, flag that common name as isPreferredName, discard the sitelink, and keep the other, non-matching common names, unflagged
        */
        $all = array();
        $labels_langs = array_keys($labels_comnames); // print_r($labels_langs);
        $orig_langs   = array_keys($orig_comnames); // print_r($orig_langs);
        $total_langs = array_merge($labels_langs, $orig_langs);
        $total_langs = array_unique($total_langs); //make unique
        $total_langs = array_values($total_langs); //reindex key
        // print_r($total_langs); exit;
        
        foreach($total_langs as $lang) {
            // 1-if there are no common name elements, keep the sitelink element (In this case you could keep the sitelink url as Source), and mark as isPreferredName
            if(!@$orig_comnames[$lang] && @$labels_comnames[$lang]) {
                $labels_comnames[$lang][0]['isPreferredName'] = true; // print_r($labels_comnames[$lang]);
                $all = array_merge($all, $labels_comnames[$lang]);
            }

            // 2-if there is no sitelink element, keep all common name elements
            if(@$orig_comnames[$lang] && !@$labels_comnames[$lang]) $all = array_merge($all, $orig_comnames[$lang]);

            // 3-if there are both, compare the string of the sitelink with the common name strings
            if(@$orig_comnames[$lang] && @$labels_comnames[$lang]) {
                //count how many sitelink matches or intersect
                $raw_orig = array(); $raw_labels = array(); //important to initialize
                foreach($orig_comnames[$lang] as $tmp)   $raw_orig[$tmp['comname']] = '';
                foreach($labels_comnames[$lang] as $tmp) $raw_labels[$tmp['comname']] = '';
                $raw_orig = array_keys($raw_orig);
                $raw_labels = array_keys($raw_labels);
                // echo "\n-------------------\n";
                $matches = array_intersect($raw_labels, $raw_orig);
                $matches = array_values($matches); //reindex key
                // print_r($matches);
                
                // 4-if the sitelink matches none of the common names, discard the sitelink and keep all common names
                if(!$matches) $all = array_merge($all, $orig_comnames[$lang]);
                // 5-if the sitelink matches one of the common names, flag that common name as isPreferredName, discard the sitelink, and keep the other common names, unflagged
                elseif(count($matches) == 1) {
                    $recs = self::get_matched_rec_and_other_recs($matches[0], $orig_comnames[$lang]);
                    $all = array_merge($all, $recs);
                }
                // 6-if the sitelink matches more than one common name, keep one of them at random, flag that common name as isPreferredName, discard the sitelink, and keep the other, non-matching common names, unflagged
                elseif(count($matches) > 1) {
                    print_r($matches); exit("\ndebug only\n");
                    $recs = self::get_matched_rec_and_other_recs($matches[0], $orig_comnames[$lang]);
                    $all = array_merge($all, $recs);
                }
            }
        }
        // print_r($all); exit("\nstop muna ".count($all)."\n");
        return $all;
    }
    private function get_comnames_from_taxon_common_name($claims, $rek) //orig using P1843
    {
        $names = array();
        if($recs = @$claims->P1843) {
            foreach($recs as $rec) {
                $name = array();
                $name['comname'] = @$rec->mainsnak->datavalue->value->text;
                $name['lang'] = @$rec->mainsnak->datavalue->value->language;
                if($name['lang'] == "be-tarask") $name['lang'] = 'be';
                $name['refs'] = self::get_vernacular_refs(@$rec->references);
                if(@$name['comname']) $names[] = $name;
            }
        }
        return $names;
    }
    private function get_vernacular_refs($recs)
    {
        if(!$recs) return array();
        $final = array();
        //step1: get all ids e.g. Q13679 (Wikispecies) for https://www.wikidata.org/wiki/Q160 (Cetacea) -- ids of all references (or organizations)
        $ids = array();
        foreach(@$recs as $rec) {
            if($imported_from = @$rec->snaks->P143) { if($id = $imported_from[0]->datavalue->value->id) $ids[$id] = ''; }
            if($stated_in = @$rec->snaks->P248)     { if($id = $stated_in[0]->datavalue->value->id)     $ids[$id] = ''; }
            if($retrieved = @$rec->snaks->P813)     { if($val = @$retrieved[0]->datavalue->value->time)  $final['retrieved'] = $val; }
            /* debug only -- good debug
            if(@$final['retrieved']) { 
                print_r($final); exit("\nelix1\n");
            } */
        }
        //step2 loop to all ids, and lookup to get
        $reks = array();
        foreach(array_keys($ids) as $id) {
            $rek = array();
            $obj = self::get_object($id);
            if($val = (string) @$obj->entities->$id->labels->en->value) $rek['organization'] = $val;
            if($val = (string) @$obj->entities->$id->descriptions->en->value) $rek['desc'] = $val;
            if($val = (string) @$obj->entities->$id->claims->P856[0]->mainsnak->datavalue->value) $rek['official website'] = $val;
            if(!@$rek['official website']) $rek['official website'] = "https://www.wikidata.org/wiki/$id";
            if($many = @$obj->entities->$id->claims->P112) { //founded by
                foreach($many as $one) {
                    if($val = $one->mainsnak->datavalue->value->id) $rek['founded by'][] = self::lookup_value($val);
                }
            }
            if($val = (string) @$obj->entities->$id->claims->P571[0]->mainsnak->datavalue->value->time) $rek['inception'] = $val;
            if($many = @$obj->entities->$id->claims->P127) { //owned by
                foreach($many as $one) {
                    if($val = $one->mainsnak->datavalue->value->id) $rek['owned by'][] = self::lookup_value($val);
                }
            }
            if(@$rek['organization']) $reks[] = $rek;
            // /* debug only - works OK
            if($org = @$rek['organization']) $this->debug['org'][$org] = $rek;
            // */
        }
        $final['info'] = $reks;
        return $final;
    }
    private function add_vernaculars($recs, $taxon_id)
    {
        foreach($recs as $rec) {
            $v = new \eol_schema\VernacularName();
            $v->taxonID         = $taxon_id;
            $v->vernacularName  = Functions::import_decode($rec['comname']);
            
            //manual
            if($rec['lang'] == "be-tarask") $rec['lang'] = 'be';
            
            $v->language        = $rec['lang'];

            if($val = @$rec['isPreferredName']) $v->isPreferredName = $val;

            $official = array();
            if($refs = @$rec['refs']['info']) {
                foreach($refs as $ref) $official[] = @$ref['official website'];
                $official = array_unique($official); //make unique
                $v->source = implode(";", $official);
            }
            else $v->source = "https://www.wikidata.org/wiki/$taxon_id";
            $this->archive_builder->write_object_to_file($v);
        }
    }
    private function get_commons_gallery($claims) //https://commons.wikimedia.org/wiki/Gorilla%20gorilla
    {
        if($val = (string) @$claims->P935[0]->mainsnak->datavalue->value) return "https://commons.wikimedia.org/wiki/" . str_replace(" ", "_", $val);
        return false;
    }
    private function get_commons_category($claims) //https://commons.wikimedia.org/wiki/Category:Gorilla%20gorilla
    {
        if($val = (string) @$claims->P373[0]->mainsnak->datavalue->value) return "https://commons.wikimedia.org/wiki/Category:" . str_replace(" ", "_", $val);
        return false;
    }
    private function get_range_map($claims)
    {
        $filenames = array();
        if($arr = @$claims->P181) {
            foreach($arr as $i) {
                if($i->mainsnak->datatype == "commonsMedia") $filenames[] = $i->mainsnak->datavalue->value;
            }
        }
        $final = array();
        if($filenames) {
            foreach($filenames as $fn) {
                if($rec = self::process_file($fn)) {
                    $rec['eol_type'] = 'map';
                    $final[] = $rec;
                }
            }
        }
        return $final;
    }
    /* old - obsolete
    private function get_taxon_parent($claims)
    {
        $parent = array();
        if($id = (string) @$claims->P171[0]->mainsnak->datavalue->value->id) {
            $id = self::replace_id_if_redirected($id);
            $parent['id'] = $id;
            $parent['name'] = self::lookup_value($id);
            //start get rank
            if($obj = self::get_object($id)) {
                $parent['taxon_name'] = self::get_taxon_name(@$obj->entities->$id); //old working param is $obj->entities->$id->claims
                $parent['rank'] = self::get_taxon_rank(@$obj->entities->$id->claims);
                if($val = @$obj->entities->$id->claims) {
                    if($val != $claims) $parent['parent'] = self::get_taxon_parent($val);
                }
            }
            return $parent;
        }
        return false;
    }*/
    private function get_taxon_parent($claims, $main_id)
    {
        $parent = array();
        if($id = (string) @$claims->P171[0]->mainsnak->datavalue->value->id) {
            $id = self::replace_id_if_redirected($id);
            if($main_id == $id) return false; //e.g. https://www.wikidata.org/wiki/Q28431692 - parent points to itself.
            $parent['id'] = $id;
            $parent['name'] = self::lookup_value($id);
            //start get rank
            if($obj = self::get_object($id)) {
                $parent['taxon_name'] = self::get_taxon_name(@$obj->entities->$id); //old working param is $obj->entities->$id->claims
                $parent['rank'] = self::get_taxon_rank(@$obj->entities->$id->claims);
                if($val = @$obj->entities->$id->claims) {
                    if($val != $claims) $parent['parent'] = self::get_taxon_parent($val, $id);
                }
            }
            return $parent;
        }
        return false;
    }
    private function replace_id_if_redirected($id)
    {
        $this->redirects['Q13862468'] = "Q10794768";
        $this->redirects['Q14050218'] = "Q10804328";
        $this->redirects['Q14469766'] = "Q10824551";
        $this->redirects['Q14376190'] = "Q10820737";
        $this->redirects['Q14513318'] = "Q10713968";
        $this->redirects['Q15029351'] = "Q13167464";
        $this->redirects['Q18583887'] = "Q13167388";
        $this->redirects['Q18549914'] = "Q13167487";
        $this->redirects['Q16481559'] = "Q10762052";
        $this->redirects['Q21446808'] = "Q10745346";
        $this->redirects['Q18519941'] = "Q23005859"; //later homonym
        $this->redirects['Q27661141'] = "Q777139";   //later homonym
        $this->redirects['Q7225609']  = "Q28148175"; //later homonym
        $this->redirects['Q18522963'] = "Q10827989"; //redirected
        $this->redirects['Q18591107'] = "Q16986192"; //redirected
        $this->redirects['Q21438944'] = "Q21223073"; //duplicated
        $this->redirects['Q13231238'] = "Q13167447"; //redirected
        $this->redirects['Q26288710'] = "Q24976183"; //redirected
        if($val = @$this->redirects[$id]) return $val;
        return $id;
    }
    private function lookup_value($id)
    {
        if($obj = self::get_object($id)) {
            /* debug only
            if($id == "Q27661141") {
                print_r($obj); exit;
            }
            if(!isset($obj->entities->$id->labels->en->value)) { //e.g. Q5614965 
                print_r($obj->entities); exit("\npls investigate 01\n");
            }*/
            if($val = (string) @$obj->entities->$id->labels->en->value) return $val;
        }
    }
    private function get_object($id)
    {
        $url = "https://www.wikidata.org/wiki/Special:EntityData/" . $id . ".json";
        $options = $this->download_options;
        if(@$options['resource_id']) unset($options['resource_id']);
        $options['expire_seconds'] = false; //can always be false, bec. valued by ID normally don't change
        if($json = Functions::lookup_with_cache($url, $options)) {
            $obj = json_decode($json);
            return $obj;
        }
        return false;
    }
    private function get_taxon_sitelinks($sitelinks)
    {
        if($obj = @$sitelinks) return $obj;
        return false;
    }
    private function get_taxon_sitelinks_by_lang($sitelinks)
    {
        $str = $this->language_code."wiki";
        if($obj = @$sitelinks->$str) return $obj;
        return false;
    }
    function create_all_taxon_dump() // utility to create an all-taxon dump
    {
        $raw_dump       = $this->path['raw_dump'];       //RAW fresh dump. NOT TO USE READILY - very big with all categories not just TAXA.
        $all_taxon_dump = $this->path['wiki_data_json']; //will use this instead. An all-taxon dump
        $f = Functions::file_open($all_taxon_dump, "w");
        $e = 0; $i = 0; $k = 0;
        foreach(new FileIterator($raw_dump) as $line_number => $row) {
            $k++;
            if(($k % 20000) == 0) echo " $k";
            if(stripos($row, "Q16521") !== false) { //string is found -- "taxon"
                $e++;
                fwrite($f, $row."\n");
            }
            else $i++;
        }
        fclose($f);
        echo "\ntaxa  wikis: [$e]\n";
        echo "\nnon-taxa  wikis: [$i]\n";
    }
    private function save_filenames_2file($files)
    {   //save to text file
        $txtfile = CONTENT_RESOURCE_LOCAL_PATH . "wikimedia_filenames_" . date("Y_m") . ".txt";
        $WRITE_pageid = fopen($txtfile, "a");
        fwrite($WRITE_pageid, implode("\n", $files) . "\n");
        fclose($WRITE_pageid);
    }
    
    // ============================ start temp file generation ================================================================================================
    function create_temp_files_based_on_wikimedia_filenames()
    {   /*
        $files = array();
        $files[] = "Abhandlungen_aus_dem_Gebiete_der_Zoologie_und_vergleichenden_Anatomie_(1841)_(16095238834).jpg";
        $files[] = "Abhandlungen_aus_dem_Gebiete_der_Zoologie_und_vergleichenden_Anatomie_(1841)_(16531419109).jpg";
        $files[] = "C%C3%A9tac%C3%A9s_de_l%27Antarctique_(Baleinopt%C3%A8res,_ziphiid%C3%A9s,_delphinid%C3%A9s)_(1913)_(20092715714).jpg";
        $files[] = str_replace(" ", "_", "Two Gambel's Quail (Callipepla gambelii) - Paradise Valley, Arizona, ca 2004.png");
        foreach($files as $file)
        */
        $main_path = $this->path['wikimedia_cache'];
        $i = 0;
        $filename = CONTENT_RESOURCE_LOCAL_PATH . "wikimedia_filenames_" . date("Y_m") . ".txt";
        if(!file_exists($filename)) exit("\nFile doesn't exist: [$filename]\nRun php5.6 wikidata.php jenkins save_all_media_filenames\n");
        foreach(new FileIterator($filename) as $line_number => $file) {
            $md5 = md5($file);
            $cache1 = substr($md5, 0, 2);
            $cache2 = substr($md5, 2, 2);
            if(!file_exists($main_path . $cache1))           mkdir($main_path . $cache1);
            if(!file_exists($main_path . "$cache1/$cache2")) mkdir($main_path . "$cache1/$cache2");
            $filename = $main_path . "$cache1/$cache2/$md5.json";
            if(!file_exists($filename)) {
                if($FILE = Functions::file_open($filename, 'w')) fclose($FILE);
            }
            $i++; if(($i % 100000) == 0) echo("\n" . number_format($i) . ". initializing file: $filename"); //just a row count indicator
            // if($i >= 100) break; //debug
        }
    }
    function fill_in_temp_files_with_wikimedia_dump_data()
    {
        $path = $this->path['commons'];
        $reader = new \XMLReader();
        $reader->open($path);
        $i = 0;
        while(@$reader->read()) {
            if($reader->nodeType == \XMLReader::ELEMENT && $reader->name == "page") {
                if($page_xml = $reader->readOuterXML()) {}
                else continue;
                $t = simplexml_load_string($page_xml, null, LIBXML_NOCDATA);
                $title = $t->title;
                // $title = "File:Two Gambel's Quail (Callipepla gambelii) - Paradise Valley, Arizona, ca 2004.png";
                $title = str_replace("File:", "", $title);
                $title = str_replace(" ", "_", $title);
                if($filename = self::taxon_media($title)) {
                    $i++; if(($i % 100000) == 0) echo("\n".number_format($i).". saving content"); //just a row count indicator
                    $month_num = date('m'); //if month is February value is 02
                    if(in_array($month_num, array('03','06','09','12'))) { //scheduled quarterly to refresh all cached information from XML.
                        $json = json_encode($t);
                        if($FILE = Functions::file_open($filename, 'w')) { // normal
                            fwrite($FILE, $json);
                            fclose($FILE);
                        }
                    }
                    else { //if not quarterly schedule, it will not overwrite cache
                        if(filesize($filename) == 0) {
                            $json = json_encode($t);
                            if($FILE = Functions::file_open($filename, 'w')) { // normal
                                fwrite($FILE, $json);
                                fclose($FILE);
                            }
                            // exit("\nmeaning, this was not saved the last time this utility was ran...\n");
                        }
                        // else echo("\nalready saved: [$filename]"); //just for debug...
                    }
                }
                // else echo " negative"; //meaning this media file is not encountered in the taxa wikidata process. //just for debug...
                /* just tests
                if(substr($title,0,5) == "File:") {
                    print_r($t); 
                    $json = json_encode($t);
                    $arr = json_decode($json, true);
                    print_r($arr); exit("\n---\n");
                }
                if($title == "File:Abhandlungen aus dem Gebiete der Zoologie und vergleichenden Anatomie (1841) (16095238834).jpg") {
                    print_r($t); exit("\n111\n");
                }
                */
            }
        }
        /*
        <page>
            <title>South Pole</title>
            <ns>0</ns>
            <id>1883</id>
            <revision>
                  <id>209011112</id>
                  <parentid>140212602</parentid>
                  <timestamp>2016-10-06T22:13:52Z</timestamp>
                  <contributor>
                        <username>CommonsDelinker</username>
                        <id>70842</id>
                  </contributor>
                  <comment>Removed Sastrugi.jpg; deleted by [[User:Ronhjones|Ronhjones]] because: [[:c:COM:L|Copyright violation]]: OTRS 2016100610022578 - From Antarctic Photo Library. Image not taken by employee of National Science Foundation. Needs permission from photographer..</comment>
                  <model>wikitext</model>
                  <format>text/x-wiki</format>
                  <text xml:space="preserve">all wiki text...</text>
                  <sha1>6dpwe9r97p716sg3uzcta9mgc5xlvsk</sha1>
            </revision>
        </page>
        */
    }
    private function taxon_media($title)
    {
        $main_path = $this->path['wikimedia_cache'];
        $md5 = md5($title);
        $cache1 = substr($md5, 0, 2);
        $cache2 = substr($md5, 2, 2);
        $filename = $main_path . "$cache1/$cache2/$md5.json";
        debug("\nfilename: [$title] [$filename]\n");
        if(file_exists($filename)) return $filename;
        else return false;
    }
    function fill_in_temp_files_with_wikimedia_metadata() //just during testing...
    {
        $title = "File:Two Gambel's Quail (Callipepla gambelii) - Paradise Valley, Arizona, ca 2004.png";
        $title = str_replace("File:", "", $title);
        $title = str_replace(" ", "_", $title);
        if(self::taxon_media($title)) echo "\n yes";
        else echo "\n no";
    }
    function process_wikimedia_txt_dump() //initial verification of the wikimedia dump file
    {
        $path = $this->path['commons'];
        /*
        $i = 0;
        foreach(new FileIterator($path) as $line_number => $row) {
            $i++;
            // $arr = json_decode($row);
            echo "\n" . $row; // print_r($row); 
            if($i >= 90000) exit("\n-end-\n");
        }
        */
        $reader = new \XMLReader();
        $reader->open($path);
        $i = 0;
        while(@$reader->read()) {
            if($reader->nodeType == \XMLReader::ELEMENT && $reader->name == "page") {
                if($page_xml = $reader->readOuterXML()) {}
                else continue;
                $t = simplexml_load_string($page_xml, null, LIBXML_NOCDATA);
                $page_id = $t->id;
                if($page_id == "47821") {
                    print_r($t); exit("\nfound 47821\n");
                }
                echo "\n$page_id";
                $title = $t->title;
                if(substr($title,0,5) == "File:") {
                    print_r($t); 
                    exit("\n$page_xml\n");
                }
                if($title == "File:Abhandlungen aus dem Gebiete der Zoologie und vergleichenden Anatomie (1841) (16095238834).jpg") {
                    print_r($t); exit("\n111\n");
                }
                // $i++; if($i%100==0) debug("Parsed taxon $i");
            }
        }
    }
    // ============================ end temp file generation ==================================================================================================
    private function format_license($license, $LicenseShortName="")
    {
        $license          = self::clean_html($license);
        $LicenseShortName = self::clean_html($LicenseShortName);
        //regular EOL licenses
        if(stripos($license, "creativecommons.org/licenses/publicdomain/") !== false)   return $this->license['public domain'];
        if(stripos($license, "creativecommons.org/licenses/by/") !== false)             return $this->license['by'];
        if(stripos($license, "creativecommons.org/licenses/by-nc/") !== false)          return $this->license['by-nc'];
        if(stripos($license, "creativecommons.org/licenses/by-sa/") !== false)          return $this->license['by-sa'];
        if(stripos($license, "creativecommons.org/licenses/by-nc-sa/") !== false)       return $this->license['by-nc-sa'];
        
        if(stripos($license, "gpl") !== false) {
            @$this->debug['gpl count']++;
            return "invalid";
        }

        //others...
        if($license == "http://creativecommons.org/licenses/by-sa")          return $this->license['by-sa']; //exact match
        if(stripos($license, "creativecommons.org/publicdomain/") !== false) return $this->license['public domain'];
        if(stripos($license, "creativecommons.org/licenses/sa/") !== false)  return $this->license['by-sa']; //[http://creativecommons.org/licenses/sa/1.0/]
        if($license == "http://creativecommons.org/licenses/by")             return $this->license['by']; //exact match
        if($license == "https://www.flickr.com/commons/usage/")              return $this->license['public domain']; //exact match
        if(urldecode($license) == "http://biodivlib.wikispaces.com/Permissions#Content provided under Due Diligence") return $this->license['no restrictions']; //exact match
        if($license == "http://wiki.data.gouv.fr/wiki/Licence_Ouverte_/_Open_Licence") return $this->license['public domain']; //exact match

        //should be invalid per Jen:
        if(stripos($license, "creativecommons.org/licenses/by-nc-nd/") !== false) return "invalid";
        if(stripos($license, "commons.wikimedia.org/wiki/File:") !== false) return "invalid";
        $proven_invalid_licenseurl = array("http://www.gnu.org/copyleft/fdl.html", "http://www.gnu.org/licenses/old-licenses/fdl-1.2.html", "http://www.gnu.org/licenses/gpl.html",
        "www.gnu.org/licenses/fdl-1.3.html", "http://artlibre.org/licence/lal/en", "http://www.gnu.org/licenses/lgpl.html");
        if(in_array($license, $proven_invalid_licenseurl)) return "invalid";
        
        // added Oct 16, 2017
        if(stripos($license, "nationalarchives.gov.uk/doc/open-government-licence") !== false) return "invalid"; //"http://www.nationalarchives.gov.uk/doc/open-government-licence/version/3"

        //blank license
        if(!$license) {
            if(in_array($LicenseShortName, array("Public domain", "cc0"))) return $this->license['public domain'];

            //multiple shortnames separated by "|"
            $shortnames = explode("|", strtolower($LicenseShortName)); //"self|Cc-zero"
            foreach($shortnames as $shortname) {
                if(in_array($shortname, array("cc-zero", "cc0", "cc-0")))    return $this->license['public domain'];
                if(substr($shortname,0,3) == "pd-")                          return $this->license['public domain']; //"PD-self" "PD-author" "pd-???" etc.
                if(stripos($shortname, "bild-pd") !== false)                 return $this->license['public domain'];
                if($shortname == "attribution")                              return $this->license['by'];
                if(substr($shortname,0,14) == strtolower("public domain "))  return $this->license['public domain']; // e.g. "Public Domain Mark"
                if(substr($shortname,0,3) == strtolower("pd/"))              return $this->license['public domain']; // e.g. "Pd/1923|1982"
                if($shortname == strtolower("FlickrVerifiedByUploadWizard")) return $this->license['by'];

                if(substr($shortname,0,12) == "cc-by-nc-sa-")                   return $this->license['by-nc-sa'];
                if(substr($shortname,0,9) == "cc-by-nc-")                       return $this->license['by-nc'];
                if(substr($shortname,0,9) == "cc-by-sa-")                       return $this->license['by-sa'];
                if(substr($shortname,0,6) == "cc-by-")                          return $this->license['by'];
                if(stripos($shortname, "self|own-pd") !== false)                return $this->license['public domain'];
                if(stripos($shortname, "no known copyright restriction") !== false) return $this->license['no restrictions'];
                if(stripos($shortname, "BHL-no known restriction") !== false)       return $this->license['no restrictions'];
            }
            
            //should be invalid per Jen
            if(!$LicenseShortName) return "invalid";
            if(stripos($LicenseShortName, "Custom license marker") !== false) return "invalid";
            if(stripos($LicenseShortName, "ExtractedFromNSRW") !== false) return "invalid";
            if(stripos($LicenseShortName, "copyright protection") !== false) return "invalid";
            if(stripos($LicenseShortName, "Copyrighted") !== false) return "invalid";
            if(stripos($LicenseShortName, "FOLP|") !== false) return "invalid";
            if(stripos($LicenseShortName, "GFDL") !== false) return "invalid";
            if(stripos($LicenseShortName, "self|") !== false) return "invalid";
            if(stripos($LicenseShortName, "User:Flickr") !== false) return "invalid";
            if(stripos($LicenseShortName, "User:Ksd5") !== false) return "invalid";
            if(stripos($LicenseShortName, " Monet ") !== false) return "invalid";
            if(stripos($LicenseShortName, "Bild-") !== false) return "invalid";
            if(stripos($LicenseShortName, "Pixabay|") !== false) return "invalid";
            if(stripos($LicenseShortName, "illustration of the Saxaul Sparrow") !== false) return "invalid";
            $invalid_exact = array("BSD", "FAL", "Faroe stamps", "Fotothek-License", "FWS Image", "GPL", "NARA-cooperation", "NAUMANN", "NPS", "Parasite", "unsplash", "WikiAfrica/TNA", "јв-ја");
            foreach($invalid_exact as $exact) {
                if($exact == $LicenseShortName) return "invalid";
            }
            // [Information|Description=en|1=An illustration of the Saxaul Sparrow (''Passer ammondendri'', called the "Turkestan Sparrow" in the book the illustration was published in)]
            
            //added Oct 16, 2017
            if(stripos($LicenseShortName, "Permission= publiek domein") !== false) return $this->license['public domain'];
            if(stripos($LicenseShortName, " PD-old") !== false) return $this->license['public domain']; //"# PD-old"
            if(stripos($LicenseShortName, " PD-US") !== false) return $this->license['public domain']; //"<!-- PD-US"
            if(stripos($LicenseShortName, "Template:PD-") !== false) return $this->license['public domain']; //    [Template:PD-Australia] => 
            if(stripos($LicenseShortName, "Brooklyn_Museum-no_known_restriction") !== false) return $this->license['no restrictions']; //"Brooklyn_Museum-no_known_restrictions"
            if(stripos($LicenseShortName, "CDC-PHIL|") !== false) return $this->license['public domain']; //"CDC-PHIL|id=2741"
            if(stripos($LicenseShortName, "Massel_tow_Credit") !== false) return "invalid"; //"Template:Massel_tow_Credit"
            if(stripos($LicenseShortName, "Blacknclick") !== false) return "invalid"; //[User:Blacknclick/Permission]
            if($LicenseShortName == "FWS") return $this->license['public domain']; //exact match
            if($LicenseShortName == "FCO") return "invalid"; //exact match --- invalid coz OGL something...
            if(stripos($LicenseShortName, "OGL|") !== false) return "invalid"; //[OGL|1=Photo: MoD/MOD] --- invalid coz OGL
            if($LicenseShortName == "OGL") return "invalid"; //exact match
            if(stripos($LicenseShortName, "KOGL-") !== false) return "invalid"; //[KOGL-type1]
            if($LicenseShortName == "PAOC") return "invalid"; //exact match
            if($LicenseShortName == "LGPL") return "invalid"; //exact match
            if($LicenseShortName == "LarsenCopyright") return "invalid"; //exact match
            if($LicenseShortName == "Attribution Entomart") return "invalid"; //exact match
            if(stripos($LicenseShortName, "CC-BY-2.0 stated") !== false) return $this->license['by']; //[(photo: CC-BY-2.0 stated)PD-US] => 
            if($LicenseShortName == "Flickr-Brooklyn-Museum-image") return $this->license['by-sa']; //exact match
            if(stripos($LicenseShortName, "license=GPL") !== false) return "invalid"; //[Free screenshot|license=GPL] => 
            if(stripos($LicenseShortName, "Jim Deacon") !== false) return "invalid"; //[=From the website of the author:"IMPORTANT: COPYRIGHT WAIVERAll of the author's images are shown as [© Jim Deacon]. They can be used freely, for any purpose, without restriction.Please ACKNOWLEDGE THE SOURCE AS: Courtesy of Jim Deacon, The University of Edinburg" http://helios.bto.ed.ac.uk/bto/FungalBiology/index.htm#top== int:license-header] => 
            if($LicenseShortName == "NOAA") return $this->license['public domain']; //exact match
            if($LicenseShortName == "anonymous-EU") return $this->license['public domain']; //exact match
            if($LicenseShortName == "AerialPhotograph-mlitJP") return "invalid"; //exact match
            if(stripos($LicenseShortName, "flickrreview|Leoboudv|") !== false) return $this->license['by-sa']; //[flickrreview|Leoboudv|2014-10-26] => 
            if(stripos($LicenseShortName, "authored by [[User:Arp|Arp]]") !== false) return $this->license['by']; //[This image is authored by [[User:Arp|Arp]]. It was uploaded to waarneming.nl and later copied to commons at a time that waarneming.nl did not yet properly support the only ''really'' free and unhampered license (CC0 Public Domain dedication) preferred by the author, so it was originally uploaded (here) as CC-BY, but it's '''not''' limited in it's use for remixing by that hampered license scheme. It is in fact available as: cc0] => 
            if(stripos($LicenseShortName, "user:Anonymous101") !== false) return $this->license['public domain']; //[user:Anonymous101/template] => 
            if($LicenseShortName == "Dead link") return "invalid"; //exact match
            if(stripos($LicenseShortName, "Hans is short for Johan") !== false) return $this->license['public domain'];
            if(stripos($LicenseShortName, "user=Ww2censor") !== false) return $this->license['public domain']; //[LicenseReview|site=http://biodiversitylibrary.org/page/43064802#page/440/mode/1up|user=Ww2censor|date=2015-09-04] => 
            if($LicenseShortName == "insignia") return "invalid"; //exact match
            if(stripos($LicenseShortName, "GNU|") !== false) return "invalid"; //[GNU|month=December|day=2|year=2008|migration=review] => 
            if(stripos($LicenseShortName, "User:Fir0002") !== false) return "invalid"; //[User:Fir0002/20D|migration=relicense] => 
            if(stripos($LicenseShortName, "Flickrreview|Lewis Hulbert") !== false) return "invalid"; //[Flickrreview|Lewis Hulbert|2014-10-25] => 

            //added Oct 17
            if(stripos($LicenseShortName, "public domain=") !== false) return $this->license['public domain'];
            if(stripos($LicenseShortName, "PD-old") !== false) return $this->license['public domain'];
            if(stripos($LicenseShortName, "PD-self") !== false) return $this->license['public domain'];
            if(stripos($LicenseShortName, "a CC-0") !== false) return $this->license['by'];
            if(stripos($LicenseShortName, "PD-user") !== false) return $this->license['public domain'];
            if(stripos($LicenseShortName, "in the public domain") !== false) return $this->license['public domain'];
            if($LicenseShortName == "Flickr-State-Library-NSW-image") return $this->license['no restrictions']; //exact match
            if($LicenseShortName == "WikiAfrica/SIA") return $this->license['no restrictions']; //exact match
            if(stripos($LicenseShortName, "No license since|") !== false) return "invalid";
            if($LicenseShortName == "East German Post") return "invalid"; //exact match
            if($LicenseShortName == "Kopimi") return "invalid"; //exact match
            if(stripos($LicenseShortName, "TARS631") !== false) return "invalid"; //TARS631 at Tramwayforum.at
            if($LicenseShortName == "Business journal") return "invalid"; //exact match
            if($LicenseShortName == "<!-- !-") return "invalid"; //exact match
            if($LicenseShortName == "== int:filedesc") return "invalid"; //exact match
            if(substr($LicenseShortName,0,12) == "SLNSW-image|") return $this->license['public domain'];
            
            //added Oct 18
            $valid_pd = array("USDA", "USFWS", "USGS", "Anonymous-EU", "DEA");
            if(in_array($LicenseShortName, $valid_pd)) return $this->license['public domain'];
            if(stripos($LicenseShortName, "Malayalam loves Wikimedia") !== false) return "invalid"; //Malayalam loves Wikimedia event|year=2011|month=April
            if(stripos($LicenseShortName, "Images by Rob Lavinsky") !== false) return $this->license['by-sa']; //Images by Rob Lavinsky
            if($LicenseShortName == "AndréWadman") return "invalid"; //exact match
            if(stripos($LicenseShortName, "user=INeverCry") !== false) return "invalid";
            if(stripos($LicenseShortName, "User:Ram-Man") !== false) return "invalid";
            if(stripos($LicenseShortName, "User:Sidpatil") !== false) return "invalid";

            // added Oct 19
            if(stripos($LicenseShortName, "ZooKeys-License") !== false) return $this->license['by'];
            if(stripos($LicenseShortName, "Flickr-change-of-license") !== false) return $this->license['by'];
            if(stripos($LicenseShortName, "-cc-by-") !== false) return $this->license['by']; //ifb-cc-by-2.5|Vesselina Lazarova|http://www.imagesfrombulgaria.com/v/bulgarian-food/Parjena_Caca.JPG.html
            if(stripos($LicenseShortName, "cc-by-sa") !== false) return $this->license['by-sa'];
            if(stripos($LicenseShortName, "Geograph|") !== false) return $this->license['by-sa']; //Geograph|691836|Trish Steel
            if(stripos($LicenseShortName, " cc-by-sa") !== false) return $this->license['by-sa']; //Thomas Pruß cc-by-sa
            if(stripos($LicenseShortName, "WikiAfrica") !== false) return $this->license['by-sa']; //WikiAfrica/Ton Rulkens|2012-10-07
            if(stripos($LicenseShortName, "Wiki Loves Earth") !== false) return $this->license['by-sa']; //Wiki Loves Earth 2014|cat
            if(stripos($LicenseShortName, "Walters Art Museum") !== false) return $this->license['by-sa']; //Walters Art Museum license|type=2D
            if($LicenseShortName == "IUCN map permission") return $this->license['public domain']; //exact match
            if($LicenseShortName == "Justphotos.ru") return $this->license['by-sa']; //exact match
            if($LicenseShortName == "MAV-FMVZ USP-license") return $this->license['by-sa']; //exact match
            if($LicenseShortName == "cc-world66") return $this->license['by-sa']; //exact match
            if($LicenseShortName == "Cc-sa") return $this->license['by-sa']; //exact match
            
            //Oct 25
            if($LicenseShortName == "YouTube CC-BY") return $this->license['by']; //exact match
            $arr = array("cc-a-", "cc-by-"); //findme exists (case insensitive) anywhere in string and followed by digit OR space
            foreach($arr as $findme) {
                $findme = preg_quote($findme, '/');
                if(preg_match("/".$findme."[0-9| ]/ims", $LicenseShortName, $arr)) {
                    return $this->license['by'];
                }
            }
            $findme = "cc-sa-";
            $findme = preg_quote($findme, '/');
            if(preg_match("/".$findme."[0-9| ]/ims", $LicenseShortName, $arr)) { //findme exists (case insensitive) anywhere in string and followed by digit OR space
                return $this->license['by-sa'];
            }
            
            // [public domain] exact
            $arr = array("cc-pd", "pdphoto.org", "Folger Shakespeare Library partnership");
            foreach($arr as $p) {
                if(strtolower($LicenseShortName) == $p) return $this->license['public domain'];
            }
            
            //['by'] stripos
            $arr = array("CC BY ", "CC-BY ", " CC-BY|", "CC-BY ", "CC-Layout", "picasareview", "AntWeb permission");
            foreach($arr as $p) {
                if(stripos($LicenseShortName, $p) !== false) return $this->license['by'];
            }
            //[by-sa] stripos
            $arr = array("Nationaal Archief", "Malayalam loves Wikipedia event", "Category:Megalops atlanticus");
            foreach($arr as $p) {
                if(stripos($LicenseShortName, $p) !== false) return $this->license['by-sa'];
            }

            //[by] exact match
            $arr = array("Premier.gov.ru", "Akkasemosalman");
            foreach($arr as $p) {
                if($LicenseShortName == $p) return $this->license['by']; //exact match
            }

            //[by-sa] exact match
            $arr = array("TamilWiki Media Contest", "RCE-license", "Wikimedia trademark", "gardenology");
            foreach($arr as $p) {
                if($LicenseShortName == $p) return $this->license['by-sa']; //exact match
            }

            /* WILL REMAIN INVALID: as of Nov 9
            [blank_license] => Array(
                    [MLW3‬] => 
                    [dvdm-h6|migration=relicense] => 
                    [BMC] => 
                    [Zachi Evenor] => 
                    [Team|event=Wikipedia Takes Waroona|team=Team Flower|id=19] => 
                    [Wuzur] => 
                    [<br/>(original text|nobold=1|1=Klettenlabkraut in Weizen] => 
                    [Andes] => 
                    [Assessments|enwiki=1|enwiki-nom=Bicolored Antbird] => 
                    [Youtube|Junichi Kubota] => 
                    [Personality rights] => 
                    [personality rights] => 
                    [NO Facebook Youtube license] => 
                    [spomenikSVN|7914] => 
                    [Location|36|2|59.1|N|139|9|1.8|E|type:landmark_region:JP-29_scale:2000] => 
                    [s*derivative work: [[User:B kimmel|B kimmel]] ([[User talk:B kimmel|<span class="signature-talk">talk</span>]])|Permission=|other_versions=] => 
                    [Flickreview|Yuval Y|20:49, 16 June 2011 (UTC)] => 
                    [Tasnim] => 
                    [OTRS|2008072210012641] => 
                    [IBC] => 
                    [QualityImage] => 
                    [youtube] => 
                    [MUSE|OTRS=yes] => 
                    [DYKfile|28 December|2006|type=image] => 
                    [Bilderwerkstatt|editor=[[:de:Benutzer:Denis Barthel|Denis Barthel]]|orig=Yucca_recurvifolia_fh_1183.24_ALA_AAA.jpg|changes=Perspektive, Ausschnitt, kleinere Edits] => 
                    [OTRS|2012011510006576] => 
                    [Location dec|46.122186|7.071841|source:Flickr] => 
                    [Beeld en Geluid Wiki] => 
                    [[[:en:Category:Frog images]]|Source=Transferred from|en.wikipedia] => 
                    [Bilderwerkstatt|editor=[[:de:Benutzer:Saman|Saman]]|orig=|changes=Etwas Staub entfernt, Kontrast und Tonwertkorrektur verändert] => 
                    [retouched|cropped] => 
                    [RetouchedPicture|cropped ''Sciurus spadiceus'' (frame) into a portrait|editor=Jacek555|orig=Sciurus spadiceus (frame).jpg] => 
                    [piqs|101897|babychen] => 
                    [personality] => 
                    [RetouchedPicture|Created GIF animation from sequence of images] => 
                    [!-] => 
                    [Youtube|channelxxxvol1] => 
                    [Picswiss|migration=relicense] => 
                    [Volganet.ru] => 
                    [@|link=http://www.opencage.info/pics.e/large_8238.asp|txt=opencage-] => 
                    ["] => 
                    [RetouchedPicture|Screenshot for distribution map|editor=Obsidian Soul|orig=Australia Victoria location map highways.svg] => 
                    [|Source=transferred from|en.wikipedia|Syp|CommonsHelper] => 
                    [Folger Shakespeare Library partnership] => 
                    [DYKfile|25 March|2008|type=image] => 
                )
            */
            
            //seemingly calphotos images:
            $arr = array("Vladlen Henríquez permission", "Mehregan Ebrahimi permission", "Václav Gvoždík permission", "Diogo B. Provete permission", "Franco Andreone permission", 
            "Josiah H. Townsend permission", "Pierre Fidenci permission", "Alessandro Catenazzi permission", "Stanley Trauth permission", 
            "Raquel Rocha Santos permission", "Mauricio Rivera Correa permission", "LarsCurfsCCSA3.0", "civertan license");
            if(in_array($LicenseShortName, $arr)) return $this->license['by-sa'];
            
            // for public domain - stripos
            $pd = array();
            $pd[] = "PD-US";
            $pd[] = "PD-NASA";
            $pd[] = "PD-NOAA";          //added Jul 9, 2018
            $pd[] = "PD-ineligible";    //added Jul 9, 2018
            $pd[] = "RatEatingSunflowerseads.jpg";
            $pd[] = "under public domain term";
            $pd[] = "From U.S. Fish and Wildlife";
            $pd[] = "Koninklijke Bibliotheek";
            $pd[] = "Latvian coins";
            $pd[] = "Russian museum photo";
            $pd[] = "USPresidentialTransition";
            foreach($pd as $p) {
                if(stripos($LicenseShortName, $p) !== false) return $this->license['public domain'];
            }

            // for invalid - stripos
            $inv = array();
            $inv[] = "editor=Kilom691";
            $inv[] = "LicenseReview|";
            $inv[] = "Frank FrägerGPL";
            $inv[] = "GPL|";
            $inv[] = "Remove this line and insert a license";
            $inv[] = "boilerplate metadata";
            $inv[] = "by-nc-nd";
            $inv[] = "PermissionOTRS";
            $inv[] = "You may choose one of the following licenses";
            $inv[] = "Mindaugas Urbonas";
            $inv[] = "Warsaw_ZOO_-_Bovidae_young";
            $inv[] = "plos";
            foreach($inv as $p) {
                if(stripos($LicenseShortName, $p) !== false) return "invalid";
            }

            //last resorts...
            if(stripos($LicenseShortName, "Information|Description") !== false) return "invalid";
            if(stripos($LicenseShortName, "Information |Description") !== false) return "invalid";
            if(stripos($LicenseShortName, "Information| Desc") !== false) return "invalid";
            if(stripos($LicenseShortName, "flickrreview|") !== false) return "invalid";
            if(stripos($LicenseShortName, "ImageNote|") !== false) return "invalid";
            if(stripos($LicenseShortName, "Check categories|") !== false) return "invalid";
            if(stripos($LicenseShortName, "LOC-image|") !== false) return "invalid";
            if(stripos($LicenseShortName, "gebruiker:Jürgen") !== false) return "invalid";
            
            // for invalid - exact match
            $arr = "Youtube|TimeScience,Imagicity,MaleneThyssenCredit,Fdrange,Arne and Bent Larsen license,Korea.net,Atelier graphique,KIT-license,Open Beelden,MUSE permission,volganet.ru,NoCoins,Stan Shebs photo,self,Multi-license,Link,WTFPL-1,En|A person kneeling next to a seal.,self2|FAL|,Fifty Birds,Laboratorio grafico,== Original upload log,Norwegian coat of arms,User:Arp/License,User:Erin Silversmith/Licence,trademark,benjamint5D,custom,Lang,User:Arjun01/I,Apache|Google,easy-border,LA2-Blitz,Autotranslate|1=1|,Frianvändning,Self,Location|57|47|35|N|152|23|39|W,OGL2,User:Pudding4brains/License,ScottForesman,FoP-Hungary,License,<!-- Ambox";
            $arr = explode(",", $arr);
            foreach($arr as $a) {
                if($LicenseShortName == $a) return "invalid"; //exact match
            }

            if(substr(strtolower($LicenseShortName),0,5) == "user:") return "invalid"; //starts with "User:"

            //start: added Jul 9, 2018 ----------------------------------------------------------------------------------------
            if($LicenseShortName == "I have contacted the author, who has agreed to release these pictures under CC-BY") return $this->license['by'];
            if($LicenseShortName == "CC BY-SA 4.0") return $this->license['by-sa'];
            if($LicenseShortName == "CC-BY") return $this->license['by'];
            //end: added Jul 9, 2018 ----------------------------------------------------------------------------------------

            $this->debug['blank_license'][$LicenseShortName] = ''; //utility debug - important
            /* finally if LicenseShortName is still undefined it will be considered 'invalid' */
            return "invalid";
        }
        return $license;
    }
    private function valid_license_YN($license)
    {
        $valid = array($this->license['public domain'], $this->license['by'], $this->license['by-nc'], $this->license['by-sa'], $this->license['by-nc-sa'], $this->license['no restrictions']);
        if(in_array($license, $valid)) return true;
        else                           return false;
    }
    // private function checkaddslashes($str){
    //     if(strpos(str_replace("\'",""," $str"),"'")!=false)
    //         return addslashes($str);
    //     else
    //         return $str;
    // }

    /* works but expensive
    if($html = Functions::lookup_with_cache("https://commons.wikimedia.org/wiki/File:".str_replace(" ", "_", $file), $options)) {
        //<a href="https://upload.wikimedia.org/wikipedia/commons/6/67/Western_Gorilla_area.png">
        if(preg_match_all("/<a href=\"https:\/\/upload.wikimedia.org(.*?)\"/ims", $html, $arr)) {
            $files2 = array_values(array_unique($arr[1]));
            $rek['media_url'] = "https://upload.wikimedia.org".$files2[0];
        }
    }
    */
    
    /*
    // for ImageDescription 1st option
    if(preg_match("/== \{\{int:filedesc\}\} ==(.*?)\}\}\\\n/ims", $wiki, $a)) {
        // echo "\n $a[1] \n";
        if(preg_match_all("/\'\'\'(.*?)<br>/ims", $a[1], $a2)) {
            $tmp = $a2[1];
            $i = 0;
            foreach($tmp as $t) {
                $t = str_replace("'", "", $t); $tmp[$i] = $t;
                if(stripos($t, "view book online") !== false) $tmp[$i] = null; //string is found
                if(stripos($t, "Text Appearing") !== false) $tmp[$i] = null; //string is found
                if(stripos($t, "Note About Images") !== false) $tmp[$i] = null; //string is found
                if(strlen($t) < 5) $tmp[$i] = null;
                $i++;
            }
            $tmp = array_filter($tmp);
            $i = 0;
            foreach($tmp as $t) {
                $tmp[$i] = self::wiki2html($t);
                $i++;
            }
            $rek['ImageDescription'] = trim(implode("<br>", $tmp));
        }
        
        //cases where ImageDescription is still blank
        // if($rek['pageid'] == "52428898")
        if(true) {
            //e.g. [pageid] => 52428898
            if(!@$rek['ImageDescription']) {
                if(preg_match("/\|Description=\{\{(.*?)\}\}/ims", $a[1]. "}}", $a2)) //2nd option
                {
                    $temp = $a2[1];
                    $arr = explode("|1=", $temp); //since "en|1=" or "ja|1=" etc...
                    $rek['ImageDescription'] = $arr[1];
                    if($rek['ImageDescription']) {}
                    elseif($rek['ImageDescription'] = $temp) {}
                    else {
                        // print_r($arr);
                        exit("\n $a[1] - investigate desc 111");
                    }
                }
                elseif($rek['ImageDescription'] = self::last_chance_for_description($wiki)) {
                    // print_r($rek);
                    // exit("\nstop muna 222\n");
                }
                else {
                    print("\n $wiki -->> investigate no ImageDescription 222\n");
                    return false;
                }
            }
            else echo "\nelicha\n";
            // print_r($rek);
        }
        // exit;
    }
    elseif(preg_match("/\|Description=\{\{(.*?)\}\}/ims", $wiki, $a)) //2nd option
    {
        $temp = $a[1];
        $arr = explode("|1=", $temp); //since "en|1=" or "ja|1=" etc...
        $rek['ImageDescription'] = $arr[1];
    }
    elseif($rek['ImageDescription'] = self::last_chance_for_description($wiki)) //3rd option
    {
        print_r($rek);
        // exit("\nstop muna\n");
    }
    else {
        print("\ninvestigate no ImageDescription 111\n");
        return false; // use API instead
    }
    */
}
?>