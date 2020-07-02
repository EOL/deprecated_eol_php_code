<?php
namespace php_active_record;
/* [SDR_all.php] */
class SummaryDataResourcesAllAPI
{
    public function __construct($folder, $folder_date)
    {
        $this->resource_id = $folder;
        /*
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        */
        $this->download_options = array('resource_id' => 'SDR_all', 'timeout' => 60*5, 'expire_seconds' => 60*60*24, 'cache' => 1, 'download_wait_time' => 1000000);
        $this->debug = array();

        $opendata_paths = self::get_opendata_paths();
        /* Terms relationships -> https://opendata.eol.org/dataset/terms-relationships --- as of Nov 12, 2019:  // print_r($opendata_paths); exit;
        Array(
        actually used:
            [preferred synonym] => https://opendata.eol.org/dataset/237b69b7-8aba-4cc4-8223-c433d700a1cc/resource/41f7fed1-3dc1-44d7-bbe5-6104156d1c1e/download/preferredsynonymnov8.csv
            [terms relationships for habitat values] => https://opendata.eol.org/dataset/237b69b7-8aba-4cc4-8223-c433d700a1cc/resource/c5ff5c62-a2ef-44be-9f59-88cd99bc8af2/download/habitat-parent-child-6-1.csv
            [terms relationships for geographic values] => https://opendata.eol.org/dataset/237b69b7-8aba-4cc4-8223-c433d700a1cc/resource/e1dcb51b-9a03-4069-b5bf-e18b6bc15798/download/geoterms-parent-child-feb19.csv
        not used:
            [parent child] => https://opendata.eol.org/dataset/237b69b7-8aba-4cc4-8223-c433d700a1cc/resource/f8036c30-f4ab-4796-8705-f3ccd20eb7e9/download/parent-child-nov8.csv
            [trait defaultUnit] => https://opendata.eol.org/dataset/237b69b7-8aba-4cc4-8223-c433d700a1cc/resource/d90b165f-92ad-44fe-aef2-ecd25721caac/download/defaultunits.csv
            [values terms] => https://opendata.eol.org/dataset/237b69b7-8aba-4cc4-8223-c433d700a1cc/resource/c464e148-a1d7-4e07-a1be-f920b99e4afb/download/values-terms-sept-7-2.csv
            [all relationships] => https://opendata.eol.org/dataset/237b69b7-8aba-4cc4-8223-c433d700a1cc/resource/2affbe5a-4bd6-4e8c-b19c-f65f9dafd078/download/all-relationships-7.csv
        )*/
        /* not used at the moment:
        $this->file['parent child']['path'] = "https://opendata.eol.org/dataset/237b69b7-8aba-4cc4-8223-c433d700a1cc/resource/f8036c30-f4ab-4796-8705-f3ccd20eb7e9/download/parent-child-aug-16-2.csv";
        $this->file['parent child']['path'] = "http://localhost/cp/summary data resources/parent-child-aug-16-2.csv";
        */
        $this->file['parent child']['fields'] = array('parent', 'child'); //used more simple words instead of: array('parent_term_URI', 'subclass_term_URI');
        
        $this->file['preferred synonym']['path'] = $opendata_paths['preferred synonym'];
        $this->file['preferred synonym']['fields'] = array('preferred', 'deprecated'); //used simple words instead of: array('preferred_term_URI', 'deprecated_term_URI');

        $this->file['parent child']['path_habitat'] = $opendata_paths['terms relationships for habitat values'];
        $this->file['parent child']['path_geoterms'] = $opendata_paths['terms relationships for geographic values'];
        
        /* not being used ATM. Will be used in production environment.
        // $this->dwca_file = "http://localhost/cp/summary data resources/carnivora_sample.tgz";
        $this->dwca_file = "http://localhost/cp/summary_data_resources/traits_all_201905.zip";
        */
        
        $this->report_file = CONTENT_RESOURCE_LOCAL_PATH . '/sample.txt';
        $this->temp_file = CONTENT_RESOURCE_LOCAL_PATH . '/temp.txt';
        
        /* ------------------ NEW June 4, 2019 ------------------ */
        $this->mysqli =& $GLOBALS['db_connection'];
        /* ------------------ NEW June 4, 2019 ------------------ */
        
        if(Functions::is_production()) { 
            $this->main_dir = "/extra/other_files/summary_data_resources/"; //eol-archive - never run here yet.
            $this->main_dir = "/u/scripts/data_files/summary_data_resources/"; //eol-backend2
            $this->working_dir = $this->main_dir."page_ids_".$folder_date."/";
            $this->trait_bank_folder = $this->main_dir.'trait_bank_'.$folder_date;
            $this->MySQL_append_files_dir = "MySQL_append_files_".$folder_date;
        }
        else {
            $this->main_dir = "/Volumes/AKiTiO4/web/cp/summary_data_resources/"; //Mac Mini
            // $this->main_dir = "/Users/eagbayani/Sites/cp/summary_data_resources/"; //MacBook
            /*
            $this->working_dir = $this->main_dir."page_ids/";
            $this->working_dir = $this->main_dir."page_ids_20190613/";  //to pertain using 13Jun2019 All Trait Export. But still using old DH
            $this->working_dir = $this->main_dir."page_ids_DHv11/";     //to pertain using DHv1.1
            $this->working_dir = $this->main_dir."page_ids_20190822/";
            $this->working_dir = $this->main_dir."page_ids_20191111/";
            */
            $this->working_dir = $this->main_dir."page_ids_".$folder_date."/";
            /*
            $this->trait_bank_folder = $this->main_dir.'trait_bank_20190613';
            $this->trait_bank_folder = $this->main_dir.'trait_bank_20190822';
            $this->trait_bank_folder = $this->main_dir.'trait_bank_20191111';
            */
            $this->trait_bank_folder = $this->main_dir.'trait_bank_'.$folder_date;
            $this->MySQL_append_files_dir = "MySQL_append_files_".$folder_date;
        }
        if(!is_dir($this->working_dir))       mkdir($this->working_dir);
        if(!is_dir($this->trait_bank_folder)) mkdir($this->trait_bank_folder);
        if(!is_dir($this->main_dir.$this->MySQL_append_files_dir)) mkdir($this->main_dir.$this->MySQL_append_files_dir);
        /* seems not used at all
        $this->jen_isvat = "/Volumes/AKiTiO4/web/cp/summary data resources/2018 09 08/jen_isvat.txt";
        */
        
        //for taxon summary
        if(Functions::is_production()) {
            $this->EOL_DH = "https://opendata.eol.org/dataset/b6bb0c9e-681f-4656-b6de-39aa3a82f2de/resource/b534cd22-d904-45e4-b0e2-aaf06cc0e2d6/download/eoldynamichierarchyv1revised.zip";
            $this->EOL_DH = "https://opendata.eol.org/dataset/b6bb0c9e-681f-4656-b6de-39aa3a82f2de/resource/bac4e11c-28ab-4038-9947-02d9f1b0329f/download/eoldynamichierarchywithlandmarks.zip";
        }
        else {
            /* not being used
            $this->EOL_DH = "http://localhost/cp/summary data resources/eoldynamichierarchyv1.zip";
            $this->EOL_DH = "http://localhost/cp/summary data resources/DH/eoldynamichierarchywithlandmarks.zip";
            */
        }
        $this->lifeState_statMeth_resource_file = CONTENT_RESOURCE_LOCAL_PATH . '/lifeStage_statMeth_resource.txt';
        
        $this->parentModeYN = false;
        $this->fullref = array();
        // $this->EATS_or_IsEatenBy_modeYN = false; //not implemented
        $this->synonyms_EATS = array('http://purl.obolibrary.org/obo/RO_0002470', 'http://purl.obolibrary.org/obo/RO_0002439', 'http://purl.obolibrary.org/obo/RO_0002444');
        $this->synonyms_IsEatenBy = array('http://purl.obolibrary.org/obo/RO_0002471', 'http://purl.obolibrary.org/obo/RO_0002458', 'http://purl.obolibrary.org/obo/RO_0002445');

        /* Aug 15'19: In addition to the two clusters, let's keep two more predicates, un-clustered: RO_0002453 (host of) RO_0002454 (has host) */
        $this->added_predicates = array('http://purl.obolibrary.org/obo/RO_0002453', 'http://purl.obolibrary.org/obo/RO_0002454');
        
        $this->magic8 = array(46702381, 2910700, 6061725, 2908256, 2913056);
        $this->magic8 = array_merge($this->magic8, array(8814528, 3014411, 10459935)); //makes it a magic 8

        /* Notes:
        With new records. Will write to DwCA.
        With existiing records. Will write to resource.txt.
        */
        $this->exemplary['REP'] = 'https://eol.org/schema/terms/representative';
        $this->exemplary['PRM'] = 'https://eol.org/schema/terms/primary';
        $this->exemplary['PRM and REP'] = 'https://eol.org/schema/terms/primary';
    }
    /*  basal values                                    taxon summary
        parent basal values                             parent taxon summary
        write resource file: basal values               write resource file: taxon summary
        write resource file: parent basal values        write resource file: parent taxon summary   */
    private function get_opendata_paths()
    {   $options = $this->download_options;
        $options['expire_seconds'] = 60*60*24; //1 day expires
        if($json = Functions::lookup_with_cache('https://opendata.eol.org/api/3/action/package_show?id=terms-relationships', $options)) {
            $o = json_decode($json);
            foreach($o->result->resources as $res) $final[$res->name] = $res->url;
        }
        /* print_r(array_keys($final)); exit;
            [0] => parent child
            [1] => preferred synonym
            [2] => trait defaultUnit
            [3] => terms relationships for habitat values
            [4] => terms relationships for geographic values
            [5] => values terms
            [6] => all relationships
        )*/
        if(!isset($final['preferred synonym']))                         exit("\n[preferred synonym] name not found\n");
        if(!isset($final['terms relationships for habitat values']))    exit("\n[terms relationships for habitat values] name not found\n");
        if(!isset($final['terms relationships for geographic values'])) exit("\n[terms relationships for geographic values] name not found\n");
        return $final;
    }
    private function setup_working_dir() //for production env only
    {
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($this->dwca_file, "traits.csv", array('timeout' => 60*10, 'expire_seconds' => 60*60*24*25)); //expires in 25 days
        return $paths;
    }
    private function working_dir()
    {
        /*
        if(Functions::is_production()) {
            if(!($info = self::setup_working_dir())) return;
        }
        else { //local development only
            // $info = Array('archive_path' => '/Library/WebServer/Documents/eol_php_code/tmp/dir_53125/carnivora_sample',
            //               'temp_dir'     => '/Library/WebServer/Documents/eol_php_code/tmp/dir_53125/');
            $info = Array('archive_path' => $this->trait_bank_folder,
                          'temp_dir'     => '/Library/WebServer/Documents/eol_php_code/tmp/not being used/'); //this field not being used ATM.
        }
        */
        $info = Array('archive_path' => $this->trait_bank_folder,
                      'temp_dir'     => '/Library/WebServer/Documents/eol_php_code/tmp/not being used/'); //this field not being used ATM.
        $this->main_paths = $info;
    }
    //==================================================================================================
    private function generate_children_of_taxa_using_parentsCSV()
    {
        $file = fopen($this->main_paths['archive_path'].'/pages.csv', 'r'); $i = 0;
        while(($line = fgetcsv($file)) !== FALSE) { $i++; 
            if($i == 1) $fields = $line;
            else {
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k]; $k++;
                }// print_r($rec); exit;
                /*Array(
                    [page_id] => 351759
                    [parent_id] => 9985
                    [canonical] => Batocera gerstaeckerii
                )*/
                $child = $rec['page_id']; $parent = $rec['parent_id'];
                $children_of[$parent][$child] = '';
            }
        }
        foreach($children_of as $parent => $children) {
            $temp = array_keys($children);
            $temp = array_diff($temp, array($parent)); //no child should be equal to parent
            $temp = self::my_atrim($temp);
            $final[$parent] = $temp;
        }
        $this->CSV_children_of = $final;
    }
    /* obsolete for ALL TRAIT EXPORT file
    private function generate_children_of_taxa_using_parentsCSV_old(){}
    */
    function print_parent_basal_values($dbase, $page_ids_param = false, $page_id_value = false, $debugModeYN = false)
    {   $this->dbname = 'traits_'.$dbase;
        self::initialize_basal_values(); 
        // self::generate_children_of_taxa_using_parentsCSV(); OBSOLETE
        $predicates = self::get_summ_process_type_given_pred('opposite', 'parents!A2:C1000', 2, 'basal value');
        if($GLOBALS['ENV_DEBUG']) print_r($predicates);

        if($debugModeYN) {
            foreach($predicates as $predicate) {
                $file = $this->main_dir."/".$this->MySQL_append_files_dir."/page_id_children_count_".pathinfo($predicate, PATHINFO_FILENAME).".txt";
                if(file_exists($file)) unlink($file);
            }
        }

        if($page_ids_param) {
            if($GLOBALS['ENV_DEBUG']) print_r($page_ids_param);
        }
        echo "\nGet page_ids for parent (BV)...\n";
        if($page_ids_param) {
            $page_ids = self::get_page_ids_andInfo_fromDH($page_ids_param);
            $resource_id = 'parent_basal_values_'.$page_id_value;
        }
        else {
            $page_ids = self::get_page_ids_andInfo_fromDH();
            $resource_id = 'parent_basal_values';
        }
        $total_page_ids = count($page_ids);
        $m = $total_page_ids/2; //for breakdown when caching...

        $WRITE = self::start_write2DwCA($resource_id, 'BV');
        
        // $excluded_page_ids = array('xxx', 'yyy'); //debug only

        /* for indicator */
        $total_predicates = count($predicates); $cnt_predicate = 0;

        foreach($predicates as $predicate) {
            $cnt_predicate++; /* for indicator */
            
            /* WRONG! Since page_id we want are parents. The parent might not have traits but its children might.
            echo "\nGet page_ids for...[$predicate]\n";
            $page_ids = self::get_page_ids_fromTraitsCSV_andInfo_fromDH(array($predicate));
            $total_page_ids = count($page_ids); $cnt_page_id = 0;
            */
            $cnt_page_id = 0;
            foreach($page_ids as $page_id => $taxon) {
                /* for indicator */
                $cnt_page_id++;
                debug("\nPredicates $cnt_predicate of $total_predicates");
                debug("\nPage IDs $cnt_page_id of $total_page_ids\n");

                // print_r($taxon); exit;
                // Array(
                //     [taxonRank] => order
                //     [Landmark] => 2
                // )
                
                // if(in_array($page_id, $excluded_page_ids)) continue; //debug only
                
                /* breakdown when caching:
                $cont = false;
                if($cnt_page_id >= 1 && $cnt_page_id < $m) $cont = true;
                // if($cnt_page_id >= $m && $cnt_page_id < $m*2) $cont = true;
                if(!$cont) continue;
                */
                
                if(!$page_id) continue;
                if(!@$taxon['taxonRank']) continue;
                if(@$taxon['taxonRank'] != "species" && $taxon['Landmark'] || @$taxon['taxonRank'] == "family") {
                    $this->original_nodes_parent = array(); //initialize for every 'parent basal values' process
                    if($ret = self::main_parents_basal_values($page_id, $predicate, $debugModeYN)) {
                        $ret['page_id'] = $page_id; $ret['predicate'] = $predicate;
                        self::write_resource_file_BasalValues($ret, $WRITE, 'parent');
                    }
                }
            }
        }
        fclose($WRITE); self::end_write2DwCA();
        if($GLOBALS['ENV_DEBUG']) print_r($this->debug);
        echo("\n-- end parents basal values --\n");
    }
    function test_parent_taxon_summary()
    {   $this->dbname = 'traits_TS'; //for the main TS method
        $this->parentModeYN = true;
        self::parse_DH(); self::initialize(); 
        // self::generate_children_of_taxa_using_parentsCSV(); OBSOLETE

        // $input[] = array('page_id' => 7662, 'predicate' => "http://purl.obolibrary.org/obo/RO_0002470"); //eats -> orig test case
        // $input[] = array('page_id' => 4528789, 'predicate' => "http://purl.obolibrary.org/obo/RO_0002470"); //eats
        // $input[] = array('page_id' => 7672, 'predicate' => "http://purl.obolibrary.org/obo/RO_0002470"); //eats //test case by Jen during dev. https://eol-jira.bibalex.org/browse/DATA-1777?focusedCommentId=62848&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-62848
        // $input[] = array('page_id' => 7665, 'predicate' => "http://purl.obolibrary.org/obo/RO_0002470"); //eats
        // $input[] = array('page_id' => 1018, 'predicate' => "http://purl.obolibrary.org/obo/RO_0002470"); //latest prob reported by Jen: https://eol-jira.bibalex.org/browse/DATA-1774?focusedCommentId=63616&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-63616
        // $input[] = array('page_id' => 1018, 'predicate' => implode(",", $this->synonyms_EATS));
        // $input[] = array('page_id' => 42208, 'predicate' => implode(",", $this->synonyms_IsEatenBy));
        // $input[] = array('page_id' => 1018, 'predicate' => "http://purl.obolibrary.org/obo/RO_0002439");
        // $input[] = array('page_id' => 1131, 'predicate' => implode(",", $this->synonyms_EATS));
        $input[] = array('page_id' => 7759, 'predicate' => "http://purl.obolibrary.org/obo/RO_0002454");

        $resource_id = 'test_parent_taxon_summary'; $WRITE = self::start_write2DwCA($resource_id, 'TS');
        foreach($input as $i) {
            $page_id = $i['page_id']; $predicate = $i['predicate'];
            $this->taxon_summary_parent_recs = array(); $this->ISVAT_TS = array();
            if($ret = self::main_parents_taxon_summary($page_id, $predicate, false)) { //3rd param false, means NOT debugModeYN
                $ret['page_id'] = $page_id; $ret['predicate'] = $predicate;
                if($GLOBALS['ENV_DEBUG']) {
                    echo "\n\nFinal result (parent taxon summary):"; print_r($ret);
                }
                self::write_resource_file_TaxonSummary($ret, $WRITE, 'parent');
            }
        }
        fclose($WRITE); self::end_write2DwCA();
        if($GLOBALS['ENV_DEBUG']) print_r($this->debug);
        echo("\n-- end method: parents: taxon summary --\n");
    }
    function print_parent_taxon_summary($dbase, $page_ids_param = false, $page_id_value = false, $debugModeYN = false)
    {   $this->dbname = 'traits_TS'; //for the main TS method
        $this->parentModeYN = true;
        self::parse_DH(); self::initialize();
        // self::generate_children_of_taxa_using_parentsCSV(); OBSOLETE
        
        // /* un-comment in real operation
        $predicates = self::get_summ_process_type_given_pred('opposite', 'parents!A2:C1000', 2, 'taxon summary'); 
        if($GLOBALS['ENV_DEBUG']) print_r($predicates);
        $predicates = self::group_predicates_if_needed($predicates); 
        if($GLOBALS['ENV_DEBUG']) print_r($predicates); //exit;
        // */
        
        /* during caching only - before 'combined predicates' scheme.
        // $predicates = array('http://purl.obolibrary.org/obo/RO_0002471', 'http://purl.obolibrary.org/obo/RO_0002623', 'http://purl.obolibrary.org/obo/RO_0002454');
        // $predicates = array('http://purl.obolibrary.org/obo/RO_0002557', 'http://purl.obolibrary.org/obo/RO_0002453', 'http://purl.obolibrary.org/obo/RO_0002445');
        // $predicates = array('http://purl.obolibrary.org/obo/RO_0002556', 'http://purl.obolibrary.org/obo/RO_0002458', 'http://purl.obolibrary.org/obo/RO_0002622');
        $predicates = array('http://purl.obolibrary.org/obo/RO_0002470', 'http://purl.obolibrary.org/obo/RO_0002444', 'http://purl.obolibrary.org/obo/RO_0002439');
        */
        
        /* WRONG! Since page_id we want are parents. The parent might not have traits but its children might.
        $page_ids = self::get_page_ids_fromTraitsCSV_andInfo_fromDH();
        */
        
        debug("\nGet page_ids for parent (TS)...\n");
        if($page_ids_param) {
            $page_ids = self::get_page_ids_andInfo_fromDH($page_ids_param);
            $resource_id = 'parent_taxon_summary_'.$page_id_value;
        }
        else {
            $page_ids = self::get_page_ids_andInfo_fromDH();
            $resource_id = 'parent_taxon_summary';
        }
        $total_page_ids = count($page_ids);
        $m = $total_page_ids/2; //for breakdown when caching...

        $WRITE = self::start_write2DwCA($resource_id, 'TS');
        /* for indicator */
        $total_predicates = count($predicates); $cnt_predicate = 0;

        // $excluded['2908256'] = ''; //will eventually might have this as permanent.
        $excluded['2913056'] = ''; //Life - definitely excluded
        
        foreach($predicates as $predicate) {
            $cnt_predicate++; /* for indicator */
            $cnt_page_id = 0;
            foreach($page_ids as $page_id => $taxon) {
                /* for indicator */
                $cnt_page_id++;
                debug("\nPredicates $cnt_predicate of $total_predicates");
                debug("\nPage IDs $cnt_page_id of $total_page_ids\n");
                // print_r($taxon); exit;
                // Array(
                //     [taxonRank] => order
                //     [Landmark] => 2
                // )

                /* breakdown when caching:
                $cont = false;
                if($cnt_page_id >= 1 && $cnt_page_id < $m) $cont = true;
                // if($cnt_page_id >= $m && $cnt_page_id < $m*2) $cont = true;
                if(!$cont) continue;
                */

                if(isset($excluded[$page_id])) continue;
                if(!$page_id) continue;
                if(!@$taxon['taxonRank']) continue;
                if(@$taxon['taxonRank'] != "species" && $taxon['Landmark'] || @$taxon['taxonRank'] == "family") {
                    $this->taxon_summary_parent_recs = array(); $this->ISVAT_TS = array();
                    if($ret = self::main_parents_taxon_summary($page_id, $predicate, $debugModeYN)) {
                        $ret['page_id'] = $page_id; $ret['predicate'] = $predicate;
                        if($GLOBALS['ENV_DEBUG']) {
                            echo "\n\nFinal result (parent taxon summary):"; print_r($ret);
                        }
                        self::write_resource_file_TaxonSummary($ret, $WRITE, 'parent');
                    }
                }
            }
        }
        fclose($WRITE); self::end_write2DwCA();
        if($GLOBALS['ENV_DEBUG']) print_r($this->debug);
        echo("\n-end print parent taxon summary-\n");
    }
    function print_basal_values($dbase)
    {   $this->dbname = 'traits_'.$dbase;
        //step 1: get all 'basal values' predicates:
        // /*
        $predicates = self::get_summ_process_type_given_pred('opposite', 'predicates!A2:F1000', 5, 'basal values');
        if($GLOBALS['ENV_DEBUG']) print_r($predicates);
        $resource_id = 'basal_values';
        // [0] => http://eol.org/schema/terms/Present
        // [1] => http://eol.org/schema/terms/Habitat
        // [2] => http://purl.obolibrary.org/obo/FLOPO_0900032
        // */
        /* can also run per single predicate:
        $predicates = array('http://eol.org/schema/terms/Present'); $resource_id = 'basal_values_Present';
        // $predicates = array('http://eol.org/schema/terms/Habitat'); $resource_id = 'basal_values_Habitat';
        */
        
        $WRITE = self::start_write2DwCA($resource_id, 'BV');
        self::initialize_basal_values();
        /* removed. Moved below
        $page_ids = self::get_page_ids_fromTraitsCSV_andInfo_fromDH();
        */

        /* for indicator */
        $total_predicates = count($predicates); $cnt_predicate = 0;

        foreach($predicates as $predicate) {
            $cnt_predicate++; /* for indicator */
            
            echo "\nGet page_ids for...[$predicate]\n";
            $page_ids = self::get_page_ids_fromTraitsCSV_andInfo_fromDH(array($predicate));
            $total_page_ids = count($page_ids); $cnt_page_id = 0;
            
            foreach($page_ids as $page_id => $taxon) {
                /* for indicator */
                $cnt_page_id++;
                debug("\nPredicates $cnt_predicate of $total_predicates");
                debug("\nPage IDs $cnt_page_id of $total_page_ids\n");
                
                if(!$page_id) continue;
                if(@$taxon['taxonRank'] == "species") {
                    if($ret = self::main_basal_values($page_id, $predicate)) {
                        $ret['page_id'] = $page_id; $ret['predicate'] = $predicate;
                        self::write_resource_file_BasalValues($ret, $WRITE, 'non-parent');
                    }
                }
                // if($cnt_page_id >= 10) break; //debug only
            }
        }
        fclose($WRITE); self::end_write2DwCA();
        if($GLOBALS['ENV_DEBUG']) print_r($this->debug);
        echo("\n-end print resource files (Basal values)-\n");
    }
    private function group_predicates_if_needed($predicates)
    {   $add_group_RO_0002470_YN = false; $add_group_RO_0002471_YN = false;
        $group_RO_0002470 = $this->synonyms_EATS;
        $group_RO_0002471 = $this->synonyms_IsEatenBy;
        $i = -1;
        foreach($predicates as $pred) { $i++;
            if(in_array($pred, $group_RO_0002470)) {
                $predicates[$i] = NULL;
                $add_group_RO_0002470_YN = true;
            }
            elseif(in_array($pred, $group_RO_0002471)) {
                $predicates[$i] = NULL;
                $add_group_RO_0002471_YN = true;
            }
            elseif(in_array($pred, $this->added_predicates)) $final[] = $pred; //those additional predicates. Outside of all other discarded predicates.
            /* per Jen - discard all other predicates
            else $final[] = $pred; //comment this line if you want to get only the "combined predicates"
            */
        }
        if($add_group_RO_0002470_YN) $final[] = implode(",", $group_RO_0002470);
        if($add_group_RO_0002471_YN) $final[] = implode(",", $group_RO_0002471);
        return $final;
    }
    function print_taxon_summary($dbase)
    {   $this->dbname = 'traits_'.$dbase;
        $this->parentModeYN = false;
        //step 1: get all 'taxon summary' predicates:
        $predicates = self::get_summ_process_type_given_pred('opposite', 'predicates!A2:F1000', 5, 'taxon summary'); echo "\nPredicates: ".count($predicates)."\n";
        $predicates = self::group_predicates_if_needed($predicates); 
        if($GLOBALS['ENV_DEBUG']) print_r($predicates); //exit;
        self::initialize(); 
        /* removed bec it is getting page_ids without predicate in question. Moved below.
        $page_ids = self::get_page_ids_fromTraitsCSV_andInfo_fromDH($predicates);
        */
        //--------initialize start
        self::parse_DH();
        $resource_id = 'taxon_summary'; $WRITE = self::start_write2DwCA($resource_id, 'TS');

        // $predicates = array('http://purl.obolibrary.org/obo/RO_0002470'); //debug only force assign
        /* for indicator */
        $total_predicates = count($predicates); $cnt_predicate = 0;

        foreach($predicates as $predicate) {
            $cnt_predicate++; $cnt_page_id = 0; /* for indicator */
            
            echo "\nGet page_ids for...[$predicate][$this->dbname]\n";
            $page_ids = self::get_page_ids_fromTraitsCSV_andInfo_fromDH(array($predicate));
            $total_page_ids = count($page_ids); $cnt_page_id = 0;
            
            foreach($page_ids as $page_id => $taxon) { //print_r($taxon);
                /* for indicator */
                $cnt_page_id++;
                debug("\nPredicates $cnt_predicate of $total_predicates");
                debug("\nPage IDs $cnt_page_id of $total_page_ids\n");
                
                if(!$page_id) continue;
                if(@$taxon['taxonRank'] == "species") {
                    $this->ISVAT_TS = array();
                    if($ret = self::main_taxon_summary($page_id, $predicate)) {
                        $ret['page_id'] = $page_id; $ret['predicate'] = $predicate;
                        if($GLOBALS['ENV_DEBUG']) {
                            echo "\n\nFinal result (taxon summary):"; print_r($ret);
                        }
                        self::write_resource_file_TaxonSummary($ret, $WRITE, 'non-parent');
                    }
                }
            }
        }
        fclose($WRITE); self::end_write2DwCA();
        echo("\n-end print taxon summary-\n");
    }
    function print_lifeStage_statMeth($dbase)
    {   $this->dbname = 'traits_'.$dbase;
        //step 1: get all 'lifestage and statistical method' predicates:
        $predicates = self::get_summ_process_type_given_pred('opposite', 'predicates!A2:F1000', 5, 'lifestage and statistical method'); //3rd param is $item index no.
        self::initialize();
        /* removed bec it is getting page_ids without predicate in question. Moved below.
        $page_ids = self::get_page_ids_fromTraitsCSV_andInfo_fromDH();
        */
        //--------initialize start
        self::parse_DH();
        //write to file
        if(!($WRITE = Functions::file_open($this->lifeState_statMeth_resource_file, "w"))) return;
        $row = array("Page ID", 'eol_pk', "Predicate", "Label");
        fwrite($WRITE, implode("\t", $row). "\n");
        
        /* for indicator */
        $total_predicates = count($predicates); $cnt_predicate = 0;
        
        foreach($predicates as $predicate) {
            $cnt_predicate++; $cnt_page_id = 0; /* for indicator */
            
            echo "\nGet page_ids for...[$predicate]\n";
            $page_ids = self::get_page_ids_fromTraitsCSV_andInfo_fromDH(array($predicate));
            $total_page_ids = count($page_ids); $cnt_page_id = 0;
            
            foreach($page_ids as $page_id => $taxon) {
                /* for indicator */
                $cnt_page_id++;
                debug("\nPredicates $cnt_predicate of $total_predicates");
                debug("\nPage IDs $cnt_page_id of $total_page_ids\n");

                //print_r($taxon);
                if(!$page_id) continue;
                if(@$taxon['taxonRank'] == "species") {
                    if($ret = self::main_lifestage_statMeth($page_id, $predicate)) {
                        $row = array($page_id, $ret['recs'][0]['eol_pk'], $predicate, $this->exemplary[$ret['label']]);
                        fwrite($WRITE, implode("\t", $row). "\n");
                    }
                }
            }
        }
        fclose($WRITE);
        echo("\n-end print resource files (lifestage+statMeth)-\n");
    }
    function pre_parent_basal_values()
    {   /*
        INSERT INTO page_ids_Present SELECT DISTINCT t.page_id from SDR.traits_BV t WHERE t.predicate = 'http://eol.org/schema/terms/Present'
        INSERT INTO page_ids_Habitat SELECT DISTINCT t.page_id from SDR.traits_BV t WHERE t.predicate = 'http://eol.org/schema/terms/Habitat';
        INSERT INTO page_ids_FLOPO_0900032 SELECT DISTINCT t.page_id from SDR.traits_BV t WHERE t.predicate = 'http://purl.obolibrary.org/obo/FLOPO_0900032';
        */
        $recs['page_ids_Present'] = 'http://eol.org/schema/terms/Present';
        $recs['page_ids_Habitat'] = 'http://eol.org/schema/terms/Habitat';
        $recs['page_ids_FLOPO_0900032'] = 'http://purl.obolibrary.org/obo/FLOPO_0900032';
        foreach($recs as $table => $predicate) {
            //truncate first
            $sql = "TRUNCATE TABLE SDR.".$table.";";
            if($result = $this->mysqli->query($sql)) echo "\nTable truncated [$table] OK.\n";
            //insert data
            /* commented 2019Nov11. Can no longer accommodate big files, memory-wise I think. Used manual, notes in SDR_all_readmeli.txt.
            $sql = "INSERT INTO SDR.".$table." SELECT DISTINCT t.page_id from SDR.traits_BV t WHERE t.predicate = '".$predicate."'";
            if($result = $this->mysqli->query($sql)) echo "\nTable updated [$table] OK.\n";
            else {
                echo "\nsomething went wrong: [$table]\n";
                print_r($result);
            }
            */
            /* Jun 23, 2020. Removed manual step. All are now scripted.
            step1: save unique page_id in a text file
            step2: insert to respective tables
            */
            //step1: save unique page_id in a text file
            $sql = "SELECT t.page_id from SDR.traits_BV t WHERE t.predicate = '".$predicate."'";
            $result = $this->mysqli->query($sql);
            $page_ids = array();
            while($result && $rec=$result->fetch_assoc()) $page_ids[$rec['page_id']] = '';
            $page_ids = array_keys($page_ids);
            $destination = $this->main_dir."/".$this->MySQL_append_files_dir."/$table".".txt";
            if(!($WRITE = Functions::file_open($destination, "w"))) return;
            fwrite($WRITE, implode("\n", $page_ids). "\n"); fclose($WRITE);
            //step2: insert to respective tables
            self::append_to_MySQL_table($table, $destination);
            echo "\nTotal rows [$table]: ".self::count_table_rows($table)."\n";
        }
    }
    function test_parent_basal_values($dbase, $debugModeYN = false)
    {   //self::parse_DH(); //this was needed for $this->report_SampleSize
        /* this was manually done for now: Jun 9, 2019 - for ALL TRAIT EXPORT - readmeli.txt for more details
        INSERT INTO page_ids_Present SELECT DISTINCT t.page_id from SDR.traits_BV t WHERE t.predicate = 'http://eol.org/schema/terms/Present'
        INSERT INTO page_ids_Habitat SELECT DISTINCT t.page_id from SDR.traits_BV t WHERE t.predicate = 'http://eol.org/schema/terms/Habitat';
        INSERT INTO page_ids_FLOPO_0900032 SELECT DISTINCT t.page_id from SDR.traits_BV t WHERE t.predicate = 'http://purl.obolibrary.org/obo/FLOPO_0900032';
        */
        $this->dbname = 'traits_'.$dbase;
        // { folder test case is [2018 10 02 basal values parent]}  ============================================================================================================
        self::initialize_basal_values(); 
        /* this generates: $this->CSV_children_of  --- Not needed here anymore since children comes form cache txt file.
        self::generate_children_of_taxa_using_parentsCSV();
        */
        // self::parse_DH(); //seems not needed here...?
        
        $input[] = array('page_id' => 7662, 'predicate' => "http://eol.org/schema/terms/Habitat"); //habitat includes -> orig test case
        // $input[] = array('page_id' => 7673, 'predicate' => "http://eol.org/schema/terms/Habitat"); //habitat includes -> questioned by Jen, missing ref under biblio field
        // $input[] = array('page_id' => 7665, 'predicate' => "http://eol.org/schema/terms/Habitat"); //habitat includes -> questioned by Jen, missing ref under biblio field
        // $input[] = array('page_id' => 7666, 'predicate' => "http://eol.org/schema/terms/Habitat"); //habitat includes
        // $input[] = array('page_id' => 7662, 'predicate' => "http://eol.org/schema/terms/Present"); //infinite loop
        // $input[] = array('page_id' => 164, 'predicate' => "http://eol.org/schema/terms/Present"); //e.g. reached level 16. May need to extend more. LONG process.

        $resource_id = 'test_parent_BV'; $WRITE = self::start_write2DwCA($resource_id, 'BV');
        foreach($input as $i) {
            $page_id = $i['page_id']; $predicate = $i['predicate'];
            $this->original_nodes_parent = array(); //initialize for every 'parent basal values' process
            if($ret = self::main_parents_basal_values($page_id, $predicate, $debugModeYN)) {
                if($GLOBALS['ENV_DEBUG']) {echo "\nFinal (parent basal values): "; print_r($ret);}
                $ret['page_id'] = $page_id; $ret['predicate'] = $predicate;
                self::write_resource_file_BasalValues($ret, $WRITE, 'parent');
            }
        }
        fclose($WRITE); self::end_write2DwCA();
        if($this->debug) {
            if($GLOBALS['ENV_DEBUG']) print_r($this->debug);
        }
        echo("\n-- end method: parents: basal values --\n");
        // print_r($this->report_SampleSize);
        // print_r($this->report_SampleSize['http://www.wikidata.org/entity/Q106447']);
        // print_r($this->report_SampleSize['http://www.geonames.org/10861432']); exit;
    }
    function test_lifeStage_statMeth($dbase)
    {   $this->dbname = 'traits_'.$dbase;
        self::initialize();
        $predicate = "http://purl.obolibrary.org/obo/VT_0001259";
        $page_ids = array(347436, 347438, 46559130);
        // $page_ids = array(328674);
        
        // /* test during All Trait Export
        $predicate = "http://eol.org/schema/terms/ArmPlusDiscLength";
        $page_ids = array(45327507);
        // */

        //write to file
        if(!($WRITE = Functions::file_open($this->lifeState_statMeth_resource_file, "w"))) return;
        $row = array("Page ID", 'eol_pk', "Predicate", "Label");
        fwrite($WRITE, implode("\t", $row). "\n");

        foreach($page_ids as $page_id) {
            $ret = self::main_lifestage_statMeth($page_id, $predicate);
            // print_r($ret); exit;
            $row = array($page_id, $ret['recs'][0]['eol_pk'], $predicate, $this->exemplary[$ret['label']]);
            fwrite($WRITE, implode("\t", $row). "\n");
        }
        fclose($WRITE);
        echo("\n-- end method: lifestage_statMeth --\n");
    }
    function test_taxon_summary($dbase)
    {   $this->dbname = 'traits_'.$dbase;
        $this->parentModeYN = false;
        self::parse_DH(); self::initialize();
        $resource_id = 'test_TS'; $WRITE = self::start_write2DwCA($resource_id, 'TS');

        // $page_id = 328607; $predicate = "http://purl.obolibrary.org/obo/RO_0002439"; //preys on - no record
        // $page_id = 7673; $predicate = "http://purl.obolibrary.org/obo/RO_0002470"; //eats
        // $page_id = 46559118; $predicate = "http://purl.obolibrary.org/obo/RO_0002439"; //preys on
        // $page_id = 328607; $predicate = "http://purl.obolibrary.org/obo/RO_0002470"; //eats
        // $page_id = 46559162; $predicate = "http://purl.obolibrary.org/obo/RO_0002470"; //eats
        // $page_id = 46559217; $predicate = "http://purl.obolibrary.org/obo/RO_0002470"; //eats
        // $input[] = array('page_id' => 46559118, 'predicate' => "http://purl.obolibrary.org/obo/RO_0002439"); //preys on
        // $input[] = array('page_id' => 328609, 'predicate' => "http://purl.obolibrary.org/obo/RO_0002470"); //eats
        // $input[] = array('page_id' => 328598, 'predicate' => "http://purl.obolibrary.org/obo/RO_0002470"); //eats //test case when writing to DwCA
        // $input[] = array('page_id' => 47054812, 'predicate' => "http://purl.obolibrary.org/obo/RO_0002470"); //eats //supposedly no records
        // $input[] = array('page_id' => 1020198, 'predicate' => "http://purl.obolibrary.org/obo/RO_0002470"); //eats - latest Jun 20, 2019 comment by Jen
        // $input[] = array('page_id' => 1018, 'predicate' => "http://purl.obolibrary.org/obo/RO_0002470"); //eats - latest Jul 16, 2019 comment by Jen - No records as of Aug 14'19
        // $input[] = array('page_id' => 328598, 'predicate' => "http://purl.obolibrary.org/obo/RO_0002470,http://purl.obolibrary.org/obo/RO_0002439,http://purl.obolibrary.org/obo/RO_0002444");
        // $input[] = array('page_id' => 328607, 'predicate' => "http://purl.obolibrary.org/obo/RO_0002439"); //preys on - with rec but no DwCA as of Aug 14'19
        // $input[] = array('page_id' => 328607, 'predicate' => "http://purl.obolibrary.org/obo/RO_0002470,http://purl.obolibrary.org/obo/RO_0002439,http://purl.obolibrary.org/obo/RO_0002444");

        // only existing no DwCA
        // $input[] = array('page_id' => 1000277, 'predicate' => "http://purl.obolibrary.org/obo/RO_0002470,http://purl.obolibrary.org/obo/RO_0002439,http://purl.obolibrary.org/obo/RO_0002444");
        // $input[] = array('page_id' => 1000277, 'predicate' => "http://purl.obolibrary.org/obo/RO_0002471,http://purl.obolibrary.org/obo/RO_0002458,http://purl.obolibrary.org/obo/RO_0002445");
        // $input[] = array('page_id' => 1000277, 'predicate' => "http://purl.obolibrary.org/obo/RO_0002453");
        // $input[] = array('page_id' => 1000277, 'predicate' => "http://purl.obolibrary.org/obo/RO_0002454"); no records OK
        // $input[] = array('page_id' => 972688, 'predicate' => "http://purl.obolibrary.org/obo/RO_0002454"); no records from 2019Aug22 traits
        $input[] = array('page_id' => 7673, 'predicate' => "http://purl.obolibrary.org/obo/RO_0002470,http://purl.obolibrary.org/obo/RO_0002439,http://purl.obolibrary.org/obo/RO_0002444");
        
        foreach($input as $i) {
            $page_id = $i['page_id']; $predicate = $i['predicate'];
            $this->ISVAT_TS = array();
            if($ret = self::main_taxon_summary($page_id, $predicate)) {
                $ret['page_id'] = $page_id; $ret['predicate'] = $predicate;
                if($GLOBALS['ENV_DEBUG']) {echo "\n\nFinal result (taxon summary):"; print_r($ret);}
                self::write_resource_file_TaxonSummary($ret, $WRITE, 'non-parent');
            }
        }
        fclose($WRITE); self::end_write2DwCA();
        echo("\n-- end method: 'taxon summary' --\n");
    }
    function test_basal_values($dbase)
    {   $this->dbname = 'traits_'.$dbase;
        self::initialize_basal_values();
        $resource_id = 'test_BV'; $WRITE = self::start_write2DwCA($resource_id, 'BV');
        
        // $input[] = array('page_id' => 7662, 'predicate' => "http://eol.org/schema/terms/Present");
        // $input[] = array('page_id' => 328607, 'predicate' => "http://eol.org/schema/terms/Present");
        // $input[] = array('page_id' => 328682, 'predicate' => "http://eol.org/schema/terms/Present");
        // $input[] = array('page_id' => 328609, 'predicate' => "http://eol.org/schema/terms/Present");
        // $input[] = array('page_id' => 328598, 'predicate' => "http://eol.org/schema/terms/Present");
        // $input[] = array('page_id' => 4442159, 'predicate' => "http://eol.org/schema/terms/Present");
        // $input[] = array('page_id' => 46559197, 'predicate' => "http://eol.org/schema/terms/Present");
        // $input[] = array('page_id' => 46559217, 'predicate' => "http://eol.org/schema/terms/Present");
        
        $input[] = array('page_id' => 7662, 'predicate' => "http://eol.org/schema/terms/Habitat"); //first test case     //test case with new 2nd deletion step
        // $input[] = array('page_id' => 328607, 'predicate' => "http://eol.org/schema/terms/Habitat");
        // $input[] = array('page_id' => 328682, 'predicate' => "http://eol.org/schema/terms/Habitat");
        // $input[] = array('page_id' => 328609, 'predicate' => "http://eol.org/schema/terms/Habitat");                        //test case with new first & second deletion steps
        // $input[] = array('page_id' => 4442159, 'predicate' => "http://eol.org/schema/terms/Habitat");
        // $input[] = array('page_id' => 46559197, 'predicate' => "http://eol.org/schema/terms/Habitat");

        // $children = array(328598, 328609, 46559217, 328682, 328607); //force assignment, development only
        // $input[] = array('page_id' => 328598, 'predicate' => "http://eol.org/schema/terms/Habitat");
        // $input[] = array('page_id' => 46559154, 'predicate' => "http://eol.org/schema/terms/Habitat"); //reached step 7

        // $input[] = array('page_id' => 46559217, 'predicate' => "http://eol.org/schema/terms/Habitat"); //test case for write resource
        // $input[] = array('page_id' => 7673, 'predicate' => "http://eol.org/schema/terms/Habitat"); //questioned by Jen, missing ref under biblio field

        // $input[] = array('page_id' => 1037781, 'predicate' => "http://eol.org/schema/terms/Present"); //left seems infinite loop
        // $input[] = array('page_id' => 328604, 'predicate' => "http://eol.org/schema/terms/Present"); //left seems infinite loop
        
        // page_id: 1004183 | predicate: [http://eol.org/schema/terms/Present]
        // $input[] = array('page_id' => 1004183, 'predicate' => "http://eol.org/schema/terms/Present"); // Jun 6, 2019 - seems infinite loop

        foreach($input as $i) {
            /* temp block
            $this->taxon_ids = array(); $this->reference_ids = array(); $this->occurrence_ids = array();
            
            //write to DwCA
            $this->resource_id = $i['page_id']."_".pathinfo($i['predicate'], PATHINFO_BASENAME);
            $file = CONTENT_RESOURCE_LOCAL_PATH . "/".$this->resource_id."_resource.txt";

            $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $this->resource_id . '_working/';
            $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));

            //write to file
            if(!($WRITE = Functions::file_open($file, "w"))) return;
            $row = array("Page ID", 'eol_pk', "Value URI", "Label");
            fwrite($WRITE, implode("\t", $row). "\n");
            */
            
            $page_id = $i['page_id']; $predicate = $i['predicate'];
            if($ret = self::main_basal_values($page_id, $predicate)) {
                $ret['page_id'] = $page_id; $ret['predicate'] = $predicate;
                if($GLOBALS['ENV_DEBUG']) print_r($ret);
                self::write_resource_file_BasalValues($ret, $WRITE, 'non-parent');
            }
            
            /* temp block
            fclose($WRITE);
            $this->archive_builder->finalize(TRUE);
            if(file_exists($this->path_to_archive_directory."taxon.tab")) Functions::finalize_dwca_resource($this->resource_id);
            */
        }
        fclose($WRITE); self::end_write2DwCA();
        if($GLOBALS['ENV_DEBUG']) print_r($this->debug);
        echo("\n-- end method: basal values --\n");
        // */
    }
    function build_up_children_cache($sought_page_id = false) //DH total recs 2,724,941 | 2,237,554 Jun 9, 2019
    {
        self::initialize(); self::generate_children_of_taxa_using_parentsCSV(); //this generates: $this->CSV_children_of

        /* good debug - proces only 1 page_id
        if(true) {
            $page_id = '39311345';
            $page_id = '7662';
            $page_id = '8880788';
            $page_id = '10459935';

            // $page_id = '2913056'; //Life
            // $page_id = '2910700';
            // $page_id = '2908256';
            
            //NEW: so only 1 connector processes 1 page_id
            $txt_file = self::get_txt_path_by_page_id($page_id, "_processing.txt");
            echo "\n$txt_file\n";
            if(file_exists($txt_file)) //continue; //being processed...
            {
                echo "\nbeing processed already... Program will terminate.\n";
                exit;
            }
            else {
                $WRITE = fopen($txt_file, 'w'); fclose($WRITE);
            }
            self::get_children_from_txt_file($page_id);
            unlink($txt_file);
        }
        exit("\nend muna\n");
        */
        //New Aug 8, 2019 - new now that we're going to use DH v1.1. Didn't have $exclude in last DH ver.
        $exclude = array(1, 281, 42430800, 2913056, 6061725, 2908256, 2910700, 51939910, 52126308, 49306824); //above Animalia and Plantae, inclusive
        
        if($sought_page_id) $param_4DH = array($sought_page_id);
        else                $param_4DH = array();
        
        $page_ids = self::get_page_ids_andInfo_fromDH($param_4DH);
        $i = 0; $total = count($page_ids); $k = 0; $m = 2237554/10;
        foreach($page_ids as $page_id => $taxon) { $k++;
            if($GLOBALS['ENV_DEBUG']) echo "\n".number_format($k)." of ".number_format($total);
            else {
                if(($k % 2000) == 0) echo "\n".number_format($k)." of ".number_format($total);
            }
            // if($k < 2205100) continue; //debug only - force to skip many records
            // if($page_id == 2634370) continue; //force to ignore a page_id

            if(in_array($page_id, $exclude)) {
                // /* temporary just to delete e.g. 1_processing.txt
                $txt_file = self::get_txt_path_by_page_id($page_id, "_processing.txt");
                if(file_exists($txt_file)) unlink($txt_file);
                // */
                continue;
            }

            //================ FOR PARENT BASAL VALUES ================ 
            if(!$page_id) continue;
            if(!@$taxon['taxonRank']) continue;
            if(@$taxon['taxonRank'] != "species" && $taxon['Landmark'] || @$taxon['taxonRank'] == "family") { $i++; //ORIG
                // /* NEW: so only 1 connector processes 1 page_id
                $txt_file = self::get_txt_path_by_page_id($page_id, "_processing.txt");
                if(file_exists($txt_file)) continue; //being processed...
                else {
                    $WRITE = fopen($txt_file, 'w'); fclose($WRITE);
                }
                // */
                self::get_children_from_txt_file($page_id);
                unlink($txt_file);
            }
            //================ FOR PARENT BASAL VALUES end ================ 

            //================ FOR PARENT TAXON SUMMARY ================ seems same for BASAL VALUES so just ignore.
            /*
            if(!$page_id) continue;
            if(!@$taxon['taxonRank']) continue;
            if(@$taxon['taxonRank'] != "species" && $taxon['Landmark'] || @$taxon['taxonRank'] == "family") {}
            */
            //================ FOR PARENT TAXON SUMMARY end ================ 

            // if($i >= 2) break; //debug only
        }
        if($GLOBALS['ENV_DEBUG']) {
            if($this->debug) print_r($this->debug);
        }
        /* test only: single page_id
        $main_page_id = 7665; //7662;
        self::get_children_from_txt_file($main_page_id);
        */
        /*
        self::initialize();
        self::investigate_traits_csv(); exit;
        */
    }
    function get_children_from_txt_file($page_id, $withCreateYN = true)
    {
        // $this->working_dir = "/Volumes/AKiTiO4/web/cp/summary data resources/page_ids/"; //debug only force assign
        $txt_file = self::get_txt_path_by_page_id($page_id, "_ch.txt"); //echo "\n$txt_file\n";
        if(file_exists($txt_file)) {
            debug("\nExists: [$page_id] $txt_file\n");
            $json = trim(file_get_contents($txt_file));
            $arr = json_decode($json, true);
            return $arr;
        }
        else {
            if($withCreateYN) {
                debug("\nNot yet generated, creating now...");
                $children = self::get_CSV_children_of($page_id); //print_r($children);
                if($children) {
                    $WRITE = fopen($txt_file, 'w');
                    fwrite($WRITE, json_encode($children)."\n");
                    fclose($WRITE);
                }
                else {
                    debug("\nNo children for [$page_id] [$txt_file]\n");
                    // if($page_id == '39311345') exit;
                }
            }
        }
    }
    private function get_childrenTBP_from_txt_file_multiple($predicates, $param) //get childrenTBP from multiple predicates
    {   /*Array(
            [0] => http://purl.obolibrary.org/obo/RO_0002470
            [1] => http://purl.obolibrary.org/obo/RO_0002439
            [2] => http://purl.obolibrary.org/obo/RO_0002444
        )*/
        $final = array();
        foreach($predicates as $predicate) {
            $arr = self::get_childrenTBP_from_txt_file($param['main_page_id'], $param['children'], $predicate, $param['table']); // print_r($arr);
            if($arr) $final = array_merge($final, $arr);
        }
        $final = array_filter($final); //remove null arrays
        $final = array_unique($final); //make unique
        $final = array_values($final); //reindex key
        return $final;
    }
    function get_childrenTBP_from_txt_file($main_page_id, $children, $predicate, $table = '') //4th param $table is for parent TS
    {
        // /* new Aug 14'19
        $ret = self::format_value_for_sql($predicate);
        if($ret['count'] == 'single')       {} //proceed below - orig
        elseif($ret['count'] == 'multiple') {
            $predicates = explode(",", str_replace("'","",$ret['value'])); 
            if($GLOBALS['ENV_DEBUG']) print_r($predicates);
            return self::get_childrenTBP_from_txt_file_multiple($predicates, array('main_page_id' => $main_page_id, 'children' => $children, 'table' => $table));
        }
        // */
        
        $txt_file = self::get_txt_path_by_page_id($main_page_id, "_chTBP_".pathinfo($predicate, PATHINFO_FILENAME).".txt"); //echo "\n$txt_file\n";
        if(file_exists($txt_file)) {
            /* Special utility - use if u want to DELETE children TBP txt file. e.g. 2948715_chTBP_Habitat.txt
            unlink($txt_file); return; //elixAug9
            */
            debug("\nExists: [$main_page_id] $txt_file\n");
            $json = trim(file_get_contents($txt_file));
            $arr = json_decode($json, true);
            return $arr;
        }
        else {
            echo "\nNot yet generated children TBP, creating now...\n";
            /* IMPORTANT NEW STEP: Jun 9, 2019 -> only children with trait data equal to $predicate should be processed so it speeds up.  *******************************************
            E.g. page_id = 163 has many children without Present trait. But it is being checked one by one, very slow process.
            */
            $new_children = array();
            foreach($children as $page_id) {
                if(self::page_id_has_trait_for_this_predicate($page_id, $predicate, $table)) $new_children[] = $page_id;
            }
            $children = $new_children; unset($new_children);
            echo "\n*New Children of [$main_page_id] with pred.: ".count($children)."\n"; //print_r($children); *New Children of [164] with pred: 218233
            /* .  ******************************************* end IMPORTANT NEW STEP */

            /* *******************************************
            all children with trait and predicate accordingly = 218233 [164][http://eol.org/schema/terms/Present]
            ***WILL NOW FILTER THIS TO JUST RANK 'species'
            */
            if($table != 'traits_TSp') {
                $new_children = array();
                foreach($children as $page_id) {
                    if(self::page_id_has_rank_equal_to($page_id, 'species')) $new_children[] = $page_id;
                }
                $children = $new_children; unset($new_children);
                echo "\n*New Children of rank species [$main_page_id]: ".count($children)."\n"; //print_r($children); *New Children of rank species [164]: 205167
            }
            // exit; //debug only
            /* ******************************************* */
            
            if($children) {
                $WRITE = fopen($txt_file, 'w'); fwrite($WRITE, json_encode($children)."\n"); fclose($WRITE);
                if(!$WRITE) self::write_investigate_file($txt_file);
                return $children;
            }
            else {
                $WRITE = fopen($txt_file, 'w'); fwrite($WRITE, json_encode(array())."\n"); fclose($WRITE);
                if(!$WRITE) self::write_investigate_file($txt_file);
            }
            return false;
        }
    }
    private function write_investigate_file($txt_file)
    {
        $file_write = $this->main_dir."/z_Eli/investigate_folder.txt"; 
        $WRITE = fopen($file_write, "a"); fwrite($WRITE, $txt_file."\n"); fclose($WRITE);
    }
    private function start_write2DwCA($resource_id, $method)
    {
        $this->resource_id = $resource_id;
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $this->resource_id . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        
        $file = CONTENT_RESOURCE_LOCAL_PATH . "/".$resource_id."_resource.txt";
        if(!($WRITE = Functions::file_open($file, "w"))) return;
        if($method == 'BV') $row = array("Page ID", 'eol_pk', "Value URI", "Label");
        if($method == 'TS') $row = array("Page ID", 'eol_pk', "object_page_id", "Label");
        fwrite($WRITE, implode("\t", $row). "\n");
        return $WRITE;
    }
    private function end_write2DwCA()
    {
        $this->archive_builder->finalize(TRUE);
        if(file_exists($this->path_to_archive_directory."taxon.tab")) Functions::finalize_dwca_resource($this->resource_id);
    }
    //############################################################################################ start write resource file - method = 'parent taxon summary'
    /* was never used in All Trait Export
    private function gen_children_of_taxon_usingDH()
    {
        // test
        // $EOLid = 298458; $EOLid = 110698;
        // $EOLid = 103449;
        // echo " - EOLid: [$EOLid] "; 
        // if($anc = self::get_ancestry_via_DH($EOLid, false)) { //2nd param false means that get all ancestry not just landmark taxa
        //     array_unshift($anc, $EOLid); //prepend $val front of $anc, $val becomes 1st record
        //     
        //     // print_r($anc);
        //     // foreach($anc as $page_id) {
        //     //     $json_file = self::get_txt_path_by_page_id($page_id, "_c.txt");
        //     //     echo "\n[$page_id] $json_file";
        //     // }
        //     
        //     self::gen_children_of_taxon_given_ancestry($anc);
        // }
        // else echo "\nNo ancestry [$val]\n";
        // exit("\nstop muna\n");
        
        $info = self::prep_DH(); $i = 0; $m = 2724950/5;
        foreach(new FileIterator($info['archive_path'].$info['tables']['taxa']) as $line_number => $line) {
            $line = explode("\t", $line); $i++;
            if($i == 1) $fields = $line;
            else {
                
                // breakdown when caching:
                // $cont = false;
                // if($i >= 1 && $i < $m) $cont = true;
                // // if($i >= $m && $i < $m*2) $cont = true;
                // // if($i >= $m*2 && $i < $m*3) $cont = true;
                // // if($i >= $m*3 && $i < $m*4) $cont = true;
                // // if($i >= $m*4 && $i < $m*5) $cont = true;
                // if(!$cont) continue;
                
                if(!$line[0]) break;
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k]; $k++;
                }
                // print_r($rec); //exit;
                if($EOLid = $rec['EOLid']) {
                    echo "\n".number_format($i);
                    echo " - EOLid: [$EOLid] "; 
                    if($anc = self::get_ancestry_via_DH($EOLid, false)) { //2nd param false means that get all ancestry not just landmark taxa
                        array_unshift($anc, $EOLid); //prepend $val front of $anc, $val becomes 1st record
                        self::gen_children_of_taxon_given_ancestry($anc);
                    }
                    else echo "\nNo ancestry [$val]\n";
                }
                else echo "\nNo EOLid\n";
                if(($i % 1000) == 0) echo "\n".number_format($i);
            }
        }
    }
    */
    private function gen_children_of_taxon_given_ancestry($anc)
    {
        $anc = array_reverse($anc);
        $temp = $anc;
        foreach($anc as $id) {
            array_shift($temp);
            if($temp) self::write_ancestry_to_file($id, $temp);
        }
    }
    private function write_ancestry_to_file($page_id, $children)
    {
        $children = array_filter($children); //lame try to delete unwanted null entries... did this when looking for answers why always ...-append... occurs
        $json_file = self::get_txt_path_by_page_id($page_id, "_c.txt");
        if(file_exists($json_file)) {
            // echo "\n[$page_id] $json_file\n";
            $json = trim(file_get_contents($json_file));
            $arr = json_decode($json, true);
            if(!is_array($arr) && is_null($arr)) $arr = array();
            echo "\nstarting count: ".count($arr)."\n";                 //good debug
            // /* ver 2
            $cont_write = false;
            foreach($children as $child) {
                if(!$child) continue;
                if(!in_array($child, $arr)) {
                    $arr[] = $child;
                    $cont_write = true;
                }
            }
            if($cont_write) { 
                echo "\nending count: ".count($arr)."\n";               //good debug
                echo " -append ";
                // echo "\n[$page_id] $json_file\n";
                $WRITE = fopen($json_file, 'w'); fwrite($WRITE, json_encode($arr)); fclose($WRITE);
            }
            else echo " -no add ";
            // */
        }
        else {
            echo " -new ";
            $WRITE = fopen($json_file, 'w'); fwrite($WRITE, json_encode($children)); fclose($WRITE);
            // echo "\nNEW: writing json [$page_id] [$json_file] [".count($children)."]";
        }
    }
    function get_summ_process_type_given_pred($order = "normal", $range = 'predicates!A2:F1000', $item_index_no = 5, $filter) //sheet found here: https://docs.google.com/spreadsheets/u/1/d/1Er57xyxT_-EZud3mNkTBn0fZ9yZi_01qtbwwdDkEsA0/edit?usp=sharing
    {
        require_library('connectors/GoogleClientAPI');
        $func = new GoogleClientAPI(); //get_declared_classes(); will give you how to access all available classes
        $params['spreadsheetID'] = '1Er57xyxT_-EZud3mNkTBn0fZ9yZi_01qtbwwdDkEsA0';
        $params['range']         = $range; //where "A" is the starting column, "C" is the ending column, and "1" is the starting row.
        $arr = $func->access_google_sheet($params);
        //start massage array
        foreach($arr as $item) {
            if($uri = $item[0]) {
                if($order == "normal") $final[$uri] = @$item[$item_index_no];
                elseif($order == "opposite") {
                    if($var = @$item[$item_index_no]) $final[$var][] = $uri;
                }
            }
        }
        return $final[$filter];
    }
    //############################################################################################ start write resource file - method = 'taxon summary'
    private function write_resource_file_TaxonSummary($info, $WRITE, $parentYN) //previously $ret
    {
        $page_id = $info['page_id']; $predicate = $info['predicate'];
        /*step 1: get all eol_pks */
        if($parentYN == "non-parent") $recs = self::assemble_recs_for_page_id_from_text_file($page_id, $predicate);
        elseif($parentYN == "parent") $recs = $this->taxon_summary_parent_recs;
        else exit("\nNot go here...\n");
        // echo "\nHere 001...\n";
        debug("\nAssembled recs to check if id exists (object_page_id): "); //print_r($recs)
        $found = array(); $existing_records_for_writing = array(); $eol_pks = array();
        foreach($info['Selected'] as $id) {
            foreach($recs as $rec) { debug(" -".$rec['object_page_id']."- "); //good debug
                if($rec['object_page_id'] == $id) {
                    $eol_pks[$rec['eol_pk']] = '';
                    $found[] = $id;
                    //write to file block
                    $row = array($page_id, $rec['eol_pk'], $id, $this->exemplary[$info['Label']]);
                    $existing_records_for_writing[] = $row;
                }
            }
        }
        if($existing_records_for_writing) self::adjust_if_needed_and_write_existing_records($existing_records_for_writing, $WRITE);
        $eol_pks = array_keys($eol_pks);
        debug("\n [$parentYN] Original recs: ".count($recs)."\n");
        if($new_records = array_diff($info['Selected'], $found)) {
            if($GLOBALS['ENV_DEBUG']) {
                echo "\nTS - Not found in traits.csv object_page_id for this page_id and predicate. Create new record(s): included in DwCA"; print_r($new_records); //good debug
            }

            // /* magic 8 now applied above (orig) and now here as well. Also now for both 'parent' and 'non-parent'
            $new_records = array_diff($new_records, $this->magic8);
            if($GLOBALS['ENV_DEBUG']) {echo "\n after magic 8: "; print_r($new_records);}
            // */
            
            /* ver 1
            $refs = self::get_refs_from_metadata_csv($eol_pks); //get refs for new records, same refs for all new records
            self::create_archive_TaxonSummary($new_records, $refs, $info);
            */
            // $this->info = $info;             //for debug only
            // /* ver 2 OK
            $new_records_refs = self::assemble_refs_for_new_recs_TS($new_records, $recs);
            self::create_archive_TaxonSummary($new_records, $new_records_refs, $info);
            // */
        }
        else debug("\nNo new records. Will not write to DwCA.\n");
    }
    //================================================================================================================================= start new scheme
    private function assemble_refs_for_new_recs_TS($new_records, $orig_recs)
    {   $refs_of = array();
        foreach($new_records as $new) {
            $descendants_of[$new] = self::get_from_ISVAT_descendants_of_TS($new);                              //1. get from $this->ISVAT which are descendants of $new
            $eol_pks_of[$new] = self::get_eol_pks_of_new_from_origRecs_TS($orig_recs, $descendants_of[$new]);  //2. get eol_pks from orig recs
            $refs_of[$new] = self::get_refs_from_metadata_csv($eol_pks_of[$new]);                           //3. get refs using eol_pks
        }
        // print_r($descendants_of); print_r($eol_pks_of); print_r($refs_of); //good debug - Jul 29 commented
        return $refs_of;
    }
    private function get_from_ISVAT_descendants_of_TS($term) //working well
    {
        $final = array();
        foreach($this->ISVAT_TS as $key => $arr) {
            if(in_array($term, $arr)) $final[$key] = '';
            if($key == $term) $final[$key] = '';
        }
        if($final) return array_keys($final);
        return array();
    }
    private function get_eol_pks_of_new_from_origRecs_TS($recs, $descendants)
    {
        $eol_pks = array();
        foreach($recs as $rec) {
            if(in_array($rec['object_page_id'], $descendants)) {
                $eol_pks[$rec['eol_pk']] = '';
            }
        }
        return array_keys($eol_pks);
    }
    //================================================================================================================================= end new scheme
    private function get_basename_from_multiple_predicates($csv) //e.g. http://purl.obolibrary.org/obo/RO_0002470,http://purl.obolibrary.org/obo/RO_0002439,http://purl.obolibrary.org/obo/RO_0002444
    {   $type = "";
        $arr = explode(",", $csv);
        foreach($arr as $url) {
            $basename = pathinfo($url, PATHINFO_BASENAME);
            if($type) $type .= "_".$basename;
            else      $type = $basename;
        }
        if($type     == 'RO_0002470_RO_0002439_RO_0002444') return "group_EATS";
        elseif($type == 'RO_0002471_RO_0002458_RO_0002445') return "group_IsEatenBy";
        else exit("\nUndefined group!\n");
        return $type; //simple implementation if desired.
    }
    private function create_archive_TaxonSummary($new_records, $new_records_refs, $ret)
    {   /*Array(
        [root] => 46557930
        [root label] => PRM
        [Selected] => Array(
                [0] => 46557930
                [1] => 207661
            )
        [Label] => REP
        [page_id] => 328598
        [predicate] => http://purl.obolibrary.org/obo/RO_0002470
        )*/
        $taxon_id = $ret['page_id'];
        $taxon = $this->add_taxon(array('page_id' => $taxon_id));

        // /* new Aug 14'19
        // predicate can be http://purl.obolibrary.org/obo/RO_0002470,http://purl.obolibrary.org/obo/RO_0002439,http://purl.obolibrary.org/obo/RO_0002444
        $ret2 = self::format_value_for_sql($ret['predicate']);
        if($ret2['count'] == 'single')       $type = pathinfo($ret['predicate'], PATHINFO_BASENAME); //orig - single predicate value
        elseif($ret2['count'] == 'multiple') $type = self::get_basename_from_multiple_predicates($ret['predicate']);
        // print_r($ret); exit("\nprint_r ret\n[$type]\n"); //debug only
        // */

        /* ver 1
        $reference_ids = '';
        if($refs) {
            if($reference_ids = self::create_references($refs)) $reference_ids = implode("; ", $reference_ids);
        }*/

        $occurrence_id = $this->add_occurrence_assoc($taxon_id, "$type"); // used in 'Summary Data Resources' implementation. Not the strategy used in EOL Associations
        foreach($new_records as $taxon_name_id) {
            /* $occurrence_id = $this->add_occurrence_assoc($taxon_id, $taxon_name_id . "_$type"); */ // used in orig EOL Associations implementation.
            $related_taxon = $this->add_taxon(array('page_id' => $taxon_name_id));
            $related_occurrence_id = $this->add_occurrence_assoc($related_taxon->taxonID, $taxon_id . "_$type");
            $a = new \eol_schema\Association_specific(); //take note used with '_specific'

            $a->label = self::get_assoc_label($ret, $taxon_name_id);
            $orig_label = $a->label;
            $a->label = $this->exemplary[$a->label];

            $a->occurrenceID = $occurrence_id;
            $a->associationType = self::new_assocType_basedOn_EATS_or_IsEatenBy($ret['predicate']);
            $a->targetOccurrenceID = $related_occurrence_id;
            
            // $a->referenceID = $reference_ids; --> ver 1
            if($reference_ids = self::create_references($new_records_refs[$taxon_name_id])) $a->referenceID = implode("; ", $reference_ids); //ver 2 OK
            
            $a->measurementDeterminedDate = date("Y-M-d");
            
            // $a->source = "https://beta.eol.org/pages/$taxon->taxonID/data?predicate=".$ret['predicate']; //e.g. https://beta.eol.org/pages/46559217/data?predicate=http://eol.org/schema/terms/Habitat
            $a->source = "https://eol.org/terms/search_results?utf8=✓&term_query[clade_id]=".$taxon->taxonID."&term_query[filters_attributes][0][pred_uri]=".$ret['predicate']."&term_query[filters_attributes][0][op]=is_any&term_query[result_type]=record&commit=Search";
            
            $a->measurementMethod   = 'summary of records available in EOL';
            $a->associationID = Functions::generate_measurementID($a, $this->resource_id, 'association');
            $this->archive_builder->write_object_to_file($a);
            $parent = $a->associationID;
            
            /* CREATING child records ------------------------------------------------------------------------------------
            Jen: https://eol-jira.bibalex.org/browse/DATA-1777?focusedCommentId=63921&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-63921
            After consult with Jeremy, we've decided to handle the REP and PRM tags as normal metadata,
            so let's make them child records, with 
            measurementType=https://eol.org/schema/terms/exemplary and
            measurementValue= https://eol.org/schema/terms/representative OR https://eol.org/schema/terms/primary
            
            Jen: https://eol-jira.bibalex.org/browse/DATA-1777?focusedCommentId=63938&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-63938
            you add a measurementOrFact file just for the child records. You use columns
            http://eol.org/schema/associationID	http://rs.tdwg.org/dwc/terms/measurementType	http://rs.tdwg.org/dwc/terms/measurementValue
            and any others that you need. the /associationID column acts like /parentMeasurementID, referring back to the records in the Associations file.
            */
            

            $m = new \eol_schema\MeasurementOrFact_specific(); //NOTE: used a new class MeasurementOrFact_specific() for non-standard fields like 'm->label'
            $m->measurementType     = 'https://eol.org/schema/terms/exemplary';
            $m->measurementValue    = $a->label;
            $m->parentMeasurementID = $parent;
            $m->measurementRemarks = $orig_label;
            $m->measurementID = Functions::generate_measurementID($m, $this->resource_id);
            $this->archive_builder->write_object_to_file($m);
        }
    }
    private function new_assocType_basedOn_EATS_or_IsEatenBy($predicate)
    {   /* Let's not do this for all association types. Let's have two sets of values for each source taxon. One set will be 
        http://purl.obolibrary.org/obo/RO_0002470, http://purl.obolibrary.org/obo/RO_0002439 and http://purl.obolibrary.org/obo/RO_0002444.
        Records for all three can all be combined into one tree for the hierarchy construction. When making new records for this set, 
        the predicate should be http://purl.obolibrary.org/obo/RO_0002470, but when labelling existing records, they can be any of the three
        AND, similarly,
        http://purl.obolibrary.org/obo/RO_0002471, http://purl.obolibrary.org/obo/RO_0002458 and http://purl.obolibrary.org/obo/RO_0002445
        with http://purl.obolibrary.org/obo/RO_0002471 being the predicate for any newly created records
        */
        
        // if($this->EATS_or_IsEatenBy_modeYN) {
        if(true) { //e.g. $predicate = "http://purl.obolibrary.org/obo/RO_0002470,http://purl.obolibrary.org/obo/RO_0002439,http://purl.obolibrary.org/obo/RO_0002444"
            foreach($this->synonyms_EATS as $p) {
                if(stripos($predicate, $p) !== false) return "http://purl.obolibrary.org/obo/RO_0002470"; //string is found
            }
            foreach($this->synonyms_IsEatenBy as $p) {
                if(stripos($predicate, $p) !== false) return "http://purl.obolibrary.org/obo/RO_0002471"; //string is found
            }
        }
        return $predicate;
    }
    /*
    private function write_resource_file_TaxonSummary_v1($ret)
    {   Array(
        [root] => 46557930
        [root label] => PRM
        [Selected] => Array(
                [0] => 46557930
                [1] => 207661
            )
        [Label] => REP
        [page_id] => 328598
        [predicate] => http://purl.obolibrary.org/obo/RO_0002470
        )
        $taxon_id = $ret['page_id'];
        $taxon = $this->add_taxon(array('page_id' => $taxon_id));
        $type = pathinfo($ret['predicate'], PATHINFO_BASENAME);
        $reference_ids = '';
        if($refs = @$ret['refs']) {
            if($reference_ids = self::create_references($refs)) $reference_ids = implode("; ", $reference_ids);
        }
        $occurrence_id = $this->add_occurrence_assoc($taxon_id, "$type"); // used in 'Summary Data Resources' implementation. Not the strategy used in EOL Associations
        foreach($ret['Selected'] as $taxon_name_id) {
            // --- $occurrence_id = $this->add_occurrence_assoc($taxon_id, $taxon_name_id . "_$type"); // used in orig EOL Associations implementation.
            $related_taxon = $this->add_taxon(array('page_id' => $taxon_name_id));
            $related_occurrence_id = $this->add_occurrence_assoc($related_taxon->taxonID, $taxon_id . "_$type");
            $a = new \eol_schema\Association_specific(); //take note used with '_specific'
            $a->label = self::get_assoc_label($ret, $taxon_name_id);
            $a->occurrenceID = $occurrence_id;
            $a->associationType = $ret['predicate'];
            $a->targetOccurrenceID = $related_occurrence_id;
            $a->referenceID = $reference_ids;
            $a->measurementDeterminedDate = date("Y-M-d");
            $a->source = "https://beta.eol.org/pages/$taxon->taxonID/data?predicate=".$ret['predicate']; //e.g. https://beta.eol.org/pages/46559217/data?predicate=http://eol.org/schema/terms/Habitat
            $a->measurementMethod   = 'summary of records available in EOL';
            $a->associationID = Functions::generate_measurementID($a, $this->resource_id, 'association');
            $this->archive_builder->write_object_to_file($a);
        }
    }
    */
    private function get_assoc_label($ret, $taxon_name_id)
    {
        if($ret['root'] == $taxon_name_id) return $ret['root label'];
        else                               return $ret['Label'];
    }
    private function add_occurrence_assoc($taxon_id, $label)
    {
        $occurrence_id = $taxon_id . "_" . str_replace(" ", "_", $label);
        $o = new \eol_schema\Occurrence();
        $o->occurrenceID = $occurrence_id;
        $o->taxonID = $taxon_id;
        if(isset($this->occurrence_ids[$occurrence_id])) return $occurrence_id;
        else {
            $this->archive_builder->write_object_to_file($o);
            $this->occurrence_ids[$occurrence_id] = '';
            return $occurrence_id;
        }
    }
    //############################################################################################ start write resource file - method = 'basal values'
    private function write_resource_file_BasalValues($info, $WRITE, $parentYN)
    {   /*when creating new records (non-tips), find and deduplicate all references and bibliographicCitations for each tip record below the node, and attach as references. MeasurementMethod= "summary of records available in EOL". Construct a source link to EOL, eg: https://beta.eol.org/pages/46559143/data */
        $page_id = $info['page_id']; $predicate = $info['predicate'];
        /*step 1: get all eol_pks */
        
        if($parentYN == "non-parent") $recs = self::assemble_recs_for_page_id_from_text_file($page_id, $predicate, array('value_uri')); //3rd param array is required_fields
        elseif($parentYN == "parent") $recs = $this->basal_values_parent_recs;
        else exit("\nNot go here...\n");
        
        debug("\n recs in writing: ".count($recs)); //print_r($recs);
        $found = array(); $existing_records_for_writing = array(); $eol_pks = array();
        foreach($info['Selected'] as $id) {
            foreach($recs as $rec) {
                if($rec['value_uri'] == $id) {
                    $eol_pks[$rec['eol_pk']] = '';
                    $found[] = $id;
                    // /* write to file block
                    $row = array($page_id, $rec['eol_pk'], $id, $this->exemplary[$info['label']]); //, $rec
                    $existing_records_for_writing[] = $row;
                    // */
                }
            }
        }
        if($existing_records_for_writing) self::adjust_if_needed_and_write_existing_records($existing_records_for_writing, $WRITE);
        
        $eol_pks = array_keys($eol_pks);
        if($new_records = array_diff($info['Selected'], $found)) {
            if($GLOBALS['ENV_DEBUG']) {echo "\nBV - Not found in traits.csv. Create new record(s) [$page_id] [$predicate]: "; print_r($new_records);} //good debug
            /* ver 1 obsolete
            $refs = self::get_refs_from_metadata_csv($eol_pks); //get refs for new records, same refs for all new records
            self::create_archive($new_records, $refs, $info);
            */
            // $this->info = $info;             //for debug only
            /* ver 2 OK */
            $new_records_refs = self::assemble_refs_for_new_recs($new_records, $recs);
            self::create_archive($new_records, $new_records_refs, $info);
        }
        else debug("\nNo new records. Will not write to DwCA.\n");
    }
    private function assemble_refs_for_new_recs($new_records, $orig_recs)
    {   $refs_of = array();
        foreach($new_records as $new) {
            $descendants_of[$new] = self::get_from_ISVAT_descendants_of($new);                              //1. get from $this->ISVAT which are descendants of $new
            $eol_pks_of[$new] = self::get_eol_pks_of_new_from_origRecs($orig_recs, $descendants_of[$new]);  //2. get eol_pks from orig recs
            $refs_of[$new] = self::get_refs_from_metadata_csv($eol_pks_of[$new]);                           //3. get refs using eol_pks
        }
        // print_r($descendants_of); print_r($eol_pks_of); print_r($refs_of); //good debug - Jul 29 commented
        // foreach($refs_of as $key => $arr) echo "\n".count($arr)."\n"; //good debug for counting refs
        return $refs_of;
    }
    private function get_eol_pks_of_new_from_origRecs($recs, $descendants)
    {   $eol_pks = array();
        foreach($recs as $rec) {
            if(in_array($rec['value_uri'], $descendants)) {
                $eol_pks[$rec['eol_pk']] = '';
            }
        }
        // echo "\nDONE: get_eol_pks_of_new_from_origRecs()\n";
        return array_keys($eol_pks);
    }
    private function get_from_ISVAT_descendants_of($term) //working well
    {   // echo "\nSTART: get_from_ISVAT_descendants_of($term)\n";
        $desc_all = array();
        $desc_x = array($term);
        $preserve_unique_desc_x = array(); //prevent infinite loop e.g. page_id: 1004183 | predicate: [http://eol.org/schema/terms/Present]
        while($desc_x) {
            if($GLOBALS['ENV_DEBUG']) {echo "\ndesc_x count: ".count($desc_x)." "; print_r($desc_x);}
            $temp = array(); //new June 28, 2020
            foreach($this->ISVAT as $a) {
                if(in_array($a[0], $desc_x)) {
                    $temp[$a[1]] = '';
                    $desc_all[$a[1]] = '';
                    if($GLOBALS['ENV_DEBUG']) print_r($a);
                }
            }
            $temp = array_keys($temp);
            $desc_x = $temp;
            $temp = array();
            
            /* prevent infinite loop START */ //added Jun 6, 2019 - still on a testing phase, hopefully doesn't mess original behavior/algorithm
            $json = json_encode($desc_x);
            if(!isset($preserve_unique_desc_x[$json])) $preserve_unique_desc_x[$json] = '';
            else break;
            /* prevent infinite loop END */
        }
        // echo "\nDONE: get_from_ISVAT_descendants_of()\n";
        return array_keys($desc_all);
    }
    private function adjust_if_needed_and_write_existing_records($rows, $WRITE)
    {   /*For selected values available in multiple records, let's do an order of precedence based on metadata, with an arbitrary tie-breaker (which you'll need in this case; sorry!). 
          Please count the number of references attached to each candidate record, add 1 if there is a bibliographicCitation for the record, and choose the record with the highest number. 
          In case of a tie, break it with any arbitrary method you like.
        */
        /* forced test data
        $rows = array();
        $rows[] = array(46559217, 'R96-PK42940163', 'http://eol.org/schema/terms/temperate_grasslands_savannas_and_shrublands', 'REP');
        $rows[] = array(46559217, 'R512-PK24322763', 'http://purl.obolibrary.org/obo/ENVO_00000078', 'REP');
        $rows[] = array(46559217, 'R512-PK24381251', 'http://purl.obolibrary.org/obo/ENVO_00000220', 'REP');
        $rows[] = array(46559217, 'R512-PK24428398', 'http://purl.obolibrary.org/obo/ENVO_00000446', 'REP');
        // $rows[] = array(46559217, 'R512-PK24244192', 'http://purl.obolibrary.org/obo/ENVO_00000446', 'REP');
        // $rows[] = array(46559217, 'R512-PK23617608', 'http://purl.obolibrary.org/obo/ENVO_00000572', 'REP');
        // $rows[] = array(46559217, 'R512-PK24249316', 'http://purl.obolibrary.org/obo/ENVO_00002033', 'REP');
        // $rows[] = array(46559217, 'R512-PK24569594', 'http://purl.obolibrary.org/obo/ENVO_00000446', 'REP');
        */
        if($GLOBALS['ENV_DEBUG']) {echo "\nExisting records: ".count($rows); print_r($rows);} //good debug - Jul 29 commented
        /*[1169] => Array(
                    [0] => 7662
                    [1] => R512-PK71414812
                    [2] => http://purl.obolibrary.org/obo/ENVO_00000090
                    [3] => REP
                )
            [1170] => Array(
                    [0] => 7662
                    [1] => R512-PK71559177
                    [2] => http://purl.obolibrary.org/obo/ENVO_00000090
                    [3] => REP
                )
        */
        //step 1: get counts
        foreach($rows as $row) {
            @$counts[$row[2]]++; //VERY IMPORTANT: the row[2] must be the value_uri for BV and object_page_id for TS
        }
        if($GLOBALS['ENV_DEBUG']) {echo "\ncounts: (to be included in resources.txt) "; print_r($counts);}
        //step 2: get eol_pk if count > 1 -> meaning multiple records
        foreach($rows as $row) {
            $eol_pk = $row[1];
            $value_uri = $row[2];
            if($counts[$value_uri] > 1) @$study[$value_uri][] = $eol_pk;
        }
        if(!isset($study)) { debug("\nNo selected values available in multiple records.\n");
            foreach($rows as $row) fwrite($WRITE, implode("\t", $row). "\n");
            return;
        }
        //step 3: choose 1 among multiple eol_pks based on metadata (references + biblio). If same count just pick one.
        foreach($study as $value_uri => $eol_pks) {
            //get refs for each eol_pk
            $total = count($eol_pks); $i = 0;
            foreach($eol_pks as $eol_pk) {
                $i++; //echo "\n[$i of $total] for ref count";
                $refs_of_eol_pk[$eol_pk][] = self::get_refs_from_metadata_csv(array($eol_pk)); //just for ref counts
            }
        }
        // echo "\n refs_of_eol_pk: "; print_r($refs_of_eol_pk);
        // echo "\n study: "; print_r($study);
        foreach($study as $value_uri => $eol_pks) {
            $ref_counts = array();
            foreach($eol_pks as $eol_pk) {
                $ref_counts[$eol_pk] = count($refs_of_eol_pk[$eol_pk]);
            }
            //compare counts and remove lesser, if equal just pick one
            // echo "\nref_counts: "; print_r($ref_counts);
            $remain[$value_uri][] = self::get_key_of_arr_with_biggest_value($ref_counts);
        }
        // echo "\n remain: ";print_r($remain);
        foreach($study as $value_uri => $eol_pks) {
            $remove[$value_uri] = array_diff($eol_pks, $remain[$value_uri]);
        }
        // echo "\n remove: ";print_r($remove);
        
        debug("\norig rows count: ".count($rows));
        //step 4: remove duplicate records
        $i = 0;
        foreach($rows as $row)
        {   /*Array(
            [0] => 46559217
            [1] => R512-PK24467582
            [2] => http://purl.obolibrary.org/obo/ENVO_00000447
            [3] => REP
            )*/
            $eol_pk = $row[1]; $value_uri = $row[2];
            if($eol_pk_2_remove = @$remove[$value_uri]) {
                if(in_array($eol_pk, $eol_pk_2_remove)) $rows[$i] = null;
            }
            $i++;
        }
        $rows = array_filter($rows);
        debug("\nnew rows count: ".count($rows)."\n");
        //step 5: finally writing the rows
        foreach($rows as $row) fwrite($WRITE, implode("\t", $row). "\n");
        return;
    }
    private function add_taxon($info)
    {
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID         = $info['page_id'];
        $taxon->EOL_taxonID     = $info['page_id'];
        if(!isset($this->taxon_ids[$taxon->taxonID])) {
            $this->taxon_ids[$taxon->taxonID] = '';
            $this->archive_builder->write_object_to_file($taxon);
        }
        return $taxon;
    }
    private function create_archive($records, $new_records_refs, $info) //EXTENSION_URL: http://rs.tdwg.org/dwc/xsd/tdwg_dwcterms.xsd
    {
        $taxon = self::add_taxon($info);
        foreach($records as $value_uri) { //e.g. http://purl.obolibrary.org/obo/ENVO_01001125
            $predicate = $info['predicate'];
            //start structured data
            $rec['label'] = $info['label'];
            $rec['taxon_id'] = $taxon->taxonID;
            $rec['measurementType'] = $predicate;
            $rec['measurementValue'] = $value_uri;
            /* ver 1
            if($reference_ids = self::create_references($refs)) $rec['referenceID'] = implode("; ", $reference_ids);
            */
            if($reference_ids = self::create_references($new_records_refs[$value_uri])) $rec['referenceID'] = implode("; ", $reference_ids);
            $rec['catnum'] = $taxon->taxonID . "_" . pathinfo($predicate, PATHINFO_BASENAME) . "_" . pathinfo($value_uri, PATHINFO_BASENAME);
            // $rec['source'] = "https://beta.eol.org/pages/$taxon->taxonID/data?predicate=$predicate"; //e.g. https://beta.eol.org/pages/46559217/data?predicate=http://eol.org/schema/terms/Habitat
            $rec['source'] = "https://eol.org/terms/search_results?utf8=✓&term_query[clade_id]=".$taxon->taxonID."&term_query[filters_attributes][0][pred_uri]=".$predicate."&term_query[filters_attributes][0][op]=is_any&term_query[result_type]=record&commit=Search";
            // if($predicate == "http://eol.org/schema/terms/Habitat") self::add_string_types($rec);
            // elseif($predicate == "xxx")                             self::add_string_types($rec);
            self::add_string_types($rec);
        }
    }
    private function create_references($refs)
    {
        if(!$refs) return array();
        $reference_ids = array();
        foreach($refs as $ref_no => $full_ref) {
            $r = new \eol_schema\Reference();
            $r->identifier = $ref_no;
            $r->full_reference = $full_ref;
            $reference_ids[$r->identifier] = '';
            if(!isset($this->reference_ids[$r->identifier])) {
               $this->reference_ids[$r->identifier] = '';
               $this->archive_builder->write_object_to_file($r);
            }
        }
        return array_keys($reference_ids);
    }
    private function add_string_types($rec)
    {    /* print_r($rec); exit;
        Array(
            [label] => REP
            [taxon_id] => 7662
            [measurementType] => http://eol.org/schema/terms/Habitat
            [measurementValue] => http://eol.org/schema/terms/tropicalOrSubtropical
            [referenceID] => Reference-8897485; Reference-8988813; Reference-8915923; Reference-8978513; Reference-8955183; Reference-9019732; Reference-9054048; Reference-8998034; Reference-8979709; Reference-9071187; Reference-8999304; Reference-9048194; Reference-9023393; Reference-8896872; Reference-9041016; Reference-9046402; Reference-8945923; Reference-8991510; Reference-8924857; Reference-8904324; Reference-8900960; Reference-9054856; Reference-8932820; Reference-8975638; Reference-9006498; Reference-8962800; Reference-8934165; Reference-8928949; Reference-8896127; Reference-9046735; Reference-8928611; Reference-8993500; Reference-8906103; Reference-9077711; Reference-9061335; Reference-8960306; Reference-8900908; Reference-8992856; Reference-9012815; Reference-8898492; Reference-9028101; Reference-8907922; Reference-8892883; Reference-9063926; Reference-8912922; Reference-8905730; Reference-8963159; Reference-9028455; Reference-8949447; Reference-8906133; Reference-9019754; Reference-8938068; Reference-8890699; Reference-8897298; Reference-9080110; Reference-9072789; Reference-8907551; Reference-8965897; Reference-8990692; Reference-9065817; Reference-8965865; Reference-8971274; Reference-8939062; Reference-9042468; Reference-8955579; Reference-9019187; Reference-9040371; Reference-9084722; Reference-9022325; Reference-8898016; Reference-9034831; Reference-9003856; Reference-8954097; Reference-9003187; Reference-8964758; Reference-9062241; Reference-9056672; Reference-8901189; Reference-8968144; Reference-9024785; Reference-9063461; Reference-8916912; Reference-9011734; Reference-8987824; Reference-8944846; Reference-8925936; Reference-8945633; Reference-8983654; Reference-8980769; Reference-8965428; Reference-8908830; Reference-9061308; Reference-8971057; Reference-8902934; Reference-8987812; Reference-9035282; Reference-8914882; Reference-9076704; Reference-9059279; Reference-8956187; Reference-8993738; Reference-8896087; Reference-8900090; Reference-8977087; Reference-8960865; Reference-8922012; Reference-8969278; Reference-9029362; Reference-8917394; Reference-9003602; Reference-8914019; Reference-9010281; Reference-9011200; Reference-9005325; Reference-8943636; Reference-9081498; Reference-9077235; Reference-9018831; Reference-8938177; Reference-9058342; Reference-9038205; Reference-8929009; Reference-9062029; Reference-8989560; Reference-9017134; Reference-8911831; Reference-9081442; Reference-9067080; Reference-9038825; Reference-8999356; Reference-8897373; Reference-8917292; Reference-9000561; Reference-8887769; Reference-8918050; Reference-9056710; Reference-9081467; Reference-8986889; Reference-9012802; Reference-8963226; Reference-8944901; Reference-8918619; Reference-8946133; Reference-8997411; Reference-9038362; Reference-8946074; Reference-8911809; Reference-8966552; Reference-8898451; Reference-9041979; Reference-8915170; Reference-8932484; Reference-9036515; Reference-9063400; Reference-8971388; Reference-9039112; Reference-9058464; Reference-8889053; Reference-8942134; Reference-8960069; Reference-9023832; Reference-8939848; Reference-8967430; Reference-8893116; Reference-9035135; Reference-8986299; Reference-8944538; Reference-8924446; Reference-8902309; Reference-9076287; Reference-8952631; Reference-9024739; Reference-8986100; Reference-9076892; Reference-8916829; Reference-8925860; Reference-8991433; Reference-9049272; Reference-8940971; Reference-8932212; Reference-9019946; Reference-8914956; Reference-8925336; Reference-9039679; Reference-9037615; Reference-9072000; Reference-8928647; Reference-9004836; Reference-8993655; Reference-8924525; Reference-9028394; Reference-8917665; Reference-9054951; Reference-8891739; Reference-8981356; Reference-8942375; Reference-8908387; Reference-9069088; Reference-9002233; Reference-8919484; Reference-8905168; Reference-8906644; Reference-9038913; Reference-9064793; Reference-8922712
            [catnum] => 7662_Habitat_tropicalOrSubtropical
            [source] => https://eol.org/terms/search_results?utf8=✓&term_query[clade_id]=7662&term_query[filters_attributes][0][pred_uri]=http://eol.org/schema/terms/Habitat&term_query[filters_attributes][0][op]=is_any&term_query[result_type]=record&commit=Search
        )
        */
        $taxon_id = $rec['taxon_id'];
        $catnum = $rec['catnum'];
        $occurrence_id = $this->add_occurrence($taxon_id, $catnum, $rec);

        $m = new \eol_schema\MeasurementOrFact_specific(); //NOTE: used a new class MeasurementOrFact_specific() for non-standard fields like 'm->label'
        $m->IAO_0000009         = $rec['label'];
        $m->occurrenceID        = $occurrence_id;
        $m->measurementOfTaxon  = 'true';
        $m->measurementType     = $rec['measurementType'];
        $m->measurementValue    = $rec['measurementValue'];
        $m->source              = $rec['source'];
        $m->measurementMethod   = 'summary of records available in EOL';
        $m->measurementDeterminedDate = "2018-Oct-10"; //date("Y-M-d");
        $m->referenceID   = @$rec['referenceID']; //not all have refs
        $m->measurementID = Functions::generate_measurementID($m, $this->resource_id);
        $parent = $m->measurementID;
        // if($m->measurementID == "08eea7c40c13234a8e9699b52676236a_parent_basal_values")
        // if($m->measurementID == "dd832955e92c2a7b823ebf5c737057b9_parent_basal_values") 
        // {
        //     echo "\n**************************************************\n";
        //     print_r($this->info);
        //     exit("\nabove is eol_pks\n");
        // }
        $this->archive_builder->write_object_to_file($m);

        /* copied from template. Not used in SDR.
        $m->bibliographicCitation   = '';
        $m->measurementRemarks      = '';
        $m->contributor             = '';
        $m->measurementID = Functions::generate_measurementID($m, $this->resource_id, 'measurement', array('occurrenceID', 'measurementType', 'measurementValue'));
        */
        
        /* CREATING child records ------------------------------------------------------------------------------------
        Jen: https://eol-jira.bibalex.org/browse/DATA-1777?focusedCommentId=63921&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-63921
        After consult with Jeremy, we've decided to handle the REP and PRM tags as normal metadata,
        so let's make them child records, with 
        measurementType=https://eol.org/schema/terms/exemplary and
        measurementValue= https://eol.org/schema/terms/representative OR https://eol.org/schema/terms/primary
        */
        $m = new \eol_schema\MeasurementOrFact_specific(); //NOTE: used a new class MeasurementOrFact_specific() for non-standard fields like 'm->label'
        $m->occurrenceID        = '';
        $m->measurementOfTaxon  = '';
        $m->measurementType     = 'https://eol.org/schema/terms/exemplary';
        $m->measurementValue    = $this->exemplary[$rec['label']];
        $m->parentMeasurementID = $parent;
        $m->measurementRemarks = $rec['label'];
        $m->measurementID = Functions::generate_measurementID($m, $this->resource_id);
        $this->archive_builder->write_object_to_file($m);
    }
    private function add_occurrence($taxon_id, $catnum, $rec)
    {
        $occurrence_id = $catnum; //can be just this, no need to add taxon_id
        $o = new \eol_schema\Occurrence();
        $o->occurrenceID = $occurrence_id;
        $o->taxonID      = $taxon_id;
        $o->occurrenceID = Functions::generate_measurementID($o, $this->resource_id, 'occurrence');
        if(isset($this->occurrence_ids[$o->occurrenceID])) return $o->occurrenceID;
        $this->archive_builder->write_object_to_file($o);
        $this->occurrence_ids[$o->occurrenceID] = '';
        return $o->occurrenceID;
    }
    function append_to_MySQL_table($table, $file_append, $csvFileYN = false)
    {
        echo "\nSaving [$table] records to MySQL...\n";
        if(filesize($file_append)) {
            /* Not needed here.
            //truncate first
            $sql = "TRUNCATE TABLE SDR.".$table.";";
            if($result = $this->mysqli->query($sql)) echo "\nTable truncated [$table] OK.\n";
            */
            
            // e.g. load csv file to a blank table
            // LOAD DATA LOCAL INFILE 'abc.csv' INTO TABLE abc
            // FIELDS TERMINATED BY ',' 
            // ENCLOSED BY '"' 
            // LINES TERMINATED BY '\r\n'
            // IGNORE 1 LINES
            // (col1, col2, col3, col4, col5...);
            
            //load data to a blank table
            if(!$csvFileYN) $sql = "LOAD DATA LOCAL INFILE '".$file_append."' INTO TABLE SDR.".$table.";";
            else            $sql = "LOAD DATA LOCAL INFILE '".$file_append."' INTO TABLE SDR.".$table." FIELDS TERMINATED BY ',' ;";
            if($result = $this->mysqli->query($sql)) echo "\nSaved table [$table] to MySQL\n";
            else                                     echo "\nError: failed saving [$table]\n";
        }
        else echo "\nNothing to save.\n";
    }
    function update_inferred_file()
    {
        self::working_dir(); //initializes $this->main_paths
        $table = 'traits_inferred'; $sql = "TRUNCATE TABLE SDR.".$table.";";
        if($result = $this->mysqli->query($sql)) echo "\nTable truncated [$table] OK.\n";
        $file = $this->main_paths['archive_path'].'/inferred.csv';
        self::append_to_MySQL_table($table, $file, $csvFileYN = true);
        echo "\nTotal rows [$table]: ".self::count_table_rows($table)."\n";
    }
    function build_MySQL_table_from_csv($table) //generic means to build MySQL table from CSV file //1st client is method: lifestage and statMeth
    {   self::working_dir(); //initializes $this->main_paths
        if($table == 'metadata_LSM') $file = fopen($this->main_paths['archive_path'].'/metadata.csv', 'r'); 
        else exit("\ntable [$table] not yet initialized\n"); //and so on...
        
        //truncate first
        $sql = "TRUNCATE TABLE SDR.".$table.";";
        if($result = $this->mysqli->query($sql)) echo "\nTable truncated [$table] OK.\n";
        
        $file_cnt = 1; $save = 0;
        $file_write = $this->main_dir."/".$this->MySQL_append_files_dir."/".$table."_".$file_cnt.".txt"; $WRITE = fopen($file_write, "w");
        
        self::initialize(); $i = 0;
        while(($line = fgetcsv($file)) !== FALSE) { $i++; 
            if(($i % 1000000) == 0) echo "\n".number_format($i);
            if($i == 1) $fields = $line;
            else {
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k]; $k++;
                }
                // print_r($rec); //exit("\nstopx\n");
                /*Array(
                    [eol_pk] => OccurrenceMetadatum-8435138
                    [trait_eol_pk] => R686-PK74397712
                    [predicate] => http://rs.tdwg.org/dwc/terms/lifeStage
                    [value_uri] => http://www.ebi.ac.uk/efo/EFO_0001272
                    [measurement] => 
                    [units_uri] => 
                    [literal] => 
                Array(
                    [eol_pk] => MetaTrait-122684148
                    [trait_eol_pk] => R686-PK74397712
                    [predicate] => http://eol.org/schema/terms/statisticalMethod
                    [value_uri] => http://semanticscience.org/resource/SIO_001114
                    [measurement] => 
                    [units_uri] => 
                    [literal] => 
                */
                if(in_array($rec['predicate'], array('http://rs.tdwg.org/dwc/terms/lifeStage', 'http://eol.org/schema/terms/statisticalMethod'))) {
                    $save++;
                    if(($save % 500000) == 0) {
                        echo "\nSaving...".number_format($save);
                        fclose($WRITE);
                        self::append_to_MySQL_table($table, $this->main_dir."/".$this->MySQL_append_files_dir."/".$table."_".$file_cnt.".txt");
                        $file_cnt++;
                        $file_write = $this->main_dir."/".$this->MySQL_append_files_dir."/metadata_LSM_".$file_cnt.".txt"; $WRITE = fopen($file_write, "w");
                    }
                    self::write_report($rec, $fields, $WRITE);
                }
            }
        }
        fclose($WRITE);
        self::append_to_MySQL_table($table, $this->main_dir."/".$this->MySQL_append_files_dir."/".$table."_".$file_cnt.".txt");
        fclose($file); echo("\n\n$table to MySQL DONE.\n\n");
        echo "\nTotal rows [$table]: ".self::count_table_rows($table)."\n";
    }
    function build_MySQL_table_from_text($table) //generic means to build MySQL table from TSV file //1st client is DH with taxonRank table.
    {   if($table == 'DH_lookup') {
            $info = self::prep_DH();
            $file = $info['archive_path'].$info['tables']['taxa'];
        }
        else exit("\nTable [$table] not yet initialized.\n"); //and so on...
        
        //truncate first
        $sql = "TRUNCATE TABLE SDR.".$table.";";
        if($result = $this->mysqli->query($sql)) echo "\nTable truncated [$table] OK.\n";
        
        $file_cnt = 1; $save = 0;
        $file_write = $this->main_dir."/".$this->MySQL_append_files_dir."/".$table."_".$file_cnt.".txt"; $WRITE = fopen($file_write, "w");
        if(!$WRITE) exit("\nerror fopen [$file_write]\n");

        $i = 0;
        foreach(new FileIterator($file) as $line_number => $line) {
            $line = explode("\t", $line); $i++; if(($i % 200000) == 0) echo "\n".number_format($i);
            if($i == 1) $fields = $line;
            else {
                if(!$line[0]) break;
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k]; $k++;
                }
                // print_r($rec); exit;
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
                )*/
                if($table == 'DH_lookup') {
                    if($rec['EOLid']) {
                        $save++;
                        if(($save % 500000) == 0) {
                            echo "\nSaving...".number_format($save); fclose($WRITE);
                            self::append_to_MySQL_table($table, $this->main_dir."/".$this->MySQL_append_files_dir."/".$table."_".$file_cnt.".txt");
                            $file_cnt++; $file_write = $this->main_dir."/".$this->MySQL_append_files_dir."/".$table."_".$file_cnt.".txt"; $WRITE = fopen($file_write, "w");
                        }
                        // self::write_report($rec, $fields, $WRITE); //normal if u want to save all columns
                        self::write_report($rec, array('EOLid', 'taxonRank'), $WRITE);
                    }
                }
                else {} //for other tables...
            }
        }
        fclose($WRITE);
        self::append_to_MySQL_table($table, $this->main_dir."/".$this->MySQL_append_files_dir."/".$table."_".$file_cnt.".txt");
        echo "\nTotal rows [$table]: ".self::count_table_rows($table)."\n";
        exit("\n\n$table to MySQL DONE.\n\n");
    }
    function generate_refs_per_eol_pk_MySQL()
    {   //truncate first
        $table = 'metadata_refs'; $sql = "TRUNCATE TABLE SDR.".$table.";";
        if($result = $this->mysqli->query($sql)) echo "\nTable truncated [$table] OK.\n";
        
        $file_cnt = 1; $save = 0;
        $file_write = $this->main_dir."/".$this->MySQL_append_files_dir."/metadata_refs_".$file_cnt.".txt"; $WRITE = fopen($file_write, "w");
        
        self::initialize();
        $file = fopen($this->main_paths['archive_path'].'/metadata.csv', 'r'); $i = 0;
        while(($line = fgetcsv($file)) !== FALSE) { $i++; 
            if(($i % 1000000) == 0) echo "\n".number_format($i);
            if($i == 1) $fields = $line;
            else {
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k]; $k++;
                }
                // print_r($rec); //exit("\nstopx refs\n");
                /*Array(
                    [eol_pk] => MetaTrait-122920149
                    [trait_eol_pk] => R788-PK74516597
                    [predicate] => http://rs.tdwg.org/dwc/terms/measurementMethod
                    [value_uri] => 
                    [measurement] => 
                    [units_uri] => 
                    [literal] => Adult body mass averaged across males and females and geographic locations.
                )*/
                // /* main operation
                $refs = array();
                if(count($fields) == count($line) && $rec['predicate'] == "http://eol.org/schema/reference/referenceID"    && $rec['literal']) $refs[$rec['eol_pk']] = strip_tags($rec['literal']);
                if(count($fields) == count($line) && $rec['predicate'] == "http://purl.org/dc/terms/bibliographicCitation" && $rec['literal']) $refs[$rec['eol_pk']] = strip_tags($rec['literal']);
                if($refs) {
                    $save++;
                    if(($save % 500000) == 0) {
                        echo "\nSaving...".number_format($save);
                        fclose($WRITE);
                        self::append_to_MySQL_table('metadata_refs', $this->main_dir."/".$this->MySQL_append_files_dir."/metadata_refs_".$file_cnt.".txt");
                        $file_cnt++;
                        $file_write = $this->main_dir."/".$this->MySQL_append_files_dir."/metadata_refs_".$file_cnt.".txt"; $WRITE = fopen($file_write, "w");
                    }
                    self::write_report($rec, $fields, $WRITE);
                }
                // */
            }
            // if($i >= 5) return; //debug
        }
        fclose($WRITE);
        self::append_to_MySQL_table('metadata_refs', $this->main_dir."/".$this->MySQL_append_files_dir."/metadata_refs_".$file_cnt.".txt");
        fclose($file); echo "\n\nMetadata_refs to MySQL DONE.\n\n";
        echo "\nTotal rows [metadata_refs]: ".self::count_table_rows('metadata_refs')."\n";
    }
    private function count_table_rows($table)
    {
        $result = $this->mysqli->query("SELECT count(*) total_rows FROM SDR.$table");
        while($result && $rec=$result->fetch_assoc()) return $rec['total_rows'];
    }
    function investigate_metadata_csv()
    {
        self::initialize();
        $file = fopen($this->main_paths['archive_path'].'/metadata.csv', 'r'); $i = 0;
        while(($line = fgetcsv($file)) !== FALSE) { $i++; 
            if(($i % 500000) == 0) echo "\n".number_format($i);
            if($i == 1) $fields = $line;
            else {
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k]; $k++;
                }
                // print_r($rec); exit;
                /*Array(
                    [eol_pk] => MetaTrait-124174152
                    [trait_eol_pk] => R261-PK74813887
                    [predicate] => http://rs.tdwg.org/dwc/terms/measurementUnit
                    [value_uri] => http://purl.obolibrary.org/obo/UO_0000021
                    [measurement] => 
                    [units_uri] => 
                    [literal] => 
                )*/
                /*
                if($rec['eol_pk'] == 'R20-PK1541446' || $rec['trait_eol_pk'] == 'R20-PK1541446') {
                    print_r($rec); exit;
                }
                */
                if($GLOBALS['ENV_DEBUG']) {
                    if($rec['predicate'] == "http://eol.org/schema/reference/referenceID") print_r($rec);
                }
                // $debug[$rec['predicate']] = '';
            }
        }
        // print_r($debug);
    }
    /*
    function generate_refs_per_eol_pk() //total eol_pks 39,931 Carnivora | 11,233,522 metadata.csv | 985,159 metadata_refs in MySQL
    {   exit; just save it to MySQL table. BE SURE TO INDEX eol_pk, trait_eol_pk
        self::initialize();
        $file = fopen($this->main_paths['archive_path'].'/metadata.csv', 'r'); $i = 0;
        while(($line = fgetcsv($file)) !== FALSE) { $i++; 
            if(($i % 100000) == 0) echo "\n".number_format($i);
            if($i == 1) $fields = $line;
            else {
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k]; $k++;
                }
                print_r($rec); //exit("\nstopx refs\n");
                Array(
                    [eol_pk] => MetaTrait-122920149
                    [trait_eol_pk] => R788-PK74516597
                    [predicate] => http://rs.tdwg.org/dwc/terms/measurementMethod
                    [value_uri] => 
                    [measurement] => 
                    [units_uri] => 
                    [literal] => Adult body mass averaged across males and females and geographic locations.
                )
                // main operation start
                $refs = array();
                if(count($fields) == count($line) && $rec['predicate'] == "http://eol.org/schema/reference/referenceID"    && $rec['literal']) $refs[$rec['eol_pk']] = strip_tags($rec['literal']);
                if(count($fields) == count($line) && $rec['predicate'] == "http://purl.org/dc/terms/bibliographicCitation" && $rec['literal']) $refs[$rec['eol_pk']] = strip_tags($rec['literal']);
                if($refs) {
                    $json_file = self::get_txt_path_by_page_id($rec['trait_eol_pk'], ".txt");
                    $arr = array();
                    if(file_exists($json_file)) {
                        $json = file_get_contents($json_file);
                        $arr = json_decode($json, true);
                        echo "\n[$json_file] appended\n";
                    }
                    else echo "\n[$json_file] created\n";
                    foreach($refs as $refno => $fullref) if(!isset($arr[$refno])) $arr[$refno] = $fullref;
                    $WRITE = fopen($json_file, 'w'); fwrite($WRITE, json_encode($arr)); fclose($WRITE); 
                }
                // main operation end
            }
            // if($i >= 5) return; //debug
        }
    }
    */
    /* not being used anymore, since the use of MySQL tables
    function delete_all_eol_pks_refs_fileTXT() //total eol_pks 39,931
    {   self::initialize();
        $eol_pks = self::get_unique_eol_pks_from_metadata_csv();
        $total = count($eol_pks); $i = 0;
        foreach($eol_pks as $eol_pk) {
            $i++; echo "\n $i of $total";
            $file = self::get_txt_path_by_page_id($eol_pk, ".txt");
            if(file_exists($file)) {
                if(unlink($file)) echo "\ndeleted";
            }
            else echo "\nnot found";
        }
    }
    private function get_unique_eol_pks_from_metadata_csv()
    {   $file = fopen($this->main_paths['archive_path'].'/metadata.csv', 'r'); $i = 0;
        while(($line = fgetcsv($file)) !== FALSE) { $i++; 
            if($i == 1) $fields = $line;
            else {
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k]; $k++;
                }
                if(count($fields) == count($line) && $rec['predicate'] == "http://eol.org/schema/reference/referenceID")    $eol_pks[$rec['trait_eol_pk']] = '';
                if(count($fields) == count($line) && $rec['predicate'] == "http://purl.org/dc/terms/bibliographicCitation") $eol_pks[$rec['trait_eol_pk']] = '';
            }
        }
        return array_keys($eol_pks);
    }*/
    private function get_refs_from_metadata_csv($eol_pks)
    {
        $str = implode(",", $eol_pks);
        $str = str_replace(",", "','", $str);
        $str = "'".$str."'";
        $sql = "SELECT m.* from SDR.metadata_refs m WHERE m.trait_eol_pk IN (".$str.")"; //echo "\n[$sql]\n";
        $result = $this->mysqli->query($sql);
        $final = array(); $final2 = array();
        while($result && $rec=$result->fetch_assoc()) $final[$rec['eol_pk']] = strip_tags($rec['literal']);
        //make fullref unique
        foreach($final as $refno => $fullref) {
            if(isset($this->fullref[$fullref])) {
                $refno = $this->fullref[$fullref];
                $final2[$refno] = $fullref;
            }
            else {
                $this->fullref[$fullref] = $refno;
                $final2[$refno] = $fullref;
            }
        }
        return $final2;
    }
    /* used in Carnivora
    private function get_refs_from_metadata_csv_OLD($eol_pks) //replaced the ver 1, which is very slow
    {
        $final = array();
        foreach($eol_pks as $eol_pk) {
            $file = self::get_txt_path_by_page_id($eol_pk, ".txt");
            if(file_exists($file)) {
                $json = file_get_contents($file);
                $arr = json_decode($json, true);
                if($arr) $final = $final + $arr;
            }
        }
        //make fullref unique
        $final2 = array();
        foreach($final as $refno => $fullref) {
            if(isset($this->fullref[$fullref])) {
                $refno = $this->fullref[$fullref];
                $final2[$refno] = $fullref;
            }
            else {
                $this->fullref[$fullref] = $refno;
                $final2[$refno] = $fullref;
            }
        }
        return $final2;
    }
    private function get_refs_from_metadata_csv_v1($eol_pks) //versy slow
    {
        // echo "\neol_pks to process: ".count($eol_pks)."\n";
        if(!$eol_pks) return array();
        $refs = array();
        $file = fopen($this->main_paths['archive_path'].'/metadata.csv', 'r'); $i = 0;
        while(($line = fgetcsv($file)) !== FALSE) { $i++; 
            if($i == 1) $fields = $line;
            else {
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k]; $k++;
                }
                // print_r($rec);exit;
                // Array(
                //     [eol_pk] => MetaTrait-19117935  [trait_eol_pk] => R261-PK22081478   [predicate] => http://rs.tdwg.org/dwc/terms/measurementMethod
                //     [literal] => Activity cycle of each species measured for non-captive populations; adult or age unspecified individuals, male, female, or sex unspecified individuals; primary, secondary, or extrapolated sources; all measures of central tendency; in all localities. Species were defined as (1) nocturnal only, (2) nocturnal/crepuscular, cathemeral, crepuscular or diurnal/crepuscular and (3) diurnal only.  Based on information from primary and secondary literature sources.  See source for details. 
                //     [measurement] => [value_uri] => [units] => [sex] => [lifestage] => [statistical_method] => [source] => 
                // )
                if(in_array($rec['trait_eol_pk'], $eol_pks) && count($fields) == count($line) && $rec['predicate'] == "http://eol.org/schema/reference/referenceID")    $refs[$rec['eol_pk']] = strip_tags($rec['literal']);
                if(in_array($rec['trait_eol_pk'], $eol_pks) && count($fields) == count($line) && $rec['predicate'] == "http://purl.org/dc/terms/bibliographicCitation") $refs[$rec['eol_pk']] = strip_tags($rec['literal']);
                // $debug[$rec['predicate']] = '';
            }
        }
        // print_r($refs); print_r($debug); exit;
        // echo "\nDONE: get_refs_from_metadata_csv()\n";
        return $refs;
    }
    */
    private function get_sought_field($recs, $field)
    {
        foreach($recs as $rec) $final[$rec[$field]] = '';
        return array_keys($final);
    }
    function get_page_ids_andInfo_fromDH($page_ids_param = array())
    {   
        //step 2 get desired info from DH
        $info = self::prep_DH(); $i = 0;
        foreach(new FileIterator($info['archive_path'].$info['tables']['taxa']) as $line_number => $line) {
            $line = explode("\t", $line); $i++;
            if($i == 1) $fields = $line;
            else {
                if(!$line[0]) break;
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k]; $k++;
                }
                /*Array(
                    [taxonID] => -168611
                    [acceptedNameUsageID] => -168611
                    [parentNameUsageID] => -105852
                    [scientificName] => Torpediniformes
                    [taxonRank] => order
                    [source] => trunk:59edf7f2-b792-4351-9f37-562dd522eeca,WOR:10215,gbif:881
                    [taxonomicStatus] => accepted
                    [canonicalName] => 
                    [scientificNameAuthorship] => 
                    [scientificNameID] => 
                    [taxonRemarks] => 
                    [namePublishedIn] => 
                    [furtherInformationURL] => 
                    [datasetID] => trunk
                    [EOLid] => 8898
                    [EOLidAnnotations] => multiple;
                    [Landmark] => 1
                )*/
                
                // /* NEW: good way to force list of page_ids
                if($page_ids_param) {
                    if(in_array($rec['EOLid'], $page_ids_param)) $page_ids[$rec['EOLid']] = array('taxonRank' => $rec['taxonRank'], 'Landmark' => $rec['Landmark']);
                }
                else $page_ids[$rec['EOLid']] = array('taxonRank' => $rec['taxonRank'], 'Landmark' => $rec['Landmark']);
                // */
            }
        }
        return $page_ids;
    }
    private function get_page_ids_fromTraitsCSV_andInfo_fromDH($predicates)
    {   //step 1: get all page_ids from traits.csv
        $ret = self::get_fields_from_file(array('page_id'), 'traits.csv', $predicates);
        if(!$ret) return array();
        $page_ids = $ret['page_id']; $ret = ''; //unset
        //step 2 get desired info from DH
        $info = self::prep_DH();
        $i = 0;
        foreach(new FileIterator($info['archive_path'].$info['tables']['taxa']) as $line_number => $line) {
            $line = explode("\t", $line); $i++;
            if($i == 1) $fields = $line;
            else {
                if(!$line[0]) break;
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k]; $k++;
                }
                /*Array(
                    [taxonID] => -168611
                    [acceptedNameUsageID] => -168611
                    [parentNameUsageID] => -105852
                    [scientificName] => Torpediniformes
                    [taxonRank] => order
                    [source] => trunk:59edf7f2-b792-4351-9f37-562dd522eeca,WOR:10215,gbif:881
                    [taxonomicStatus] => accepted
                    [canonicalName] => 
                    [scientificNameAuthorship] => 
                    [scientificNameID] => 
                    [taxonRemarks] => 
                    [namePublishedIn] => 
                    [furtherInformationURL] => 
                    [datasetID] => trunk
                    [EOLid] => 8898
                    [EOLidAnnotations] => multiple;
                    [Landmark] => 1
                )*/
                if(isset($page_ids[$rec['EOLid']])) $page_ids[$rec['EOLid']] = array('taxonRank' => $rec['taxonRank'], 'Landmark' => $rec['Landmark']);
            }
        }
        return $page_ids;
    }
    private function format_value_for_sql($value)
    {   $arr = explode(",", $value);
        $arr = array_map("trim", $arr);
        if(count($arr) == 1) return array('count' => 'single', 'value' => $arr[0]); //e.g. http://eol.org/schema/terms/IsDispersalVectorFor
        if(count($arr) > 1)  return array('count' => 'multiple', 'value' => "'".str_replace(",", "','", $value)."'"); //e.g. 'http://purl.obolibrary.org/obo/RO_0002470','http://purl.obolibrary.org/obo/RO_0002439','http://purl.obolibrary.org/obo/RO_0002444'
    }
    private function get_fields_from_file($headers, $filename, $predicates)
    {
        $pred = $predicates[0];
        $ret = self::format_value_for_sql($pred);
        if($ret['count'] == 'single')       $sql = "SELECT DISTINCT(t.page_id) from SDR.".$this->dbname." t WHERE t.predicate = '".$ret['value']."'";
        elseif($ret['count'] == 'multiple') $sql = "SELECT DISTINCT(t.page_id) from SDR.".$this->dbname." t WHERE t.predicate IN (".$ret['value'].")";
        
        // echo "\nQuery start: [$sql]\n";
        $result = $this->mysqli->query($sql);
        $final = array();
        while($result && $rec=$result->fetch_assoc()) {
            foreach($headers as $head) $final[$head][$rec[$head]] = '';
        }
        debug("\nQuery end OK\n");
        return $final;
        /* working but too slow for All Export File
        $file = fopen($this->main_paths['archive_path'].'/'.$filename, 'r'); $i = 0;
        while(($line = fgetcsv($file)) !== FALSE) {
            $i++;
            if($i == 1) $fields = $line;
            else {
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k]; $k++;
                }
                // print_r($rec); exit;
                Array(
                    [eol_pk] => R788-PK74508166
                    [page_id] => 1180180
                    [resource_pk] => 
                    [resource_id] => 694
                    [source] => 
                    [scientific_name] => <i>Zygodontomys brevicauda</i>
                    [predicate] => http://eol.org/schema/terms/ExtinctionStatus
                    [object_page_id] => 
                    [value_uri] => http://eol.org/schema/terms/extant
                    [normal_measurement] => 
                    [normal_units_uri] => 
                    [normal_units] => 
                    [measurement] => 
                    [units_uri] => 
                    [units] => 
                    [literal] => http://eol.org/schema/terms/extant
                )
                if(in_array($rec['predicate'], $predicates)) {
                    foreach($headers as $head) $final[$head][$rec[$head]] = '';
                }
            }
        }
        return $final;
        */
    }
    //############################################################################################ start method = 'parents basal values'
    function get_all_recs_for_each_pageID($children, $predicate, $dbname = false) //3rd param $dbname came from /lib/SDR_report.php
    {
        if($dbname) $this->dbname = $dbname;
        $recs = array(); $children_total = count($children); $i = 0;
        foreach($children as $page_id) { $i++; //echo "\n$i of $children_total [$page_id][$predicate]\n"; //good debug
            $child_recs = self::assemble_recs_for_page_id_from_text_file($page_id, $predicate, array('value_uri')); // echo "\n".count($child_recs)."\n";
            if($child_recs) $recs = array_merge($recs, $child_recs);
        }
        return $recs;
    }
    private function main_parents_basal_values($main_page_id, $predicate, $debugModeYN = false)
    {   
        self::per_line('parents basal values', $main_page_id, $predicate);
        /* 1. get all children of page_id with rank = species */
        // $children = array(328598, 328609, 46559217, 328682, 328607); //force assignment, development only

        // /*
        // if($children = self::get_CSV_children_of($main_page_id, $predicate)) { //$predicate param here is just for debug | OBSOLETE. Value is now cached to txt file.
        if($children = self::get_children_from_txt_file($main_page_id, false)) { //Value is now cached to txt file
            debug("\n*Children of [$main_page_id]: ".count($children)."\n"); //print_r($children);    *Children of [164]: 1433142
        }
        else {
            debug("\n*No children found for [$main_page_id]\n");
            return array();
        }
        // */
        
        /* debug force assign
        if($main_page_id == 7662) $children = array(328598, 328609, 46559217, 328682, 328607); //force assignment, development only
        echo "\n*Children of [$main_page_id]: "; print_r($children);
        */
        
        /* obsolete
        2. get all values for each child from method = 'basal values'
        foreach($children as $page_id) {
            if($val = self::main_basal_values($page_id, $predicate)) $records[] = $val;
        }
        3. get all selected values
        $page_ids = array();
        $recs = array();
        foreach($records as $rec) {
            // if($val = @$rec['Selected']) $page_ids = array_merge($page_ids, $val); //version 1 - didn't use
            if($val = @$rec['recs']) $recs = array_merge($recs, $val);
        }
        */
        
        if($children = self::get_childrenTBP_from_txt_file($main_page_id, $children, $predicate)) {
            $children_count = count($children);
            debug("\n*Children TBP of [$main_page_id]: ".$children_count."\n");
            if($children_count > 1000) return array();
        }
        else {
            debug("\n*No children TBP found for [$main_page_id]\n");
            return array();
        }
        // return; //used for caching only
        if($debugModeYN) {
            $file_write = $this->main_dir."/".$this->MySQL_append_files_dir."/page_id_children_count_".pathinfo($predicate, PATHINFO_FILENAME).".txt"; $WRITE = fopen($file_write, "a");
            fwrite($WRITE, implode("\t", array($main_page_id, count($children)))."\n"); fclose($WRITE);
            return false;
        }
        
        /* 2. get all recs for each child */
        // /* prev script here was refactored to get_all_recs_for_each_pageID()
        $recs = self::get_all_recs_for_each_pageID($children, $predicate);
        // echo "\n".count($recs)."\n"; exit("\nxxx\n");
        // */

        /* version 1 - didn't use
        $original_records = $page_ids;
        asort($original_records); $original_records = array_values($original_records); //reindexes key
        $page_ids = array_unique($page_ids);
        asort($page_ids);
        $page_ids = array_values($page_ids); //reindexes key
        echo "\n==========================================================\nParent process for taxon ID $main_page_id, predicate $predicate\n";
        echo "\nChildren used for computation: "; print_r($children);
        echo "\n==========================================================\nCombined values from the original records (all REC records of children), raw: ".count($original_records); // print_r($original_records);
        foreach($original_records as $id) echo "\n$id";
        echo "\n==========================================================\nDeduplicated: ".count($page_ids);
        print_r($page_ids);
        $uris = $page_ids;
        self::set_ancestor_ranking_from_set_of_uris($uris);
        $ISVAT = self::get_initial_shared_values_ancestry_tree_v2($uris); //initial "shared values ancestry tree" ---> parent left, term right
        if($val = self::main_basal_values(NULL, NULL, 'parent basal values', $ISVAT, $uris)) {
            print_r($val); exit("\nelix\n");
        }
        */
        if(!$recs) {
            debug("\nNo recs for any of the children for predicate [$predicate]\n");
            return false;
        }
        
        // /* to get SampleSize count from: https://eol-jira.bibalex.org/browse/DATA-1773?focusedCommentId=63621&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-63621
        // self::buildup_SampleSize_data($recs, $predicate); //was semi-abandoned bec. it is very slow
        // */
        
        if($ret = self::main_basal_values(NULL, NULL, 'parent basal values', $recs)) {
            if($GLOBALS['ENV_DEBUG']) {
                print_r($ret);
                foreach($ret['Selected'] as $term) { //debug only - good debug - mainstay
                    echo "\n[$term]: ";
                    if($val = @$this->parents_of[$term]) print_r($val);
                    else echo " -- no parent";
                }
            }
            return $ret;
        }
    }
    /* not used anymore...
    private function get_initial_shared_values_ancestry_tree_v2($uris)
    {
        $final = array();
        foreach($uris as $term) {
            $parent = self::get_parent_of_term($term);
            $final[] = array($parent, $term);
        }
        return $final;
    }
    */
    private function page_id_has_trait_for_this_predicate($page_id, $predicate, $table = '') //3rd param $table is for 'parent taxon summary'
    {
        if($predicate == 'http://eol.org/schema/terms/Present') $table = 'page_ids_Present';
        elseif($predicate == 'http://eol.org/schema/terms/Habitat') $table = 'page_ids_Habitat';
        elseif($predicate == 'http://purl.obolibrary.org/obo/FLOPO_0900032') $table = 'page_ids_FLOPO_0900032';
        elseif($table == 'traits_TSp') {} //so not to break the flow if-then-else
        else exit("\nTable not yet initialized!\n");
        $sql = "SELECT t.page_id from SDR.".$table." t WHERE t.page_id = '".$page_id."' LIMIT 1";

        if($table == 'traits_TSp') $sql = "SELECT t.page_id from SDR.".$table." t WHERE t.page_id = '".$page_id."' AND t.predicate = '".$predicate."' LIMIT 1";
        
        $result = $this->mysqli->query($sql);
        if($result && $rec=$result->fetch_assoc()) return true;
        return false;
    }
    private function page_id_has_rank_equal_to($page_id, $find_rank)
    {
        $sql = "SELECT d.taxonRank from SDR.DH_lookup d WHERE d.EOLid = '".$page_id."' AND d.taxonRank = '".$find_rank."'";
        $result = $this->mysqli->query($sql);
        if($result && $rec=$result->fetch_assoc()) return true;
        return false;
    }
    //############################################################################################ start method = 'parents taxon summary'
    private function main_parents_taxon_summary($main_page_id, $predicate, $debugModeYN)
    {   
        self::per_line('parents taxon summary', $main_page_id, $predicate);
        /* 1. get all children of page_id with rank = species */
        // $children = array(328598, 46559162, 328607, 46559217, 328609); //force assign, during dev only
        // /*
        $children = array();
        /* only added this for Carnivora, but for All Trait Export should be excluded based on text below. Jun 9, 2019.
        $children[] = $main_page_id;
        */
        // and, just for the taxon summary parents (not for the basal value parents) a change in the contributing child taxa: please include
        // all descendant taxa at all ranks, up to and including the taxon in question, so the summary for page 7666 should be based on a record pool including records for page 7666.
        // You may want to include a filter so, if we re-run this in a few months, the summary records created for 7666 are not included in the new pool of records.
        // (This is entirely because of the quality of the data. Basal value records, habitat and geography, include many questionable records at, for instance, the family level.
        // Interactions records include a lot of pretty reasonable records for the same taxa.)
        
        // /* based on latest Jun 25, 2019: https://eol-jira.bibalex.org/browse/DATA-1775?focusedCommentId=63544&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-63544
        $children[] = $main_page_id; //INCLUDE the taxon in question in the contributing child taxa.
        // */
        
        // if($mga_anak = self::get_CSV_children_of($main_page_id)) $children = array_merge($children, $mga_anak); OBSOLETE. children is now cached to txt file.
        
        if($mga_anak = self::get_children_from_txt_file($main_page_id, false)) { //Value is now cached to txt file
            $children = array_merge($children, $mga_anak);  $children = array_unique($children);    $children = array_values($children); //index the keys
            debug("\n*Children of [$main_page_id]: ".count($children)."\n"); //print_r($children);    *Children of [xxx]: xxx
        }
        else {
            debug("\n*No children found for [$main_page_id]\n");
            // exit("\nelix stop taxon summary\n"); //debug only
            return array();
        }
        // */
        
        /* refactored into a function
        IMPORTANT NEW STEP: Jun 9, 2019 -> only children with trait data equal to $predicate should be processed so it speeds up.  *******************************************
        E.g. page_id = 163 has many children without Present trait. But it is being checked one by one, very slow process.
        $new_children = array();
        foreach($children as $page_id) {
            if(self::page_id_has_trait_for_this_predicate($page_id, $predicate, 'traits_TSp')) $new_children[] = $page_id;
        }
        $children = $new_children; unset($new_children);
        echo "\n*New Children of [$main_page_id] with pred.: ".count($children)."\n"; //print_r($children); *New Children of [164] with pred: 218233
        ******************************************* end IMPORTANT NEW STEP
        */
        
        if($children = self::get_childrenTBP_from_txt_file($main_page_id, $children, $predicate, 'traits_TSp')) { //has script to handle multiple predicates ALREADY
            $children_count = count($children);
            debug("\n*Children TBP of [$main_page_id]: ".$children_count."\n");
            if($children_count > 1000) return array();
        }
        else {
            debug("\n*No children TBP found for [$main_page_id]\n");
            return array();
        }
        // return; //used for caching only
        if($debugModeYN) {
            $file_write = $this->main_dir."/".$this->MySQL_append_files_dir."/page_id_children_count_".pathinfo($predicate, PATHINFO_FILENAME).".txt"; $WRITE = fopen($file_write, "a");
            fwrite($WRITE, implode("\t", array($main_page_id, count($children)))."\n"); fclose($WRITE);
            return false;
        }
        
        // exit("\nstop muna\n");
        
        /* 2. get all values for each child from method = 'taxon summary' */
        // $children = array(328609); //debug
        $records = array();
        foreach($children as $page_id) {
            if($val = self::main_taxon_summary($page_id, $predicate)) { //has script to handle multiple predicates ALREADY
                if($GLOBALS['ENV_DEBUG']) {
                    echo "\nFinal result: taxon summary: "; print_r($val);
                }
                $records[] = $val;
                $this->debug['parent taxon summary'][$main_page_id."_".$predicate][] = $page_id; //the parent taxa and corresponding predicate, with children that have records. Not all children under this parent but only those with records.
            }
        }
        if(count($records) == 1) { debug("\n**Only 1 child has records. Use result of this child as result of the parent process ".count($records)." For [$main_page_id], $predicate\n");
            /*Array
                [0] => Array
                        [root] => 1642
                        [root label] => PRM and REP
                        [Selected] => Array
                                [0] => 45511473
                                [1] => 1642
                        [Label] => REP
                        [refs] => Array ---- this is now obsolete
                    )
            )*/
            $rec = $records[0];
            return array('root' => $rec['root'], 'root label' => $rec['root label'], 'Selected' => $rec['Selected'], 'Label' => $rec['Label']);
        }
        elseif(count($records) > 1) debug("\n**Multiple children have records ".count($records)." For [$main_page_id], $predicate\n");
        else                        debug("\n**No children, even inclusive have any records. For [$main_page_id], $predicate\n");
        
        if(!$records) return array();
        /* 3. get all selected values */
        $page_ids = array();
        foreach($records as $rec) {
            if($val = @$rec['Selected']) $page_ids = array_merge($page_ids, $val);
        }
        $original_records = $page_ids;
        $page_ids = array_unique($page_ids);
        $page_ids = array_values($page_ids); //reindexes key
        if($GLOBALS['ENV_DEBUG']) {
            echo "\n==========================================================\nStart: parent process for taxon ID $main_page_id, predicate $predicate\n";
            echo "\nChildren used for computation: "; print_r($children);
            echo "\n==========================================================\nCombined values from the original records (all REC records of children), raw:";
            print_r($original_records);
            // asort($original_records); print_r($original_records);
            echo "\n==========================================================\nCombined values from the original records (all REC records of children), deduplicated:";
            print_r($page_ids);
            //now get similar report from 'taxon summary'
            echo "\n==========================================================\nHierarchies of taxon values:";
        }
        $hierarchies_of_taxon_values = array();
        foreach($page_ids as $page_id) {
            $anc = self::get_ancestry_via_DH($page_id);
            $hierarchies_of_taxon_values[$page_id] = $anc;
        }

        // /* NEW STEP: If the common root of the dataset is anything else, you can leave it. Only remove it if it is in the magic 5 of deletable taxa. 
        // $hierarchies_of_taxon_values = self::adjust_2913056($hierarchies_of_taxon_values); MOVED BELOW...
        // */
        if($GLOBALS['ENV_DEBUG']) print_r($hierarchies_of_taxon_values);
        
        //start store counts 2:
        foreach($hierarchies_of_taxon_values as $page_id => $anc) {
            $k = 0;
            foreach($anc as $id) {
                @$counts[$id]++;
                if($k > 0) $children_of[$id][] = $anc[$k-1];
                $k++;
            }
        }

        // print_r($counts); //print_r($children_of); //good debug
        $final = array();
        foreach($hierarchies_of_taxon_values as $page_id => $anc) {
            foreach($anc as $id) {
                if($count = @$counts[$id]) {
                    if($count >= 2) { //meaning this ancestor exists in other recs
                        if($arr = @$children_of[$id]) {
                            $arr = array_unique($arr);
                            if(count($arr) > 1) $final[$page_id][] = $id; //meaning child is not the same for all recs
                        }
                    }
                }
            }
        }
        debug("\n==========================================================\nReduced hierarchies: \n");
        $hierarchies_of_taxon_values = array(); //to be used
        foreach($page_ids as $page_id) {
            debug("\n[$page_id] -> ");
            $hierarchies_of_taxon_values[$page_id] = array(); //''; changed val from '' to array(). Just to be consistent with array treatment on later steps. Jun 25, 2019
            if($val = @$final[$page_id]) {
                if($GLOBALS['ENV_DEBUG']) print_r($val);
                $hierarchies_of_taxon_values[$page_id] = $val;
            }
            else debug("no more ancestry");
        }

        // /* NEW STEP: If the common root of the dataset is anything else, you can leave it. Only remove it if it is in the magic 5 of deletable taxa. 
        $hierarchies_of_taxon_values = self::adjust_2913056($hierarchies_of_taxon_values);
        if(!$hierarchies_of_taxon_values) return array(); //new Aug 12'19
        // */
        if($GLOBALS['ENV_DEBUG']) {echo "\n==========================================================\nHierarchies after removal of the 5 deletable taxa:"; print_r($hierarchies_of_taxon_values);}
        $final = $hierarchies_of_taxon_values; //needed assignment
        debug("\n==========================================================\nroots < 15% removal step:\n");
        /* ---------------------------------------------------------------------------------------------------------------------------------------
        "NEW STEP: IF there are multiple roots, discard those representing less than 15% of the original records",
        discard: yes, *in this step* discard means that whole hierarchy
        "original records" is a set just upstream of your second section in your result file: 
        "combined values from the original records (all REC records of children), deduplicated:Array". 
        The list I want is before deduplication, 
        i.e. if 207661 was a value for more than one of the child taxa, it should count more than once in the 15% calculation.
        */
        $ret_roots = self::get_all_roots($final); //get all roots of 'Reduced hierarchies'
        $all_roots = $ret_roots['roots'];
        $count_all_roots = count($all_roots);
        // echo "\nRoots info: "; print_r($ret_roots); //debug only

        // $all_roots = array(1, 42430800); //test force assignment -- debug only
        // if(true) {

        if($count_all_roots > 1) {
            if($GLOBALS['ENV_DEBUG']) {echo "\nMultiple roots: "; print_r($all_roots);}
            $temp_final = self::roots_lessthan_15percent_removal_step($original_records, $all_roots, $final);
            if($temp_final != $final) {
                $final = $temp_final;
                if($GLOBALS['ENV_DEBUG']) {echo "\nHierarchies after discarding those representing less than 15% of the original records: "; print_r($final);}
            }
            // else echo "\nfinal and temp_final are equal\n"; //just debug
            unset($temp_final);
        }
        else debug("\nJust one root ($all_roots[0]). Will skip this step.\n");
        debug("\n==========================================================\nFinal step:\n");
        /* ---------------------------------------------------------------------------------------------------------------------------------------
        IF >1 roots remain:,
        All the remaining roots are REP records,
        the one that appears in the most ancestries is the PRM,
        ,
        IF one root remains:,
        All direct children of the remaining root are REP records,
        the one that appears in the most ancestries is the PRM,
        (i.e. same behavior as taxon summary),
        ,
        "In this case, one root remains (taxon ID=1)",
        REP records:,
        2774383,
        166,
        10459935,
        ,
        PRM record:,
        2774383,
        */
        $ret_roots = self::get_all_roots($final); //get all roots of 'Reduced hierarchies'
        $all_roots = $ret_roots['roots'];
        $count_all_roots = count($all_roots);
        if($GLOBALS['ENV_DEBUG']) {echo "\nList of root(s) and the corresponding no. of records it existed:"; print_r($ret_roots);} //good debug
        if($count_all_roots == 1) {
            debug("\nAll direct children of the remaining root are REP records, the one that appears in the most ancestries is the PRM.\n");
            //from taxon summary:
            $ret = self::get_immediate_children_of_root_info($final);
            $immediate_children_of_root         = $ret['immediate_children_of_root'];
            $immediate_children_of_root_count   = $ret['immediate_children_of_root_count'];
            if($GLOBALS['ENV_DEBUG']) {echo "\nImmediate children of root => and the no. of records it existed:"; print_r($immediate_children_of_root_count); echo "\n";}
            /* ver. 1 strategy
            $root_ancestor = array_unique($root_ancestor);
            */
            // /* ver. 2 strategy
            $root_ancestor = self::get_key_of_arr_with_biggest_value($immediate_children_of_root_count);
            // */
            $immediate_children_of_root = array_keys($immediate_children_of_root);
            if($GLOBALS['ENV_DEBUG']) {
                echo "\nPRM record: $root_ancestor (the one that appears in the most ancestries)";
                echo "\nREP records: "; print_r($immediate_children_of_root);
            }
            return array('tree' => $final, 'root' => $root_ancestor, 'root label' => 'PRM and REP', 'Selected' => $immediate_children_of_root, 'Label' => 'REP');
            
        } //end IF one root remains ------------------------------------------------------------
        elseif($count_all_roots > 1) { //has not met this criteria yet in our test cases.
            debug("\nMore than 1 root remain. All the remaining roots are REP records, the one that appears in the most ancestries is the PRM.\n");
            /* IF >1 roots remain:,
            - All the remaining roots are REP records,
            - the one that appears in the most ancestries is the PRM,
            e.g. List of roots and the corresponding no. of records it existed:
            $ret_roots = Array(
                [roots] => Array(
                        [0] => 1
                        [1] => 173 
                        [2] => 143
                [count_of_roots] => Array(
                        [1] => 7
                        [173] => 2
                        [143] = 1
            )*/
            $root_ancestor = self::get_key_of_arr_with_biggest_value($ret_roots['count_of_roots']);
            if($GLOBALS['ENV_DEBUG']) {
                echo "\nPRM record: $root_ancestor (the one that appears in the most ancestries)";
                echo "\nREP records: "; print_r($ret_roots['roots']);
            }
            return array('tree' => $final, 'root' => $root_ancestor, 'root label' => 'PRM and REP', 'Selected' => $ret_roots['roots'], 'Label' => 'REP');
        } //end if > 1 roots remain ------------------------------------------------------------
        return array(); //new Aug 12'19
        exit("\nexit muna [$predicate]\n");
    }
    private function roots_lessthan_15percent_removal_step($original_records, $all_roots, $final_from_main)
    {
        /* compute how many records from the original_records does the root exists */
        foreach($original_records as $page_id) {
            $ancestries[] = self::get_ancestry_via_DH($page_id);
        }
        foreach($all_roots as $root) {
            if(!isset($final[$root])) $final[$root] = 0;
            foreach($ancestries as $anc) {
                if(in_array($root, $anc)) @$final[$root]++;
            }
        }
        // print_r($final); //good debug
        /* get those that are < 15% */
        $remove = array();
        foreach($final as $root => $count) {
            $percentage = ($count/count($original_records))*100;
            $final2['roots % in original records'][$root] = $percentage;
            if($percentage < 15) $remove[] = $root;
        }
        if($GLOBALS['ENV_DEBUG']) print_r($final2);
        // echo "\nremove: "; print_r($remove);
        if($remove) {
            /* remove from $final_from_main those with roots that are < 15% coverage in $original_records */
            foreach($final_from_main as $page_id => $ancestry) {
                if($ancestry) {
                    $orig_ancestry = $ancestry;
                    $root = array_pop($ancestry); //the last rec from an array
                    if(in_array($root, $remove)) {}
                    else $final3[$page_id] = $orig_ancestry;
                }
                else $final3[$page_id] = $ancestry;
            }
            // echo "\nwent here 01\n";
            return $final3;
        }
        else  {
            // echo "\nwent here 02\n";
            return $final_from_main;
        }
    }
    private function get_all_roots($reduced_hierarchies)
    {
        $final = array();
        foreach($reduced_hierarchies as $page_id => $anc) {
            if($anc) {
                $last = array_pop($anc);
                $final[$last] = '';
                @$count_of_roots[$last]++;
            }
            else { //case where both a root and a tip.
                $final[$page_id] = '';
                @$count_of_roots[$page_id]++;
            }
        }
        return array('roots' => array_keys($final), 'count_of_roots' => $count_of_roots);
    }
    private function adjust_2913056($hierarchies_of_taxon_values) //If the common root of the dataset is anything else, you can leave it. Only remove it if it is 2913056 
    {
        /* Rules: magic five (46702381, 2910700, 6061725, 2908256, 2913056)
        - If the root node is any of these five, and if it is common to all 'hierarchies_of_taxon_values', then I'll remove that root node from all hierarchies.
        - If there are multiple root nodes, but all are included in the magic five -> remove all
        - if there are multiple root nodes, some are outside of the magic five -> remove magic 5 roots, leave the others
        */
        
        /* Jennifer Hammock added a comment - 06/Aug/19 10:59 PM
        Ah, thanks, Eli- I left this task for so long I've forgotten important pieces of it. Hang in there. I think the following would make sense to try:
        1. select all values which are taxa known to the Dynamic Hierarchy
        2. retrieve DH ancestry for all values, using landmarks taxa (including Family taxa) only
        3. construct a hierarchy of all values and nearest shared ancestors (not all shared ancestors, only those necessary to connect the values)
        4. remove the magic 5 wherever they appear. (this may leave you with multiple roots; that's OK)
        5. if multiple roots, label all roots as REP, and the one that appears in the most hierarchies PRM
        6. if single root, label all immediate children REP, and the one that appears in the most hierarchies PRM

        I'm not sure how much that differs from what you have now. I think it might simplify the magic 5 rules, if nothing else. Let's try it on the regular taxon summary process 
        (not yet on parents taxon summary) and see how it goes.
        I suppose this one was inevitably going to be iterative/experimental, but I think we're getting close!
        */
        $root_nodes_to_remove = $this->magic8;
        $cont_for_more = false;
        $final = array();
        foreach($hierarchies_of_taxon_values as $page_id => $anc) {
            if(!is_array($anc)) {
                $final[$page_id] = $anc;
                continue;
            }
            $orig_anc = $anc;
            $last = array_pop($anc);
            if(in_array($last, $root_nodes_to_remove)) {
                $final[$page_id] = $anc;
                $cont_for_more = true;
            }
            else $final[$page_id] = $orig_anc;
        }
        if($cont_for_more) {
            while(true) {
                $cont_for_more = false;
                foreach($final as $page_id => $anc) {
                    $orig_anc = $anc;
                    $last = array_pop($anc);
                    if(in_array($last, $root_nodes_to_remove)) {
                        $final2[$page_id] = $anc;
                        $cont_for_more = true;
                    }
                    else $final2[$page_id] = $orig_anc;
                }
                if($cont_for_more) {
                    $final = $final2;
                    $final2 = array();
                }
                else break; //break from while true
            }
            return self::last_round_2del_magic5($final2, $root_nodes_to_remove);
        }
        else return self::last_round_2del_magic5($final, $root_nodes_to_remove);
        
        /* version 1 obsolete
        $life = 2913056;
        $remove_last_rec = true;
        foreach($hierarchies_of_taxon_values as $page_id => $anc) {
            $last = array_pop($anc);
            if($last != $life) {
                $remove_last_rec = false; //if only if one is not $life then don't remove last rec.
                break; //end loop
            }
        }
        if($remove_last_rec) {
            echo "\nNOTE: Common root of hierarchies of taxon values (n=".count($hierarchies_of_taxon_values).") is 'Life:2913056'. Will remove this common root.\n";
            $final = array();
            foreach($hierarchies_of_taxon_values as $page_id => $anc) {
                array_pop($anc);
                $final[$page_id] = $anc;
            }
            return $final;
        }
        else return $hierarchies_of_taxon_values;
        */
    }
    private function last_round_2del_magic5($final, $root_nodes_to_remove) //makes a difference test pTS 1018 - RO_0002470 without 2908256_1018_RO_0002470 2913056_1018_RO_0002470 rightfully so.
    {   $final2 = array();
        foreach($final as $page_id => $anc) {
            if(in_array($page_id, $root_nodes_to_remove) && !$anc) continue; //new Aug 13'19
            else $final2[$page_id] = $anc;
        }
        return $final2;
    }
    //############################################################################################ end method = 'parents'
    private function extract_DH() //for production env only
    {
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($this->EOL_DH, "taxa.txt", array('timeout' => 60*10, 'expire_seconds' => 60*60*24*25)); //expires in 25 days
        $tables['taxa'] = 'taxa.txt';
        $paths['tables'] = $tables;
        return $paths;
    }
    private function prep_DH()
    {
        if(Functions::is_production()) {
            // if(!($info = self::extract_DH())) return;
            $info = Array('archive_path' => '/u/scripts/data_files/DH_v1_1/',
                          'temp_dir' => 'not used',
                          'tables' => Array('taxa' => 'taxon.tab'));
        }
        else { //local development only
            /*
            $info = Array('archive_path' => '/Library/WebServer/Documents/eol_php_code/tmp/dir_52635/EOL_dynamic_hierarchy/',   //for eoldynamichierarchyv1.zip
                          'temp_dir' => '/Library/WebServer/Documents/eol_php_code/tmp/dir_52635/',
                          'tables' => Array('taxa' => 'taxa.txt'));
            */
            
            $info = Array('archive_path' => '/Volumes/AKiTiO4/web/cp/summary data resources/DH/eoldynamichierarchywithlandmarks/',   //for eoldynamichierarchywithlandmarks.zip
                          'temp_dir' => '/Library/WebServer/Documents/eol_php_code/tmp/dir_77578/',
                          'tables' => Array('taxa' => 'taxa.txt'));

            // /* Aug 7, 2019 - cannot use latest DH yet, since I used the older version during caching steps. "/Volumes/AKiTiO4/d_w_h/EOL Dynamic Hierarchy Active Version/DH_v1_1/taxon.tab"; //latest active DH ver.
            $info = Array('archive_path' => '/Volumes/AKiTiO4/d_w_h/EOL Dynamic Hierarchy Active Version/DH_v1_1/',
                          'temp_dir' => '/Library/WebServer/Documents/eol_php_code/tmp/xxx/',
                          'tables' => Array('taxa' => 'taxon.tab'));
            // */
            
            /*
            // for MacBook
            $info = Array('archive_path' => '/Users/eagbayani/Sites/cp/summary data resources/DH/eoldynamichierarchywithlandmarks/',   //for eoldynamichierarchywithlandmarks.zip
                          'temp_dir' => '/Library/WebServer/Documents/eol_php_code/tmp/dir_77578/',
                          'tables' => Array('taxa' => 'taxa.txt'));
            */
        }
        print_r($info);
        return $info;
    }
    function parse_DH()
    {   echo "\nparsing DH...\n";
        $info = self::prep_DH();
        $i = 0;
        foreach(new FileIterator($info['archive_path'].$info['tables']['taxa']) as $line_number => $line) {
            $line = explode("\t", $line); $i++;
            if($i == 1) $fields = $line;
            else {
                if(!$line[0]) break;
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k]; $k++;
                }
                // print_r($rec); exit;
                /*Array(
                    [taxonID] => -168611
                    [acceptedNameUsageID] => -168611
                    [parentNameUsageID] => -105852
                    [scientificName] => Torpediniformes
                    [taxonRank] => order
                    [source] => trunk:59edf7f2-b792-4351-9f37-562dd522eeca,WOR:10215,gbif:881
                    [taxonomicStatus] => accepted
                    [canonicalName] => 
                    [scientificNameAuthorship] => 
                    [scientificNameID] => 
                    [taxonRemarks] => 
                    [namePublishedIn] => 
                    [furtherInformationURL] => 
                    [datasetID] => trunk
                    [EOLid] => 8898
                    [EOLidAnnotations] => multiple;
                    [Landmark] => 1
                )
                Array(
                    [taxonID] => 93302
                    [acceptedNameUsageID] => 93302
                    [parentNameUsageID] => -1
                    [scientificName] => Cellular Organisms
                    [taxonRank] => clade
                    [source] => trunk:b72c3e8e-100e-4e47-82f6-76c3fd4d9d5f
                    [taxonomicStatus] => accepted
                    [canonicalName] => 
                    [scientificNameAuthorship] => 
                    [scientificNameID] => 
                    [taxonRemarks] => 
                    [namePublishedIn] => 
                    [furtherInformationURL] => 
                    [datasetID] => trunk
                    [EOLid] => 6061725
                    [EOLidAnnotations] => manual;
                    [Landmark] => 
                )
                */
                /* debugging
                // if($rec['EOLid'] == 3014446) {print_r($rec); exit;}
                // if($rec['taxonID'] == 93302) {print_r($rec); exit;}
                // if($rec['Landmark']) print_r($rec);
                if(in_array($rec['EOLid'], Array(7687,3014522,42399419,32005829,3014446,2908256))) print_r($rec);
                */
                $this->EOL_2_DH[$rec['EOLid']] = $rec['taxonID'];
                $this->DH_2_EOL[$rec['taxonID']] = $rec['EOLid'];
                $this->parent_of_taxonID[$rec['taxonID']] = $rec['parentNameUsageID'];
                $this->landmark_value_of[$rec['EOLid']] = $rec['Landmark'];
                if($rec['taxonRank'] == 'family') $this->is_family[$rec['EOLid']] = '';
                
/*
From eoldynamichierarchywithlandmarks.zip:
taxonID	acceptedNameUsageID	parentNameUsageID	scientificName	taxonRank	source	taxonomicStatus	canonicalName	scientificNameAuthorship	scientificNameID	taxonRemarks	namePublishedIn	furtherInformationURL	datasetID	EOLid	EOLidAnnotations	Landmark
-100000	-100000	-79407	Frescocyathus nagagreboensis Barta-Calmus, 1969	species	gbif:4943435	accepted	Frescocyathus nagagreboensis	Barta-Calmus, 1969				https://www.gbif-uat.org/species/4943435	6cfd67d6-4f9b-400b-8549-1933ac27936f			
-100001	-100001	-79407	Frescocyathus tessieri Barta-Calmus, 1969	species	gbif:8640616	accepted	Frescocyathus tessieri	Barta-Calmus, 1969				https://www.gbif-uat.org/species/8640616	6cfd67d6-4f9b-400b-8549-1933ac27936f			
-100002	-100002	-79409	Diphhelia raristella Haime & Milne-Edwards	species	gbif:4879811	accepted	Diphhelia raristella	Haime & Milne-Edwards				https://www.gbif-uat.org/species/4879811	0938172b-2086-439c-a1dd-c21cb0109ed5			

From DH v1.1:
taxonID	source	furtherInformationURL	acceptedNameUsageID	parentNameUsageID	scientificName	higherClassification	taxonRank	taxonomicStatus	taxonRemarks	datasetID	canonicalName	EOLid	EOLidAnnotations	Landmark
EOL-000000000001	trunk:1bfce974-c660-4cf1-874a-bdffbf358c19,NCBI:1				Life		clade	valid		trunk	Life	2913056		3
EOL-000000000002	trunk:a0af9616-4500-4402-987d-3dcf4dec5794,NCBI:131567			EOL-000000000001	Cellular Organisms	Life	clade	valid		trunk	Cellular Organisms	6061725		
EOL-000000000003	trunk:be97d60f-6568-4cba-92e3-9d068a1a85cf,NCBI:2,WOR:6			EOL-000000000002	Bacteria	Life|Cellular	domain	valid		trunk	Bacteria	288		1
*/
            }
        }
        /* may not want to force assign this:
        $this->DH_2_EOL[93302] = 6061725; //Biota - Cellular Organisms
        */
        
        // remove temp dir
        // recursive_rmdir($info['temp_dir']);
        // echo ("\n temporary directory removed: " . $info['temp_dir']);
    }
    function get_ancestry_via_DH($page_id, $landmark_only = true)
    {
        $final = array(); $final2 = array();
        $taxonID = @$this->EOL_2_DH[$page_id];
        if(!$taxonID) {
            debug("\nThis page_id [$page_id] is not found in DH.");
            return array();
        }
        while(true) {
            if($parent = @$this->parent_of_taxonID[$taxonID]) $final[] = $parent;
            else break;
            $taxonID = $parent;
        }
        $i = 0;
        foreach($final as $taxonID) {
            // echo "\n$i. [$taxonID] => ";
            if($EOLid = @$this->DH_2_EOL[$taxonID]) {
                /* new strategy: using Landmark value   ver 1
                if($this->landmark_value_of[$EOLid]) $final2[] = $EOLid; */

                if($landmark_only) { //default; new strategy: using Landmark value   ver 2
                    if($this->landmark_value_of[$EOLid] || isset($this->is_family[$EOLid])) $final2[] = $EOLid;
                }
                else { //orig strategy
                    $final2[] = $EOLid;
                }
            }
            $i++;
        }
        return $final2;
    }
    private function main_taxon_summary($page_id, $predicate)
    {   /* latest comment: June 2019 by Jen
        Basal values looks great! More in existing records and fewer de novo records than I expected, but that's the data. Values that I've looked at are all reasonable.

        Taxon summary includes reasonable records overall, but I think some may be missing. example: records for 1020198 with predicate http://purl.obolibrary.org/obo/RO_0002470 (eats)

        The results I see are 1 and 899325, which are both good, but there are a number of other DH-accepted taxa for "1020198 eats" which fall outside both groups. 
        By my reckoning: 3686, 45297170, 45297071, 910371, 12010, 907115, 916934, 47078402, 11731, 11755, 47075911, 11963, 12082, 12316, 12741, 10478, 12706, 12005, 896262, 12739, 897003. 
        I think I would expect some of these to provide a "de novo" record which would be REP and PRM, but when I looked in the taxon summary DwC-A, 
        I can't find 1020198 under EOL_taxonID in taxon.tab

        That was several manual steps, so I may have missed it. Before you believe me that something is missing, you may want to double check...

        There may also be duplicate records in the taxon_summary DwC-A. For instance, for 149275 with predicate http://purl.obolibrary.org/obo/RO_0002470 (eats), 
        I believe there are two identical records
        */
        
        /* working but seems not needed. Just bring it back when requested.
        $ancestry = self::get_ancestry_via_DH($page_id);
        echo "\n$page_id: (ancestors below, with {Landmark value} in curly brackets)";
        foreach($ancestry as $anc_id) echo "\n --- $anc_id {".$this->landmark_value_of[$anc_id]."}";
        */
        self::per_line('taxon summary', $page_id, $predicate);
        
        // $path = self::get_txt_path_by_page_id($page_id); //not needed anymore
        $recs = self::assemble_recs_for_page_id_from_text_file($page_id, $predicate, array(), 'TS'); //has script to handle multiple predicates ALREADY
                                                                                                     // 3rd param here is irrelevant, 4th param is important.
        if(!$recs) { echo "\nNo records for [$page_id] [$predicate].\n"; return; }
        debug("\nAssembled recs: ".count($recs)."\n");
        // print_r($recs); exit;
        
        /* debug only - good debug - listing initial collection
        echo "\n".implode("\t", array_keys($recs[0]));
        foreach($recs as $rec) echo "\n".implode("\t", $rec);
        */
        
        if($this->parentModeYN) $this->taxon_summary_parent_recs = array_merge($this->taxon_summary_parent_recs, $recs); //to be in writing resource file
        
        /* Jen's verbatim instruction: to get the reduced 'tree'
        For each ancestor, find all recs in which it appears (recs set 1)
        If the parent of that ancestor is the same in all the recs in rec set 1, remove the parent

        Eli's interpretation: which gets the same results:
        - get all ancestors that exist also in other recs.
        - among these ancestors, select those where it has > 1 children. Don't include those with the same child in its occurrence in other recs.
        */
        $unique_page_ids = array();
        foreach($recs as $rec) {
            if($page_id = @$rec['object_page_id']) {
                if(isset($unique_page_ids[$page_id])) continue;
                else $unique_page_ids[$page_id] = '';
                
                $anc = self::get_ancestry_via_DH($page_id);
                // /* initial report for Jen
                // echo "\nAncestry [$page_id]: "; print_r($anc); //orig initial report
                if($anc) {
                    if($GLOBALS['ENV_DEBUG']) {
                        echo "\n$page_id: (ancestors below, with {Landmark value} in curly brackets)";
                        foreach($anc as $anc_id) echo "\n --- $anc_id {".$this->landmark_value_of[$anc_id]."}";
                    }
                }
                // */
                //start store counts 1:
                $k = 0;
                foreach($anc as $id) {
                    @$counts[$id]++;
                    @$orig_counts_with_left[$id]++;
                    if($k > 0) $children_of[$id][] = $anc[$k-1];
                    $k++;
                }
                @$orig_counts_with_left[$page_id]++;
            }
        }
        // print_r($counts); print_r($children_of); //good debug
        $final = array(); $eol_pks = array(); //$eol_pks here is for getting the refs
        $unique_page_ids = array();
        foreach($recs as $rec) {
            if($page_id = @$rec['object_page_id']) {
                if(isset($unique_page_ids[$page_id])) continue;
                else $unique_page_ids[$page_id] = '';

                $anc = self::get_ancestry_via_DH($page_id);
                foreach($anc as $id) {
                    if($count = @$counts[$id]) {
                        if($count >= 2) { //meaning this ancestor exists in other recs
                            if($arr = @$children_of[$id]) {
                                $arr = array_unique($arr);
                                if(count($arr) > 1) {
                                    $final[$page_id][] = $id; //meaning child is not the same for all recs
                                    // $eol_pks[$rec['eol_pk']] = ''; //abandoned already
                                }
                            }
                        }
                    }
                }
            }
        }
        /* For refs START --- commented since refs are now assembled at write_resource_file_TaxonSummary()
        $refs = array();
        if($eol_pks) $refs = self::get_refs_from_metadata_csv(array_keys($eol_pks));
        For refs END */
        $refs = array();

        debug("\n==========================================\nTips on left. Ancestors on right.\n");
        // print_r($final);
        foreach($final as $tip => $ancestors) {
            if($GLOBALS['ENV_DEBUG']) {
                echo "\n$tip: (reduced ancestors below, with {Landmark value} in curly brackets)";
                foreach($ancestors as $anc_id) echo "\n --- $anc_id {".$this->landmark_value_of[$anc_id]."}";
            }
        }
        debug("\n");
        /* may not need this anymore: get tips
        $tips = array_keys($final); //next step is get all tips from $final; 
        echo "\n tips: ".count($tips)." - "; print_r($tips);
        */
        /* from Jen: After the tree is constructed:
        - Select all immediate children of the root and label REP.
        - Label the root PRM
        */
        if($GLOBALS['ENV_DEBUG']) {
            echo "\nTS final hierarchies: ".count($final)."\n"; print_r($final);
        }
        if(!$this->parentModeYN) {
            $final = self::adjust_2913056($final); //apply magic 5 for taxon_summary
            if($GLOBALS['ENV_DEBUG']) {
                echo "\nTS final hierarchies, after magic 8: ".count($final)."\n"; print_r($final);
            }
        }
        
        $this->ISVAT_TS = $this->ISVAT_TS + $final;
        
        if(!$final) return false;
        $ret = self::get_immediate_children_of_root_info($final);
        $immediate_children_of_root         = $ret['immediate_children_of_root'];
        $immediate_children_of_root_count   = $ret['immediate_children_of_root_count'];
        
        if($GLOBALS['ENV_DEBUG']) {
            echo "\nImmediate children of root => no. of records it existed:";
            print_r($immediate_children_of_root_count); echo "\n";
        }
        /* ver. 1 strategy
        $root_ancestor = array_unique($root_ancestor);
        */
        // /* ver. 2 strategy
        $root_ancestor = self::get_key_of_arr_with_biggest_value($immediate_children_of_root_count);
        // */
        $immediate_children_of_root = array_keys($immediate_children_of_root);
        
        if($GLOBALS['ENV_DEBUG']) {echo "\n root: "; print_r($root_ancestor);}
        // echo "\n immediate_children_of_root: "; print_r($immediate_children_of_root);
        /* START NEW: Per Jen: Please make the PRM record, not the root, but the REP record that appears in the most hierarchies in the original list. 
        I think we might end up doing that with all four applicable methods (basal values and taxon summary, regular and parents). */
        if($GLOBALS['ENV_DEBUG']) {
            echo "\nImmediate children of root => no. of records it existed FROM THE ORIGINAL RECORDS: ";
            foreach($immediate_children_of_root as $id) echo "\n [$id] => ".$orig_counts_with_left[$id];
        }
        return array('root' => $root_ancestor, 'root label' => 'PRM and REP', 'Selected' => $immediate_children_of_root, 'Label' => 'REP', 'refs' => $refs);
        //'tree' => $final, 'orig_counts_with_left' => $orig_counts_with_left
    }
    //$predicate
    private function get_immediate_children_of_root_info($final)
    {   $immediate_children_of_root = array();
        $immediate_children_of_root_count = array();
        foreach($final as $tip => $ancestors) {
            if($ancestors) {
                $root_ancestor[] = end($ancestors);
                $no_of_rows = count($ancestors);
                if($no_of_rows > 1) $idx = $ancestors[$no_of_rows-2]; // rows should be > 1 bec if only 1 then there is no child for that root.
                elseif($no_of_rows == 1) $idx = $tip; 
                else exit("\nInvestigate: won't go here...\n");
                $immediate_children_of_root[$idx] = '';
                @$immediate_children_of_root_count[$idx]++;
            }
        }
        return array('immediate_children_of_root' => $immediate_children_of_root, 'immediate_children_of_root_count' => $immediate_children_of_root_count);
    }
    private function get_key_of_arr_with_biggest_value($arr)
    {   $val = 0; $ret = '';
        foreach($arr as $key => $value) {
            if($value > $val) $ret = $key;
            $val = $value;
        }
        return $ret;
    }
    private function main_lifestage_statMeth($page_id, $predicate)
    {   
        self::per_line('lifestage & statMeth', $page_id, $predicate);
        // $path = self::get_txt_path_by_page_id($page_id); //not needed anymore
        $recs = self::assemble_recs_for_page_id_from_text_file($page_id, $predicate);
        // print_r($recs); exit;
        if(!$recs) { echo "\nNo records for [$page_id] [$predicate].\n"; return; }
        debug("\nCandidate records: ".count($recs)."\n"); //print_r($recs); //good debug
        if    ($ret = self::lifestage_statMeth_Step0($recs)) {}
        elseif($ret = self::lifestage_statMeth_Step1($recs)) {}
        elseif($ret = self::lifestage_statMeth_Step23456789($recs)) {}
        else exit("\nsingle simple answer (PRM) if still needed: put REP records in order of value and select one from the middle (arbitrary tie breaks OK)\n");
        if($val = @$ret['recs']) $ret['recs_total'] = count($val);
        if(count($ret['recs']) > 1) {
            if($GLOBALS['ENV_DEBUG']) print_r($ret);
            $ret['recs'] = self::sort_value_then_get_mid_record($ret['recs']);
            $ret['recs_total'] = count($ret['recs']);
            $ret['label'] = 'REP';
            if($GLOBALS['ENV_DEBUG']) print_r($ret);
            debug("\nMore than 1 record, do sort and picked middle record.\n"); //good debug
        }
        else {
            // print_r($ret); exit("\nNormal operation. Debug only\n"); //debug only
        }
        return $ret;
    }
    private function sort_value_then_get_mid_record($arr)
    {   /* Sorry, I missed your question June 11th about selecting PRM from multiple records.
        Please sort by measurementValue and, if there are only two records, select the greater value for PRM. */
        $new = array();
        foreach($arr as $rec) $new[$rec['measurement']] = $rec;
        ksort($new); $final = array();
        foreach($new as $rec) $final[] = $rec; //so to have a fresh index from 0 then 1 and so on...
        return Functions::get_middle_record_from_array($final);
    }
    private function lifestage_statMeth_Step0($recs)
    {
        if(count($recs) == 1) return array('label' => 'PRM and REP', 'recs' => $recs, 'step' => 0);
        else return false;
    }
    private function lifestage_statMeth_Step1($recs)
    {   $possible_adult_lifestage = array("http://www.ebi.ac.uk/efo/EFO_0001272", "http://purl.obolibrary.org/obo/PATO_0001701", "http://eol.org/schema/terms/parasiticAdult", "http://eol.org/schema/terms/freelivingAdult", "http://eol.org/schema/terms/ovigerous", "http://purl.obolibrary.org/obo/UBERON_0007222", "http://eol.org/schema/terms/youngAdult");
        $final = array();
        foreach($recs as $rec) {
            /* print_r($rec); exit;
            Array(
                [eol_pk] => R143-PK39533097
                [page_id] => 46559130
                [scientific_name] => <i>Enhydra lutris</i>
                ...more fields below
            */
            if(in_array($rec['lifestage'], $possible_adult_lifestage)) {
                $statMethods = array("http://semanticscience.org/resource/SIO_001109", "http://semanticscience.org/resource/SIO_001110", "http://semanticscience.org/resource/SIO_001111");
                if(in_array($rec['statistical_method'], $statMethods)) $final[] = $rec;
            }
        }
        if(!$final) return false;
        else {
            if    (count($final) == 1) return array('label' => 'PRM and REP', 'recs' => $final, 'step' => 1);
            elseif(count($final) > 1)  return array('label' => 'REP', 'recs' => $final, 'step' => 1);
        }
    }
    private function lifestage_statMeth_Step23456789($recs) //steps 2,3,4,5 & 6 7 8 & 9
    {   /* Step 2,3,4,5 */
        $possible_adult_lifestage = array("http://www.ebi.ac.uk/efo/EFO_0001272", "http://purl.obolibrary.org/obo/PATO_0001701", "http://eol.org/schema/terms/parasiticAdult", "http://eol.org/schema/terms/freelivingAdult", "http://eol.org/schema/terms/ovigerous", "http://purl.obolibrary.org/obo/UBERON_0007222", "http://eol.org/schema/terms/youngAdult");
        $statMethods = array("http://eol.org/schema/terms/average", "http://semanticscience.org/resource/SIO_001114", "http://www.ebi.ac.uk/efo/EFO_0001444", ""); //in specific order
        $step = 1;
        foreach($statMethods as $method) { $step++;
            $final = array();
            foreach($recs as $rec) {
                if(in_array($rec['lifestage'], $possible_adult_lifestage)) {
                    if($rec['statistical_method'] == $method) $final[] = $rec;
                }
            }
            if($final) {
                if    (count($final) == 1) return array('label' => 'PRM and REP', 'recs' => $final, 'step' => $step);
                elseif(count($final) > 1)  return array('label' => 'REP', 'recs' => $final, 'step' => $step);
            }
        }
        
        /* original Step 6 , 7 , 8 -- now separated below, with an additional step 6.5 --> now becomes 6,7 (6.5),8,9
        $stages = array("http://purl.obolibrary.org/obo/PO_0007134", "", "http://eol.org/schema/terms/subadult"); //in specific order
        $step = 5;
        foreach($stages as $stage) { $step++;
            $final = array();
            foreach($recs as $rec) {
                if($rec['lifestage'] == $stage) $final[] = $rec;
            }
            if($final) {
                if    (count($final) == 1) return array('label' => 'PRM and REP', 'recs' => $final, 'step' => $step);
                elseif(count($final) > 1)  return array('label' => 'REP', 'recs' => $final, 'step' => $step);
            }
        }
        */

        /* Step 6 */
        $stages = array("http://purl.obolibrary.org/obo/PO_0007134"); $step = 5;
        foreach($stages as $stage) { $step++;
            $final = array();
            foreach($recs as $rec) {
                if($rec['lifestage'] == $stage) $final[] = $rec;
            }
            if($final) {
                if    (count($final) == 1) return array('label' => 'PRM and REP', 'recs' => $final, 'step' => $step);
                elseif(count($final) > 1)  return array('label' => 'REP', 'recs' => $final, 'step' => $step);
            }
        }
        /* Step 7 -- 6.5 */
        $stages = array(""); $step = 6;
        $statMethods = array('http://semanticscience.org/resource/SIO_001109', 'http://semanticscience.org/resource/SIO_001110', 'http://semanticscience.org/resource/SIO_001111', 
                             'http://eol.org/schema/terms/average', 'http://semanticscience.org/resource/SIO_001114');
        foreach($stages as $stage) { $step++;
            $final = array();
            foreach($recs as $rec) {
                if($rec['lifestage'] == $stage && in_array($rec['statistical_method'], $statMethods)) $final[] = $rec;
            }
            if($final) {
                if    (count($final) == 1) return array('label' => 'PRM and REP', 'recs' => $final, 'step' => $step);
                elseif(count($final) > 1)  return array('label' => 'REP', 'recs' => $final, 'step' => $step);
            }
        }
        /* Step 8 , 9 -- 7 , 8 */
        $stages = array("", "http://eol.org/schema/terms/subadult"); //in specific order
        $step = 7;
        foreach($stages as $stage) { $step++;
            $final = array();
            foreach($recs as $rec) {
                if($rec['lifestage'] == $stage) $final[] = $rec;
            }
            if($final) {
                if    (count($final) == 1) return array('label' => 'PRM and REP', 'recs' => $final, 'step' => $step);
                elseif(count($final) > 1)  return array('label' => 'REP', 'recs' => $final, 'step' => $step);
            }
        }
        /* Step 10 -- 9 */
        $final = array();
        foreach($recs as $rec) {
            $possible_adult_lifestage = array("http://www.ebi.ac.uk/efo/EFO_0001272", "http://purl.obolibrary.org/obo/PATO_0001701", "http://eol.org/schema/terms/parasiticAdult", "http://eol.org/schema/terms/freelivingAdult", "http://eol.org/schema/terms/ovigerous", "http://purl.obolibrary.org/obo/UBERON_0007222", "http://eol.org/schema/terms/youngAdult", "adult");
            if(in_array($rec['lifestage'], $possible_adult_lifestage)) {
                $statMethods = array("http://semanticscience.org/resource/SIO_001113");
                if(in_array($rec['statistical_method'], $statMethods)) $final[] = $rec;
            }
        }
        if(!$final) return false;
        else {
            if    (count($final) == 1) return array('label' => 'PRM and REP', 'recs' => $final, 'step' => 10);
            elseif(count($final) > 1)  return array('label' => 'REP', 'recs' => $final, 'step' => 10);
        }
        return false;
    }
    private function get_txt_path_by_page_id($page_id, $ext = ".txt")
    {   $path = self::get_md5_path($this->working_dir, $page_id);
        return $path . $page_id . $ext;
    }
    private function per_line($method, $page_id, $predicate)
    {
        if($GLOBALS['ENV_DEBUG']) echo "\n================================================================Method: $method - page_id: $page_id | predicate: [$predicate]";
        else {
            @$this->modulo++;
            if(($this->modulo % 5000) == 0) echo "\n".number_format($this->modulo)."===============Method: $method - page_id: $page_id | predicate: [$predicate]";
        }
    }
    // private function main_basal_values($page_id, $predicate, $type = 'basal values', $param_isvat = false, $original_nodes = array()) //version 1 - didn't use
    private function main_basal_values($page_id, $predicate, $type = 'basal values', $recs = array()) //for basal values
    {
        $this->original_nodes = array(); //IMPORTANT to initialize especially for multiple calls of this function main_basal_values()
        if($type == 'basal values') {
            $this->parent_basal_values_YesNo = false;
            self::per_line('basal values', $page_id, $predicate);
            $recs = self::assemble_recs_for_page_id_from_text_file($page_id, $predicate, array('value_uri')); //3rd param array is required_fields
            if(!$recs) {
                debug("\nNo records for [$page_id] [$predicate].\n");
                return false;
            }
        }
        elseif($type == 'parent basal values') { //for parent basal values
            $this->parent_basal_values_YesNo = true;
            
            /* version 1 - didn't use
            $this->original_nodes = $original_nodes;
            $ISVAT = $param_isvat;
            */
            $this->basal_values_parent_recs = $recs;
            $this->original_nodes = $this->original_nodes_parent;
            if(!$recs) {
                debug("\n01. No records for [$page_id] [$predicate].\n");
                return false;
            }
        }
        else exit("\nShould not go here...\n");
        debug("\n A1 recs: ".count($recs));
        $recs = self::check_inferred_file($recs, $page_id, $predicate);
        debug("\n A2 recs: ".count($recs)."\n");
        // exit;
        $uris = self::get_valueUris_from_recs($recs);
        debug("\n B uris: ".count($uris));
        
        self::set_ancestor_ranking_from_set_of_uris($uris);
        // print_r($this->ancestor_ranking_preferred); exit;
        $ISVAT = self::get_initial_shared_values_ancestry_tree($recs); //initial "shared values ancestry tree" ---> parent left, term right
        // print_r($ISVAT); exit;
        $ISVAT = self::sort_ISVAT($ISVAT, 1);
        if(!$ISVAT) return false;
        $info = self::add_new_nodes_for_NotRootParents($ISVAT);
        $new_nodes = $info['new_nodes'];
        if($GLOBALS['ENV_DEBUG']) {
            echo "\n\nnew nodes 0:\n"; foreach($new_nodes as $a) echo "\n".$a[0]."\t".$a[1];
        }
        // print_r($new_nodes); exit;
        
        $info['new_nodes'] = self::sort_ISVAT($new_nodes, 2);
        $new_nodes = $info['new_nodes'];
        $roots     = $info['roots'];
        /* good debug
        echo "\n\nnew nodes 1:\n"; foreach($new_nodes as $a) echo "\n".$a[0]."\t".$a[1];
        echo "\n\nRoots 1: ".count($roots)."\n"; print_r($roots);
        */
        
        // /* merge
        $info = self::merge_nodes($info, $ISVAT);
        $ISVAT     = $info['new_isvat'];
        $roots     = $info['new_roots'];
        $new_nodes = array();
        // */
        
        // /* for jen:
        if($GLOBALS['ENV_DEBUG']) {
            echo "\n================================================================\npage_id: $page_id | predicate: [$predicate]\n";
            echo "\n\ninitial shared values ancestry tree: ".count($ISVAT)."\n";
            foreach($ISVAT as $a) echo "\n".$a[0]."\t".$a[1];
        }
        $this->ISVAT = $ISVAT;
        if($GLOBALS['ENV_DEBUG']) {
            // echo "\n\nnew nodes: ".count($new_nodes)."\n"; foreach($new_nodes as $a) echo "\n".$a[0]."\t".$a[1]; //good debug
            echo "\n\nInitial roots: ".count($roots)."\n"; print_r($roots);
        }
        // */
        
        //for step 1: So, first you must identify the tips- any values that don't appear in the left column. The parents, for step one, will be the values to the left of the tip values.
        $tips = self::get_tips($ISVAT);
        if($GLOBALS['ENV_DEBUG']) {
            echo "\n tips: ".count($tips);
            foreach($tips as $tip) echo "\n$tip";
            echo "\n-end tips-\n"; //exit;
        }
        
        if($this->parent_basal_values_YesNo) { //parent mode
            $lessthanORequal5 = 15;
            $lessthanORequal4 = 15;
            // $lessthanORequal5 = 5;
            // $lessthanORequal4 = 4;
        }
        else { //non-parent mode
            $lessthanORequal5 = 5;
            $lessthanORequal4 = 4;
        }
        
        if(count($tips) <= $lessthanORequal5 ) $selected = $tips;
        else { // > 5
            // /* Two new steps from Jen & Katja                                               UNCOMMENT IN REAL OPERATION...
            $ret_from_2new_steps = self::two_new_steps($ISVAT, $roots, $tips);
            $roots = $ret_from_2new_steps['roots'];
            $tips = $ret_from_2new_steps['tips'];
            $ISVAT = $ret_from_2new_steps['ISVAT'];
            if($GLOBALS['ENV_DEBUG']) {
                echo "\nnew tips: ".count($tips); foreach($tips as $tip) echo "\n".$tip;
                echo "\n";
            }
            // */
            if($GLOBALS['ENV_DEBUG']) {
                echo "\n\nroots after deletion-steps: ".count($roots)."\n"; print_r($roots);
            }
            
            $step_1 = self::get_step_1($ISVAT, $roots, $tips, 1);
            if(count($step_1) <= $lessthanORequal4) $selected = $step_1; //select set 1
            else {
                $step_2 = self::get_step_1($ISVAT, $roots, $step_1, 2);
                if(count($step_2) <= $lessthanORequal4) $selected = $step_2; //select set 2
                else {
                    $step_3 = self::get_step_1($ISVAT, $roots, $step_2, 3);
                    if($step_2 == $step_3) {
                        debug("\nSteps 2 and 3 are identical.\n");
                        if(count($step_3) <= $lessthanORequal4) $selected = $step_3; //select set 3
                        else {
                            debug("\nSelect root ancestors\n");
                            $selected = $roots;
                        }
                    }
                    else {
                        debug("\nStep 2 and Step 3 are different. Proceed with Step 4\n");
                        $step_4 = self::get_step_1($ISVAT, $roots, $step_3, 4);
                        if($step_3 == $step_4) {
                            debug("\nSteps 3 and 4 are identical.\n");
                            if(count($step_4) <= $lessthanORequal4) $selected = $step_4; //select set 4
                            else {
                                debug("\nSelect root ancestors\n");
                                $selected = $roots;
                            }
                        }
                        else {
                            debug("\nStep 3 and Step 4 are different. Proceed with Step 5\n");
                            // exit("\nConstruct Step 5\n");
                            $step_5 = self::get_step_1($ISVAT, $roots, $step_4, 5);
                            if($step_4 == $step_5) {
                                debug("\nSteps 4 and 5 are identical.\n");
                                if(count($step_5) <= $lessthanORequal4) $selected = $step_5; //select set 5
                                else {
                                    debug("\nSelect root ancestors\n");
                                    $selected = $roots;
                                }
                            }
                            else {
                                debug("\nStep 4 and Step 5 are different. Proceed with Step 6\n");
                                // exit("\nConstruct Step 6\n");
                                $step_6 = self::get_step_1($ISVAT, $roots, $step_5, 6);
                                if($step_5 == $step_6) {
                                    debug("\nSteps 5 and 6 are identical.\n");
                                    if(count($step_6) <= $lessthanORequal4) $selected = $step_6; //select set 6
                                    else {
                                        debug("\nSelect root ancestors\n");
                                        $selected = $roots;
                                    }
                                }
                                else {
                                    debug("\nStep 5 and Step 6 are different. Proceed with Step 7\n");
                                    // exit("\nConstruct Step 7\n");
                                    $step_7 = self::get_step_1($ISVAT, $roots, $step_6, 7);
                                    if($step_6 == $step_7) {
                                        debug("\nSteps 6 and 7 are identical.\n");
                                        if(count($step_7) <= $lessthanORequal4) $selected = $step_7; //select set 7
                                        else {
                                            debug("\nSelect root ancestors\n");
                                            $selected = $roots;
                                        }
                                    }
                                    elseif($step_5 == $step_7 && $step_4 == $step_6) {
                                        debug("\nSteps 5 and 7 are identical; Steps 4 and 6 are identical.\n");
                                        if(count($step_7) <= $lessthanORequal4) $selected = $step_7; //select set 7
                                        else {
                                            debug("\nSelect root ancestors\n");
                                            $selected = $roots;
                                        }
                                    }
                                    else {
                                        debug("\nStep 6 and Step 7 are different. Proceed with Step 8\n");
                                        exit("\nConstruct Step 8\n");
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        //label PRM and REP if one record, REP if > 1
        $label = '';
        if    (count($selected) == 1) $label = 'PRM and REP';
        elseif(count($selected) > 1)  $label = 'REP';
        debug("\n----- label as: [$label] [".count($selected)."]\n");
        $selected = array_values($selected); //reindex array
        
        $ret = array('Selected' => $selected, 'label' => $label);
        // if($type == 'basal values') $ret['recs'] = $recs;    //if you want add 'recs' to the return value
        return $ret;
        /*
        if tips <= 5 SELECT ALL TIPS 
        else
            GET SET_1
            if SET_1 <= 4 SELECT SET_1
            else 
                GET SET_2
                if SET_2 <= 4 SELECT SET_2
                else
                    GET SET_3
                    if SET_2 == SET_3
                        if SET_3 <= 4 SELECT SET_3
                        else SELECT ROOT_ANCESTORS
                    else CONTINUE PROCESS UNTIL all parents of the values in the set are roots, THEN IF <= 4 SELECT THAT SET else SELECT ROOT_ANCESTORS.

        if(WHAT IS SELECTED == 1) label as: 'PRM and REP'
        elseif(WHAT IS SELECTED > 1) label as: 'REP'

        So in our case: page_id: 7662 | predicate: [http://eol.org/schema/terms/Habitat]
        I will be creating new rocords based on 'ROOT_ANCESTORS'.
        */
    }
    private function check_inferred_file($recs, $page_id, $predicate)
    {   /*[40] => Array(
                [eol_pk] => R512-PK89527614
                [page_id] => 7662
                [resource_pk] => 
                [resource_id] => 413
                [source] => https://eol.org/search?q=Carnivora
                [scientific_name] => Carnivora
                [predicate] => http://eol.org/schema/terms/Habitat
                [object_page_id] => 
                [value_uri] => http://purl.obolibrary.org/obo/ENVO_00000446
                [normal_measurement] => 
                [normal_units_uri] => 
                [normal_units] => 
                [measurement] => 
                [units_uri] => 
                [units] => 
                [literal] => http://purl.obolibrary.org/obo/ENVO_00000446
        )*/
        
        /* debug only
        $recs[] = Array(
                    'eol_pk' => 'R499-PK106095708',
                    'page_id' => 'elix',
                    'resource_pk' => '',
                    'resource_id' => '413',
                    'source' => 'https://eol.org/search?q=Carnivora',
                    'scientific_name' => 'Carnivora',
                    'predicate' => 'http://eol.org/schema/terms/Habitat',
                    'object_page_id' => '',
                    'value_uri' => 'http://purl.obolibrary.org/obo/ENVO_00000446',
                    'normal_measurement' => '',
                    'normal_units_uri' => '',
                    'normal_units' => '',
                    'measurement' => '',
                    'units_uri' => '',
                    'units' => '',
                    'literal' => 'http://purl.obolibrary.org/obo/ENVO_00000446'
                 );
        */
        
        // step 1: get all unique eol_pk and build $recs_info for lookup on step 2
        foreach($recs as $rec) {
            $unique_eol_pks[$rec['eol_pk']] = '';
            $recs_info[$rec['eol_pk']] = $rec;
        }
        $unique_eol_pks = array_keys($unique_eol_pks); //print_r($unique_eol_pks); exit;
        // step2: query inferred file if it has page_id(s) with these eol_pks from step 1.
        $in_str = implode(",", $unique_eol_pks);
        $in_str = str_replace(",", "','", $in_str);
        $in_str = "'".$in_str."'";
        // $in_str = "'R499-PK106095708'"; //debug only
        $sql = "SELECT DISTINCT t.page_id, t.inferred_trait FROM SDR.traits_inferred t WHERE t.inferred_trait IN ($in_str) ORDER BY t.page_id;";
        $result = $this->mysqli->query($sql);
        if($result->num_rows) {
            exit("\nGot hit from inferred file [$page_id], [$predicate]\nJust checking if we ever get hits.\n");
            while($result && $rek=$result->fetch_assoc()) { //print_r($rek);
                if($val = @$recs_info[$rek['inferred_trait']]) {
                    $val['page_id'] = $rek['page_id'];
                    $val['resource_pk'] = '';
                    $val['resource_id'] = '';
                    $val['source'] = '';
                    $val['scientific_name'] = '';
                    $val['object_page_id'] = '';
                    $recs[] = $val;
                }
            }
        }
        else debug("\nNothing from inferred file\n");
        // print_r($recs); exit;
        return $recs;
    }
    private function two_new_steps($ISVAT, $roots, $tips)
    {   if($GLOBALS['ENV_DEBUG']) {
            echo "\nroots: ".count($roots)." "; print_r($roots);
        }
        /* Important definition of terms by Jen:
        - rows with just one node in them are only needed for orphans (nodes that don't appear anywhere else). These nodes are both tips and roots.
        - tips are nodes that never appear on the left of a two node row
        - roots are nodes that never appear on the right of a two node row
        - nodes that appear both on the left of a two node row and on the right of a two node row are neither roots nor tips
        
            DELETE ALONG WITH CHILDREN
            look for these nodes in the list of roots
            are there any other roots aside from the nodes in this list?
                if not, do nothing
                if so, keep the root nodes that are NOT on this list, and all their descendants. Discard all other nodes 
            the list:
        */
        debug("\n--------------------------------------------DELETE ALONG WITH CHILDREN step: -START-\n");
        $delete_list_1 = array('http://purl.obolibrary.org/obo/ENVO_00000094', 'http://purl.obolibrary.org/obo/ENVO_01000155', 'http://purl.obolibrary.org/obo/ENVO_00000002', 'http://purl.obolibrary.org/obo/ENVO_00000077');
        $delete_list_1[] = "http://purl.obolibrary.org/obo/ENVO_00000358"; $delete_list_1[] = "http://purl.obolibrary.org/obo/ENVO_00000144";
        if($GLOBALS['ENV_DEBUG']) {echo "\nDelete List: "; print_r($delete_list_1);}
        if($roots_inside_the_list = self::get_roots_inside_the_list($roots, $delete_list_1)) {
            if($GLOBALS['ENV_DEBUG']) {
                echo "\nThere are root(s) in the 1st delete-list: ".count($roots_inside_the_list)." "; print_r($roots_inside_the_list);
                echo "\norig 'shared values ancestry tree': ".count($ISVAT)."\n";
            }
            foreach($ISVAT as $a) {
                if(              in_array($a[0], $roots_inside_the_list)) {}
                elseif(!$a[0] && in_array($a[1], $roots_inside_the_list)) {}
                else $new_isvat[] = $a;
            }
            if($GLOBALS['ENV_DEBUG']) {echo "\ntrimmed shared ancestry tree: ".count($new_isvat); foreach($new_isvat as $a) echo "\n".$a[0]."\t".$a[1];}
            $roots = array_diff($roots, $roots_inside_the_list);
            if($GLOBALS['ENV_DEBUG']) {echo "\n\nnew roots: ".count($roots)."\n"; print_r($roots);}
            /* working; but not needed here coz we're not adding roots here
            $cleaned = self::remove_undesirable_roots($roots);
            if($cleaned != $roots) {
                $roots = $cleaned;
                echo "\n\nnew roots (removed non-root): ".count($roots)."\n"; print_r($roots);
            }*/
        }
        else {
            debug("\nAll root nodes are not on the list. Keeping all root nodes and all descendants. Do nothing.\n");
            $new_isvat = $ISVAT;
        }
        debug("\n-------------------------------------------- -END-\n");
        /*DELETE, BUT KEEP THE CHILDREN
            look for these nodes in the list of roots
            remove them. Their immediate children are now roots.
            the list:
        (it's OK if occasionally this leaves you with no records.)
        */
        debug("\n--------------------------------------------DELETE, BUT KEEP THE CHILDREN step: -START-\n");
        $delete_list_2 = array('http://purl.obolibrary.org/obo/ENVO_01001305', 'http://purl.obolibrary.org/obo/ENVO_00002030', 'http://purl.obolibrary.org/obo/ENVO_01000687');
        $delete_list_2[] = "http://purl.obolibrary.org/obo/ENVO_00001995"; $delete_list_2[] = "http://purl.obolibrary.org/obo/ENVO_00002227";
        if($GLOBALS['ENV_DEBUG']) {
            echo "\nDelete List: "; print_r($delete_list_2);
            echo "\n\nroots: ".count($roots)."\n"; print_r($roots);
        }
        if($roots_inside_the_list = self::get_roots_inside_the_list($roots, $delete_list_2)) {
            if($GLOBALS['ENV_DEBUG']) {echo "\nThere are root(s) found in the 2nd delete-list: ".count($roots_inside_the_list)." "; print_r($roots_inside_the_list);}
            //1. get $temp_tree_deleted, will use this in deciding which can be root, which can be removed.
            foreach($new_isvat as $a) {
                if(!in_array($a[0], $roots_inside_the_list)) $temp_tree_deleted[] = $a;
                else $possible_root_or_deleted[$a[1]] = '';
            }
            $possible_root_or_deleted = array_keys($possible_root_or_deleted);
            if($GLOBALS['ENV_DEBUG']) {echo "\n-----------Diagnostics -START- -----------\n*Nodes in question: "; print_r($possible_root_or_deleted);}
            $all_left_of_tree = self::get_one_side_of_tree($temp_tree_deleted, 'left');
            $all_right_of_tree = self::get_one_side_of_tree($temp_tree_deleted, 'right');
            //2. decide which can be root and which can be removed.
            $orphans = array(); $add_2_roots = array(); $neither_root_nor_tip = array(); //initialization
            foreach($possible_root_or_deleted as $node) {
                if(!in_array($node, $all_left_of_tree) && !in_array($node, $all_right_of_tree)) {
                    $orphans[] = $node;
                    $add_2_roots[$node] = '';
                }
                elseif(in_array($node, $all_left_of_tree) && in_array($node, $all_right_of_tree)) $neither_root_nor_tip[] = $node;
                elseif(in_array($node, $all_left_of_tree) && !in_array($node, $all_right_of_tree)) $add_2_roots[$node] = '';
            }
            foreach($new_isvat as $a) {
                if(in_array($a[0], $roots_inside_the_list)) {
                    if(in_array($a[1], $orphans)) $new_isvat_2[] = array("", $a[1]);
                }
                else $new_isvat_2[] = $a;
            }
            
            $add_2_roots = array_keys($add_2_roots);
            if($GLOBALS['ENV_DEBUG']){
                echo "*Will become orphan/single rows: "; print_r($orphans);
                echo "*Will be added to roots: "; print_r($add_2_roots);
            }
            // /* working; moving back and forth as first - DEFINITELY UNCOMMENT
            $add_2_roots = self::remove_undesirable_roots($add_2_roots, $delete_list_2);
            // */
            if($GLOBALS['ENV_DEBUG']){
                echo "*Will be added to roots (removed non-root): "; print_r($add_2_roots);

                echo "*Neither root nor tip: "; print_r($neither_root_nor_tip);
                echo "-----------Diagnostics -END- -----------\n";
                echo "\ntrimmed shared ancestry tree: ".count($new_isvat_2); foreach($new_isvat_2 as $a) echo "\n".$a[0]."\t".$a[1];
            }
            $roots = array_diff($roots, $roots_inside_the_list);
            if($add_2_roots) {
                debug("\nHas additional roots.\n");
                $roots = array_merge($roots, $add_2_roots);
            }
            else debug("\nNo additional roots.\n");
            if($roots_inside_the_list) debug("\nRoots got reduced");
            if($GLOBALS['ENV_DEBUG']) {echo "\n\nnew roots: ".count($roots)."\n"; print_r($roots);}
        }
        else {
            debug("\nNo roots inside the list. Do nothing.\n");
            $new_isvat_2 = $new_isvat;
        }
        debug("\n-------------------------------------------- -END-\n");
        return array('roots' => $roots, 'tips' => self::get_tips($new_isvat_2), 'ISVAT' => $new_isvat_2);
    }
    private function get_one_side_of_tree($tree, $side)
    {   foreach($tree as $a) {
            if    ($side == 'left') $final[$a[0]] = '';
            elseif($side == 'right') $final[$a[1]] = '';
        }
        return array_keys($final);
    }
    private function get_roots_inside_the_list($roots, $list)
    {   $roots_inside_the_list = array();
        foreach($roots as $root) {
            if(in_array($root, $list)) $roots_inside_the_list[] = $root;
        }
        return $roots_inside_the_list;
    }
    private function get_tips($isvat)
    {
        foreach($isvat as $a) {
            $left[$a[0]] = '';
            $right[$a[1]] = '';
        }
        $right = array_keys($right);
        $final = array();
        foreach($right as $node) {
            if(!isset($left[$node])) $final[$node] = '';
        }
        $final = array_keys($final);
        asort($final);
        return $final;
    }
    private function get_step_1($isvat, $roots, $tips, $step_no)
    {   /*  - find all tips
            - find all nodes that are parents of tips
            - in each case, check whether either the tip or the parent is a root
                -- if either the tip or the parent is a root, put the tip in set 1
                -- if neither the tip nor the parent is a root, put the parent in set 1
            - (deduplicate set 1) */
        foreach($isvat as $a) {
            $parent_of_right[$a[1]] = $a[0];
        }
        $final = array();
        foreach($tips as $tip) {
            if($parent = @$parent_of_right[$tip]) {
                if(in_array($tip, $roots) || in_array($parent, $roots)) $final[$tip] = '';
                if(!in_array($tip, $roots) && !in_array($parent, $roots)) $final[$parent] = '';
            }
            else {
                if(in_array($tip, $roots)) $final[$tip] = '';
            }
        }
        $final = array_keys($final);
        asort($final);
        $final = array_values($final); //reindex key
        //optional display
        if($GLOBALS['ENV_DEBUG']) {
            echo "\nStep $step_no:".count($final)."\n";
            foreach($final as $a) echo "\n".$a;
            echo "\n-end Step $step_no-\n";
        }
        return $final;
    }
    private function remove_undesirable_roots($roots, $delete_list = array())
    {   $temp = array();
        foreach($roots as $root) {
            if($parents = @$this->parents_of[$root]) {
                $parents = array_diff($parents, $delete_list);
                if($parents) {}
                else $temp[$root] = '';
            }
            else $temp[$root] = '';
        }
        return array_keys($temp);
    }
    private function merge_nodes($info, $ISVAT)
    {   $new_nodes = $info['new_nodes'];
        $roots     = $info['roots'];
        $roots     = self::remove_undesirable_roots($roots); //new step to remove un-desirable roots
        
        $new_isvat = array_merge($ISVAT, $new_nodes);
        $new_isvat = self::sort_ISVAT($new_isvat, 3);
        $new_isvat = self::remove_orphans_that_exist_elsewhere($new_isvat);
        
        $new_roots = $roots;
        foreach($new_isvat as $a) {
            if(!$a[0]) {
                /* orig
                $new_roots[] = $a[1];
                */
                // /* new: to catch those who became roots but not suppose to be
                // if($this->parent_basal_values_YesNo) {
                    if(@$this->parents_of[$a[1]]) {}
                    else $new_roots[] = $a[1];
                // }
                // */
            }
        }
        asort($new_roots);
        
        //scan new isvat for new roots
        foreach($new_isvat as $a) {
            if(!$a[0]) continue;
            if(@$this->parents_of[$a[0]]) {} // echo " - not root, has parents ".count($arr);
            else $new_roots[] = $a[0];
        }
        $new_roots = array_unique($new_roots);
        $new_roots = array_filter($new_roots); //remove null values
        asort($new_roots);
        return array('new_roots' => $new_roots, 'new_isvat' => $new_isvat);
    }
    private function remove_orphans_that_exist_elsewhere($isvat) //that is remove the orphan row
    {   //first get all non-orphan rows
        foreach($isvat as $a) {
            if($a[0]) {
                $left[$a[0]] = '';
                $right[$a[1]] = '';
            }
        }
        //if orphan $a[1] exists elsewhere then remove that orphan row
        //The way I was thinking of documenting, it wouldn't need to be listed as an orphan if it also appears in any relationship pair.
        foreach($isvat as $a) {
            if(!$a[0] && ( isset($left[$a[1]]) || isset($right[$a[1]]) ) ) {
                debug("\n === $a[0] --- $a[1] === remove orphan coz it exists elsewhere \n"); //the orphan row ENVO_00000446 was removed here...
            }
            else $final[] = $a;
        }
        return $final;
    }
    private function sort_ISVAT($arr, $num) //also remove parent nodes where there is only one child. Make child an orphan.
    {   if(!$arr) return array();
        rsort($arr);
        foreach($arr as $a) {
            @$temp[$a[0]][$a[1]] = ''; //to be used in $totals
            $right_cols[$a[1]] = '';
            $temp2[$a[0]] = $a[1];
            $left_cols[$a[0]] = '';
        }
        asort($temp);
        foreach($temp as $key => $value) $totals[$key] = count($value);
        if($GLOBALS['ENV_DEBUG']) print_r($totals);

        $discard_parents = array(); debug("\n-------------------- [$num]\n");
        foreach($totals as $key => $total_children) {
            if($total_children == 1) {
                debug("\n $key: with 1 child ");
                if(isset($right_cols[$key])) debug(" -- appears in a relationship pair (right)");
                /* "Ancestors can be removed if they are parents of only one node BUT that node must NOT be an original node" THIS IS WRONG RULE!!!
                elseif(isset($this->original_nodes[$temp2[$key]])) {
                    echo "\nxxx $key --- ".@$temp2[$key]." parent of just 1 node BUT an original node\n";
                }
                */
                // /* THIS IS THE CORRECT RULE
                elseif(isset($this->original_nodes[$key])) {
                    debug("\nxxx $key --- parent of just 1 node BUT ancestor is an original node\n");
                }
                // */
                else $discard_parents[] = $key;
            }
        }
        if($GLOBALS['ENV_DEBUG']) {echo "\n discarded_parents:"; print_r($discard_parents); echo "\n-----\n";}
        
        $final = array();
        foreach($arr as $a) {
            if(in_array($a[0], $discard_parents)) $final[] = array("", $a[1]);
            else                                  $final[] = array($a[0], $a[1]);
        }
        asort($final);
        $final = array_unique($final, SORT_REGULAR);
        return $final;
    }
    private function get_predicates_per_method_and_parentYN($method)
    {
        if($method == 'BV') $predicates = self::get_summ_process_type_given_pred('opposite', 'predicates!A2:F1000', 5, 'basal values');
        // elseif($method == 'BVp') $predicates = self::get_summ_process_type_given_pred('opposite', 'parents!A2:C1000', 2, 'basal value');
        elseif($method == 'TS') $predicates = self::get_summ_process_type_given_pred('opposite', 'predicates!A2:F1000', 5, 'taxon summary');
        elseif($method == 'TSp') $predicates = self::get_summ_process_type_given_pred('opposite', 'parents!A2:C1000', 2, 'taxon summary');
        elseif($method == 'LSM') $predicates = self::get_summ_process_type_given_pred('opposite', 'predicates!A2:F1000', 5, 'lifestage and statistical method');
        echo "\nPredicates [$method]: ".count($predicates)."\n";
        if(!count($predicates)) exit("\nCheck params!\n");
        return $predicates;
    }
    function generate_page_id_txt_files_MySQL($method)
    {   $table = 'traits_'.$method;
        //truncate first
        $sql = "TRUNCATE TABLE SDR.".$table.";";
        if($result = $this->mysqli->query($sql)) echo "\nTable truncated [$table] OK.\n";
        
        $predicates = self::get_predicates_per_method_and_parentYN($method); //print_r($predicates); return;
        $filename = "traits_".$method."_"; $file_cnt = 1;
        $file_write = $this->main_dir."/".$this->MySQL_append_files_dir."/".$filename.$file_cnt.".txt"; $WRITE = fopen($file_write, "w");
        self::working_dir();
        $file = fopen($this->main_paths['archive_path'].'/traits.csv', 'r'); //11,276,098 rows in traits.csv | 11,276,097 in MySQL
        $i = 0; $save_cnt = 0;
        while(($line = fgetcsv($file)) !== FALSE) { $i++;
            if(($i % 1000000) == 0) echo "\n".number_format($i);
            if($i == 1) {
                $fields = $line;
                if($method == "LSM") {
                    $fields_write = $fields;
                    $fields_write[] = 'lifestage';
                    $fields_write[] = 'statistical_method';
                }
                else $fields_write = $fields;
            }
            else {
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k]; $k++;
                }
                // print_r($rec); exit;
                /* Fields from the [traits_all_201905.zip]:
                    eol_pk,page_id,resource_pk,resource_id,source,scientific_name,predicate,object_page_id,value_uri,normal_measurement,normal_units_uri,
                    normal_units,measurement,units_uri,units,literal
                It is missing these 4 fields from the carnivora sample:
                    [sex] => 
                    [lifestage] => 
                    [statistical_method] => 
                    [target_scientific_name] => 
                Array(
                    [eol_pk] => R788-PK74508166
                    [page_id] => 1180180
                    [resource_pk] => 
                    [resource_id] => 694
                    [source] => 
                    [scientific_name] => <i>Zygodontomys brevicauda</i>
                    [predicate] => http://eol.org/schema/terms/ExtinctionStatus
                    [object_page_id] => 
                    [value_uri] => http://eol.org/schema/terms/extant
                    [normal_measurement] => 
                    [normal_units_uri] => 
                    [normal_units] => 
                    [measurement] => 
                    [units_uri] => 
                    [units] => 
                    [literal] => http://eol.org/schema/terms/extant
                )*/
                if(!in_array($rec['predicate'], $predicates)) continue;
                $save_cnt++;
                if(($save_cnt % 1000000) == 0) {
                    echo "\nSaving...".number_format($save_cnt);
                    fclose($WRITE);
                    self::append_to_MySQL_table($table, $this->main_dir."/".$this->MySQL_append_files_dir."/".$table."_".$file_cnt.".txt");
                    $file_cnt++; $file_write = $this->main_dir."/".$this->MySQL_append_files_dir."/".$filename.$file_cnt.".txt"; $WRITE = fopen($file_write, "w");
                }
                // /* NEW to linkup lifeStage and statMeth to traits
                if($method == "LSM") {
                    $rec['lifestage']           = self::lookup_value_from_metadata($rec['eol_pk'], 'http://rs.tdwg.org/dwc/terms/lifeStage');
                    $rec['statistical_method']  = self::lookup_value_from_metadata($rec['eol_pk'], 'http://eol.org/schema/terms/statisticalMethod');
                    // if($rec['lifestage']) print_r($rec); //debug only
                }
                // */
                self::write_report($rec, $fields_write, $WRITE);
                // if($i >= 100) break; //debug only
            }
        }
        fclose($WRITE);
        self::append_to_MySQL_table($table, $this->main_dir."/".$this->MySQL_append_files_dir."/".$table."_".$file_cnt.".txt");
        fclose($file); echo "\n\nTraits to MySQL DONE [$table]";
        echo "\nTotal rows [$table]: ".self::count_table_rows($table)."\n--------------------\n";
    }
    private function lookup_value_from_metadata($eol_pk, $predicate)
    {
        $sql = "SELECT m.value_uri from SDR.metadata_LSM m WHERE m.trait_eol_pk = '".$eol_pk."' AND m.predicate = '".$predicate."'";
        $result = $this->mysqli->query($sql);
        while($result && $rec=$result->fetch_assoc()) return $rec['value_uri'];
        return '';
    }
    private function write_report($save_rec, $fields, $fileH)
    {   $arr = array();
        foreach($fields as $f) $arr[] = @$save_rec[$f];
        fwrite($fileH, implode("\t", $arr)."\n");
    }
    private function get_md5_path($path, $taxonkey)
    {   $md5 = md5($taxonkey);
        $cache1 = substr($md5, 0, 2);
        $cache2 = substr($md5, 2, 2);
        if(!file_exists($path . $cache1)) mkdir($path . $cache1);
        if(!file_exists($path . "$cache1/$cache2")) mkdir($path . "$cache1/$cache2");
        return $path . "$cache1/$cache2/";
    }
    private function assemble_recs_for_page_id_from_text_file($page_id, $predicate, $required_fields = array(), $method = '') //4th param is used by TS only so far
    {   $ret = self::format_value_for_sql($predicate);
        if($ret['count'] == 'single')       $sql = "SELECT t.* from SDR.".$this->dbname." t WHERE t.page_id = '".$page_id."' AND t.predicate = '".$predicate."'";
        elseif($ret['count'] == 'multiple') $sql = "SELECT t.* from SDR.".$this->dbname." t WHERE t.page_id = '".$page_id."' AND t.predicate in (".$ret['value'].")";
        /* this caused probs with pTS - eol_pk missing in existing records report. Have abandoned totally. Implemented $unique_page_ids instead - worked OK
        if($method == 'TS') { //has to have unique page_id + object_page_id
            if($ret['count'] == 'single')       $sql = "SELECT DISTINCT t.page_id, t.object_page_id, t.value_uri from SDR.".$this->dbname." t WHERE t.page_id = '".$page_id."' AND t.predicate = '".$predicate."'";
            elseif($ret['count'] == 'multiple') $sql = "SELECT DISTINCT t.page_id, t.object_page_id, t.value_uri from SDR.".$this->dbname." t WHERE t.page_id = '".$page_id."' AND t.predicate in (".$ret['value'].")";
        }
        */
        // echo "\nAssemble recs start [$sql]\n";
        $result = $this->mysqli->query($sql);
        $recs = array();
        while($result && $rec=$result->fetch_assoc()) {
            // print_r($rec); exit;
            /* e.g. Method: basal values
            Array(
                [eol_pk] => R512-PK71412778
                [page_id] => 46559217
                [resource_pk] => 
                [resource_id] => 413
                [source] => http://eol.org/pages/1053894
                [scientific_name] => <i>Vulpes lagopus</i> (Linnaeus 1758)
                [predicate] => http://eol.org/schema/terms/Habitat
                [object_page_id] => 
                [value_uri] => http://purl.obolibrary.org/obo/ENVO_00000112
                [normal_measurement] => 
                [normal_units_uri] => 
                [normal_units] => 
                [measurement] => 
                [units_uri] => 
                [units] => 
                [literal] => http://purl.obolibrary.org/obo/ENVO_00000112
            )*/
            if($required_fields) {
                foreach($required_fields as $required_fld) {
                    if(!$rec[$required_fld]) continue; //e.g. value_uri
                    else $recs[] = $rec;
                }
            }
            else $recs[] = $rec;
            $this->original_nodes[$rec['value_uri']] = '';
            $this->original_nodes_parent[$rec['value_uri']] = '';
        }
        // echo "\nAssemble recs end.\n";
        return $recs;
    }
    private function assemble_recs_for_page_id_from_text_file_OLD($page_id, $predicate, $required_fields = array())
    {   $recs = array();
        $txt_file = self::get_txt_path_by_page_id($page_id); // echo "\n$txt_file\n";
        if(!file_exists($txt_file)) { // echo "\nFile does not exist.\n";
            return false;
        }
        $i = 0;
        foreach(new FileIterator($txt_file) as $line_number => $line) {
            $line = explode("\t", $line); $i++;
            if($i == 1) $fields = $line;
            else {
                if(!$line[0]) break;
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k]; $k++;
                }
                $rec = array_map('trim', $rec);
                // print_r($rec); exit;
                /* Array( old during development with Jen
                    [page_id] => 46559197
                    [scientific_name] => <i>Arctocephalus tropicalis</i>
                    [predicate] => http://eol.org/schema/terms/Present
                    [value_uri] => http://www.marineregions.org/gazetteer.php?p=details&id=australia
                )*/
                /*Array(
                    [eol_pk] => R143-PK39533505
                    [page_id] => 46559197
                    [scientific_name] => <i>Arctocephalus tropicalis</i>
                    [resource_pk] => 17255
                    [predicate] => http://eol.org/schema/terms/WeaningAge
                    [sex] => 
                    [lifestage] => 
                    [statistical_method] => 
                    [source] => http://genomics.senescence.info/species/entry.php?species=Arctocephalus_tropicalis
                    [object_page_id] => 
                    [target_scientific_name] => 
                    [value_uri] => 
                    [literal] => 
                    [measurement] => 239
                    [units] => http://purl.obolibrary.org/obo/UO_0000033
                    [normal_measurement] => 0.6543597746702533
                    [normal_units_uri] => http://purl.obolibrary.org/obo/UO_0000036
                    [resource_id] => 50
                )*/
                if($predicate == $rec['predicate']) {
                    if($required_fields) {
                        foreach($required_fields as $required_fld) {
                            if(!$rec[$required_fld]) continue; //e.g. value_uri
                            else $recs[] = $rec;
                        }
                    }
                    else $recs[] = $rec;
                }
                $this->original_nodes[$rec['value_uri']] = '';
                $this->original_nodes_parent[$rec['value_uri']] = '';
            }
        }
        return $recs;
    }
    private function initialize()
    {   self::working_dir();
    }
    function initialize_basal_values()
    {   self::working_dir();
        self::generate_terms_values_child_parent_list($this->file['parent child']['path_habitat']);
        self::generate_terms_values_child_parent_list($this->file['parent child']['path_geoterms']);
        self::generate_preferred_child_parent_list();
    }
    private function add_new_nodes_for_NotRootParents($list)
    {   //1st step: get unique parents
        foreach($list as $rec) {
            /*Array(
                [0] => http://www.geonames.org/6255151
                [1] => http://www.marineregions.org/gazetteer.php?p=details&id=australia
            )*/
            $unique[$rec[0]] = '';
        }
        //2nd step: check if parent is not root (meaning has parents), if yes: get parent and add the new node:
        $recs = array(); $roots = array();
        foreach(array_keys($unique) as $child) {
            // echo "\n$child: ";
            if($arr = @$this->parents_of[$child]) { // echo " - not root, has parents ".count($arr);
                foreach($arr as $new_parent) {
                    if($new_parent) $recs[] = array($new_parent, $child);
                }
            }
            else $roots[] = $child; // echo " - already root";
        }
        return array('roots' => $roots, 'new_nodes' => $recs);
    }
    private function get_valueUris_from_recs($recs)
    {   $uris = array();
        foreach($recs as $rec) $uris[] = $rec['value_uri'];
        return $uris;
    }
    private function get_initial_shared_values_ancestry_tree($recs)
    {   $final = array(); $i = 0;
        // $WRITE = fopen($this->temp_file, 'w'); fclose($WRITE); //used initially for debugging. Not used anymore.
        foreach($recs as $rec) { $i++;
            $term = $rec['value_uri'];
            /* old ways
            $parent = self::get_parent_of_term($term, $i);
            $final[] = array($parent, $term);
            */
            $pairs = self::create_pairs_from_this_term($term, $i);
            $final = array_merge($final, $pairs);
        }
        return $final;
    }
    /* may not be needed anymore...
    function start_ok()
    {   self::initialize();
        $uris = array(); //just during development --- assign uris here...
        self::set_ancestor_ranking_from_set_of_uris($uris);
        $terms[] = "http://www.marineregions.org/gazetteer.php?p=details&id=australia";
        $terms[] = "http://www.marineregions.org/gazetteer.php?p=details&id=4366";
        $terms[] = "http://www.marineregions.org/gazetteer.php?p=details&id=4364";
        $terms[] = "http://www.geonames.org/2186224";
        $terms[] = "http://www.geonames.org/3370751";                               //error
        $terms[] = "http://www.marineregions.org/gazetteer.php?p=details&id=1914";  //error
        $terms[] = "http://www.marineregions.org/gazetteer.php?p=details&id=1904";  //error
        $terms[] = "http://www.marineregions.org/gazetteer.php?p=details&id=1910";
        $terms[] = "http://www.marineregions.org/gazetteer.php?p=details&id=4276";
        $terms[] = "http://www.marineregions.org/gazetteer.php?p=details&id=4365";
        $terms[] = "http://www.geonames.org/953987";
        $terms[] = "http://www.marineregions.org/mrgid/1914";
        $WRITE = fopen($this->temp_file, 'w'); fclose($WRITE);
        foreach($terms as $term) self::get_parent_of_term($term);
        exit("\nend 01\n");
    }*/
    private function set_ancestor_ranking_from_set_of_uris($uris)
    {   $final = array(); $final_preferred = array();
        foreach($uris as $term) {
            if(!$term) continue;
            if($preferred_terms = @$this->preferred_names_of[$term]) {
                // echo "\nThere are preferred term(s):\n"; // print_r($preferred_terms);
                foreach($preferred_terms as $pterm) {
                    @$final_preferred[$pterm]++; // echo "\nparent(s) of $pterm:";
                    if($parents = @$this->parents_of[$pterm]) { // print_r($parents);
                        foreach($parents as $parent) @$final[$parent]++;
                    }
                    // else echo " -- NO parent";
                }
            }
            else { //no preferred term
                if($parents = @$this->parents_of[$term]) {
                    foreach($parents as $parent) @$final[$parent]++;
                }
                // else exit("\n\nHmmm no preferred and no immediate parent for term: [$term]\n\n"); //seems acceptable
            }
        }//end main
        arsort($final);
        $this->ancestor_ranking_withCounts = $final;
        $final = array_keys($final);
        $this->ancestor_ranking = $final;

        arsort($final_preferred);
        $this->ancestor_ranking_preferred_withCounts = $final_preferred;
        if($GLOBALS['ENV_DEBUG']) print_r($final_preferred); //exit;
        $final_preferred = array_keys($final_preferred);
        $this->ancestor_ranking_preferred = $final_preferred;
    }
    private function get_rank_most_parent($parents, $preferred_terms = array())
    {   if(!$preferred_terms) {
            if($GLOBALS['ENV_DEBUG']) {echo "\nancestor_ranking_preferred: "; print_r($this->ancestor_ranking_preferred_withCounts);}
            //1st option: if any is a preferred name then choose that
            foreach($this->ancestor_ranking_preferred as $parent) {
                if(in_array($parent, $parents)) {
                    $WRITE = fopen($this->temp_file, 'a'); fwrite($WRITE, $parent."\n"); fclose($WRITE);
                    debug("\nwent here 01\n");
                    return $parent;
                }
            }
        }
        else {
            // don't do THIS if preferred + parents are all inside $this->ancestor_ranking
            $all_inside = true;
            $temp = array_merge($parents, $preferred_terms);
            foreach($temp as $id) {
                if(!in_array($id, $this->ancestor_ranking)) $all_inside = false;
            }
            if(!$all_inside) {
                //THIS:
                foreach($this->ancestor_ranking as $parent) {
                    if(in_array($parent, $preferred_terms)) {
                        $WRITE = fopen($this->temp_file, 'a'); fwrite($WRITE, $parent."\n"); fclose($WRITE);
                        debug("\nwent here 02\n");
                        return $parent;
                    }
                }
            }
            if(count($preferred_terms) == 1 && in_array($preferred_terms[0], $this->ancestor_ranking) && in_array($preferred_terms[0], $this->ancestor_ranking_preferred)) {
                $WRITE = fopen($this->temp_file, 'a'); fwrite($WRITE, $preferred_terms[0]."\n"); fclose($WRITE);
                debug("\nwent here 03\n");
                return $preferred_terms[0];
            }
        }
        
        //2nd option
        if($GLOBALS['ENV_DEBUG']) {echo "\nlast option: ancestor_ranking "; print_r($this->ancestor_ranking_withCounts);}
        $inclusive = array_merge($parents, $preferred_terms);
        foreach($this->ancestor_ranking as $parent) {
            if(in_array($parent, $inclusive)) {
                $WRITE = fopen($this->temp_file, 'a'); fwrite($WRITE, $parent."\n"); fclose($WRITE);
                debug("\nwent here 04\n");
                return $parent;
            }
        }
        
        echo "\nInvestigate parents not included in ranking... weird...\n";
        print_r($inclusive);
        exit("\n===============\n");
    }
    private function create_pairs_from_this_term($term, $num) //this will replace get_parent_of_term()
    {   debug("\n--------------------------------------------------------------------------------------------------------------------------------------- \n"."term in question: [$term] $num:\n");
        $pairs = array();
        if($preferred_terms = @$this->preferred_names_of[$term]) {
            if($GLOBALS['ENV_DEBUG']) {echo "\nThere are preferred term(s):\n"; print_r($preferred_terms);}
            foreach($preferred_terms as $preferred) {
                if($val = self::is_pair_OK($preferred, $term)) $pairs[] = $val;
            }
            foreach($preferred_terms as $preferred) {
                debug("\nparent(s) of $preferred:\n");
                if($parents = @$this->parents_of[$preferred]) {
                    if($GLOBALS['ENV_DEBUG']) print_r($parents);
                    foreach($parents as $parent) {
                        if($val = self::is_pair_OK($parent, $preferred)) $pairs[] = $val;
                    }
                }
                else debug(" -- NO parent");
            }
        }
        else {
            debug("\nThere is NO preferred term\n");
            if($immediate_parents = @$this->parents_of[$term]) {
                if($GLOBALS['ENV_DEBUG']) {echo "\nThere are immediate parent(s) for term in question:\n"; print_r($immediate_parents);}
                foreach($immediate_parents as $parent) {
                    if($val = self::is_pair_OK($parent, $term)) $pairs[] = $val;
                }
            }
        }
        if($GLOBALS['ENV_DEBUG']) {foreach($pairs as $a) echo "\n".$a[0]." - ".$a[1];}
        return $pairs;
    }
    private function is_pair_OK($parent_orig, $child_orig)
    {
        $parent = self::remove_protocol($parent_orig);
        $child = self::remove_protocol($child_orig);

        // causes infinite loop
        // $parent = $parent_orig;
        // $child = $child_orig;
        
        if($parent == $child) {
            if($parent_orig != $child_orig) $this->debug['diff protocol'][$parent_orig][$child_orig] = '';
                                         // $this->debug[] = "Investigate: [$parent_orig] [$child_orig] meaning diff protocol";
            return false;
        }
        return array($parent_orig, $child_orig);
    }
    private function remove_protocol($url)
    {   $arr = explode("://", $url);
        return $arr[1];
    }
    /*
    private function get_parent_of_term($term, $num) -- replaced by create_pairs_from_this_term()
    {   echo "\n--------------------------------------------------------------------------------------------------------------------------------------- \n"."term in question: [$term] $num:\n";
        if($preferred_terms = @$this->preferred_names_of[$term]) {
            echo "\nThere are preferred term(s):\n";
            print_r($preferred_terms);
            foreach($preferred_terms as $term) {
                echo "\nparent(s) of $term:\n";
                if($parents = @$this->parents_of[$term]) {
                    print_r($parents);
                    $chosen = self::get_rank_most_parent($parents, $preferred_terms);
                    echo "\nCHOSEN PARENT: ".$chosen."\n";
                    return $chosen;
                }
                else echo " -- NO parent";
            }
        }
        else {
            echo "\nThere is NO preferred term\n";
            if($immediate_parents = @$this->parents_of[$term]) {
                echo "\nThere are immediate parent(s) for term in question:\n";
                print_r($immediate_parents);
                $chosen = self::get_rank_most_parent($immediate_parents);
                echo "\nCHOSEN PARENT*: ".$chosen."\n";
                return $chosen;
            }
        }
        // $this->preferred_names_of[$term]    function generate_preferred_child_parent_list
        // $this->parents_of[$term]            function generate_terms_values_child_parent_list
    }
    */
    private function generate_preferred_child_parent_list()
    {
        $options = $this->download_options;
        $options['expire_seconds'] = 60*60*24; //expires in a day
        $temp_file = Functions::save_remote_file_to_local($this->file['preferred synonym']['path'], $options);
        $file = fopen($temp_file, 'r'); $i = 0;
        $fields = $this->file['preferred synonym']['fields'];
        while(($line = fgetcsv($file)) !== FALSE) {
            $i++;
            if($line) {
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k];
                    if(!isset($line[$k])) exit("\nInvestigate file: ".$this->file['preferred synonym']['path']."\nWill terminate.\n");
                    $k++;
                }
                $rec = array_map('trim', $rec);
                // print_r($rec); exit;
                /* Array(
                    [preferred] => http://marineregions.org/mrgid/19161
                    [deprecated] => http://marineregions.org/gazetteer.php?p=details&id=19161
                )*/
                $this->preferred_names_of[$rec['deprecated']][] = $rec['preferred'];
            }
        }
        fclose($file); unlink($temp_file);
    }
    private function get_ancestry_of_term($page_id)
    {   $final = array(); $final2 = array();
        if($parent_ids = @$this->terms_values_child_parent_list[$page_id]) {
            foreach($parent_ids as $temp_id) {
                while(true) {
                    if($parent_ids2 = @$this->terms_values_child_parent_list[$temp_id]) {
                        foreach($parent_ids2 as $temp_id2) {
                            while(true) {
                                if($parent_ids3 = @$this->terms_values_child_parent_list[$temp_id2]) {
                                    foreach($parent_ids3 as $temp_id3) {
                                        $final['L3'][] = $temp_id3;
                                        $final2[$temp_id3] = '';
                                        $temp_id2 = $temp_id3;
                                    }
                                }
                                else break;
                            }
                            $final['L2'][] = $temp_id2;
                            $final2[$temp_id2] = '';
                            $temp_id = $temp_id2;
                        }
                    }
                    else break;
                }
                $final['L1'][] = $temp_id;
                $final2[$temp_id] = '';
                $page_id = $temp_id;
            }
        }
        return array($final, array_keys($final2));
        /*
        $final = array();
        $temp_id = $page_id;
        while(true) {
            if($parent_id = @$this->terms_values_child_parent_list[$temp_id]) {
                $final[] = $parent_id;
                $temp_id = $parent_id;
            }
            else break;
        }
        return $final;
        */
    }
    private function generate_terms_values_child_parent_list($file = false)
    {
        if(!$file) exit("\nUndefined file: [$file]\n");
        $options = $this->download_options;
        $options['expire_seconds'] = 60*60*24; //expires in a day
        $temp_file = Functions::save_remote_file_to_local($file, $options);
        $file = fopen($temp_file, 'r');
        $i = 0;
        $fields = $this->file['parent child']['fields'];
        while(($line = fgetcsv($file)) !== FALSE) {
            $i++;
            if($line) {
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k];
                    if(!isset($line[$k])) exit("\nInvestigate file: ".$file."\nWill terminate.\n");
                    $k++;
                }
                $rec = array_map('trim', $rec);
                // print_r($rec); exit;
                /* Array(
                    [parent] => ﻿http://purl.obolibrary.org/obo/ENVO_00000111
                    [child] => http://purl.obolibrary.org/obo/ENVO_01000196
                )*/
                $this->parents_of[$rec['child']][] = $rec['parent'];
                $this->children_of[$rec['parent']][] = $rec['child'];
            }
        }
        fclose($file); unlink($temp_file);
    }
    private function generate_child_parent_list()
    {   $file = fopen($this->main_paths['archive_path'].'/parents.csv', 'r');
        $i = 0;
        while(($line = fgetcsv($file)) !== FALSE) {
            $i++;
            if($i == 1) $fields = $line;
            else {
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k];
                    $k++;
                }
                // print_r($rec); exit;
                /* Array(
                    [child] => 47054812
                    [parent] => 7662
                )*/
                $final[$rec['child']] = $rec['parent'];
            }
        }
        fclose($file);
        return $final;
    }
    private function print_taxon_and_ancestry($preds)
    {   $WRITE = fopen($this->report_file, 'a');
        fwrite($WRITE, "Taxa (with ancestry) having data for predicate in question and similar terms: \n\n");
        fwrite($WRITE, implode("\t", array("page_id", "scientific_name", "ancestry"))."\n");
        $file = fopen($this->main_paths['archive_path'].'/traits.csv', 'r');
        $i = 0;
        while(($line = fgetcsv($file)) !== FALSE) {
            $i++;
            if($i == 1) $fields = $line;
            else {
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k];
                    $k++;
                }
                // print_r($rec); exit;
                /*Array(
                    [eol_pk] => R96-PK42815719
                    [page_id] => 328076
                    ...more fields below
                )*/
                if(in_array($rec['predicate'], $preds)) {
                    $ancestry = self::get_ancestry_using_page_id($rec['page_id']);
                    if(!isset($printed_already[$rec['page_id']])) {
                        fwrite($WRITE, implode("\t", array($rec['page_id'], $rec['scientific_name'], implode("|", $ancestry)))."\n");
                        $printed_already[$rec['page_id']] = '';
                    }
                }
            }
        }
        fclose($file);
        fwrite($WRITE, "==================================================================================================================================================================\n");
        fclose($WRITE);
    }
    private function given_predicates_get_values_from_traits_csv($preds)
    {   $WRITE = fopen($this->report_file, 'a');
        fwrite($WRITE, "Records from traits.csv having data for predicate in question and similar terms: \n\n");
        fwrite($WRITE, implode("\t", array("page_id", "scientific_name", "predicate", "value_uri OR literal"))."\n");
        $file = fopen($this->main_paths['archive_path'].'/traits.csv', 'r');
        $i = 0;
        while(($line = fgetcsv($file)) !== FALSE) {
            $i++;
            if($i == 1) {
                $fields = $line;
                print_r($fields); //exit;
            }
            else {
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k];
                    $k++;
                }
                /*Array(
                    [eol_pk] => R96-PK42815719
                    [page_id] => 328076
                    [scientific_name] => <i>Tremarctos ornatus</i>
                    ...more fields below
                )*/
                if(in_array($rec['predicate'], $preds)) {
                    // echo "\n".self::get_value($rec);
                    // print_r($rec); //exit;
                    fwrite($WRITE, implode("\t", array($rec['page_id'], $rec['scientific_name'], $rec['predicate'], self::get_value($rec)))."\n");
                }
            }
        }
        fclose($file);
    }
    private function get_ancestry_using_page_id($page_id)
    {   $final = array(); $temp_id = $page_id;
        while(true) {
            if($parent_id = @$this->child_parent_list[$temp_id]) {
                $final[] = $parent_id;
                $temp_id = $parent_id;
            }
            else break;
        }
        return $final;
    }
    private function get_value($rec)
    {
        if($val = @$rec['value_uri']) return $val;
        if($val = @$rec['literal']) return $val;
    }
    private function given_predicate_get_similar_terms($pred) //used during initial report to Jen
    {   $final = array();
        $final[$pred] = ''; //processed predicate is included
        //from 'parent child':
        $temp_file = Functions::save_remote_file_to_local($this->file['parent child'], $this->download_options);
        $file = fopen($temp_file, 'r');
        while(($line = fgetcsv($file)) !== FALSE) {
          if($line[0] == $pred) $final[$line[1]] = '';
        }
        fclose($file); unlink($temp_file);
        //from 'preferred synonym':
        $temp_file = Functions::save_remote_file_to_local($this->file['preferred synonym'], $this->download_options);
        $file = fopen($temp_file, 'r');
        while(($line = fgetcsv($file)) !== FALSE) {
          if($line[1] == $pred) $final[$line[0]] = '';
        }
        fclose($file); unlink($temp_file);
        $final = array_keys($final);
        //start write
        $WRITE = fopen($this->report_file, 'w');
        fwrite($WRITE, "REPORT FOR PREDICATE: $pred\n\n");
        fwrite($WRITE, "==================================================================================================================================================================\n");
        fwrite($WRITE, "Similar terms from [terms relationship files]:\n\n");
        foreach($final as $url) fwrite($WRITE, $url . "\n");
        fwrite($WRITE, "==================================================================================================================================================================\n");
        fclose($WRITE);
        //end write
        return $final;
    }
    private function investigate_traits_csv()
    {   $file = fopen($this->main_paths['archive_path'].'/traits.csv', 'r');
        $i = 0;
        while(($line = fgetcsv($file)) !== FALSE) {
            $i++; 
            if($i == 1) $fields = $line;
            else {
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k]; $k++;
                }
                // print_r($rec); //exit;
                /*Array(
                    [eol_pk] => R96-PK42724728
                    [page_id] => 328673
                    [scientific_name] => <i>Panthera pardus</i>
                    ...more fields below
                )*/
                // if($rec['target_scientific_name']) print_r($rec);
                // if($rec['lifestage']) print_r($rec);
                if($rec['object_page_id']) print_r($rec);
            }
        }
    }
    private function get_CSV_children_of($page_id, $predicate = '') //$predicate param here is just for debug
    {   debug("\nGetting children of [$page_id]...\n");
        $anaks = array();
        if($children = @$this->CSV_children_of[$page_id]) $anaks = array_merge($anaks, $children);
        else return array();
        foreach($children as $child) {
            if($children2 = @$this->CSV_children_of[$child]) $anaks = array_merge($anaks, $children2);
            else continue;
            foreach($children2 as $child2) {
                if($children3 = @$this->CSV_children_of[$child2]) $anaks = array_merge($anaks, $children3);
                else continue;
                foreach($children3 as $child3) {
                    if($children4 = @$this->CSV_children_of[$child3]) $anaks = array_merge($anaks, $children4);
                    else continue;
                    foreach($children4 as $child4) {
                        if($children5 = @$this->CSV_children_of[$child4]) $anaks = array_merge($anaks, $children5);
                        else continue;

                        $ret = self::check_max_children_reached($anaks, 'L5', $page_id);
                        if($ret['exit']) return self::my_atrim($ret['anaks']);
                        else             $anaks = $ret['anaks'];

                        foreach($children5 as $child5) {
                            if($children6 = @$this->CSV_children_of[$child5]) $anaks = array_merge($anaks, $children6);
                            else continue;
                            foreach($children6 as $child6) {
                                if($children7 = @$this->CSV_children_of[$child6]) $anaks = array_merge($anaks, $children7);
                                else continue;
                                foreach($children7 as $child7) {
                                    if($children8 = @$this->CSV_children_of[$child7]) $anaks = array_merge($anaks, $children8);
                                    else continue;
                                    foreach($children8 as $child8) {
                                        if($children9 = @$this->CSV_children_of[$child8]) $anaks = array_merge($anaks, $children9);
                                        else continue;
                                        foreach($children9 as $child9) {
                                            if($children10 = @$this->CSV_children_of[$child9]) $anaks = array_merge($anaks, $children10);
                                            else continue;

                                            $ret = self::check_max_children_reached($anaks, 'L10', $page_id);
                                            if($ret['exit']) return self::my_atrim($ret['anaks']);
                                            else             $anaks = $ret['anaks'];

                                            foreach($children10 as $child10) {
                                                if($children11 = @$this->CSV_children_of[$child10]) $anaks = array_merge($anaks, $children11);
                                                else continue;
                                                foreach($children11 as $child11) {
                                                    if($children12 = @$this->CSV_children_of[$child11]) $anaks = array_merge($anaks, $children12);
                                                    else continue;
                                                    foreach($children12 as $child12) {
                                                        if($children13 = @$this->CSV_children_of[$child12]) $anaks = array_merge($anaks, $children13);
                                                        else continue;
                                                        foreach($children13 as $child13) {
                                                            if($children14 = @$this->CSV_children_of[$child13]) $anaks = array_merge($anaks, $children14);
                                                            else continue;
                                                            foreach($children14 as $child14) {
                                                                if($children15 = @$this->CSV_children_of[$child14]) $anaks = array_merge($anaks, $children15);
                                                                else continue;

                                                                $ret = self::check_max_children_reached($anaks, 'L15', $page_id);
                                                                if($ret['exit']) return self::my_atrim($ret['anaks']);
                                                                else             $anaks = $ret['anaks'];

                                                                foreach($children15 as $child15) {
                                                                    if($children16 = @$this->CSV_children_of[$child15]) $anaks = array_merge($anaks, $children16);
                                                                    else continue;
                                                                    foreach($children16 as $child16) {
                                                                        if($children17 = @$this->CSV_children_of[$child16]) $anaks = array_merge($anaks, $children17);
                                                                        else continue;
                                                                        foreach($children17 as $child17) {
                                                                            if($children18 = @$this->CSV_children_of[$child17]) $anaks = array_merge($anaks, $children18);
                                                                            else continue;
                                                                            foreach($children18 as $child18) {
                                                                                if($children19 = @$this->CSV_children_of[$child18]) $anaks = array_merge($anaks, $children19);
                                                                                else continue;
                                                                                foreach($children19 as $child19) {
                                                                                    if($children20 = @$this->CSV_children_of[$child19]) $anaks = array_merge($anaks, $children20);
                                                                                    else continue;
                                                                                    
                                                                                    $ret = self::check_max_children_reached($anaks, 'L20', $page_id);
                                                                                    if($ret['exit']) return self::my_atrim($ret['anaks']);
                                                                                    else             $anaks = $ret['anaks'];
                                                                                    
                                                                                    foreach($children20 as $child20) {
                                                                                        if($children21 = @$this->CSV_children_of[$child20]) $anaks = array_merge($anaks, $children21);
                                                                                        else continue;
                                                                                        foreach($children21 as $child21) {
                                                                                            if($children22 = @$this->CSV_children_of[$child21]) $anaks = array_merge($anaks, $children22);
                                                                                            else continue;
                                                                                            foreach($children22 as $child22) {
                                                                                                if($children23 = @$this->CSV_children_of[$child22]) $anaks = array_merge($anaks, $children23);
                                                                                                else continue;
                                                                                                foreach($children23 as $child23) {
                                                                                                    if($children24 = @$this->CSV_children_of[$child23]) $anaks = array_merge($anaks, $children24);
                                                                                                    else continue;
                                                                                                    foreach($children24 as $child24) {
                                                                                                        if($children25 = @$this->CSV_children_of[$child24]) $anaks = array_merge($anaks, $children25);
                                                                                                        else continue;
                                                                                                        
                                                                                                        $ret = self::check_max_children_reached($anaks, 'L25', $page_id);
                                                                                                        if($ret['exit']) return self::my_atrim($ret['anaks']);
                                                                                                        else             $anaks = $ret['anaks'];
                                                                                                        
                                                                                                        foreach($children25 as $child25) {
                                                                                                            if($children26 = @$this->CSV_children_of[$child25]) $anaks = array_merge($anaks, $children26);
                                                                                                            else continue;
    foreach($children26 as $child26) {
        if($children27 = @$this->CSV_children_of[$child26]) $anaks = array_merge($anaks, $children27);
        else continue;
        foreach($children27 as $child27) {
            if($children28 = @$this->CSV_children_of[$child27]) $anaks = array_merge($anaks, $children28);
            else continue;
            foreach($children28 as $child28) {
                if($children29 = @$this->CSV_children_of[$child28]) $anaks = array_merge($anaks, $children29);
                else continue;
                foreach($children29 as $child29) {
                    if($children30 = @$this->CSV_children_of[$child29]) $anaks = array_merge($anaks, $children30);
                    else continue;
                    
                    $ret = self::check_max_children_reached($anaks, 'L30', $page_id);
                    if($ret['exit']) return self::my_atrim($ret['anaks']);
                    else             $anaks = $ret['anaks'];
                    
                    foreach($children30 as $child30) {
                        if($children31 = @$this->CSV_children_of[$child30]) $anaks = array_merge($anaks, $children31);
                        else continue;
                        foreach($children31 as $child31) {
                            if($children32 = @$this->CSV_children_of[$child31]) $anaks = array_merge($anaks, $children32);
                            else continue;
                            foreach($children32 as $child32) {
                                if($children33 = @$this->CSV_children_of[$child32]) $anaks = array_merge($anaks, $children33);
                                else continue;
                                foreach($children33 as $child33) {
                                    if($children34 = @$this->CSV_children_of[$child33]) $anaks = array_merge($anaks, $children34);
                                    else continue;
                                    foreach($children34 as $child34) {
                                        if($children35 = @$this->CSV_children_of[$child34]) $anaks = array_merge($anaks, $children35);
                                        else continue;

                                        $ret = self::check_max_children_reached($anaks, 'L35', $page_id);
                                        if($ret['exit']) return self::my_atrim($ret['anaks']);
                                        else             $anaks = $ret['anaks'];
                                        
                                        foreach($children35 as $child35) {
                                            if($children36 = @$this->CSV_children_of[$child35]) $anaks = array_merge($anaks, $children36);
                                            else continue;
                                            foreach($children36 as $child36) {
                                                if($children37 = @$this->CSV_children_of[$child36]) $anaks = array_merge($anaks, $children37);
                                                else continue;
                                                foreach($children37 as $child37) {
                                                    if($children38 = @$this->CSV_children_of[$child37]) $anaks = array_merge($anaks, $children38);
                                                    else continue;
                                                    foreach($children38 as $child38) {
                                                        if($children39 = @$this->CSV_children_of[$child38]) $anaks = array_merge($anaks, $children39);
                                                        else continue;
                                                        foreach($children39 as $child39) {
                                                            if($children40 = @$this->CSV_children_of[$child39]) $anaks = array_merge($anaks, $children40);
                                                            else continue;

                                                            $ret = self::check_max_children_reached($anaks, 'L40', $page_id);
                                                            if($ret['exit']) return self::my_atrim($ret['anaks']);
                                                            else             $anaks = $ret['anaks'];
                                                            
                                                            foreach($children40 as $child40) {
                                                                if($children41 = @$this->CSV_children_of[$child40]) $anaks = array_merge($anaks, $children41);
                                                                else continue;
                                                                foreach($children41 as $child41) {
                                                                    if($children42 = @$this->CSV_children_of[$child41]) $anaks = array_merge($anaks, $children42);
                                                                    else continue;
                                                                    foreach($children42 as $child42) {
                                                                        if($children43 = @$this->CSV_children_of[$child42]) $anaks = array_merge($anaks, $children43);
                                                                        else continue;
                                                                        foreach($children43 as $child43) {
                                                                            if($children44 = @$this->CSV_children_of[$child43]) $anaks = array_merge($anaks, $children44);
                                                                            else continue;
                                                                            foreach($children44 as $child44) {
                                                                                if($children45 = @$this->CSV_children_of[$child44]) $anaks = array_merge($anaks, $children45);
                                                                                else continue;

                                                                                $ret = self::check_max_children_reached($anaks, 'L45', $page_id);
                                                                                if($ret['exit']) return self::my_atrim($ret['anaks']);
                                                                                else             $anaks = $ret['anaks'];

                                                                                foreach($children45 as $child45) {
                                                                                    if($children46 = @$this->CSV_children_of[$child45]) $anaks = array_merge($anaks, $children46);
                                                                                    else continue;
                                                                                    foreach($children46 as $child46) {
                                                                                        if($children47 = @$this->CSV_children_of[$child46]) $anaks = array_merge($anaks, $children47);
                                                                                        else continue;
                                                                                        foreach($children47 as $child47) {
                                                                                            if($children48 = @$this->CSV_children_of[$child47]) $anaks = array_merge($anaks, $children48);
                                                                                            else continue;

    foreach($children48 as $child48) {
        if($children49 = @$this->CSV_children_of[$child48]) $anaks = array_merge($anaks, $children49);
        else continue;
        foreach($children49 as $child49) {
            if($children50 = @$this->CSV_children_of[$child49]) $anaks = array_merge($anaks, $children50);
            else continue;
            
            $ret = self::check_max_children_reached($anaks, 'L50', $page_id);
            if($ret['exit']) return self::my_atrim($ret['anaks']);
            else             $anaks = $ret['anaks'];
            
            foreach($children50 as $child50) {
                if($children51 = @$this->CSV_children_of[$child50]) $anaks = array_merge($anaks, $children51);
                else continue;
                foreach($children51 as $child51) {
                    if($children52 = @$this->CSV_children_of[$child51]) $anaks = array_merge($anaks, $children52);
                    else continue;
                    foreach($children52 as $child52) {
                        if($children53 = @$this->CSV_children_of[$child52]) $anaks = array_merge($anaks, $children53);
                        else continue;
                        foreach($children53 as $child53) {
                            if($children54 = @$this->CSV_children_of[$child53]) $anaks = array_merge($anaks, $children54);
                            else continue;
                            foreach($children54 as $child54) {
                                if($children55 = @$this->CSV_children_of[$child54]) $anaks = array_merge($anaks, $children55);
                                else continue;
                                foreach($children55 as $child55) {
                                    if($children56 = @$this->CSV_children_of[$child55]) $anaks = array_merge($anaks, $children56);
                                    else continue;

                                    $this->debug['reached L55'][$page_id][$predicate] = ''; //return array();

                                    // /* for 46451825 who is the only one that reached L55
                                    $anaks = array_diff($anaks, array($page_id));
                                    $anaks = self::my_atrim($anaks); return $anaks;
                                    // */
                                    
                                }
                            }
                        }
                    }
                }
            }
        }
    }
                                                                                        }
                                                                                    }
                                                                                }
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
                                                                                                            }
                                                                                                        }
                                                                                                    }
                                                                                                }
                                                                                            }
                                                                                        }
                                                                                    }
                                                                                }
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        $anaks = self::my_atrim($anaks);
        "\nDone getting children of [$page_id] OK\n";
        return $anaks;
    }
    private function check_max_children_reached($anaks, $level, $page_id)
    {   //echo "\nblock level reached: $level\n";
        $anaks = array_diff($anaks, array($page_id));
        $anaks = array_unique($anaks); //make unique
        if(count($anaks) >= 10000) return array("exit" => true, 'anaks' => $anaks);
        else                       return array("exit" => false, 'anaks' => $anaks);
    }
    private function my_atrim($arr)
    {   $arr = array_filter($arr); //remove null arrays
        $arr = array_unique($arr); //make unique
        $arr = array_values($arr); //reindex key
        return $arr;
    }
    /* report for Jen
    self::parse_DH();
    $WRITE = fopen($this->report_file, 'w');
    $i = 0;
    foreach(new FileIterator($this->info_path['archive_path'].$this->info_path['tables']['taxa']) as $line_number => $line) {
        $line = explode("\t", $line); $i++;
        if($i == 1) $fields = $line;
        else {
            if(!$line[0]) break;
            $rec = array(); $k = 0;
            foreach($fields as $fld) {
                $rec[$fld] = $line[$k]; $k++;
            }
            // print_r($rec); exit;
            if($page_id = $rec['EOLid']) {
                $ancestry = self::get_ancestry_via_DH($page_id);
                fwrite($WRITE, $page_id . "\t" . implode(" | ", $ancestry) . "\n");
            }
        }
    }
    fclose($WRITE); exit(); exit("\n-end report-\n");
    */
    /* another report for Jen
    self::initialize();
    $i = 0;
    $file = fopen($this->main_paths['archive_path'].'/traits.csv', 'r');
    while(($line = fgetcsv($file)) !== FALSE) {
        $i++; if($i == 1) $fields = $line;
        else {
            $rec = array(); $k = 0;
            foreach($fields as $fld) {
                $rec[$fld] = $line[$k]; $k++;
            }
            // print_r($rec); exit;
            if($page_id = @$rec['object_page_id'])  $final[$page_id] = '';
            if($page_id = @$rec['page_id'])         $final[$page_id] = '';
        }
    }
    $WRITE = fopen($this->report_file, 'w');
    $final = array_keys($final);
    foreach($final as $page_id) {
        $ancestry = self::get_ancestry_via_DH($page_id);
        fwrite($WRITE, $page_id . "\t" . implode(" | ", $ancestry) . "\n");
    }
    fclose($WRITE); exit("\n-end report-\n");
    */

        /*
    Hi Jen, we are now just down to 1 discrepancy. But I think (hopefully) the last one is just something you've missed doing it manually.
    But more importantly, let me share my algorithm how I chose the parent. Please review closely and suggest improvement or even revise if needed.
    I came up with this using our case scenario for page_id 46559197 and your explanations why you chose your parents.
    Like what I said it came down to now just 1 discrepancy.

    I process each of the 12 terms, one by one.
        http://www.marineregions.org/gazetteer.php?p=details&id=australia
        http://www.marineregions.org/gazetteer.php?p=details&id=4366
        http://www.marineregions.org/gazetteer.php?p=details&id=4364
        http://www.geonames.org/2186224
        http://www.geonames.org/3370751
        http://www.marineregions.org/gazetteer.php?p=details&id=1914
        http://www.marineregions.org/gazetteer.php?p=details&id=1904
        http://www.marineregions.org/gazetteer.php?p=details&id=1910
        http://www.marineregions.org/gazetteer.php?p=details&id=4276
        http://www.marineregions.org/gazetteer.php?p=details&id=4365
        http://www.geonames.org/953987
        http://www.marineregions.org/mrgid/1914

    1.  First I get the preferred term(s) of the term in question. 
        Case A: If there are any: e.g. (pref1, pref2) Mostly only 1 preferred term.
            I get the immediate parent(s) each of the preferred terms. 
            e.g. pref1_parent1, pref1_parent2, pref1_parent3

        Case B: If there are NO preferred term(s)
            I get the immediate parent(s) of the term in question.
            e.g. term_parent1, term_parent2

    2.  Then whatever the Case be, I sent the collected items to the ranking selection.
            */
}
?>