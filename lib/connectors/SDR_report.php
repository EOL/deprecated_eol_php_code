<?php
namespace php_active_record;
/* [.php] */
class SDR_report
{
    public function __construct($folder)
    {
        $this->resource_id = $folder;
        /*
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        */
        $this->download_options = array('resource_id' => 'SDR_all', 'timeout' => 60*5, 'expire_seconds' => 60*60*24, 'cache' => 1, 'download_wait_time' => 1000000);
        $this->debug = array();
        
        /* Terms relationships -> https://opendata.eol.org/dataset/terms-relationships */
        /* not used at the moment:
        $this->file['parent child']['path'] = "https://opendata.eol.org/dataset/237b69b7-8aba-4cc4-8223-c433d700a1cc/resource/f8036c30-f4ab-4796-8705-f3ccd20eb7e9/download/parent-child-aug-16-2.csv";
        $this->file['parent child']['path'] = "http://localhost/cp/summary data resources/parent-child-aug-16-2.csv";
        */
        $this->file['parent child']['fields'] = array('parent', 'child'); //used more simple words instead of: array('parent_term_URI', 'subclass_term_URI');
        
        $this->file['preferred synonym']['path'] = "https://opendata.eol.org/dataset/237b69b7-8aba-4cc4-8223-c433d700a1cc/resource/41f7fed1-3dc1-44d7-bbe5-6104156d1c1e/download/preferredsynonym-aug-16-1-2.csv";
        $this->file['preferred synonym']['path'] = "http://localhost/cp/summary data resources/preferredsynonym-aug-16-1-2-3.csv";
        $this->file['preferred synonym']['path'] = "https://opendata.eol.org/dataset/237b69b7-8aba-4cc4-8223-c433d700a1cc/resource/41f7fed1-3dc1-44d7-bbe5-6104156d1c1e/download/preferredsynonym-sept-27.csv";
        $this->file['preferred synonym']['fields'] = array('preferred', 'deprecated'); //used simple words instead of: array('preferred_term_URI', 'deprecated_term_URI');

        $this->file['parent child']['path_habitat'] = "http://localhost/cp/summary data resources/habitat-parent-child.csv"; 
        $this->file['parent child']['path_habitat'] = "http://localhost/cp/summary data resources/habitat-parent-child-6-1.csv";
        $this->file['parent child']['path_habitat'] = "https://opendata.eol.org/dataset/237b69b7-8aba-4cc4-8223-c433d700a1cc/resource/c5ff5c62-a2ef-44be-9f59-88cd99bc8af2/download/habitat-parent-child-6-1.csv";
        
        $this->file['parent child']['path_geoterms'] = "http://localhost/cp/summary data resources/geoterms-parent-child.csv";
        $this->file['parent child']['path_geoterms'] = "http://localhost/cp/summary data resources/geoterms-parent-child-1.csv";
        $this->file['parent child']['path_geoterms'] = "https://opendata.eol.org/dataset/237b69b7-8aba-4cc4-8223-c433d700a1cc/resource/e1dcb51b-9a03-4069-b5bf-e18b6bc15798/download/geoterms-parent-child-1.csv";
        
        // $this->dwca_file = "http://localhost/cp/summary data resources/carnivora_sample.tgz";
        $this->dwca_file = "http://localhost/cp/summary_data_resources/traits_all_201905.zip";
        $this->report_file = CONTENT_RESOURCE_LOCAL_PATH . '/sample.txt';
        $this->temp_file = CONTENT_RESOURCE_LOCAL_PATH . '/temp.txt';
        
        /* ------------------ NEW June 4, 2019 ------------------ */
        $this->main_dir = "/Volumes/AKiTiO4/web/cp/summary_data_resources/"; //Mac Mini
        // $this->main_dir = "/Users/eagbayani/Sites/cp/summary_data_resources/"; //MacBook
        $this->mysqli =& $GLOBALS['db_connection'];
        /* ------------------ NEW June 4, 2019 ------------------ */
        
        if(Functions::is_production())  $this->working_dir = "/extra/summary data resources/page_ids/";
        else{
                                        // $this->working_dir = "/Volumes/AKiTiO4/web/cp/summary data resources/page_ids/";
                                        $this->working_dir = $this->main_dir."page_ids/";
                                        $this->working_dir = $this->main_dir."page_ids_20190613/";
        }
        /* seems not used as all
        $this->jen_isvat = "/Volumes/AKiTiO4/web/cp/summary data resources/2018 09 08/jen_isvat.txt";
        */
        
        //for taxon summary
        /*
        if(Functions::is_production())  $this->EOL_DH = "https://opendata.eol.org/dataset/b6bb0c9e-681f-4656-b6de-39aa3a82f2de/resource/b534cd22-d904-45e4-b0e2-aaf06cc0e2d6/download/eoldynamichierarchyv1revised.zip";
        else                            $this->EOL_DH = "http://localhost/cp/summary data resources/eoldynamichierarchyv1.zip";
        */
        if(Functions::is_production())  $this->EOL_DH = "https://opendata.eol.org/dataset/b6bb0c9e-681f-4656-b6de-39aa3a82f2de/resource/bac4e11c-28ab-4038-9947-02d9f1b0329f/download/eoldynamichierarchywithlandmarks.zip";
        else                            $this->EOL_DH = "http://localhost/cp/summary data resources/DH/eoldynamichierarchywithlandmarks.zip";
        
        $this->EOL_DH = "http://localhost/cp/summary data resources/DH/eoldynamichierarchywithlandmarks.zip";
        $this->lifeState_statMeth_resource_file = CONTENT_RESOURCE_LOCAL_PATH . '/lifeStage_statMeth_resource.txt';
        
        $this->parentModeYN = false;
        $this->fullref = array();
        
    }
    private function initialize()
    {
        require_library('connectors/SummaryDataResourcesAllAPI');
        $this->func = new SummaryDataResourcesAllAPI('');
    }
    function gen_SampleSize_4parent_BV($dbase, $page_ids_param)
    {   
        self::initialize();
        $this->func->parse_DH(); //this was needed for $this->report_SampleSize
        $this->dbname = 'traits_'.$dbase;
        $this->func->initialize_basal_values(); 
        $predicates = $this->func->get_summ_process_type_given_pred('opposite', 'parents!A2:C1000', 2, 'basal value'); print_r($predicates);
        echo "\nGet page_ids for parent (BV)...\n";
        if($page_ids_param) {
            $page_ids = $this->func->get_page_ids_andInfo_fromDH($page_ids_param);
            $resource_id = 'parent_basal_values_'.$page_id_value;
        }
        else {
            $page_ids = $this->func->get_page_ids_andInfo_fromDH();
            $resource_id = 'parent_basal_values';
        }
        $total_page_ids = count($page_ids);
        /* for indicator */ $total_predicates = count($predicates); $cnt_predicate = 0;
        foreach($predicates as $predicate) {
            $cnt_predicate++; /* for indicator */
            $cnt_page_id = 0;
            $m = 2237554/3; //for breakdown when caching...
            foreach($page_ids as $page_id => $taxon) {
                /* for indicator */
                $cnt_page_id++;
                echo "\nPredicates $cnt_predicate of $total_predicates";
                echo "\nPage IDs $cnt_page_id of $total_page_ids\n";
                /* breakdown when caching:
                $cont = false;
                if($cnt_page_id >= 1 && $cnt_page_id < $m) $cont = true;
                // if($cnt_page_id >= $m && $cnt_page_id < $m*2) $cont = true;
                // if($cnt_page_id >= $m*2 && $cnt_page_id < $m*3) $cont = true;
                if(!$cont) continue;
                */
                if(!$page_id) continue;
                if(!@$taxon['taxonRank']) continue;
                if(@$taxon['taxonRank'] != "species" && $taxon['Landmark'] || @$taxon['taxonRank'] == "family") {
                    self::main_gen_SampleSize_4parent_BV($page_id, $predicate);
                }
            }
        }
        print_r($this->report_SampleSize);
        self::write_SampleSize_2txt();
        echo("\n-- end gen_SampleSize_for_parent_BV --\n");
    }
    private function write_SampleSize_2txt()
    {   /*[7662] => Array (
        [https://www.wikidata.org/entity/Q648799] => Array(
                    [46559130] => 
                    [46559154] => 
                    [46559162] => 
                    [46559170] => 
                    [46559206] => 
                    [46559208] => 
        )*/
        $WRITE = Functions::file_open(CONTENT_RESOURCE_LOCAL_PATH.'/SampleSize_table.txt', 'w');
        fwrite($WRITE, implode("\t", array('parent_id', 'value_term', 'children_ids'))."\n");
        foreach($this->report_SampleSize as $page_id => $rek) {
            foreach($rek as $uri => $page_ids) {
                $page_ids = array_keys($page_ids);
                $arr = array($page_id, $uri, implode(";", $page_ids));
                fwrite($WRITE, implode("\t", $arr)."\n");
            }
        }
        fclose($WRITE);
    }
    private function main_gen_SampleSize_4parent_BV($main_page_id, $predicate)
    {   /* 1. get all children of page_id with rank = species */
        if($children = self::get_children_from_txt_file($main_page_id, false)) { //Value is now cached to txt file
            echo "\n*Children of [$main_page_id]: ".count($children)."\n"; //print_r($children); *Children of [164]: 1433142
        }
        else { echo "\n*No children found for [$main_page_id]\n"; return array(); }
        
        if($children = self::get_childrenTBP_from_txt_file($main_page_id, $children, $predicate)) {
            $children_count = count($children);
            echo "\n*Children TBP of [$main_page_id]: ".$children_count."\n";
            if($children_count > 1000) return array();
        }
        else {
            echo "\n*No children TBP found for [$main_page_id]\n";
            return array();
        }
        /* 2. get all recs for each child */
        $recs = self::get_all_recs_for_each_pageID($children, $predicate); // echo "\n".count($recs)."\n"; exit("\nxxx\n");
        if(!$recs) {
            echo "\nNo recs for any of the children for predicate [$predicate]\n";
            return false;
        }
        // /* to get SampleSize count from: https://eol-jira.bibalex.org/browse/DATA-1773?focusedCommentId=63621&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-63621
        self::buildup_SampleSize_data($main_page_id, $recs, $predicate, $children); //was semi-abandoned bec. it is very slow
        // */
    }
    private function buildup_SampleSize_data($main_page_id, $recs_from_descendants, $predicate, $children)
    { /* each record should have a SampleSize= the number of descendant taxa with records with that value in their ancestry (or as their record value). */
        /* Array(
        [0] => Array( -- many fields removed here...
                [page_id] => 347438 --> this is the descendant/child taxon
                [predicate] => http://eol.org/schema/terms/Habitat
                [value_uri] => http://purl.obolibrary.org/obo/ENVO_00000446
        )*/
        echo "\n".count($this->EOL_2_DH)."\n"; echo "\n".count($this->DH_2_EOL)."\n"; //exit;
        foreach($recs_from_descendants as $rec) { $page_id = $rec['page_id'];
            $this->report_SampleSize[$main_page_id][$rec['value_uri']][$page_id] = ''; //(or as their record value)
        }
        foreach($children as $page_id) {
            if($anc = self::get_ancestry_via_DH($page_id, false)) { // print_r($anc);
                echo("\n[".$page_id."]has ancestry [".count($anc)."]\n");
                // echo "\ndoing this now...\n";
                if($recs_from_ancestry = self::get_all_recs_for_each_pageID($anc, $predicate)) {
                    // echo "\n recs_from_ancestry of descendant [$page_id]: ".count($recs_from_ancestry)."\n";     //debug
                    // echo "\nfirst rec in recs_from_ancestry: ".$recs_from_ancestry[0]['page_id']."\n";           //debug
                    foreach($recs_from_ancestry as $rec) {
                        // print_r($rec); exit;
                        /*Array(
                            [page_id] => 44709
                            [scientific_name] => Cryptoprocta
                            [predicate] => http://eol.org/schema/terms/Habitat
                            [value_uri] => http://purl.obolibrary.org/obo/ENVO_00002009
                            many other fields...
                        )*/
                        $this->report_SampleSize[$main_page_id][$rec['value_uri']][$page_id] = ''; //the number of descendant taxa with records with that value in their ancestry
                    }
                }
            }
        }
        // print_r($this->report_SampleSize['http://www.marineregions.org/mrgid/14289']); exit;
    }

}